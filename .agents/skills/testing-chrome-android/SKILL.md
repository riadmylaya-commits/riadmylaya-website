---
name: testing-chrome-android
description: How to reproduce and instrument mobile-only bugs (blank page, layout, cache) of the riadmylaya.com production site on a real Chrome Android instance, using an Android emulator + adb + CDP on this machine. Use when a client reports a symptom that only happens on a phone.
---

# Testing riadmylaya.com on real Chrome Android (emulator + adb + CDP)

Desktop Chrome cannot reproduce mobile-only symptoms (Chrome desktop will not go below ~485 px,
has no tab eviction, and no Android back-stack). Use a real Android Chrome instead.

## 1. Set up the emulator (once, ~10 min)

```bash
# cmdline-tools -> /home/ubuntu/android/cmdline, SDK root -> /home/ubuntu/android/sdk
sdkmanager --sdk_root=/home/ubuntu/android/sdk "platform-tools" "emulator" \
  "system-images;android-34;google_apis_playstore;x86_64"
avdmanager create avd -n rmtest -k "system-images;android-34;google_apis_playstore;x86_64" -d pixel_5
```

Two traps that cost time:

* `FATAL | Broken AVD system path. Check your ANDROID_SDK_ROOT value` — the AVD looks for
  `$SDK/sdk/system-images/...`. Fix with `ln -sfn /home/ubuntu/android/sdk /home/ubuntu/android/sdk/sdk`.
* `This user doesn't have permissions to use KVM (/dev/kvm)` — `sudo -n chmod 666 /dev/kvm`
  (passwordless sudo is available). Without KVM the x86_64 image refuses to boot.
* The box runs **nested virtualization**: first paint takes 5–15 s. A white screen for a few
  seconds is an emulator artefact, **not** a bug — always re-screenshot after waiting before
  calling something a blank page.

Chrome first run needs GUI clicks: dismiss the emulator dialogs, choose "Use without an account",
then open an incognito tab if the test needs a cold cache.

## 2. Instrument with CDP

```bash
adb forward tcp:9222 localabstract:chrome_devtools_remote
curl -s http://localhost:9222/json/list   # tab ids + urls
```

Helpers already on the box (outside the repo, safe to reuse/adapt):

* `/home/ubuntu/cdp.py` — `TAB=<id> python3 cdp.py eval '<js>' | nav <url> [s] | watch <s> | current`
* `/home/ubuntu/measure.py` — `TAB=<id> python3 measure.py <url> [loads] [wait] [--hard]`;
  prints layout metrics (scrollY, scrollHeight, `--rm-head-h`, body visibility/overflow,
  sticky header/TOC rects, `elementFromPoint` at the viewport centre, open `<details>`) **and** a
  CDP summary (console, exceptions, CSP/log entries, `Network.loadingFailed`, HTTP >= 400,
  `backForwardCacheNotUsed`, disk-cache hits).
* `/home/ubuntu/bfprobe.py` — navigate away + back, reports bfcache refusal reasons.

`TAB` matters: ids shift after Chrome restarts, and incognito tabs are separate targets. Re-list
before each run. With >10 open tabs the emulator is slow enough that CDP `recv` can time out —
close tabs with `curl http://localhost:9222/json/close/<id>` before measuring.

## 3. Useful adb levers for mobile-only scenarios

```bash
adb shell input keyevent 4                       # hardware BACK
adb shell input keyevent 3                       # HOME (background Chrome)
adb shell input swipe 540 1800 540 400 300       # scroll
adb shell settings put system accelerometer_rotation 0
adb shell settings put system user_rotation 1    # landscape (0 = portrait)
adb shell svc wifi disable; adb shell svc data disable   # offline
adb shell am start -a android.intent.action.VIEW -d '<url>'   # WhatsApp-style external link
adb shell am force-stop com.android.chrome        # simulates OS-killed Chrome / tab eviction
```

* `am send-trim-memory` fails on a foreground process ("Unable to set a background trim level")
  and refuses to raise the level once set; `am force-stop` + relaunch is the reliable way to
  emulate the "54 tabs, low memory, tab discarded" condition.
* The emulator is not rootable (`adbd cannot run as root in production builds`), so individual
  renderer processes cannot be killed.

## 4. Known production facts worth re-checking, not re-discovering

* **Blank-page history (read this first).** The guest-area HTML used to be served with
  `Cache-Control: no-cache, no-store, must-revalidate` and **no ETag**; CDP then reported
  `Page.backForwardCacheNotUsed -> MainResourceHasCacheControlNoStore`, so the pages could never be
  restored from bfcache or disk and a discarded/restored tab showed a **blank white viewport with the
  correct URL** until the HTML re-downloaded. If an "intermittent blank page on Android" report comes
  back, re-measure the HTML headers first — a regression to `no-store` (or a lost `FileETag`) is the
  prime suspect.
