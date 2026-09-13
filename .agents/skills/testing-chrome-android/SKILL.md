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

* The guest-area HTML (`/preparer-mon-sejour`, `/en/prepare-your-stay`, `/es/preparar-mi-estancia`)
  is served by LiteSpeed with **`Cache-Control: no-cache, no-store, must-revalidate`** and **no ETag**.
  Consequence measured over CDP: `Page.backForwardCacheNotUsed -> MainResourceHasCacheControlNoStore`,
  i.e. these pages can **never** be restored from the back/forward cache or from disk. Every back
  navigation and every tab restore is a full network round-trip, so a discarded tab shows a **blank
  white viewport with the correct URL** until the HTML arrives — this is the most likely explanation
  for "intermittent blank page" reports on loaded phones, and the recommended fix is to drop
  `no-store` in favour of something revalidatable (`no-cache` + `ETag`, or a short `max-age`).
* The static assets (`/rm-services-data.js`, `/transfer-quote.js`, `/rm-booking-engine.js`,
  `/rm-custom.css`) are `public, max-age=2592000` with **no ETag**; the JS files are referenced
  **without** a `?v=` query while the CSS files have one. A phone can therefore run 30-day-old JS
  against fresh HTML. It cannot blank the page (they are `defer`red and only drive the booking
  engines) but it can silently break a pricing engine — add a version query if the JS changes.
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
