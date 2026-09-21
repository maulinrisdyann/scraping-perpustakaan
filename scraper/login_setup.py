"""
Login manual satu kali ke akun Google, simpan session (cookies) ke file lokal.
Scraper google_maps.py pakai file ini terus-menerus, jadi tidak perlu simpan
email/password di mana pun.

Cara pakai:
    source venv/bin/activate
    python3 login_setup.py

Browser bakal kebuka. Login manual seperti biasa (termasuk 2FA kalau ada).
Setelah masuk ke maps.google.com dan kelihatan sudah login, kembali ke
terminal dan tekan Enter.
"""

import asyncio
from pathlib import Path

from playwright.async_api import async_playwright

SESSION_DIR = Path(__file__).parent / "session"
SESSION_FILE = SESSION_DIR / "google_state.json"


async def main():
    SESSION_DIR.mkdir(exist_ok=True)

    async with async_playwright() as p:
        browser = await p.chromium.launch(
            headless=False,
            channel="chrome",
            args=["--disable-blink-features=AutomationControlled"],
        )
        context = await browser.new_context(locale="id-ID", viewport={"width": 1366, "height": 900})
        await context.add_init_script(
            "Object.defineProperty(navigator, 'webdriver', { get: () => undefined });"
        )
        page = await context.new_page()
        await page.goto("https://accounts.google.com/", wait_until="domcontentloaded")

        print("\nSilakan login manual di jendela browser yang terbuka.")
        input("Setelah selesai login dan bisa buka maps.google.com, tekan Enter di sini...\n")

        await page.goto("https://www.google.com/maps?hl=id", wait_until="domcontentloaded")
        await page.wait_for_timeout(2000)

        await context.storage_state(path=str(SESSION_FILE))
        print(f"Session tersimpan di {SESSION_FILE}")

        await browser.close()


if __name__ == "__main__":
    asyncio.run(main())
