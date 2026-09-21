"""
Login manual satu kali ke akun Facebook dummy, simpan session (cookies) ke
file lokal. Scraper scrape_facebook.py pakai file ini terus-menerus, jadi
tidak perlu simpan password di mana pun setelah ini.

Kenapa guest view tidak cukup: halaman Facebook klien (kpadkotamagelang)
memblokir total visitor yang tidak login -- "Konten Ini Tidak Tersedia Saat
Ini" muncul di post, permalink, timeline halaman, maupun versi mobile.
Terverifikasi lewat halaman Facebook publik lain (NASA) yang masih bisa
diintip separuh, sedangkan halaman klien benar-benar tertutup. Jadi perlu
akun dummy yang login, sama seperti pola Instagram.

Cara pakai:
    source venv/bin/activate
    python3 login_setup.py

Browser Chrome asli bakal kebuka. Login manual seperti biasa (termasuk
verifikasi tambahan kalau diminta). Setelah masuk dan bisa buka
facebook.com/kpadkotamagelang, kembali ke terminal dan tekan Enter.
"""

import asyncio
from pathlib import Path

from playwright.async_api import async_playwright

SESSION_DIR = Path(__file__).parent / "session"
SESSION_FILE = SESSION_DIR / "facebook_state.json"


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
        await page.goto("https://www.facebook.com/", wait_until="domcontentloaded")

        print("\nSilakan login manual di jendela browser yang terbuka (pakai akun dummy).")
        input("Setelah selesai login dan bisa buka facebook.com/kpadkotamagelang, tekan Enter di sini...\n")

        await page.goto("https://www.facebook.com/kpadkotamagelang", wait_until="domcontentloaded")
        await page.wait_for_timeout(2000)

        await context.storage_state(path=str(SESSION_FILE))
        print(f"Session tersimpan di {SESSION_FILE}")

        await browser.close()


if __name__ == "__main__":
    asyncio.run(main())
