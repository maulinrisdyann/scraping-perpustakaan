"""
Scraper ulasan Google Maps (custom, Playwright).

Alur: search-based navigation -> verifikasi tempat via CID -> buka tab Ulasan
-> scroll sampai semua review termuat -> expand "Lihat lainnya" -> extract ->
upsert idempotent ke MySQL (kunci: data-review-id asli dari Google).

Butuh session login (lihat login_setup.py) karena Google menyembunyikan tab
Ulasan untuk visitor yang tidak login (limited view, sejak awal 2026).
Harus jalan headless=False -- headless terdeteksi terpisah dari status login
dan tetap dilempar ke limited view.

Jalankan:
    source venv/bin/activate
    python3 scrape_google_maps.py
"""

import os
import re
import sys
from datetime import datetime, timezone
from pathlib import Path

import mysql.connector
from dotenv import load_dotenv
from playwright.sync_api import sync_playwright

BASE_DIR = Path(__file__).parent
SESSION_FILE = BASE_DIR / "session" / "google_state.json"

STABLE_ROUNDS_TO_STOP = 3
MAX_SCROLL_ROUNDS = 60
SCROLL_WAIT_MS = 1400

load_dotenv(BASE_DIR / ".env")


def db_connect():
    return mysql.connector.connect(
        host=os.environ["DB_HOST"],
        port=int(os.environ["DB_PORT"]),
        database=os.environ["DB_DATABASE"],
        user=os.environ["DB_USERNAME"],
        password=os.environ.get("DB_PASSWORD") or "",
    )


def get_active_google_maps_targets(conn):
    cur = conn.cursor(dictionary=True)
    cur.execute(
        """
        SELECT tu.id, tu.label, tu.search_query, tu.place_identifier, s.id AS source_id
        FROM tracked_urls tu
        JOIN sources s ON s.id = tu.source_id
        WHERE s.name = 'google_maps' AND tu.is_active = 1
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
            (source_id, tracked_url_id, external_review_id, author_name, rating,
             review_text, review_relative_time, owner_reply_text, owner_reply_relative_time, scraped_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE
            author_name = VALUES(author_name),
            rating = VALUES(rating),
            review_text = VALUES(review_text),
            review_relative_time = VALUES(review_relative_time),
            owner_reply_text = VALUES(owner_reply_text),
            owner_reply_relative_time = VALUES(owner_reply_relative_time),
            scraped_at = VALUES(scraped_at)
        """,
        (
            source_id,
            tracked_url_id,
            review["external_review_id"],
            review["author_name"],
            review["rating"],
            review["review_text"],
            review["review_relative_time"],
            review["owner_reply_text"],
            review["owner_reply_relative_time"],
            datetime.now(timezone.utc),
        ),
    )
    # ON DUPLICATE KEY UPDATE: rowcount 1 = insert baru, 2 = row lama ke-update, 0 = tidak berubah
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


def find_scrollable_ancestor(card_handle):
    return card_handle.evaluate_handle(
        """
        el => {
            let node = el.parentElement;
            while (node) {
                const style = window.getComputedStyle(node);
                if ((style.overflowY === 'auto' || style.overflowY === 'scroll') && node.scrollHeight > node.clientHeight) {
                    return node;
                }
                node = node.parentElement;
            }
            return null;
        }
        """
    )


def extract_target_review_count(page):
    label = page.locator('span[role="img"][aria-label*="bintang"]').first
    if label.count() == 0:
        return None
    aria = label.get_attribute("aria-label") or ""
    m = re.search(r"(\d+)\s*Ulasan", aria)
    return int(m.group(1)) if m else None


