"""Renders a short WAV preview of the sonification layer described in
visualizer-demo.html, using the same hash-to-frequency mapping and
envelope shapes as the browser's Web Audio implementation, so the file
is a faithful stand-in for what the live page sounds like.

Run: python3 sonification/render_demo.py [output.wav]
"""

import math
import struct
import sys
import wave

SR = 44100


def hash_to_freq(text, lo, hi):
    """Mirrors hashToFreq() in visualizer-demo.html."""
    h = 0
    for ch in text:
        h = (h * 31 + ord(ch)) & 0xFFFFFFFF
    return lo + (h % 1000) / 1000 * (hi - lo)


def sine(t, f):
    return math.sin(2 * math.pi * f * t)


def triangle(t, f):
    return (2 / math.pi) * math.asin(math.sin(2 * math.pi * f * t))


def tone(freq, wave_shape="sine", dur=0.4, peak=0.14, pan=0.0):
    """One capture-blip or arpeggio note. Mirrors tone() in the page's AudioEngine."""
    n = int(dur * SR)
    attack = max(1, int(0.02 * SR))
    osc = sine if wave_shape == "sine" else triangle

    sig = [0.0] * n
    for i in range(n):
        t = i / SR
        v = osc(t, freq)
        if i < attack:
            env = peak * (i / attack)
        else:
            frac = (i - attack) / max(1, n - attack)
            env = peak * math.exp(-5 * frac)
        sig[i] = v * env

    sig = one_pole_lowpass(sig, min(4000, freq * 4))

    left_gain = math.sqrt(0.5 * (1 - pan))
    right_gain = math.sqrt(0.5 * (1 + pan))
    return [(s * left_gain, s * right_gain) for s in sig]


def one_pole_lowpass(signal, cutoff_hz):
    dt = 1.0 / SR
    rc = 1.0 / (2 * math.pi * max(20, cutoff_hz))
    alpha = dt / (rc + dt)
    out = [0.0] * len(signal)
    prev = 0.0
    for i, x in enumerate(signal):
        prev = prev + alpha * (x - prev)
        out[i] = prev
    return out


def traffic_level(t):
    """Piecewise-linear traffic curve for this demo: quiet, a spike
    around the first haiku, quiet again."""
    points = [(0, 0.15), (4.5, 0.15), (5.5, 0.75), (9.0, 0.75), (9.5, 0.2), (14.0, 0.2)]
    for (t0, v0), (t1, v1) in zip(points, points[1:]):
        if t0 <= t <= t1:
            frac = 0 if t1 == t0 else (t - t0) / (t1 - t0)
            return v0 + frac * (v1 - v0)
    return points[-1][1]


def render_drone(duration):
    n = int(duration * SR)
    out = [0.0] * n
    phase = 0.0
    prev = 0.0
    dt = 1.0 / SR
    fade = int(1.0 * SR)

    for i in range(n):
        t = i / SR
        level = traffic_level(t)
        freq = 50 + level * 14
        cutoff = 220 + level * 900
        gain = 0.035 + level * 0.09

        phase += freq / SR
        phase -= math.floor(phase)
        raw = 2 * (phase - 0.5)  # sawtooth

        rc = 1.0 / (2 * math.pi * cutoff)
        alpha = dt / (rc + dt)
        prev = prev + alpha * (raw - prev)

        fade_env = 1.0
        if i < fade:
            fade_env = i / fade
        elif i > n - fade:
            fade_env = (n - i) / fade

        out[i] = prev * gain * fade_env

    return [(s * 0.85, s * 0.85) for s in out]


def mix(buffers_with_offsets, total_samples):
    left = [0.0] * total_samples
    right = [0.0] * total_samples
    for offset_samples, buf in buffers_with_offsets:
        for i, (l, r) in enumerate(buf):
            idx = offset_samples + i
            if idx < total_samples:
                left[idx] += l
                right[idx] += r
    return left, right


def normalize(left, right, ceiling=0.9):
    peak = max(max(abs(x) for x in left), max(abs(x) for x in right), 1e-9)
    if peak > ceiling:
        scale = ceiling / peak
        left = [x * scale for x in left]
        right = [x * scale for x in right]
    return left, right


def write_wav(path, left, right):
    n = len(left)
    frames = bytearray()
    for i in range(n):
        l = max(-1.0, min(1.0, left[i]))
        r = max(-1.0, min(1.0, right[i]))
        frames += struct.pack("<hh", int(l * 32767), int(r * 32767))
    with wave.open(path, "wb") as wf:
        wf.setnchannels(2)
        wf.setsampwidth(2)
        wf.setframerate(SR)
        wf.writeframes(bytes(frames))


EVENTS = [
    (1.0, "blip", "raining again, ordered soup, forgot the bread"),
    (2.3, "blip", "cat knocked the mug off exactly at 3am"),
    (3.1, "blip", "the wifi dropped during the important part"),
    (5.0, "haiku", ["kettle steam curling", "into evening's grey window", "the bus never comes"]),
    (7.0, "blip", "third coffee, the deadline moved, it always moves"),
    (8.2, "blip", "planted tomatoes too early again this year"),
    (10.0, "haiku", ["wifi drops again", "the sentence hangs unfinished", "traffic hums outside"]),
    (12.0, "blip", "missed the bus, watched it turn the corner slow"),
]

DURATION = 14.0


def main():
    out_path = sys.argv[1] if len(sys.argv) > 1 else "overheard_sonics_demo.wav"

    buffers = [(0, render_drone(DURATION))]

    for start, kind, payload in EVENTS:
        offset = int(start * SR)
        if kind == "blip":
            freq = hash_to_freq(payload, 220, 720)
            pan = hash_to_freq(payload + "p", -1, 1)
            buffers.append((offset, tone(freq, "sine", dur=0.4, peak=0.14, pan=pan)))
        elif kind == "haiku":
            for i, line in enumerate(payload):
                freq = hash_to_freq(line, 300, 900)
                line_offset = offset + int(i * 0.22 * SR)
                buffers.append((line_offset, tone(freq, "triangle", dur=0.9, peak=0.16, pan=(i - 1) * 0.4)))

    total_samples = int(DURATION * SR)
    left, right = mix(buffers, total_samples)
    left, right = normalize(left, right)
    write_wav(out_path, left, right)
    print(f"wrote {out_path} ({DURATION}s, {len(EVENTS)} events)")


if __name__ == "__main__":
    main()
