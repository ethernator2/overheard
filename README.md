# overheard

An art project that listens to the Bluesky firehose the way [Carnivore](https://r-s-g.org/carnivore/) and the DCS-1000 listened to network traffic: intercept, extract, surface. Fragments of public posts are pulled from the stream, occasionally resolving into haiku (5-7-5), and republished alongside a real-time visualization of the "traffic."

Planned home: `overheard.vejadu.xyz`, served from a Debian 12 LAMP box (Apache, MariaDB, PHP 8.2, Perl).

## Why

Inspired directly by the Carnivore art projects of the early 2000s, and by an earlier project of the author's own: an audio analyser built on real Carnivore-captured network data, mapping IP ports and traffic volume to frequency and modulation. It was originally an art piece, but proved genuinely useful at a busy data center support desk — technicians could hear unusual or high traffic in the background without it interrupting their work. `overheard` revives that idea for a different kind of traffic: public speech instead of packets.

## Concept

1. **Intercept** — consume the Bluesky AT Protocol Jetstream firehose (`wss://jetstream2.us-east.bsky.network/subscribe`) rather than polling search, in keeping with the packet-sniffing spirit of the original.
2. **Extract** — run intercepted post text through a haiku extractor (syllable-counting heuristic); most fragments pass through as noise, some settle into 5-7-5.
3. **Surface** — a web front end shows the capture log and a real-time visualizer: a Winamp/Milkdrop-style plasma/particle field reframed as a surveillance console, plus (planned) a sonification layer echoing the original audio analyser — traffic volume and "port"-like signal features mapped to frequency/modulation, designed to sit in the background rather than demand attention.

## Status

- [x] Visual design direction settled: CRT/phosphor terminal console aesthetic (see `visualizer-demo.html`), validated against the author's own first-hand experience of period data-center monitoring tools.
- [x] Domain/name decided: `overheard.vejadu.xyz`
- [ ] Real Jetstream firehose consumer
- [ ] Syllable-counting haiku extraction engine (the demo's extraction is a stub — hand-picked sample triplets, not live analysis)
- [ ] MariaDB schema for captured fragments/haiku
- [ ] PHP front end serving the capture log + visualizer from live data
- [ ] Sonification layer (frequency/modulation from traffic features)
- [ ] Server + DNS: Apache vhost on the Debian box, A record for `overheard.vejadu.xyz`

## Files

- `visualizer-demo.html` — standalone teaser of the visual console, running on a scripted sample feed (no live connection yet).

## Notes on scope

Bluesky's API terms and rebroadcast/republication rules should be checked before going live — the piece surfaces public speech, which the surveillance framing should stay honest about rather than obscure.