def scrape_place_reviews(page, target):
    search_url = f"https://www.google.com/maps/search/{target['search_query'].replace(' ', '+')}?hl=id"
    page.goto(search_url, wait_until="domcontentloaded", timeout=60000)
    page.wait_for_timeout(4000)

    if "/place/" not in page.url:
        first_result = page.locator("a.hfpxzc").first
        if first_result.count() == 0:
            raise RuntimeError("Tempat tidak ditemukan di hasil pencarian")
        first_result.click()
        page.wait_for_timeout(3000)

    if target["place_identifier"]:
        if target["place_identifier"] not in page.url:
            raise RuntimeError(
                f"Verifikasi tempat gagal: place_identifier {target['place_identifier']!r} "
                f"tidak ada di url hasil search ({page.url}). Kemungkinan search nyasar ke tempat lain."
            )

    reviews_tab = page.locator('button[role="tab"][aria-label*="Ulasan"]')
    if reviews_tab.count() == 0:
        raise RuntimeError(
            "Tab Ulasan tidak ditemukan. Kemungkinan session login sudah expired -- "
            "jalankan ulang login_setup.py."
        )
    reviews_tab.first.click()
    page.wait_for_timeout(2000)

    target_count = extract_target_review_count(page)

    cards = page.locator("div.jftiEf[data-review-id]")
    if cards.count() == 0:
        raise RuntimeError("Tidak ada review card yang termuat setelah membuka tab Ulasan")

    scroll_handle = find_scrollable_ancestor(cards.first)

    prev_count = 0
    stable_rounds = 0
    for _ in range(MAX_SCROLL_ROUNDS):
        count = cards.count()
        if target_count is not None and count >= target_count:
            break
        if count == prev_count:
            stable_rounds += 1
            if stable_rounds >= STABLE_ROUNDS_TO_STOP:
                break
        else:
            stable_rounds = 0
        prev_count = count

        scroll_handle.evaluate(
            "el => { el.scrollTop = el.scrollHeight; el.dispatchEvent(new Event('scroll', {bubbles: true})); }"
        )
        page.wait_for_timeout(SCROLL_WAIT_MS)

    # expand semua teks yang terpotong -- ulangi beberapa kali karena tombol baru
    # muncul seiring card baru ke-render
    for _ in range(5):
        more_buttons = page.locator('button[aria-label="Lihat lainnya"]')
        n = more_buttons.count()
        if n == 0:
            break
        for i in range(n):
            try:
                more_buttons.nth(i).click(timeout=1000)
            except Exception:
                pass
        page.wait_for_timeout(800)

    results = []
    count = cards.count()
    for i in range(count):
        card = cards.nth(i)
        review_id = card.get_attribute("data-review-id")
        if not review_id:
            continue

        author = ""
        author_el = card.locator("div.d4r55").first
        if author_el.count() > 0:
            author = author_el.inner_text().strip()

        rating = None
        rating_el = card.locator('span[role="img"]').first
        if rating_el.count() > 0:
            aria = rating_el.get_attribute("aria-label") or ""
            m = re.search(r"(\d+)\s*bintang", aria)
            if m:
                rating = int(m.group(1))

        relative_time = ""
        time_el = card.locator("span.rsqaWe").first
        if time_el.count() > 0:
            relative_time = time_el.inner_text().strip()

        text = ""
        text_el = card.locator("div.MyEned span.wiI7pd").first
        if text_el.count() > 0:
            text = text_el.inner_text().strip()

        owner_reply_text = ""
        owner_reply_relative_time = ""
        owner_reply_el = card.locator("div.CDe7pd").first
        if owner_reply_el.count() > 0:
            reply_text_el = owner_reply_el.locator("div.wiI7pd").first
            if reply_text_el.count() > 0:
                owner_reply_text = reply_text_el.inner_text().strip()
            reply_time_el = owner_reply_el.locator("span.DZSIDd").first
            if reply_time_el.count() > 0:
                owner_reply_relative_time = reply_time_el.inner_text().strip()

        results.append(
            {
                "external_review_id": review_id,
                "author_name": author or None,
                "rating": rating,
                "review_text": text or None,
                "review_relative_time": relative_time or None,
                "owner_reply_text": owner_reply_text or None,
                "owner_reply_relative_time": owner_reply_relative_time or None,
            }
        )

    return results


def run():
    conn = db_connect()
    targets = get_active_google_maps_targets(conn)

    if not targets:
        print("Tidak ada tracked_urls aktif untuk source google_maps.")
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
                reviews = scrape_place_reviews(page, target)
                new_count = 0
                for review in reviews:
                    if upsert_review(conn, target["source_id"], target["id"], review):
                        new_count += 1
                mark_last_scraped(conn, target["id"])
                log_scrape_run(
                    conn, target["source_id"], target["id"], "success",
                    new_count, f"{len(reviews)} review diproses, {new_count} baru", started_at,
                )
                print(f"OK: {len(reviews)} review diproses, {new_count} baru")
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
