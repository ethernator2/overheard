<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>overheard — about</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap');

:root{
  --bg: #060a09;
  --panel: #0c1613;
  --line: #1c2b25;
  --phosphor: #6fe6a3;
  --phosphor-dim: #2f5744;
  --phosphor-bright: #b8ffd8;
  --amber: #ffb454;
  --wire: #4a9aa8;
  --ink: #cfe9dc;
  --ink-dim: #9fc6b3;
  --ink-dim2: #6f8e81;
}

*{ box-sizing:border-box; }
html,body{ margin:0; padding:0; }
body{
  background: var(--bg);
  color: var(--ink);
  font-family: 'IBM Plex Mono', ui-monospace, 'SF Mono', Consolas, monospace;
  min-height: 100vh;
}

.page{
  position: relative;
  min-height: 100vh;
  background:
    repeating-linear-gradient(180deg, rgba(111,230,163,0.025) 0px, rgba(111,230,163,0.025) 1px, transparent 1px, transparent 3px),
    radial-gradient(ellipse at 50% 0%, #0b1512 0%, #060a09 70%);
}
.page::after{
  content:"";
  position:fixed; inset:0;
  pointer-events:none;
  background: radial-gradient(ellipse at center, transparent 55%, rgba(0,0,0,0.55) 100%);
  z-index: 0;
}

.topbar{
  position: relative;
  z-index: 1;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap: 16px;
  padding: 14px 22px;
  border-bottom: 1px solid var(--line);
  background: linear-gradient(180deg, rgba(15,26,22,0.9), rgba(12,22,19,0.6));
  flex-wrap: wrap;
}
.brand{ display:flex; align-items:baseline; gap:12px; }
.brand-mark{
  font-family:'Oswald', sans-serif;
  font-weight:700;
  font-size: 1.15rem;
  letter-spacing: 0.14em;
  color: var(--phosphor-bright);
  text-transform: uppercase;
  text-decoration: none;
}
.brand-mark span{ color: var(--amber); }
.brand-sub{
  font-size: 0.72rem;
  letter-spacing: 0.08em;
  color: var(--ink-dim2);
  text-transform: uppercase;
}
.topbar a.nav-link{
  font-size: 0.72rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--ink-dim2);
  text-decoration: none;
  border: 1px solid var(--phosphor-dim);
  border-radius: 3px;
  padding: 5px 10px;
  transition: border-color 0.15s, color 0.15s;
}
.topbar a.nav-link:hover{ border-color: var(--phosphor); color: var(--ink); }

main{
  position: relative;
  z-index: 1;
  max-width: 640px;
  margin: 0 auto;
  padding: 56px 22px 80px;
}

h1{
  font-family: 'Oswald', sans-serif;
  font-weight: 600;
  font-size: 1.6rem;
  letter-spacing: 0.02em;
  color: var(--phosphor-bright);
  text-transform: uppercase;
  margin: 0 0 6px;
  text-wrap: balance;
}
.deck{
  font-size: 0.92rem;
  color: var(--ink-dim);
  margin: 0 0 40px;
  line-height: 1.6;
}

h2{
  font-family: 'Oswald', sans-serif;
  font-weight: 600;
  font-size: 0.78rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--amber);
  margin: 44px 0 14px;
}
h2:first-of-type{ margin-top: 0; }

p{
  font-size: 0.92rem;
  line-height: 1.75;
  color: var(--ink);
  margin: 0 0 18px;
}
p a{ color: var(--phosphor); text-decoration: none; border-bottom: 1px dotted var(--phosphor-dim); }
p a:hover{ color: var(--phosphor-bright); }

.pullquote{
  margin: 28px 0;
  padding: 16px 20px;
  border-left: 2px solid var(--amber);
  background: rgba(255,180,84,0.06);
  color: var(--ink);
  font-size: 0.92rem;
  line-height: 1.7;
}

