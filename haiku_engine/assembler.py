"""Splices words from a continuous multi-author stream into 5-7-5 haiku.

The feed is not assumed to be one coherent message: each word can come
from a different intercepted post. Words are laid onto a line until one
would overflow it, at which point the in-progress attempt is abandoned
and a fresh attempt starts with that word. This favours a stream that
keeps moving over recovering every possible haiku.
"""

import re

from syllables import syllable_count

TARGET = (5, 7, 5)

_URL = re.compile(r"https?://\S+")
_MENTION = re.compile(r"@[\w.-]+")
_WORD = re.compile(r"[A-Za-z']+")


def tokenize(post_text, source_id=None):
    """Yield (word, source_id) pairs from a single post's text."""
    cleaned = _URL.sub(" ", post_text)
    cleaned = _MENTION.sub(" ", cleaned)
    for match in _WORD.finditer(cleaned):
        word = match.group(0).strip("'")
        if word:
            yield word, source_id


class HaikuAssembler:
    """Stateful splicer: feed it words one at a time from many posts."""

    def __init__(self):
        self._reset()

    def _reset(self):
        self.line_index = 0
        self.line_syllables = 0
        self.lines = [[], [], []]  # each entry: list of (word, source_id)

    def feed_word(self, word, source_id=None):
        """Feed one word. Returns a completed Haiku, or None."""
        syl = syllable_count(word)
        if syl == 0:
            return None

        target = TARGET[self.line_index]
        if self.line_syllables + syl > target:
            # Doesn't fit — abandon this attempt, start fresh with this word.
            self._reset()
            target = TARGET[self.line_index]
            if syl > target:
                # Can't even open a line with this word alone; drop it.
                self._reset()
                return None

        self.lines[self.line_index].append((word, source_id))
        self.line_syllables += syl

        if self.line_syllables == target:
            self.line_index += 1
            self.line_syllables = 0
            if self.line_index == 3:
                haiku = Haiku(self.lines)
                self._reset()
                return haiku
        return None

    def feed_post(self, post_text, source_id=None):
        """Feed an entire post's text; returns a list of completed Haiku."""
        results = []
        for word, sid in tokenize(post_text, source_id):
            haiku = self.feed_word(word, sid)
            if haiku:
                results.append(haiku)
        return results


class Haiku:
    def __init__(self, lines):
        # lines: list of 3 lists of (word, source_id)
        self.lines = [list(line) for line in lines]

    @property
    def text(self):
        return [" ".join(w for w, _ in line) for line in self.lines]

    @property
    def sources(self):
        """Distinct source ids that contributed words, in order of appearance."""
        seen = []
        for line in self.lines:
            for _, sid in line:
                if sid is not None and sid not in seen:
                    seen.append(sid)
        return seen

    def __str__(self):
        return "\n".join(self.text)
