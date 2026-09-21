"""
Alternatif login_setup.py: import cookie langsung dari browser yang sudah
login ke Instagram, bukan login ulang lewat instaloader.

Kenapa perlu ini: login browser dan login program (instaloader) itu dua
"konteks kepercayaan" terpisah di mata Instagram -- lolos checkpoint di
browser tidak otomatis melonggarkan login instaloader, jadi checkpoint bisa
muncul berulang terus. Cara ini "meminjam" sesi browser yang sudah
terverifikasi, jadi tidak perlu checkpoint lagi.

Syarat: sudah login & lolos verifikasi di browser (Chrome/Safari/Firefox)
di Mac ini dengan akun dummy-nya.

Cara pakai:
    source venv/bin/activate
    python3 import_browser_session.py chrome

Browser yang didukung: chrome, chromium, brave, edge, firefox, safari,
opera, opera_gx, vivaldi.

Catatan macOS: mengambil cookie Chrome/Brave/Edge butuh akses Keychain --
macOS akan menampilkan popup minta password login Mac kamu, itu normal.
"""

import sys
from pathlib import Path

import instaloader

SESSION_DIR = Path(__file__).parent / "session"


def main():
    browser = (sys.argv[1] if len(sys.argv) > 1 else "chrome").lower()

    SESSION_DIR.mkdir(exist_ok=True)

    L = instaloader.Instaloader(
        download_pictures=False,
        download_videos=False,
        download_video_thumbnails=False,
        download_geotags=False,
        download_comments=False,
        save_metadata=False,
        compress_json=False,
    )

    try:
        import browser_cookie3

        supported = {
            "brave": browser_cookie3.brave,
            "chrome": browser_cookie3.chrome,
            "chromium": browser_cookie3.chromium,
            "edge": browser_cookie3.edge,
            "firefox": browser_cookie3.firefox,
            "safari": browser_cookie3.safari,
            "opera": browser_cookie3.opera,
            "opera_gx": browser_cookie3.opera_gx,
            "vivaldi": browser_cookie3.vivaldi,
        }
        if browser not in supported:
            print(f"Browser '{browser}' tidak didukung. Pilihan: {', '.join(supported)}")
            return

        cookies = {}
        for cookie in supported[browser](cookie_file=None):
            if "instagram" in cookie.domain:
                cookies[cookie.name] = cookie.value

        if not cookies:
            print(
                f"Tidak ketemu cookie Instagram di {browser}. "
                f"Pastikan kamu sudah login & buka instagram.com di {browser} ini."
            )
            return

        L.context.update_cookies(cookies)
    except Exception as e:
        print(f"Gagal ambil cookie dari {browser}: {e}")
        return

    username = L.test_login()
    if not username:
        print(
            "Cookie ditemukan tapi tidak valid/kadaluarsa. "
            "Coba buka instagram.com lagi di browser, pastikan masih login, lalu ulangi."
        )
        return

    L.context.username = username

    session_file = SESSION_DIR / f"{username}.session"
    L.save_session_to_file(str(session_file))
    (SESSION_DIR / "active_username.txt").write_text(username)

    print(f"Berhasil import sesi untuk {username}. Session tersimpan di {session_file}")


if __name__ == "__main__":
    main()
