"""Always-on Bluesky Jetstream consumer.

Subscribes to the Jetstream firehose (filtered to post-creation events,
optionally one language) rather than polling search — the packet-sniffing
spirit of the project. Every post's text is fed word-by-word into a
HaikuAssembler shared across the whole stream, so haiku are spliced from
many different authors' posts, not lifted whole from one. Raw fragments
and completed haiku are written to MariaDB as they arrive; the web front
end just reads that table, so nothing here needs to be fast, only steady.

Run: python3 firehose/consumer.py
Deploy: see ../deploy/overheard-listener.service
"""

import asyncio
import json
import logging
import random
import sys
from pathlib import Path

import pymysql
import websockets

sys.path.insert(0, str(Path(__file__).resolve().parent.parent / "haiku_engine"))
from assembler import HaikuAssembler  # noqa: E402

import config  # noqa: E402
import moderation  # noqa: E402

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
log = logging.getLogger("overheard.consumer")


def db_connect():
    return pymysql.connect(
        host=config.DB_HOST,
        port=config.DB_PORT,
        user=config.DB_USER,
        password=config.DB_PASS,
        database=config.DB_NAME,
        charset="utf8mb4",
        autocommit=True,
    )


def load_cursor(conn):
    with conn.cursor() as cur:
        cur.execute("SELECT last_cursor FROM consumer_state WHERE id = 1")
        row = cur.fetchone()
        return row[0] if row and row[0] else None


def save_cursor(conn, cursor_value):
    with conn.cursor() as cur:
        cur.execute(
            "INSERT INTO consumer_state (id, last_cursor) VALUES (1, %s) "
            "ON DUPLICATE KEY UPDATE last_cursor = %s",
            (cursor_value, cursor_value),
        )


def store_fragment(conn, uri, did, text):
    with conn.cursor() as cur:
        cur.execute(
            "INSERT IGNORE INTO fragments (source_uri, author_did, text) VALUES (%s, %s, %s)",
            (uri, did, text),
        )


def store_haiku(conn, haiku):
    with conn.cursor() as cur:
        cur.execute(
            "INSERT INTO haiku (line1, line2, line3, source_uris) VALUES (%s, %s, %s, %s)",
            (*haiku.text, json.dumps(haiku.sources)),
        )


def build_url(cursor=None):
    url = config.JETSTREAM_URL + "?wantedCollections=app.bsky.feed.post"
    if cursor:
        url += f"&cursor={cursor}"
    return url


def extract_post(msg):
    """Returns (uri, did, text) for a wanted post-create event, or None."""
    if msg.get("kind") != "commit":
        return None
    commit = msg.get("commit") or {}
    if commit.get("operation") != "create" or commit.get("collection") != "app.bsky.feed.post":
        return None

    record = commit.get("record") or {}
    text = record.get("text")
    if not text:
        return None

    if config.LANG_FILTER:
        # Posts with no langs tag at all are common on Bluesky and are not
        # safe to assume are English — drop them too, not just mismatches.
        langs = record.get("langs") or []
        if config.LANG_FILTER not in langs:
            return None

    if not moderation.is_allowed(text):
        return None

    if config.SAMPLE_RATE < 1.0 and random.random() > config.SAMPLE_RATE:
        return None

    did = msg.get("did", "")
    rkey = commit.get("rkey", "")
    return f"at://{did}/app.bsky.feed.post/{rkey}", did, text


async def run():
    conn = db_connect()
    assembler = HaikuAssembler()
    cursor = load_cursor(conn)
    backoff = 2

    while True:
        url = build_url(cursor)
        try:
            log.info("connecting: %s", url)
            async with websockets.connect(url, ping_interval=20, ping_timeout=20) as ws:
                backoff = 2
                async for raw in ws:
                    try:
                        msg = json.loads(raw)
                    except json.JSONDecodeError:
                        continue

                    cursor = msg.get("time_us", cursor)

                    post = extract_post(msg)
                    if post:
                        uri, did, text = post
                        store_fragment(conn, uri, did, text)
                        if random.random() <= config.HAIKU_FEED_RATE:
                            for haiku in assembler.feed_post(text, source_id=uri):
                                store_haiku(conn, haiku)
                                log.info("haiku: %s", " / ".join(haiku.text))

                    if cursor:
                        save_cursor(conn, cursor)
        except (websockets.exceptions.WebSocketException, OSError) as exc:
            log.warning("connection lost (%s), retrying in %ss", exc, backoff)
            await asyncio.sleep(backoff)
            backoff = min(backoff * 2, 60)


def main():
    try:
        asyncio.run(run())
    except KeyboardInterrupt:
        pass


if __name__ == "__main__":
    main()
