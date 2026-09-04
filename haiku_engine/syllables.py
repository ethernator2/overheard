"""Syllable counting.

Primary source is pyphen's en_US hyphenation dictionary (LibreOffice's
Hunspell hyphenation data) — counting hyphenation points is a solid proxy
for syllable count. Falls back to a vowel-group heuristic only if pyphen
isn't installed; it's noticeably less accurate.
"""

import re

try:
    import pyphen

    _dic = pyphen.Pyphen(lang="en_US")
except ImportError:
    _dic = None

_VOWEL_GROUPS = re.compile(r"[aeiouy]+", re.IGNORECASE)

_SILENT_E = re.compile(r"[^aeiouy]e$", re.IGNORECASE)
_LE_ENDING = re.compile(r"[^aeiouy]le$", re.IGNORECASE)


def _heuristic_count(word):
    groups = _VOWEL_GROUPS.findall(word)
    count = len(groups)

    if _SILENT_E.search(word) and not _LE_ENDING.search(word):
        count -= 1

    if _LE_ENDING.search(word) and len(word) > 2 and word[-3] not in "aeiouy":
        count += 1

    return max(1, count)


def syllable_count(word):
    """Estimate the syllable count of a single word."""
    word = word.lower().strip("'\"-")
    if not word:
        return 0

    if _dic is None:
        return _heuristic_count(word)

    hyphenated = _dic.inserted(word)
    return hyphenated.count("-") + 1
