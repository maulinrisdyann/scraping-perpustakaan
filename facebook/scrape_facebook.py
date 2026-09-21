"""
Scraper komentar Facebook (Playwright, guest view TIDAK cukup).

Halaman Facebook klien (kpadkotamagelang) memblokir total visitor yang
tidak login -- "Konten Ini Tidak Tersedia Saat Ini" muncul di post,
permalink, timeline halaman, maupun versi mobile. Jadi wajib pakai session
login (lihat login_setup.py) sebelum scraper ini bisa jalan.

Pendekatan selector: Facebook memakai class name CSS ter-obfuscate
(berubah tiap deploy), jadi scraper ini berpegang ke `role`/`aria-label`
yang jauh lebih stabil -- sama seperti scraper Google Maps di Fase 1.
    - Container komentar & reply: div[role="article"][aria-label^="Comment by "]
      atau [aria-label^="Reply by "]
    - Teks komentar: div[dir="auto"] pertama di dalam container
    - ID unik: parameter comment_id (base64) dari href link penulis
    - Tanggal absolut: aria-label link waktu singkat ("1w", "3d", dst)

Butuh session login (lihat login_setup.py) -- jalankan itu dulu sebelum
script ini kalau belum pernah / session sudah expired.

Kalau terjadi captcha/blocked/error tak terduga: dicatat ke scrape_logs
dengan status failed, TIDAK menghentikan proses url tracked lainnya.

Jalankan:
    source venv/bin/activate
    python3 scrape_facebook.py
"""

import os
import re
import sys
from datetime import datetime, timezone
from pathlib import Path
from urllib.parse import parse_qs, urlparse

import mysql.connector
from dotenv import load_dotenv
from playwright.sync_api import sync_playwright

BASE_DIR = Path(__file__).parent
SESSION_FILE = BASE_DIR / "session" / "facebook_state.json"

STABLE_ROUNDS_TO_STOP = 2
MAX_EXPAND_ROUNDS = 20
EXPAND_WAIT_MS = 1200

