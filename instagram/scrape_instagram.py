"""
Scraper komentar Instagram (instaloader).

Baca tracked_urls dengan source=instagram yang aktif, ambil semua komentar
(termasuk balasan) dari tiap url postingan, upsert idempotent ke MySQL
(kunci: id komentar asli dari Instagram).

Butuh session login (lihat login_setup.py) -- jalankan itu dulu sebelum
script ini kalau belum pernah / session sudah expired.

Jalankan:
    source venv/bin/activate
    python3 scrape_instagram.py
"""

import os
import re
import sys
import time
from datetime import datetime, timezone
from pathlib import Path

import instaloader
import mysql.connector
from dotenv import load_dotenv
from instaloader.instaloadercontext import copy_session

BASE_DIR = Path(__file__).parent
SESSION_DIR = BASE_DIR / "session"
SHORTCODE_RE = re.compile(r"instagram\.com/(?:p|reel|tv)/([A-Za-z0-9_-]+)")
DELAY_BETWEEN_TARGETS_SEC = 5

# Samakan dengan UA di login_setup.py / import_browser_session.py biar konsisten.
MACOS_USER_AGENT = (
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36"
)

load_dotenv(BASE_DIR / ".env")


def db_connect():
    return mysql.connector.connect(
        host=os.environ["DB_HOST"],
        port=int(os.environ["DB_PORT"]),
        database=os.environ["DB_DATABASE"],
        user=os.environ["DB_USERNAME"],
        password=os.environ.get("DB_PASSWORD") or "",
    )


def get_active_instagram_targets(conn):
    cur = conn.cursor(dictionary=True)
    cur.execute(
        """
        SELECT tu.id, tu.label, tu.url, s.id AS source_id
        FROM tracked_urls tu
        JOIN sources s ON s.id = tu.source_id
        WHERE s.name = 'instagram' AND tu.is_active = 1
        """
    )
    rows = cur.fetchall()
    cur.close()
    return rows


def log_scrape_run(conn, source_id, tracked_url_id, status, new_count, message, started_at):
    cur = conn.cursor()
    cur.execute(
        """
        INSERT INTO scrape_logs (source_id, tracked_url_id, status, new_reviews_count, message, started_at, finished_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s)
        """,
        (source_id, tracked_url_id, status, new_count, message, started_at, datetime.now(timezone.utc)),
    )
    conn.commit()
    cur.close()


def upsert_review(conn, source_id, tracked_url_id, review):
    cur = conn.cursor()
    cur.execute(
        """
        INSERT INTO reviews
            (source_id, tracked_url_id, external_review_id, author_name,
             review_text, review_date, scraped_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE
            author_name = VALUES(author_name),
            review_text = VALUES(review_text),
            review_date = VALUES(review_date),
            scraped_at = VALUES(scraped_at)
        """,
        (
            source_id,
            tracked_url_id,
            review["external_review_id"],
            review["author_name"],
            review["review_text"],
            review["review_date"],
            datetime.now(timezone.utc),
        ),
    )
    is_new_insert = cur.rowcount == 1
    conn.commit()
    cur.close()
    return is_new_insert


def mark_last_scraped(conn, tracked_url_id):
    cur = conn.cursor()
    cur.execute(
        "UPDATE tracked_urls SET last_scraped_at = %s WHERE id = %s",
        (datetime.now(timezone.utc), tracked_url_id),
    )
    conn.commit()
    cur.close()


def load_instaloader():
    marker = SESSION_DIR / "active_username.txt"
    if not marker.exists():
        raise RuntimeError(
            "Belum ada session login. Jalankan login_setup.py dulu."
        )
    username = marker.read_text().strip()
    session_file = SESSION_DIR / f"{username}.session"
    if not session_file.exists():
        raise RuntimeError(
            f"Session file {session_file} tidak ditemukan. Jalankan ulang login_setup.py."
        )

    L = instaloader.Instaloader(
        download_pictures=False,
        download_videos=False,
        download_video_thumbnails=False,
        download_geotags=False,
        download_comments=False,
        save_metadata=False,
        compress_json=False,
        user_agent=MACOS_USER_AGENT,
    )
    L.load_session_from_file(username, str(session_file))
    return L


def extract_shortcode(url):
    m = SHORTCODE_RE.search(url)
    if not m:
        raise ValueError(f"Tidak bisa menemukan shortcode dari url: {url}")
    return m.group(1)


def _comment_dict(node, created_at_key):
    user = node.get("user") or {}
    return {
        "external_review_id": str(node["pk"]),
        "author_name": user.get("username"),
        "review_text": node.get("text") or None,
        "review_date": datetime.fromtimestamp(node[created_at_key], tz=timezone.utc),
    }


