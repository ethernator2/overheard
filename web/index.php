<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

$pdo = overheard_db();

$recentFragments = array_reverse(
    $pdo->query('SELECT id, text, captured_at FROM fragments ORDER BY id DESC LIMIT 15')->fetchAll()
);
$recentHaiku = array_reverse(
    $pdo->query('SELECT id, line1, line2, line3, source_uris, captured_at FROM haiku ORDER BY id DESC LIMIT 6')->fetchAll()
);

$initialEvents = [];
foreach ($recentFragments as $row) {
    $initialEvents[] = overheard_to_event('fragments', $row);
}
foreach ($recentHaiku as $row) {
    $initialEvents[] = overheard_to_event('haiku', $row);
}
usort($initialEvents, fn($a, $b) => strcmp($a['captured_at'], $b['captured_at']));

$sinceFragment = overheard_max_id($recentFragments, 0);
$sinceHaiku = overheard_max_id($recentHaiku, 0);
$totalFragments = (int) $pdo->query('SELECT COUNT(*) FROM fragments')->fetchColumn();
$totalHaiku = (int) $pdo->query('SELECT COUNT(*) FROM haiku')->fetchColumn();
$caseId = 'vjd-' . date('Y-m-d');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>overheard</title>
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
  overflow-x: hidden;
}

.console{
  position: relative;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background:
    repeating-linear-gradient(180deg, rgba(111,230,163,0.025) 0px, rgba(111,230,163,0.025) 1px, transparent 1px, transparent 3px),
    radial-gradient(ellipse at 50% 0%, #0b1512 0%, #060a09 70%);
}

.console::after{
  content:"";
  position:fixed; inset:0;
  pointer-events:none;
  background: radial-gradient(ellipse at center, transparent 55%, rgba(0,0,0,0.55) 100%);
  z-index: 50;
}

.topbar{
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
}
.brand-mark span{ color: var(--amber); }
.brand-sub{
  font-size: 0.72rem;
  letter-spacing: 0.08em;
  color: var(--ink-dim2);
  text-transform: uppercase;
}

.status-cluster{
  display:flex;
  align-items:center;
  gap: 22px;
  font-size: 0.72rem;
  letter-spacing: 0.06em;
  color: var(--ink-dim2);
  text-transform: uppercase;
}
.status-item{ display:flex; align-items:center; gap:7px; white-space:nowrap; }

.dot{
  width:7px; height:7px; border-radius:50%;
  background: var(--phosphor);
  box-shadow: 0 0 6px var(--phosphor);
  animation: pulse 1.4s ease-in-out infinite;
}
@media (prefers-reduced-motion: reduce){ .dot{ animation:none; } }
@keyframes pulse{ 0%,100%{ opacity:1; } 50%{ opacity:0.35; } }

.metric strong{
  color: var(--phosphor-bright);
  font-weight:600;
  font-variant-numeric: tabular-nums;
}

.audio-toggle{
  display:flex; align-items:center; gap:7px;
  font-family: inherit;
  font-size: 0.72rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-dim2);
  background: transparent;
  border: 1px solid var(--phosphor-dim);
  border-radius: 3px;
  padding: 5px 10px;
  cursor: pointer;
  transition: border-color 0.15s, color 0.15s;
}
.audio-toggle:hover{ border-color: var(--phosphor); color: var(--ink); }
.audio-toggle:focus-visible{ outline: 2px solid var(--phosphor); outline-offset: 2px; }
.audio-toggle .dot{ animation:none; background: var(--ink-dim2); box-shadow:none; }
.audio-toggle[aria-pressed="true"]{ border-color: var(--amber); color: var(--amber); }
.audio-toggle[aria-pressed="true"] .dot{
  background: var(--amber); box-shadow: 0 0 6px var(--amber);
  animation: pulse 1.4s ease-in-out infinite;
}
@media (prefers-reduced-motion: reduce){ .audio-toggle[aria-pressed="true"] .dot{ animation:none; } }

.main{
  flex: 1;
  display: grid;
  grid-template-columns: minmax(0,1fr) 340px;
  gap: 1px;
  background: var(--line);
  min-height: 560px;
}

.viz-pane{
  position:relative;
  background: var(--bg);
  padding: 18px 18px 0;
  display:flex;
  flex-direction:column;
  min-height: 0;
}