* **Current expected headers (measured after the cache fix).** Guest HTML: `200`,
  `cache-control: no-cache, must-revalidate`, `etag: "...-...;br"`, `content-encoding: br`, HTTP/2,
  no `pragma`/`expires`; `If-None-Match` -> `304`. With those headers the blank page no longer
  reproduces: back navigation is restored with **no** `backForwardCacheNotUsed` event, a tab restored
  after `am force-stop` repaints within a few seconds, and a tab restored **with Wi-Fi + data
  disabled** still paints the full page from cache under Chrome's "No internet connection" banner
  (that offline case was fully blank before the fix — it is the sharpest regression probe).
* Local scripts are now versioned (`?v=<epoch>` on `ads-tracking.js`, `rm-antibot.js`,
  `rm-services-data.js`, `transfer-quote.js`, `rm-booking-engine.js`) and carry ETags; they stay
  `public, max-age=2592000` and are served `fromDiskCache` on repeat loads. The CSS files
  (`assets/theme/css/style.css`, `mbr-additional.css`, `rm-custom.css`) still have **no ETag**.
  When checking a deploy, assert every `?v=` script is `200` (a stale `?v=` would 404).
* `/home/ubuntu/hdrprobe.py` — `TAB=<id> python3 hdrprobe.py <url>`: navigates the Android target and
  prints status + `cache-control`/`etag`/`content-encoding`/`fromDiskCache` for the main document and
  every versioned asset, plus console errors and bfcache refusals. Fastest way to verify cache and
  script-version assertions on a phone in one shot.
* Anti-bot fields to expect in the DOM of every `form[action*="rm-envoi.php"]` (6 per guest page):
  hidden `_ts` (page-open `Date.now()`, same value for all forms) and a trap `_url` with
  `value === ""`, `tabIndex === -1`, `getBoundingClientRect().right` around `-9960`, inside an
  `aria-hidden="true"` wrapper. Turnstile has been switched on/off several times: check the
  deployed `SITEKEY` in `https://riadmylaya.com/rm-antibot.js` first. If it is empty, `.cf-turnstile`
  and any `challenges.cloudflare.com` script/iframe count must be `0`; if it is set, expect 6
  `.cf-turnstile` boxes per guest page.
* **Measuring Turnstile on Android.** The challenge iframe lives in a **closed shadow root**, so
  counting `iframe` elements always returns 0 and is *not* a "did not render" signal. Measure
  `.cf-turnstile` `offsetHeight` (0 = not rendered, ~71 = a visible checkbox) and the length of
  `[name="cf-turnstile-response"]`. A widget only renders after it is scrolled reasonably near the
  viewport, and `scrollIntoView()` via CDP is often ignored on Chrome Android — scroll with
  `adb shell input swipe` or a `#anchor` intent instead, and tap the checkbox by clicking the
  emulator window with the mouse (simplest reliable way to hit it).
* **A mobile client can obtain a Turnstile token** (verified on the emulator with sitekey
  `0x4AAAAAAEydHUtiaotTS4bC`): tapping the checkbox shows "Succès !" and fills
  `cf-turnstile-response` with ~752 chars. Earlier runs where Android showed height 0 / no token were
  measured on a **stale CDP tab id** — every `am start ... -d <url>` intent creates a *new* target,
  so re-run `curl -s http://localhost:9222/json/list` and re-read `TAB` after each navigation, or
  you will silently instrument an old tab.
* The repeated `Failed to execute 'postMessage' on 'DOMWindow': target origin
  'https://challenges.cloudflare.com'` console errors from `api.js` are **noise on Android** — they
  appear even when the widget renders and issues a valid token. Do not treat them as the cause of a
  Turnstile failure.
* Healthy portrait baseline (Pixel 5, 393x722): `scrollHeight` ~7.2k, `--rm-head-h: 52px`
  (68px in landscape), `body{visibility:visible;opacity:1}`, no full-viewport `position:fixed`
  element. Hash deep links (`#transfert`, `#diner`, `#hammam`, `#contact`, unknown anchors) keep
  `scrollY` inside the document and open the targeted `<details>` — the bottom inline script is
  not a blank-page suspect.
* URL oddities that render fine and are **not** causes of a blank page: a trailing U+2060
  (`/en/prepare-your-stay%E2%81%A0` -> styled 404 card) and a trailing slash
  (`/en/prepare-your-stay/` -> same page, fully styled).
* Expected console noise: one `net::ERR_ABORTED` canceled `Fetch` per navigation and a CSP refusal
  for `https://stats.g.doubleclick.net/g/collect` (GA telemetry, not in `connect-src`). Neither
  affects rendering.

## Devin Secrets Needed

None for this skill (production is public). Mailbox checks need
`RIADMYLAYA_CONTACT_MAILBOX_PASSWORD` — see `.agents/skills/testing-static-site/SKILL.md`.
