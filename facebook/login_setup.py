

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