# Kata kunci tombol "muat lebih banyak komentar/balasan" -- dicoba dalam
# Inggris & Indonesia karena bahasa akun FB bisa beda-beda.
LOAD_MORE_PATTERN = re.compile(
    r"(view|see|show|lihat|tampilkan).*(comment|repl|komentar|balasan)"
    r"|^\d+\s*(more repl|repl|balasan lainnya|komentar lainnya)",
    re.IGNORECASE,
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


def get_active_facebook_targets(conn):
    cur = conn.cursor(dictionary=True)
    cur.execute(
        """
        SELECT tu.id, tu.label, tu.url, s.id AS source_id
        FROM tracked_urls tu
        JOIN sources s ON s.id = tu.source_id
        WHERE s.name = 'facebook' AND tu.is_active = 1
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
             review_text, review_relative_time, review_date, scraped_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE
            author_name = VALUES(author_name),
            review_text = VALUES(review_text),
            review_relative_time = VALUES(review_relative_time),
            review_date = VALUES(review_date),
            scraped_at = VALUES(scraped_at)
        """,
        (
            source_id,
            tracked_url_id,
            review["external_review_id"],
            review["author_name"],
            review["review_text"],
            review["review_relative_time"],
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


def expand_all_comments(page):
    """Klik berulang semua tombol 'muat lebih banyak komentar/balasan'
    sampai jumlah komentar stabil beberapa kali berturut-turut."""
    comment_locator = page.locator(
        'div[role="article"][aria-label^="Comment by "], div[role="article"][aria-label^="Reply by "]'
    )

    prev_count = -1
    stable_rounds = 0
    for _ in range(MAX_EXPAND_ROUNDS):
        count = comment_locator.count()
        if count == prev_count:
            stable_rounds += 1
            if stable_rounds >= STABLE_ROUNDS_TO_STOP:
                break
        else:
            stable_rounds = 0
        prev_count = count

        buttons = page.locator('div[role="button"]:visible')
        clicked_any = False
        for i in range(min(buttons.count(), 30)):
            btn = buttons.nth(i)
            try:
                text = btn.inner_text(timeout=500).strip()
            except Exception:
                continue
            if text and LOAD_MORE_PATTERN.search(text):
                try:
                    btn.click(timeout=2000)
                    clicked_any = True
                    page.wait_for_timeout(EXPAND_WAIT_MS)
                except Exception:
                    pass

        if not clicked_any:
            page.wait_for_timeout(EXPAND_WAIT_MS)


def extract_comment_id(article):
    links = article.locator('a[role="link"]')
    for i in range(links.count()):
        href = links.nth(i).get_attribute("href")
        if not href:
            continue
        qs = parse_qs(urlparse(href).query)
        comment_id = qs.get("comment_id")
        if comment_id:
            return comment_id[0]
    return None


def extract_author_name(article):
    links = article.locator('a[role="link"]')
    for i in range(links.count()):
        text = links.nth(i).inner_text().strip()
        if text:
            return text
    return None


TIME_ABBREV_RE = re.compile(r"^\d+\s*[smhdw]$|^just now$|^now$", re.IGNORECASE)
ABSOLUTE_DATE_FORMATS = [
    "%A, %B %d, %Y at %I:%M %p",
    "%d %B %Y pukul %H.%M",
    "%d %B %Y jam %H.%M",
]


def extract_time_info(article):
    links = article.locator('a[role="link"]')
    for i in range(links.count()):
        text = links.nth(i).inner_text().strip()
        if text and TIME_ABBREV_RE.match(text):
            aria = links.nth(i).get_attribute("aria-label")
            review_date = None
            if aria:
                for fmt in ABSOLUTE_DATE_FORMATS:
                    try:
                        review_date = datetime.strptime(aria, fmt)
                        break
                    except ValueError:
                        continue
            return text, review_date
    return None, None


def scrape_post_comments(page, url):
    page.goto(url, wait_until="domcontentloaded", timeout=60000)
    page.wait_for_timeout(4000)

    blocked_marker = page.locator("text=Konten Ini Tidak Tersedia Saat Ini")
    if blocked_marker.count() > 0:
        raise RuntimeError(
            "Konten tidak tersedia (guest-view block atau post sudah dihapus/private). "
            "Kalau ini terjadi walau sudah login, kemungkinan session expired -- "
            "jalankan ulang login_setup.py."
        )

    expand_all_comments(page)

    articles = page.locator(
        'div[role="article"][aria-label^="Comment by "], div[role="article"][aria-label^="Reply by "]'
    )

    results = []
    count = articles.count()
    for i in range(count):
        article = articles.nth(i)

        comment_id = extract_comment_id(article)
        if not comment_id:
            continue

        author_name = extract_author_name(article)

        text_el = article.locator('div[dir="auto"]').first
        review_text = text_el.inner_text().strip() if text_el.count() > 0 else None

        relative_time, review_date = extract_time_info(article)

        results.append(
            {
                "external_review_id": comment_id,
                "author_name": author_name,
                "review_text": review_text or None,
                "review_relative_time": relative_time,
                "review_date": review_date,
            }
        )

    return results


def run():
    conn = db_connect()
    targets = get_active_facebook_targets(conn)

    if not targets:
        print("Tidak ada tracked_urls aktif untuk source facebook.")
        conn.close()
        return

    if not SESSION_FILE.exists():
        print("Belum ada session login. Jalankan login_setup.py dulu.", file=sys.stderr)
        conn.close()
        return

    with sync_playwright() as p:
        browser = p.chromium.launch(
            headless=False,
            channel="chrome",
            args=["--disable-blink-features=AutomationControlled"],
        )
        context = browser.new_context(
            locale="id-ID",
            viewport={"width": 1366, "height": 900},
            storage_state=str(SESSION_FILE),
        )
        context.add_init_script(
            "Object.defineProperty(navigator, 'webdriver', { get: () => undefined });"
        )
        page = context.new_page()

        for target in targets:
            started_at = datetime.now(timezone.utc)
            print(f"\n=== Scraping: {target['label']} ===")
            try:
                comments = scrape_post_comments(page, target["url"])
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

        browser.close()

    conn.close()


if __name__ == "__main__":
    run()