def _fetch_comments_page(L, mediaid, min_id=None):
    """
    Request manual ke endpoint komentar iPhone-simulated milik instaloader,
    TANPA strip header Referer/Origin/X-Requested-With seperti yang dilakukan
    instaloader.get_iphone_json() secara default.

    instaloader.get_comments() bawaan konsisten gagal ("something went wrong")
    di endpoint ini walau session valid -- kemungkinan karena header hasil
    stripping-nya itu sendiri yang sekarang dianggap mencurigakan oleh
    Instagram (request "murni app" tanpa jejak referrer web). Request manual
    dengan header web tetap ada, terbukti konsisten berhasil, jadi dipakai
    di sini apa adanya.
    """
    params = {"can_support_threading": "true", "permalink_enabled": "false"}
    if min_id:
        params["min_id"] = min_id

    with copy_session(L.context._session, L.context.request_timeout) as tempsession:
        tempsession.headers["ig-intended-user-id"] = str(L.context.user_id)
        tempsession.headers["x-pigeon-rawclienttime"] = f"{time.time():.6f}"
        tempsession.headers.update(L.context.iphone_headers)
        resp = tempsession.get(
            f"https://i.instagram.com/api/v1/media/{mediaid}/comments/",
            params=params,
            timeout=L.context.request_timeout,
        )
    resp.raise_for_status()
    data = resp.json()
    if data.get("status") != "ok":
        raise RuntimeError(f"Fetch komentar gagal: {data}")
    return data


def _fetch_all_child_comments(L, mediaid, comment_pk):
    """Ambil SEMUA balasan sebuah komentar, bukan cuma preview_child_comments.

    Instagram cuma nampilin sebagian kecil balasan di response komentar utama
    (preview_child_comments) kalau balasannya banyak. Endpoint ini yang
    dipakai UI Instagram sendiri saat orang klik 'Lihat balasan lainnya'.
    """
    results = []
    max_id = ""
    for _ in range(50):  # batas wajar, cukup buat thread balasan yang sangat panjang
        with copy_session(L.context._session, L.context.request_timeout) as tempsession:
            tempsession.headers["ig-intended-user-id"] = str(L.context.user_id)
            tempsession.headers["x-pigeon-rawclienttime"] = f"{time.time():.6f}"
            tempsession.headers.update(L.context.iphone_headers)
            resp = tempsession.get(
                f"https://i.instagram.com/api/v1/media/{mediaid}/comments/{comment_pk}/child_comments/",
                params={"max_id": max_id},
                timeout=L.context.request_timeout,
            )
        resp.raise_for_status()
        data = resp.json()
        if data.get("status") != "ok":
            break

        for child in data.get("child_comments", []):
            results.append(_comment_dict(child, "created_at"))

        max_id = data.get("next_max_id")
        if not max_id:
            break
        time.sleep(1)

    return results


def scrape_post_comments(L, url):
    shortcode = extract_shortcode(url)
    post = instaloader.Post.from_shortcode(L.context, shortcode)

    results = []
    min_id = None
    while True:
        try:
            data = _fetch_comments_page(L, post.mediaid, min_id)
        except Exception:
            time.sleep(5)
            data = _fetch_comments_page(L, post.mediaid, min_id)

        for node in data.get("comments", []):
            results.append(_comment_dict(node, "created_at_utc"))

            child_count = node.get("child_comment_count", 0)
            preview_children = node.get("preview_child_comments") or []

            if child_count > 0 and child_count == len(preview_children):
                # preview sudah lengkap, gak perlu request tambahan
                for child in preview_children:
                    results.append(_comment_dict(child, "created_at"))
            elif child_count > 0:
                # ada balasan yang gak ke-cover preview -> ambil semua via endpoint child_comments
                results.extend(_fetch_all_child_comments(L, post.mediaid, node["pk"]))

        min_id = data.get("next_min_id")
        if not min_id:
            break
        time.sleep(2)

    return results


def run():
    conn = db_connect()
    targets = get_active_instagram_targets(conn)

    if not targets:
        print("Tidak ada tracked_urls aktif untuk source instagram.")
        conn.close()
        return

    L = load_instaloader()

    for i, target in enumerate(targets):
        started_at = datetime.now(timezone.utc)
        print(f"\n=== Scraping: {target['label']} ===")
        try:
            comments = scrape_post_comments(L, target["url"])
            new_count = 0
            for comment in comments:
                if upsert_review(conn, target["source_id"], target["id"], comment):
                    new_count += 1
            mark_last_scraped(conn, target["id"])
            log_scrape_run(
                conn, target["source_id"], target["id"], "success",
                new_count, f"{len(comments)} komentar diproses, {new_count} baru", started_at,
            )
            print(f"OK: {len(comments)} komentar diproses, {new_count} baru")
        except Exception as e:
            log_scrape_run(
                conn, target["source_id"], target["id"], "failed",
                0, str(e), started_at,
            )
            print(f"GAGAL: {e}", file=sys.stderr)

        if i < len(targets) - 1:
            time.sleep(DELAY_BETWEEN_TARGETS_SEC)

    conn.close()


if __name__ == "__main__":
    run()