.pane-label{
  font-family:'Oswald', sans-serif;
  font-size: 0.7rem;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: var(--ink-dim2);
  margin-bottom: 10px;
  display:flex;
  justify-content:space-between;
}
.pane-label b{ color: var(--phosphor); font-weight:600; }

/* Canvas elements carry an intrinsic size from their width/height
   attributes (set in JS to real pixel-buffer dimensions), and some
   browsers let that leak into flex/grid sizing as an aspect ratio even
   with explicit CSS width/height set. Wrapping in a plain positioned
   box and absolutely filling it removes the canvas from flex/grid
   sizing entirely, so its own attributes can't influence layout. */
.viz-canvas-wrap{
  position: relative;
  flex: 1;
  min-height: 340px;
}
canvas#viz{
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  display:block;
  border: 1px solid var(--line);
  border-bottom:none;
  background: #050908;
}

.traffic-wrap{
  position: relative;
  height: 64px;
  margin-bottom: 18px;
}
canvas#traffic{
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  display: block;
  border: 1px solid var(--line);
  border-top:none;
  background: #050908;
}

.log-pane{
  background: var(--panel);
  display:flex;
  flex-direction:column;
  min-height: 0;
}
.log-head{ padding: 16px 18px 10px; border-bottom: 1px solid var(--line); }
.log-head .pane-label{ margin-bottom:4px; }
.log-head p{ margin:0; font-size: 0.72rem; color: var(--ink-dim2); line-height:1.5; }

.log-body{
  flex:1;
  overflow-y: auto;
  padding: 10px 14px 14px;
  display:flex;
  flex-direction: column-reverse;
  gap: 8px;
}
.log-body::-webkit-scrollbar{ width:8px; }
.log-body::-webkit-scrollbar-thumb{ background: var(--line); border-radius:4px; }

.entry{
  font-size: 0.78rem;
  line-height: 1.5;
  padding: 8px 10px;
  border-left: 2px solid var(--phosphor-dim);
  background: rgba(111,230,163,0.03);
  color: var(--ink-dim);
  animation: fadein 0.4s ease;
}
@keyframes fadein{ from{ opacity:0; transform: translateY(-4px);} to{ opacity:1; transform:none; } }
@media (prefers-reduced-motion: reduce){ .entry{ animation:none; } }

.entry .tag{
  display:block;
  font-family:'Oswald', sans-serif;
  font-size: 0.62rem;
  letter-spacing: 0.1em;
  color: var(--ink-dim2);
  text-transform: uppercase;
  margin-bottom: 3px;
}
.entry.haiku{ border-left-color: var(--amber); background: rgba(255,180,84,0.06); color: var(--ink); }
.entry.haiku .tag{ color: var(--amber); }
.entry.haiku .lines{ white-space: pre-line; }
.entry.empty{ color: var(--ink-dim2); font-style: italic; border-left-color: var(--line); }

.footer{
  border-top: 1px solid var(--line);
  padding: 10px 22px;
  display:flex;
  align-items:center;
  justify-content: space-between;
  gap: 16px;
  font-size: 0.68rem;
  color: var(--ink-dim2);
  letter-spacing: 0.04em;
  flex-wrap: wrap;
  background: rgba(12,22,19,0.6);
}
.footer a{ color: var(--phosphor); text-decoration:none; border-bottom: 1px dotted var(--phosphor-dim); }
.footer a:hover{ color: var(--phosphor-bright); }

@media (max-width: 860px){
  .main{ grid-template-columns: 1fr; }
  .log-pane{ min-height: 320px; }
  .viz-canvas-wrap{ min-height: 260px; }
}
</style>
</head>
<body>
<div class="console">
  <div class="topbar">
    <div class="brand">
      <div class="brand-mark">Over<span>·</span>heard</div>
      <div class="brand-sub">project vejadu — live capture</div>
    </div>
    <div class="status-cluster">
      <div class="status-item"><span class="dot"></span>live feed</div>
      <div class="status-item metric">packets/s <strong id="mPps">0</strong></div>
      <div class="status-item metric">captured <strong id="mCap"><?= (int) $totalFragments ?></strong></div>
      <div class="status-item metric">extracted <strong id="mExt"><?= (int) $totalHaiku ?></strong></div>
      <div class="status-item">case <?= htmlspecialchars($caseId, ENT_QUOTES) ?></div>
      <button type="button" class="audio-toggle" id="audioToggle" aria-pressed="false">
        <span class="dot"></span><span id="audioToggleLabel">engage audio monitor</span>
      </button>
    </div>
  </div>

  <div class="main">
    <div class="viz-pane">
      <div class="pane-label"><span>intercept field</span><b>ch. 04</b></div>
      <div class="viz-canvas-wrap"><canvas id="viz"></canvas></div>
      <div class="traffic-wrap"><canvas id="traffic"></canvas></div>
    </div>
    <div class="log-pane">
      <div class="log-head">
        <div class="pane-label"><span>capture log</span><b id="logCount"><?= (int) $totalFragments ?></b></div>
        <p>fragments pulled from the Bluesky firehose. every so often, words from several unrelated posts settle into 5-7-5 and get pulled out.</p>
      </div>
      <div class="log-body" id="logBody"></div>
    </div>
  </div>

  <div class="footer">
    <div>reading live from the intercept archive — moderation is currently off by design, see the project notes</div>
    <div>overheard.vejadu.xyz</div>
  </div>
