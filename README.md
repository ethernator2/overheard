# overheard

An art project that listens to the Bluesky firehose the way [Carnivore](https://r-s-g.org/carnivore/) and the DCS-1000 listened to network traffic: intercept, extract, surface. Fragments of public posts are pulled from the stream, occasionally resolving into haiku (5-7-5), and republished alongside a real-time visualization of the "traffic."

Planned home: `overheard.vejadu.xyz`, served from a Debian 12 LAMP box (Apache, MariaDB, PHP 8.2, Perl).

## Why

Inspired directly by the Carnivore art projects of the early 2000s, and by an earlier project of the author's own: an audio analyser built on real Carnivore-captured network data, mapping IP ports and traffic volume to frequency and modulation. It was originally an art piece, but proved genuinely useful at a busy data center support desk — technicians could hear unusual or high traffic in the background without it interrupting their work. `overheard` revives that idea for a different kind of traffic: public speech instead of packets.

## Concept

1. **Intercept** — consume the Bluesky AT Protocol Jetstream firehose (`wss://jetstream2.us-east.bsky.network/subscribe`) rather than polling search, in keeping with the packet-sniffing spirit of the original.
2. **Extract** — words from many different intercepted posts are spliced together into a single 5-7-5 haiku as they arrive, not "does this one post already scan" — closer to how the original Carnivore art pieces recombined fragments of unrelated transmissions. See `haiku_engine/`.
3. **Surface** — a web front end shows the capture log and a real-time visualizer: a Winamp/Milkdrop-style plasma/particle field reframed as a surveillance console, plus a sonification layer echoing the original audio analyser — traffic volume and a hash-derived "phantom port" per fragment mapped to frequency/modulation, designed to sit in the background rather than demand attention. Opt-in only (`engage audio monitor`), never autoplays.

## Status

- [x] Visual design direction settled: CRT/phosphor terminal console aesthetic (see `visualizer-demo.html`), validated against the author's own first-hand experience of period data-center monitoring tools.
- [x] Domain/name decided: `overheard.vejadu.xyz`
- [x] Haiku extraction engine — real syllable counting (pyphen-based) and multi-author word splicing, see `haiku_engine/`
- [x] Sonification layer — ambient drone tied to traffic volume, per-capture tones from a text hash, haiku triggers a 3-note arpeggio (see `visualizer-demo.html`, previewable offline via `sonification/render_demo.py`)
- [x] Real Jetstream firehose consumer — always-on, filtered to `app.bsky.feed.post` creates (+ optional language filter), writing to MariaDB (see `firehose/`). Not yet deployed to the server.
- [ ] PHP front end serving the capture log + visualizer from live DB data (currently the visualizer still runs on a scripted sample feed)
- [ ] Server + DNS: Apache vhost on the Debian box, A record for `overheard.vejadu.xyz`

## Files

- `visualizer-demo.html` — the console: visualizer, capture log, and sonification layer, running on a scripted sample feed (not yet wired to the live DB).
- `haiku_engine/` — Python haiku extraction engine.
  - `syllables.py` — syllable counting (pyphen-backed, with a no-dependency heuristic fallback).
  - `assembler.py` — `HaikuAssembler`, which splices words from a continuous multi-post stream into 5-7-5 haiku.
  - `demo.py` — feeds a sample multi-author stream through the assembler; `python3 demo.py` to run.
- `sonification/render_demo.py` — offline WAV renderer mirroring the browser's Web Audio sonification logic, for previewing without opening a browser.
- `firehose/` — the always-on listener.
  - `consumer.py` — connects to Jetstream, feeds post text into a shared `HaikuAssembler`, writes fragments/haiku to MariaDB. Resumes from its last cursor on restart.
  - `config.py` — reads DB/Jetstream settings from the environment; see `.env.example`.
  - `schema.sql` — `fragments`, `haiku`, `consumer_state` tables; includes a suggested retention cron line.
- `deploy/overheard-listener.service` — systemd unit for running `consumer.py` as an always-on background service.

## Deploying the listener

1. `mysql -u root -p -e "CREATE DATABASE overheard CHARACTER SET utf8mb4; CREATE USER 'overheard'@'localhost' IDENTIFIED BY '...'; GRANT ALL ON overheard.* TO 'overheard'@'localhost';"`
2. `mysql -u overheard -p overheard < firehose/schema.sql`
3. Copy the repo to `/opt/overheard` on the server, `python3 -m venv .venv && .venv/bin/pip install -r requirements.txt`.
4. `cp firehose/.env.example firehose/.env` and fill in real DB credentials.
5. `sudo cp deploy/overheard-listener.service /etc/systemd/system/ && sudo systemctl enable --now overheard-listener`
6. `journalctl -u overheard-listener -f` to watch it capture.

Bandwidth stays small on purpose: the consumer subscribes only to `app.bsky.feed.post` creates (not the raw firehose) and, by default, only `en`-language posts — a few KB/s continuous, independent of whether anyone is visiting the site.

## Notes on scope

Bluesky's API terms and rebroadcast/republication rules should be checked before going live — the piece surfaces public speech, which the surveillance framing should stay honest about rather than obscure.
