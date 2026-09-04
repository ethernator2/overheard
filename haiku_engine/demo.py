"""Feeds a simulated multi-author stream through the assembler and prints
whatever haiku fall out, with provenance (which fake post ids contributed).

Run: python3 demo.py
"""

from assembler import HaikuAssembler

STREAM = [
    ("p1042", "raining again, ordered soup, forgot the bread"),
    ("p1043", "cat knocked the mug off the counter at 3am"),
    ("p1044", "nobody replies to good morning posts anymore"),
    ("p1045", "traffic on the bridge again same song on repeat"),
    ("p1046", "watched the kettle boil dry and thought about nothing"),
    ("p1047", "third coffee the deadline moved it always moves"),
    ("p1048", "found a spider in the shower we are roommates now"),
    ("p1049", "the wifi dropped during the important part"),
    ("p1050", "planted tomatoes too early again this year"),
    ("p1051", "missed the bus and watched it turn the corner slow"),
    ("p1052", "left the porch light on for no one in particular"),
    ("p1053", "somebody's dog barking in perfect time outside"),
    ("p1054", "the printer jammed on page one of forty pages"),
    ("p1055", "streetlight buzzing louder than the traffic tonight"),
    ("p1056", "bought a plant to feel responsible for something small"),
]


def main():
    assembler = HaikuAssembler()
    found = []
    for post_id, text in STREAM:
        found.extend(assembler.feed_post(text, source_id=post_id))

    print(f"fed {len(STREAM)} posts, extracted {len(found)} haiku\n")
    for h in found:
        print(str(h))
        print(f"  (spliced from: {', '.join(h.sources)})\n")


if __name__ == "__main__":
    main()
