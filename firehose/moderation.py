"""Moderation hook — intentionally a no-op for now.

The listener runs unfiltered on purpose while the project is still
finding its feet: seeing the piece work against real, unmoderated
Bluesky traffic is the point before deciding what (if anything) to
filter. `is_allowed` is the one place consumer.py checks before storing
a fragment, so a real policy — a blocklist, a profanity filter, a
"flag but don't auto-publish" queue — can be dropped in here later
without touching the consumer.
"""


def is_allowed(text):
    """Always True for now. Replace with real moderation logic later."""
    return True
