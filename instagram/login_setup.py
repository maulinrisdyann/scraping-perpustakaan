"""
Login manual satu kali ke akun Instagram dummy, simpan session ke file lokal.
Scraper scrape_instagram.py pakai file ini terus-menerus, jadi tidak perlu
simpan password di mana pun setelah ini.

Cara pakai:
    source venv/bin/activate
    python3 login_setup.py

Nanti diminta username & password lewat terminal (bukan dikirim ke mana pun
selain langsung ke Instagram). Kalau Instagram minta kode 2FA (email/SMS),
tinggal masukkan juga di terminal yang sama.

Kalau muncul error soal "checkpoint"/"suspicious login": buka aplikasi
Instagram atau email akun dummy itu, approve/verifikasi login-nya secara
manual, baru jalankan ulang script ini.
"""

import getpass
from pathlib import Path

import instaloader

SESSION_DIR = Path(__file__).parent / "session"

# Default instaloader pakai UA Chrome-di-Linux, gak match sama device asli
# (Mac) -- ganti ke UA Chrome macOS biar sinyalnya konsisten & gak mencurigakan.
MACOS_USER_AGENT = (
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36"
)


def main():
    SESSION_DIR.mkdir(exist_ok=True)

    username = input("Username Instagram (akun dummy): ").strip()
    password = getpass.getpass("Password: ")

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

    try:
        L.login(username, password)
    except instaloader.TwoFactorAuthRequiredException:
        code = input("Kode 2FA yang dikirim Instagram: ").strip()
        L.two_factor_login(code)
    except instaloader.BadCredentialsException:
        print("Username atau password salah. Coba lagi.")
        return
    except instaloader.LoginException as e:
        message = str(e)
        print("Instagram minta verifikasi tambahan (checkpoint) sebelum login diizinkan.")
        if "Point your browser to " in message:
            path = message.split("Point your browser to ", 1)[1].split(" - follow", 1)[0].strip()
            url = path if path.startswith("http") else f"https://www.instagram.com{path}"
            print(f"\nBuka url ini di browser (idealnya browser yang sudah/biasa login akun {username}):")
            print(f"  {url}\n")
        print(
            "Ikuti instruksi verifikasinya (biasanya konfirmasi 'ini saya' atau "
            "kode email/SMS). Setelah selesai, jalankan ulang script ini."
        )
        return
    except instaloader.ConnectionException as e:
        print(f"Gagal login: {e}")
        print(
            "Kalau errornya soal 'checkpoint' atau 'suspicious login', buka "
            "aplikasi Instagram / email akun ini, approve login-nya manual, "
            "lalu jalankan ulang script ini."
        )
        return

    session_file = SESSION_DIR / f"{username}.session"
    L.save_session_to_file(str(session_file))

    (SESSION_DIR / "active_username.txt").write_text(username)

    print(f"Login berhasil. Session tersimpan di {session_file}")


if __name__ == "__main__":
    main()
