# overheard

An art project that listens to the Bluesky firehose the way [Carnivore](https://r-s-g.org/carnivore/) and the DCS-1000 listened to network traffic: intercept, extract, surface. Words from many unrelated posts are spliced together as they arrive, occasionally settling into a found haiku (5-7-5) — that's the actual point of the piece, and the default view shows only those, with a "view all" toggle down to the raw fragment noise they're pulled from. All of it runs alongside a real-time visualization of the "traffic."

Live at `overheard.vejadu.xyz`, served from a Debian 12 LAMP box (Apache/Virtualmin, MariaDB, PHP 8.2, Perl).

## Why

Inspired directly by the Carnivore art projects of the early 2000s, and by an earlier project of the author's own: an audio analyser built on real Carnivore-captured network data, mapping IP ports and traffic volume to frequency and modulation. It was originally an art piece, but proved genuinely useful at a busy data center support desk — technicians could hear unusual or high traffic in the background without it interrupting their work. `overheard` revives that idea for a different kind of traffic: public speech instead of packets.

## Concept

1. **Intercept** — consume the Bluesky AT Protocol Jetstream firehose (`wss://jetstream2.us-east.bsky.network/subscribe`) rather than polling search, in keeping with the packet-sniffing spirit of the original.
2. **Extract** — words from many different intercepted posts are spliced together into a single 5-7-5 haiku as they arrive, not "does this one post already scan" — closer to how the original Carnivore art pieces recombined fragments of unrelated transmissions. See `haiku_engine/`.
3. **Surface** — a web front end shows the capture log (found haiku by default, "view all" reveals the fragments underneath) and a real-time visualizer: a Winamp/Milkdrop-style plasma/particle field reframed as a surveillance console, where burst size tracks how many distinct posts a haiku was actually spliced from — plus a sonification layer echoing the original audio analyser — traffic volume and a hash-derived "phantom port" per fragment mapped to frequency/modulation, designed to sit in the background rather than demand attention. Opt-in only (`engage audio monitor`), never autoplays.

## Status

**Live at [overheard.vejadu.xyz](https://overheard.vejadu.xyz).**

- [x] Visual design direction settled: CRT/phosphor terminal console aesthetic (see `visualizer-demo.html`), validated against the author's own first-hand experience of period data-center monitoring tools.
- [x] Domain live: `overheard.vejadu.xyz`, DNS A record via Infomaniak, HTTPS via Virtualmin's Let's Encrypt integration.
- [x] Haiku extraction engine — real syllable counting (pyphen-based) and multi-author word splicing, see `haiku_engine/`.
- [x] Sonification layer — ambient drone tied to traffic volume, per-capture tones from a text hash, haiku triggers a 3-note arpeggio (see `visualizer-demo.html`, previewable offline via `sonification/render_demo.py`). Burst size in the visualizer scales with how many distinct posts a haiku was actually spliced from.
- [x] Real Jetstream firehose consumer, deployed and running as a systemd service (`overheard-listener`) on the production server — always-on, filtered to `app.bsky.feed.post` creates and language, writing to MariaDB (see `firehose/`).
- [ ] Moderation — deliberately stubbed as a no-op (`firehose/moderation.py`) for now, so the piece can be seen running against real, unfiltered traffic before deciding what (if anything) to filter.
- [x] PHP front end (`web/`) reading the capture log + visualizer from live MariaDB data via polling, deployed behind a Virtualmin-managed vhost. Defaults to a found-haiku-only view with real history paging (`haiku.php`); "view all" reveals the raw fragments. `visualizer-demo.html` remains as the standalone scripted-feed version for reference/teaser purposes.
- [x] Server + DNS — served via Virtualmin/Webmin rather than the hand-written `deploy/overheard.vejadu.xyz.conf` (kept in the repo for reference; superseded once Virtualmin generated its own vhost). PHP execution had to be explicitly enabled per-domain in Virtualmin (PHP-FPM) — it's off by default on a fresh sub-server.

## Files

- `visualizer-demo.html` — standalone reference/teaser copy of the console (visualizer, capture log, sonification), deliberately kept on a scripted sample feed rather than the live DB — `web/index.php` is the real, live-data version.
- `haiku_engine/` — Python haiku extraction engine.
  - `syllables.py` — syllable counting (pyphen-backed, with a no-dependency heuristic fallback).
  - `assembler.py` — `HaikuAssembler`, which splices words from a continuous multi-post stream into 5-7-5 haiku.
  - `demo.py` — feeds a sample multi-author stream through the assembler; `python3 demo.py` to run.
- `sonification/render_demo.py` — offline WAV renderer mirroring the browser's Web Audio sonification logic, for previewing without opening a browser.
- `firehose/` — the always-on listener.
  - `consumer.py` — connects to Jetstream, feeds post text into a shared `HaikuAssembler`, writes fragments/haiku to MariaDB. Resumes from its last cursor on restart.
  - `config.py` — reads DB/Jetstream settings from the environment; see `.env.example`.
  - `schema.sql` — `fragments`, `haiku`, `consumer_state` tables; includes a suggested retention cron line.
  - `moderation.py` — the one moderation checkpoint in the pipeline; currently a deliberate no-op.
- `deploy/overheard-listener.service` — systemd unit for running `consumer.py` as an always-on background service.
- `deploy/overheard.vejadu.xyz.conf` — Apache vhost; `DocumentRoot` is `web/` specifically, so the firehose/DB code and `.env` files stay off-limits over HTTP.
- `web/` — PHP front end reading from the live DB.
  - `index.php` — the console page: seeds from recent captures on load (so it's never empty), then polls `api.php` for new ones. Defaults to a "found haiku only" view — a "view all" toggle reveals the raw fragment noise underneath, since haiku are the actual point.
  - `api.php` — JSON polling endpoint (`?since_fragment=N&since_haiku=N`), returns new events + running totals.
  - `haiku.php` — pages backward through the haiku archive (`?before=<id>&limit=N`), newest-first, powering the "load earlier haiku" control in haiku-only mode.
  - `db.php` — shared PDO helpers.
  - `config.example.php` — copy to `config.php` (gitignored) with real DB credentials.

## Deploying the listener

1. `mysql -u root -p -e "CREATE DATABASE overheard CHARACTER SET utf8mb4; CREATE USER 'overheard'@'localhost' IDENTIFIED BY '...'; GRANT ALL ON overheard.* TO 'overheard'@'localhost';"` — if root's password isn't at hand, Debian's `debian-sys-maint` account (credentials in `/etc/mysql/debian.cnf`) can always get in.
2. `mysql -u overheard -p overheard < firehose/schema.sql`
3. Copy the repo to `/opt/overheard` on the server, `python3 -m venv .venv && .venv/bin/pip install -r requirements.txt`.
4. `cp firehose/.env.example firehose/.env` and fill in real DB credentials.
5. `sudo useradd --system --no-create-home --shell /usr/sbin/nologin overheard` — dedicated system user for the listener; default file permissions are enough for it to read the repo, no chown needed since `consumer.py` never writes to disk.
6. `sudo chown alison:overheard firehose/.env && sudo chmod 640 firehose/.env` — swap `alison` for whichever account owns the repo; keeps the DB password readable by you and the service, not world-readable.
7. `sudo cp deploy/overheard-listener.service /etc/systemd/system/ && sudo systemctl daemon-reload && sudo systemctl enable --now overheard-listener`
8. `journalctl -u overheard-listener -f` to watch it capture.
9. `cp web/config.example.php web/config.php`, fill in the same DB credentials.

### Serving the web front end (actual deployment used Virtualmin)

`deploy/overheard.vejadu.xyz.conf` is a hand-written Apache vhost kept in the repo for reference, but the site is actually served through **Virtualmin** (a hosting control panel), which generates and owns its own vhost config — don't install the hand-written one alongside it, they'll collide on `ServerName`.

1. Create a Virtualmin "Virtual Server" for `overheard.vejadu.xyz`. This provisions its own Apache vhost, an SSL cert, and a `public_html` under something like `/home/<user>/domains/overheard.vejadu.xyz/public_html`.
2. Point that document root at the repo instead of Virtualmin's default placeholder files:
   ```bash
   sudo mv .../public_html .../public_html.virtualmin-default   # keep, don't delete
   sudo ln -s /opt/overheard/web .../public_html
   ```
3. **PHP execution is off by default on a fresh Virtualmin sub-server** — it serves `.php` files as plain text until you explicitly enable it. In Virtualmin: *this domain → Server Configuration → PHP Configuration*, set an execution mode (PHP-FPM recommended) and version (8.2). This was the actual cause of "the page just shows raw PHP source" during setup — not a symlink or file-ownership issue, despite how it initially looked.

Bandwidth stays small on purpose: the consumer subscribes only to `app.bsky.feed.post` creates (not the raw firehose) and, by default, only `en`-language posts — a few KB/s continuous, independent of whether anyone is visiting the site.

## Tuning the pace

Two independent knobs in `firehose/.env` control how busy the piece feels, both found through local live testing against the real firehose:

- `OVERHEARD_SAMPLE_RATE` (default `0.05`) — fraction of matching posts kept for the capture log at all. The unsampled `en`-filtered firehose runs at dozens of posts/second, far too dense for a capture log meant to feel like occasional interception.
- `OVERHEARD_HAIKU_FEED_RATE` (default `0.12`) — of the fragments kept above, the fraction whose words are fed into the haiku assembler. This has to be much lower than it might seem: a continuously-fed assembler completes a 5-7-5 window on ~80% of what it's given (any long enough word stream keeps hitting a 17-syllable window), so without this second knob "occasional" haiku become closer to constant.

Local testing (real Jetstream, `en` filter, both defaults above) produced roughly 1 fragment every 1-2 seconds and 1 haiku every 20-30 seconds — a reasonable starting pace, worth re-tuning once it's live and you can watch it for longer.

## Notes on scope

Bluesky's API terms and rebroadcast/republication rules should be checked before going live — the piece surfaces public speech, which the surveillance framing should stay honest about rather than obscure.
