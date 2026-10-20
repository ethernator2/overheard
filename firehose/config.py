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

# Fraction of matching posts kept for the capture log at all, after the
# language/moderation filters — the raw firehose is dense enough that
# even a small fraction is plenty. 0.05 means roughly 1 in 20.
SAMPLE_RATE = float(os.environ.get("OVERHEARD_SAMPLE_RATE", "0.05"))

# Of the fragments kept above, the fraction whose words are actually fed
# into the haiku assembler. Separate from SAMPLE_RATE on purpose: a
# continuously-fed assembler completes a 5-7-5 window almost constantly
# (measured ~80% of kept fragments during testing), which reads as noise,
# not "every so often". Keeping the capture log lively while starving the
# assembler is what makes haiku feel like a find rather than a given.
HAIKU_FEED_RATE = float(os.environ.get("OVERHEARD_HAIKU_FEED_RATE", "0.12"))

# How often the Jetstream cursor is persisted, in seconds. Saving it on
# every message meant ~60 committed transactions a second (each one an
# fsync) for a value only read on restart — ~7 GB/day of disk writes.
# A restart now replays at most this many seconds of firehose, which is
# harmless: fragments are INSERT IGNORE on their unique source_uri.
CURSOR_SAVE_SECS = float(os.environ.get("OVERHEARD_CURSOR_SAVE_SECS", "5"))