</div>

<script>
var INITIAL_EVENTS = <?= json_encode($initialEvents) ?>;
var CURSOR = { fragment: <?= (int) $sinceFragment ?>, haiku: <?= (int) $sinceHaiku ?> };
</script>
<script>
(function(){
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ---------- sonification ----------
  var Audio_ = (function(){
    var ctx = null, master = null, droneOsc = null, droneGain = null, droneFilter = null;
    var enabled = false;

    function hashToFreq(text, lo, hi){
      var h = 0;
      for(var i=0;i<text.length;i++){ h = (h*31 + text.charCodeAt(i)) >>> 0; }
      return lo + (h % 1000)/1000 * (hi - lo);
    }

    function ensureCtx(){
      if(ctx) return;
      ctx = new (window.AudioContext || window.webkitAudioContext)();
      master = ctx.createGain();
      master.gain.value = 0.5;
      master.connect(ctx.destination);

      droneFilter = ctx.createBiquadFilter();
      droneFilter.type = 'lowpass';
      droneFilter.frequency.value = 340;
      droneFilter.Q.value = 0.7;

      droneGain = ctx.createGain();
      droneGain.gain.value = 0.05;

      droneOsc = ctx.createOscillator();
      droneOsc.type = 'sawtooth';
      droneOsc.frequency.value = 55;
      droneOsc.connect(droneFilter);
      droneFilter.connect(droneGain);
      droneGain.connect(master);
      droneOsc.start();
    }

    function tone(freq, opts){
      opts = opts || {};
      var wave = opts.wave || 'sine';
      var dur = opts.dur || 0.5;
      var peak = opts.peak !== undefined ? opts.peak : 0.18;
      var delay = opts.delay || 0;
      var pan = opts.pan !== undefined ? opts.pan : 0;

      var t0 = ctx.currentTime + delay;
      var osc = ctx.createOscillator();
      osc.type = wave;
      osc.frequency.value = freq;

      var filt = ctx.createBiquadFilter();
      filt.type = 'lowpass';
      filt.frequency.value = Math.min(4000, freq*4);

      var g = ctx.createGain();
      g.gain.setValueAtTime(0, t0);
      g.gain.linearRampToValueAtTime(peak, t0 + 0.02);
      g.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);

      if(ctx.createStereoPanner){
        var panner = ctx.createStereoPanner();
        panner.pan.value = pan;
        osc.connect(filt); filt.connect(g); g.connect(panner); panner.connect(master);
      } else {
        osc.connect(filt); filt.connect(g); g.connect(master);
      }

      osc.start(t0);
      osc.stop(t0 + dur + 0.05);
    }

    return {
      toggle: function(){
        if(!enabled){
          ensureCtx();
          if(ctx.state === 'suspended') ctx.resume();
          enabled = true;
        } else {
          enabled = false;
          if(ctx && ctx.state === 'running') ctx.suspend();
        }
        return enabled;
      },
      captureBlip: function(text){
        if(!enabled) return;
        var freq = hashToFreq(text, 220, 720);
        var pan = hashToFreq(text+'p', -1, 1);
        tone(freq, { wave: 'sine', dur: 0.4, peak: 0.14, pan: pan });
      },
      haikuArpeggio: function(lines){
        if(!enabled) return;
        lines.forEach(function(line, i){
          var freq = hashToFreq(line, 300, 900);
          tone(freq, { wave: 'triangle', dur: 0.9, peak: 0.16, delay: i*0.22, pan: (i-1)*0.4 });
        });
      },
      setTrafficLevel: function(level){
        if(!enabled || !droneGain) return;
        var t = ctx.currentTime;
        droneGain.gain.linearRampToValueAtTime(0.035 + level*0.09, t + 0.4);
        droneFilter.frequency.linearRampToValueAtTime(220 + level*900, t + 0.4);
        droneOsc.frequency.linearRampToValueAtTime(50 + level*14, t + 0.4);
      }
    };
  })();

  var audioToggleBtn = document.getElementById('audioToggle');
  var audioToggleLabel = document.getElementById('audioToggleLabel');
  audioToggleBtn.addEventListener('click', function(){
    var on = Audio_.toggle();
    audioToggleBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
    audioToggleLabel.textContent = on ? 'audio monitor armed' : 'engage audio monitor';
  });

  // ---------- capture log ----------
  var logBody = document.getElementById('logBody');
  var logCountEl = document.getElementById('logCount');
  var mCap = document.getElementById('mCap');
  var mExt = document.getElementById('mExt');
  var mPps = document.getElementById('mPps');
  var MAX_ENTRIES = 40;

  function addEntry(kind, content){
    var el = document.createElement('div');
    el.className = 'entry' + (kind === 'haiku' ? ' haiku' : '');
    var tag = document.createElement('span');
    tag.className = 'tag';
    var body = document.createElement('div');

    if(kind === 'haiku'){
      tag.textContent = 'extract · 5-7-5';
      body.className = 'lines';
      body.textContent = content.join('\n');
    } else {
      tag.textContent = 'fragment';
      body.textContent = content;
    }

    el.appendChild(tag);
    el.appendChild(body);
    logBody.insertBefore(el, logBody.firstChild);

    while(logBody.children.length > MAX_ENTRIES){
      logBody.removeChild(logBody.lastChild);
    }
  }

  var tickEvents = [];

  function renderEvent(evt, isLive){
    if(evt.kind === 'haiku'){
      addEntry('haiku', evt.lines);
      if(isLive){ pulse(1.0, '#ffb454'); Audio_.haikuArpeggio(evt.lines); }
    } else {
      addEntry('text', evt.text);
      if(isLive){ pulse(0.55, '#6fe6a3'); Audio_.captureBlip(evt.text); }
    }
    if(isLive) tickEvents.push(Date.now());
  }

  if(INITIAL_EVENTS.length === 0){
    var el = document.createElement('div');
    el.className = 'entry empty';
    el.textContent = 'listening — nothing captured yet';
    logBody.appendChild(el);
  } else {
    INITIAL_EVENTS.forEach(function(evt){ renderEvent(evt, false); });
  }

  setInterval(function(){
    var now = Date.now();
    tickEvents = tickEvents.filter(function(t){ return now - t < 5000; });
    mPps.textContent = (tickEvents.length/5).toFixed(1);
  }, 500);

  function poll(){
    var url = 'api.php?since_fragment=' + CURSOR.fragment + '&since_haiku=' + CURSOR.haiku;
    fetch(url).then(function(res){ return res.json(); }).then(function(data){
      data.events.forEach(function(evt){ renderEvent(evt, true); });
      CURSOR.fragment = data.since_fragment;
      CURSOR.haiku = data.since_haiku;
      mCap.textContent = data.total_fragments;
      mExt.textContent = data.total_haiku;
      logCountEl.textContent = data.total_fragments;
    }).catch(function(err){
      console.error('poll failed', err);
    }).finally(function(){
      setTimeout(poll, 4000 + Math.random()*1500);
    });
  }
  setTimeout(poll, 1500);

  // ---------- visualizer canvas ----------
  var canvas = document.getElementById('viz');
  var ctx = canvas.getContext('2d');
  var pulses = [];

  function pulse(strength, color){
    pulses.push({ r: 0, max: 40 + strength*160, strength: strength, color: color, born: Date.now() });
  }

  function resizeCanvas(){
    var rect = canvas.parentElement.getBoundingClientRect();
    canvas.width = Math.floor(rect.width * devicePixelRatio);
    canvas.height = Math.floor(rect.height * devicePixelRatio);
  }
  window.addEventListener('resize', resizeCanvas);
  resizeCanvas();

  var particles = [];
  function spawnParticles(cx, cy, n, color){
    for(var i=0;i<n;i++){
      var a = Math.random()*Math.PI*2;
      var speed = 0.4 + Math.random()*1.8;
      particles.push({
        x: cx, y: cy,
        vx: Math.cos(a)*speed, vy: Math.sin(a)*speed,
        life: 1, decay: 0.004 + Math.random()*0.01,
        color: color
      });
    }
  }

  var lastSpawn = 0;
  function drawViz(){
    var w = canvas.width, h = canvas.height;
    ctx.fillStyle = 'rgba(5,9,8,0.16)';
    ctx.fillRect(0,0,w,h);

    var cx = w/2, cy = h/2;

    ctx.strokeStyle = 'rgba(74,154,168,0.14)';
    ctx.lineWidth = 1;
    for(var r=1;r<=4;r++){
      ctx.beginPath();
      ctx.arc(cx, cy, (Math.min(w,h)/2) * (r/4) * 0.9, 0, Math.PI*2);
      ctx.stroke();
    }

    pulses.forEach(function(p){
      p.r += (p.max/28);
      var alpha = Math.max(0, 1 - p.r/p.max);
      ctx.beginPath();
      ctx.arc(cx, cy, p.r, 0, Math.PI*2);
      ctx.strokeStyle = p.color;
      ctx.globalAlpha = alpha * 0.8;
      ctx.lineWidth = 2 * devicePixelRatio;
      ctx.stroke();
      ctx.globalAlpha = 1;
      if(alpha <= 0 && Math.random() < 0.3){
        spawnParticles(cx, cy, Math.floor(6+p.strength*14), p.color);
      }
    });
    pulses = pulses.filter(function(p){ return p.r < p.max*1.3; });

    if(!reduceMotion && Date.now() - lastSpawn > 500){
      spawnParticles(cx + (Math.random()-0.5)*40, cy + (Math.random()-0.5)*40, 1, 'rgba(111,230,163,0.6)');
      lastSpawn = Date.now();
    }

    particles.forEach(function(p){
      p.x += p.vx; p.y += p.vy;
      p.vx *= 0.995; p.vy *= 0.995;
      p.life -= p.decay;
      if(p.life <= 0) return;
      ctx.beginPath();
      ctx.arc(p.x, p.y, 1.6*devicePixelRatio*p.life+0.6, 0, Math.PI*2);
      ctx.fillStyle = p.color;
      ctx.globalAlpha = Math.max(0, p.life);
      ctx.fill();
      ctx.globalAlpha = 1;
    });
    particles = particles.filter(function(p){ return p.life > 0 && p.x > -50 && p.x < w+50 && p.y > -50 && p.y < h+50; });

    requestAnimationFrame(drawViz);
  }
  requestAnimationFrame(drawViz);

  // ---------- traffic strip (driven by real capture cadence) ----------
  var tcanvas = document.getElementById('traffic');
  var tctx = tcanvas.getContext('2d');
  var bars = [];
  var BAR_COUNT = 90;
  for(var i=0;i<BAR_COUNT;i++) bars.push(0.08);

  function resizeTraffic(){
    var rect = tcanvas.parentElement.getBoundingClientRect();
    tcanvas.width = Math.floor(rect.width * devicePixelRatio);
    tcanvas.height = Math.floor(rect.height * devicePixelRatio);
  }
  window.addEventListener('resize', resizeTraffic);
  resizeTraffic();

  function currentLevel(){
    var now = Date.now();
    var recent = tickEvents.filter(function(t){ return now - t < 4000; }).length;
    return Math.min(1, recent/6);
  }

  function stepTraffic(){
    bars.shift();
    var level = currentLevel();
    var jitter = Math.random()*0.06;
    bars.push(Math.min(1, level*0.85 + jitter + 0.04));
    Audio_.setTrafficLevel(level);
  }
  setInterval(stepTraffic, reduceMotion ? 900 : 220);

  function drawTraffic(){
    var w = tcanvas.width, h = tcanvas.height;
    tctx.fillStyle = '#050908';
    tctx.fillRect(0,0,w,h);
    var bw = w / BAR_COUNT;
    for(var i=0;i<bars.length;i++){
      var bh = bars[i]*h*0.9;
      var amber = bars[i] > 0.55;
      tctx.fillStyle = amber ? 'rgba(255,180,84,0.85)' : 'rgba(111,230,163,0.65)';
      tctx.fillRect(i*bw+1, h-bh, Math.max(1,bw-2), bh);
    }
    requestAnimationFrame(drawTraffic);
  }
  requestAnimationFrame(drawTraffic);

})();
</script>
</body>
</html>
