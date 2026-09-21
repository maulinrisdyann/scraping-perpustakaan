"""
Analisis sentimen untuk review yang belum dianalisis.

Model: w11wo/indonesian-roberta-base-sentiment-classifier
Label: positive / neutral / negative

Idempotent: cuma proses review dengan sentiment_label masih NULL, jadi aman
dijalankan berkali-kala (misal habis tiap siklus scraping).

Jalankan:
    source venv/bin/activate
    python3 analyze_sentiment.py
"""

import os
from pathlib import Path

# hf_xet (backend download cepat huggingface_hub) macet di beberapa jaringan --
# paksa pakai jalur download HTTP biasa.
os.environ.setdefault("HF_HUB_DISABLE_XET", "1")

import mysql.connector
from dotenv import load_dotenv
from transformers import pipeline

BASE_DIR = Path(__file__).parent
MODEL_NAME = "w11wo/indonesian-roberta-base-sentiment-classifier"
BATCH_SIZE = 16

load_dotenv(BASE_DIR / ".env")


def db_connect():
    return mysql.connector.connect(
        host=os.environ["DB_HOST"],
        port=int(os.environ["DB_PORT"]),
        database=os.environ["DB_DATABASE"],
        user=os.environ["DB_USERNAME"],
        password=os.environ.get("DB_PASSWORD") or "",
    )


def fetch_unanalyzed(conn):
    cur = conn.cursor(dictionary=True)
    cur.execute(
        """
        SELECT id, review_text FROM reviews
        WHERE sentiment_label IS NULL
          AND review_text IS NOT NULL
          AND review_text != ''
        """
    )
    rows = cur.fetchall()
    cur.close()
    return rows


def update_sentiment(conn, review_id, label, score):
    cur = conn.cursor()
    cur.execute(
        "UPDATE reviews SET sentiment_label = %s, sentiment_score = %s WHERE id = %s",
        (label, score, review_id),
    )
    conn.commit()
    cur.close()


def ensure_complaint(conn, review_id):
    """Buat row complaints (status belum_dibalas) kalau review ini negatif
    dan belum punya complaint. Idempotent lewat unique constraint review_id."""
    cur = conn.cursor()
    cur.execute(
        """
        INSERT IGNORE INTO complaints (review_id, status, created_at, updated_at)
        VALUES (%s, 'belum_dibalas', NOW(), NOW())
        """,
        (review_id,),
    )
    conn.commit()
    cur.close()


def chunked(items, size):
    for i in range(0, len(items), size):
        yield items[i:i + size]


def run():
    conn = db_connect()
    rows = fetch_unanalyzed(conn)

    if not rows:
        print("Tidak ada review baru yang perlu dianalisis.")
        conn.close()
        return

    print(f"Memuat model {MODEL_NAME} ...")
    classifier = pipeline(
        "sentiment-analysis",
        model=MODEL_NAME,
        tokenizer=MODEL_NAME,
        truncation=True,
        max_length=512,
        device=-1,
    )

    print(f"Menganalisis {len(rows)} review ...")
    total_done = 0
    for batch in chunked(rows, BATCH_SIZE):
        texts = [r["review_text"] for r in batch]
        predictions = classifier(texts)
        for row, pred in zip(batch, predictions):
            update_sentiment(conn, row["id"], pred["label"], float(pred["score"]))
            if pred["label"] == "negative":
                ensure_complaint(conn, row["id"])
            total_done += 1
        print(f"  {total_done}/{len(rows)} selesai")

    conn.close()
    print("Selesai.")


if __name__ == "__main__":
    run()