.byline{
  margin-top: 56px;
  padding-top: 20px;
  border-top: 1px solid var(--line);
  font-size: 0.78rem;
  color: var(--ink-dim2);
  letter-spacing: 0.03em;
}

.footer{
  position: relative;
  z-index: 1;
  border-top: 1px solid var(--line);
  padding: 14px 22px;
  font-size: 0.68rem;
  color: var(--ink-dim2);
  letter-spacing: 0.04em;
  background: rgba(12,22,19,0.6);
}
.footer a{ color: var(--phosphor); text-decoration:none; border-bottom: 1px dotted var(--phosphor-dim); }
.footer a:hover{ color: var(--phosphor-bright); }

@media (max-width: 560px){
  main{ padding: 40px 18px 60px; }
  h1{ font-size: 1.3rem; }
}
</style>
</head>
<body>
<div class="page">
  <div class="topbar">
    <a class="brand-mark" href="/">Over<span>·</span>heard</a>
    <a class="nav-link" href="/">&larr; back to the intercept</a>
  </div>

  <main>
    <h1>Why this listens</h1>
    <p class="deck">A packet sniffer, a wiretap console, and a hunch that a social feed already reads like broken verse.</p>

    <h2>The source</h2>
    <p>This project takes its method from <a href="https://r-s-g.org/carnivore/" target="_blank" rel="noopener">Carnivore</a>, the FBI's DCS-1000 — a system built to sit inside an ISP's network and read everything passing through it — and from the wave of net-art projects the leaked spec inspired, which turned that same capability toward something closer to found poetry than surveillance: intercept a stream nobody fully controls, and see what it says when you're not supposed to be listening.</p>

    <h2>Some of this is personal</h2>
    <p>In the 1990s and 2000s I ran a commercial hosting business that also did computer forensics work in support of law enforcement — which meant a lot of hands-on time with exactly the kind of monitoring and traffic-analysis tooling this project's console borrows its look from. The phosphor-green, alert-amber, scanline aesthetic isn't a costume; it's closer to what those screens actually looked like.</p>
    <p>During that period I also built an audio analyser on top of real Carnivore-captured network data: IP ports and traffic volume mapped to frequency and modulation, so a spike in unusual or heavy traffic became a sound rather than something you had to be staring at a monitor to catch. It started as an art piece. It ended up genuinely useful — colleagues at a busy support desk could hear when something needed attention without it interrupting whatever they were doing. <code>overheard</code>'s sonification layer is a direct descendant of that: traffic volume and a hash-derived tone per capture, built to sit in the background the same way.</p>

    <div class="pullquote">Scrolling a feed like Bluesky has always had a certain rhythm to it — short, declarative, oddly loaded fragments, one after another. It's felt like a kind of broken verse for a long time before this existed. <code>overheard</code> is just a formal way of noticing that.</div>

    <h2>How it actually works</h2>
    <p>A listener subscribes to Bluesky's public firehose — the same packet-sniffing instinct as the original, aimed at a different kind of traffic. Words from many different, unrelated posts are spliced together as they arrive; most of it passes through as noise, and every so often three lines settle into 5-7-5. That's a found haiku: not written, not generated by a model, just recognised in a stream that was never trying to make one.</p>

    <h2>On the noise</h2>
    <p>The firehose is public speech, unfiltered, exactly as posted. This project doesn't moderate what it captures — that's a deliberate choice for now, made so the piece could be seen running against something real before deciding what, if anything, to filter. It means what surfaces here is an honest, occasionally rough, reflection of a live platform, not a curated one.</p>

    <div class="byline">overheard listens continuously; nothing here is written in advance.</div>
  </main>

  <div class="footer">
    <a href="/">overheard.vejadu.xyz</a> · inspired by <a href="https://r-s-g.org/carnivore/" target="_blank" rel="noopener">r-s-g.org/carnivore</a>
  </div>
</div>
</body>
</html>
