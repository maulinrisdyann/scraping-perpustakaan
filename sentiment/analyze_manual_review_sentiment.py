"""Analisis satu manual_reviews Monggo Lapor dengan model sentimen existing."""

import argparse
import os
from pathlib import Path

os.environ.setdefault("HF_HUB_DISABLE_XET", "1")

import mysql.connector
from dotenv import load_dotenv
from transformers import AutoModelForSequenceClassification, AutoTokenizer, pipeline

BASE_DIR = Path(__file__).parent
MODEL_NAME = "w11wo/indonesian-roberta-base-sentiment-classifier"

load_dotenv(BASE_DIR / ".env")


def db_connect():
    return mysql.connector.connect(
        host=os.environ["DB_HOST"], port=int(os.environ["DB_PORT"]),
        database=os.environ["DB_DATABASE"], user=os.environ["DB_USERNAME"],
        password=os.environ.get("DB_PASSWORD") or "",
    )


def inferred_rating(label, score):
    if label == "positive":
        return 5 if score >= 0.80 else 4
    if label == "neutral":
        return 3
    if label == "negative":
        return 1 if score >= 0.80 else 2
    raise ValueError(f"Label sentimen tidak dikenal: {label}")


def run(review_id):
    conn = db_connect()
    cur = conn.cursor(dictionary=True)
    cur.execute("""
        SELECT id, review_text FROM manual_reviews
        WHERE id = %s AND sentiment_label IS NULL AND review_text != ''
    """, (review_id,))
    review = cur.fetchone()
    cur.close()

    if not review:
        conn.close()
        print("Manual review tidak ditemukan atau sudah dianalisis.")
        return

    tokenizer = AutoTokenizer.from_pretrained(MODEL_NAME, local_files_only=True)
    model = AutoModelForSequenceClassification.from_pretrained(MODEL_NAME, local_files_only=True)
    classifier = pipeline("sentiment-analysis", model=model, tokenizer=tokenizer,
                          truncation=True, max_length=512, device=-1)
    prediction = classifier(review["review_text"])[0]
    score = float(prediction["score"])
    label = prediction["label"]

    cur = conn.cursor()
    cur.execute("""
        UPDATE manual_reviews
        SET sentiment_label = %s, sentiment_score = %s, rating = %s, updated_at = NOW()
        WHERE id = %s
    """, (label, score, inferred_rating(label, score), review_id))
    conn.commit()
    cur.close()
    conn.close()
    print("Analisis manual review selesai.")


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--review-id", type=int, required=True)
    run(parser.parse_args().review_id)
