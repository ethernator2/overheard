"""Configuration for the firehose consumer, read from the environment
(see .env.example — populate a real .env and load it via systemd's
EnvironmentFile= or `source .env` for local runs)."""

import os

DB_HOST = os.environ.get("OVERHEARD_DB_HOST", "localhost")
DB_PORT = int(os.environ.get("OVERHEARD_DB_PORT", "3306"))
DB_USER = os.environ.get("OVERHEARD_DB_USER", "overheard")
DB_PASS = os.environ.get("OVERHEARD_DB_PASS", "")
DB_NAME = os.environ.get("OVERHEARD_DB_NAME", "overheard")

JETSTREAM_URL = os.environ.get(
    "OVERHEARD_JETSTREAM_URL", "wss://jetstream2.us-east.bsky.network/subscribe"
)

# Restrict to one language to keep both bandwidth and haiku quality sane.
# Empty string disables the filter (takes every language).
LANG_FILTER = os.environ.get("OVERHEARD_LANG_FILTER", "en")
