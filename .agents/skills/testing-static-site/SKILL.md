---
name: testing-static-site
description: How to run and browser-test the riadmylaya-website static site locally, including the production fallback proxy needed because the repo only holds a subset of the live site. Use when verifying pages, JS behaviour or layout in public_html/.
---

# Testing riadmylaya-website locally

## Repo shape (important)

- Pure static site: **no build, no npm, no dev server**. Everything lives in `public_html/`.
- The repo contains only a **subset** of the live site. These exist **only in production**
  (`https://riadmylaya.com`) and are 404 locally:
  - `/assets/**`, `/rm-custom.css`, `/common-ui.js`, `/promo-config.js`, `/spa-booking.js`
  - many pages: `/contact`, `/rooms`, `/visit`, `/en/contact`, `/es/page11`, `/blog/`
- Live URLs are **extension-less** (`/activites-groupe`), so requests must map to `<path>.html`.
- FR pages use `<base href="/">`; EN/ES pages use **relative** asset paths and rely on the
  server having `/en/assets` and `/es/assets`.

### Any NEW image added to an EN/ES page will 404 unless the path is absolute

This is the single highest-yield check when a change adds or swaps an image on
`en/*.html` / `es/*.html`. `activities.html` has `<base href="/">` but
`en/activities.html` and `es/activities.html` **do not**, so a relative
`data-src="assets/images/foo.jpg"` resolves to `/en/assets/images/foo.jpg`.
Old images happen to work because production mirrors those directories; a
**newly added** file only exists at the site root, so EN/ES silently 404 while FR
looks perfect. Verify before testing:

```bash
grep -c '<base ' public_html/activities.html public_html/en/activities.html public_html/es/activities.html
ls public_html/en/assets public_html/es/assets   # do not exist in the repo
for L in "" en/ es/; do curl -s -o /dev/null -w "$L %{http_code}\n" "https://riadmylaya.com/${L}assets/images/<newimage>.jpg"; done
```

The fix is a root-relative `data-src="/assets/images/foo.jpg"` (or adding `<base href="/">`).
**Never conclude from FR alone** — always assert per-language.

> The repo blueprint (Devin settings) is wrong for this repo — it declares `npm install`,
> `npx eslint src/`, `npx tsc --noEmit`, `npm run build`, `npm run dev`. None of these apply.
> Ignore them; there is no `package.json`. Do not waste time trying to build.

## Serve it: static server + production 404 fallback proxy

Save as `/home/ubuntu/serve_proxy.py` and run
`nohup python3 /home/ubuntu/serve_proxy.py > /tmp/proxy.log 2>&1 &`
then browse `http://localhost:8099/...`. Check `/tmp/proxy.log` to see which paths were
served locally (`LOCAL`) vs proxied to production (`PROXY <path> -> <status>`) — this is the
fastest way to confirm you are testing *your* local file and not the live page.

```python
#!/usr/bin/env python3
"""Serve public_html/ locally, proxying any 404 to https://riadmylaya.com."""
import os, sys, urllib.request, urllib.error
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer

ROOT = "/home/ubuntu/repos/riadmylaya-website/public_html"
UPSTREAM = "https://riadmylaya.com"


class H(SimpleHTTPRequestHandler):
    def translate_path(self, path):
        p = path.split("?", 1)[0].split("#", 1)[0]
        local = os.path.normpath(os.path.join(ROOT, p.lstrip("/")))
        if os.path.isdir(local):
            idx = os.path.join(local, "index.html")
            if os.path.exists(idx):
                return idx
        # extension-less live URLs -> .html
        if not os.path.exists(local) and not p.endswith("/"):
            if os.path.exists(local + ".html"):
                return local + ".html"
        return local

    def send_head(self):
        local = self.translate_path(self.path)
        if os.path.isfile(local):
            return SimpleHTTPRequestHandler.send_head(self)
        return self.proxy()

    def proxy(self):
        req = urllib.request.Request(UPSTREAM + self.path, headers={
            "User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120 Safari/537.36",
            "Accept": "*/*",
        })
        try:
            r = urllib.request.urlopen(req, timeout=25)
            body, code = r.read(), r.status
            ctype = r.headers.get("Content-Type", "application/octet-stream")
        except urllib.error.HTTPError as e:
            body, code = e.read(), e.code
            ctype = e.headers.get("Content-Type", "text/html")
        except Exception as e:
            body, code, ctype = str(e).encode(), 502, "text/plain"
        sys.stderr.write("PROXY %s -> %s\n" % (self.path, code))
        self.send_response(code)
        self.send_header("Content-Type", ctype)
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        if self.command != "HEAD":
            self.wfile.write(body)
        return None

    def log_message(self, fmt, *args):
        sys.stderr.write("LOCAL " + (fmt % args) + "\n")


ThreadingHTTPServer(("0.0.0.0", 8099), H).serve_forever()
```

## Gotchas that will mislead you

### This Chrome strips `target="_blank"` from every anchor
`getAttribute('target')` returns `null` in the live DOM even when the served HTML has
`target="_blank"`, so external links open in the **same tab**. This affects
**pre-existing pages too**, so it is an environment artifact, not a site bug.
Verify with a control page before reporting a "opens in same tab" defect:

```js
// on any untouched page, e.g. /activities
[...document.querySelectorAll('a')].filter(a => a.getAttribute('target')).length  // 0 => env artifact
```
To prove the markup is correct, diff the server response against the live DOM:
```js
fetch(location.href).then(r=>r.text()).then(t=>{
  const d = new DOMParser().parseFromString(t,'text/html');
  window.__r = {
    parsed: [...d.querySelectorAll('a[target]')].length,   // from server HTML
    live:   [...document.querySelectorAll('a')].filter(a=>a.getAttribute('target')).length
  };
});
```
Report new-tab behaviour as **untested / environment-limited** rather than failed.

### Mobile viewport: Chrome has a ~500px minimum window width
`wmctrl -r :ACTIVE: -e 0,0,0,430,800` is clamped (you get ~532px outer / ~485px CSS).
485px is still below the site's 520px single-column breakpoint, so it is usable — but you
cannot reach a true 375px phone width by window resizing. Confirm the actual viewport and
resulting layout numerically instead of trusting the window size:
```js
JSON.stringify({
  innerWidth: window.innerWidth,
  noHorizScroll: document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1,
  themeCols: getComputedStyle(document.querySelector('.rm-gyg-themes__grid')).gridTemplateColumns
})
```
Restore with `wmctrl -r :ACTIVE: -b add,maximized_vert,maximized_horz`.
A maximized window **silently ignores** resize requests, so unmaximize first:
```bash
wmctrl -r :ACTIVE: -b remove,maximized_vert,maximized_horz
xdotool getactivewindow windowsize 800 900   # then re-read window.innerWidth
```
Screenshots of the small window are tiny on a scaled display — use the `zoom` action on the
browser viewport region to produce legible mobile evidence.

### Localised page templates are easy to get wrong
Pages are Mobirise exports; the navbar is identified by a `cid-*` class and each language
directory has its **own** `assets/mbr-additional.css`. A page that copies the FR navbar into
`en/` or `es/` renders **French labels and unstyled/dark navbar** because the FR `cid` has no
rules in the EN/ES CSS. Always compare a new page's navbar `cid` against its sibling:
```bash
cd public_html && for f in en/activities.html en/<new-page>.html es/activities.html es/<new-page>.html; do
  echo "=== $f"; grep -oE 'class="menu menu2 cid-[a-zA-Z0-9]+"' $f
  grep -oE '>(Accueil|Home|Inicio|Chambres|Rooms|Habitaciones|Visite|Visit|Visita|Contacts?|Contacto)<' $f | tr '\n' ' '; echo
done
```
Also sanity-check navbar height/colour, which exposes missing CSS immediately:
```js
JSON.stringify({h: document.querySelector('.menu nav').getBoundingClientRect().height,
                bg: getComputedStyle(document.querySelector('.menu nav')).backgroundColor})
// healthy pages: rgb(225,73,29) orange. Height is viewport-dependent:
//   ~149px at 1600px wide (expanded desktop bar), ~71px at <=~991px (collapsed burger bar).
// Do NOT treat a single absolute height as the pass criterion - always compare the new page
// against its sibling reference page (en/activities.html, es/activities.html) at the SAME
// window width. A broken navbar shows as dark grey rgb(40,40,40) regardless of height.
```

### Fixed navbar overlaps hero content
The navbar is fixed and ~138-149px tall on desktop (~71px collapsed on narrow viewports).
Custom hero sections need enough top padding or their first line hides behind it. Working
values for this site's custom hero: `padding:170px 0 70px` desktop plus
`@media(max-width:991px){padding:150px 0 60px}`. Measure rather than eyeball:
```js
const nb = document.querySelector('.menu nav').getBoundingClientRect();
const el = document.querySelector('.rm-gyg-hero__eyebrow');
nb.bottom - el.getBoundingClientRect().top   // > 0 means covered
```

### Production link targets
Some localised contact URLs are broken in production independently of the repo — check before
blaming a PR:
```bash
for u in https://riadmylaya.com/contact https://riadmylaya.com/en/contact https://riadmylaya.com/es/page11; do
  echo -n "$u -> "; curl -s -o /dev/null -w "%{http_code} final=%{url_effective}\n" -L "$u"; done
```
At time of writing `/es/page11` redirects to `/es/contact`, which is **404**.

## GetYourGuide affiliate logic (`public_html/gyg-affiliate.js`)

Single knob: `var PARTNER_ID` near the top. **As of commit 83d6e66 the real production ID
`PGMWEHF` is committed** — do NOT "clean" it back to `""`. Only revert if you introduced a
temporary test value yourself; check `git diff` to tell the two cases apart before editing.
- Empty ⇒ `[data-gyg-widget]` get `hidden`, `[data-gyg-fallback]` are shown, GYG links untracked.
- Set ⇒ `partner_id` + `cmp=riadmylaya` appended to `[data-gyg-link]` hrefs, widget attrs set,
  and `https://widget.getyourguide.com/dist/pa.umd.production.min.js` injected.

To test the tracked state, temporarily set `PARTNER_ID = "TESTID123"`, hard-reload, then assert:
```js
const w = document.querySelector('[data-gyg-widget]'), f = document.querySelector('[data-gyg-fallback]');
JSON.stringify({
  widgetHidden: w.hasAttribute('hidden'), fallbackHidden: f.hasAttribute('hidden'),
  partner: w.getAttribute('data-gyg-partner-id'), cmp: w.getAttribute('data-gyg-cmp'),
  script: !!document.querySelector('script[src*="pa.umd.production.min.js"]'),
  firstLink: document.querySelector('[data-gyg-link]').getAttribute('href')
})
```
The real GetYourGuide widget **did** render live activities from this environment (no
Cloudflare/anti-bot block observed), with both the test ID and the real `PGMWEHF`, but treat a
blocked/empty widget as an acceptable finding.

### Beware: "widget visible / fallback hidden" is NOT proof the affiliate JS ran
In the HTML the widget div has **no** `hidden` and the fallback **already has** `hidden`. So the
tracked-state visuals look identical even if `gyg-affiliate.js` never executes. The assertions
that actually discriminate working from broken are all runtime-only:
```js
const w = document.querySelector('[data-gyg-widget]');
const links = [...document.querySelectorAll('[data-gyg-link]')];
JSON.stringify({
  partner: w.getAttribute('data-gyg-partner-id'),   // injected by JS, absent from served HTML
  script: !!document.querySelector('script[src*="pa.umd.production.min.js"]'),
  total: links.length,                              // expect 9 = 1 fallback CTA + 8 theme cards
  tracked: links.filter(a => a.href.includes('partner_id=') && a.href.includes('cmp=riadmylaya')).length
})
```
Also note `URL.searchParams` re-serialises the existing query, so `?q=Marrakech%2C%20Morocco`
becomes `?q=Marrakech%2C+Morocco` after rewriting. `+` and `%20` are equivalent in a query
string — do not report this as a defect.

### Only the group pages load `gyg-affiliate.js` — check before asserting on rewriting
The three `activities.html` pages (FR/EN/ES) contain GetYourGuide *markup* but do **not** include
the `gyg-affiliate.js` script tag; only the three group pages do. So an assertion like "this href
was not rewritten" is **vacuously true** on the `activities` pages and proves nothing there.
Always confirm which pages actually run the script before designing such a test:
```bash
cd public_html && for f in activities.html en/activities.html es/activities.html \
  activites-groupe.html en/group-activities.html es/actividades-en-grupo.html; do
  echo -n "$f : "; grep -c 'gyg-affiliate\.js' $f; done   # 0 on activities.html, 1 on group pages
```
```js
!!document.querySelector('script[src*="gyg-affiliate.js"]')   // false on the activities pages
```

### Links that must NOT be rewritten: check the `data-gyg-link` attribute
`gyg-affiliate.js` only rewrites `[data-gyg-link]`. Some affiliate links (e.g. GetYourGuide
**short links** like `https://gyg.me/pieHSnog`) already carry tracking after their 301 redirect
and are deliberately given **no** `data-gyg-link`, so appending `partner_id`/`cmp` would be wrong.
When such a link is added, the discriminating test is to load a **group** page (where the script
demonstrably runs) and assert the bare href survived while the other links did get tracked:
```js
const a = document.querySelector('#gyg-banner a.rm-gyg-cta-banner__btn');
const tracked = [...document.querySelectorAll('[data-gyg-link]')];
JSON.stringify({
  href: a.getAttribute('href'),                     // must stay EXACTLY the short link, no '?'
  rel: a.getAttribute('rel'),                       // 'nofollow sponsored noopener'
  optedOut: !a.hasAttribute('data-gyg-link'),       // true
  trackedCount: tracked.length,                     // must stay 9, NOT 10
  allTracked: tracked.every(x => x.href.includes('partner_id=') && x.href.includes('cmp=riadmylaya'))
})
```
A count of 10 means the new link was wrongly wired into the rewriter.

### `gyg.me` short links: 403 under `curl`, fine in the browser
`curl -L https://gyg.me/<code>` returns **403** from GetYourGuide's bot protection on the final
landing page, even though the `301` Location header correctly contains `partner_id=...`. Clicking
the same link in the real browser loads the destination normally. Judge short links by clicking
them in the browser and reading the address bar — do not report the curl 403 as a broken link.

### Expect one third-party console error from the widget
Once the GYG widget hydrates, the console shows `Hydration completed but contains mismatches.`
plus a large GetYourGuide ASCII-art banner. Both originate from GetYourGuide's own React bundle,
not from the site's code. Attribute it explicitly rather than counting it as a page error, and
check the console **after** the widget has loaded so you do not mistake an empty console for a
clean one.

**If (and only if) you introduced a temporary test value, revert it** and prove the tree is clean
(the committed real `PGMWEHF` must be left alone):
```bash
cd /home/ubuntu/repos/riadmylaya-website && git status --porcelain && git diff --exit-code -- public_html/gyg-affiliate.js
```
The `edit` tool is the reliable way to revert; if a shell `sed`/heredoc appears to hang or
returns no output, re-verify with `git status` before assuming the revert failed.

### Production CSP blocks the GetYourGuide widget — test prod separately from local

The widget can render perfectly on `localhost:8099` and still be **completely dead on
production**, because the live host sends a `Content-Security-Policy` header that is not in
the repo and does not whitelist GetYourGuide:

```bash
curl -sI https://riadmylaya.com/activites-groupe | grep -i content-security-policy
# script-src 'self' 'unsafe-inline' 'unsafe-eval' googletagmanager google-analytics jscache maps.googleapis
# frame-src  'self' youtube portal.freetobook maps.google
```

Neither `script-src` nor `frame-src` includes `https://widget.getyourguide.com`, so
`pa.umd.production.min.js` is blocked and the widget container stays empty. Because the
fallback block is already `hidden` in the HTML, the failure is **silent** — the section just
looks blank, with no visible error. Detect it from the live DOM rather than by eye:

```js
const w = document.querySelector('[data-gyg-widget]');
({children: w.children.length, h: w.getBoundingClientRect().height,
  iframes: document.querySelectorAll('iframe').length})
// healthy: children > 0 and an iframe present; blocked: children 0, iframes 0
```

Note `performance.getEntriesByType('resource')` reports `responseStatus: 0` for the widget
script whether it was CSP-blocked or merely cross-origin-opaque, so it is **not** a reliable
signal — use the DOM check above, and confirm the CSP header. Also `curl` the script URL: if
it returns 200 from the shell but the widget is empty in the browser, CSP (not the network)
is the cause. Always test the widget on **both** local and production; they can disagree.

The CSP lives in the hosting `.htaccess`, **outside the repo**, so it can only be fixed by the
owner/host — never by a commit. The working whitelist (confirmed to make the widget render in
production) adds `https://widget.getyourguide.com` to **`script-src` and `frame-src`**, plus
`https://widget.getyourguide.com https://api.getyourguide.com` to **`connect-src`**. After such a
change, an `curl -sI | grep -i content-security-policy` header check is **necessary but not
sufficient**: the header can be correct while the widget still fails for another reason, so always
confirm in the browser with the DOM check plus a screenshot of real activity content.

A healthy production widget measures roughly: `iframes >= 1` whose `src` host is
`widget.getyourguide.com`, widget element height around **950px** (a CSP-blocked one is ~60px), and
`data-gyg-locale-code` matching the page language (`fr-FR` on FR, `en-US` on EN, `es-ES` on ES) —
GetYourGuide does localize the activity titles per page, so language is assertable.

### Verify the console capture works before reporting "console clean"

An empty console readout is ambiguous: it can mean "no errors" or "capture is broken / buffer was
already drained" (the log buffer is incremental — reading it consumes it, so a second read looks
empty even when the first had errors). Prove the pipeline works before claiming cleanliness, using
a deferred log so it originates from the page rather than the evaluation context:

```js
setTimeout(() => { console.error('PAGE_ORIGIN_SANITY_CHECK') }, 300); 'scheduled'
```

Then read the console again and confirm `PAGE_ORIGIN_SANITY_CHECK` appears. Only after that does an
empty console on a real page count as evidence of no CSP violations.

Note the third-party `Hydration completed but contains mismatches.` warning is **intermittent** — it
appeared on earlier passes and not on later ones. Do not treat its absence as a regression signal,
and do not treat its presence as a site defect.

## Navbar language switcher (globe dropdown): hover-navigation hazard

The theme's own `assets/theme/js/script.js` opens navbar dropdowns on hover by dispatching a
**synthetic `new Event("click")`** on the `<a class="dropdown-toggle">`, while
`assets/dropdown/js/navbar-dropdown.js` relies on `preventDefault()` in its click listener. A
`new Event(...)` is **not cancelable**, so `preventDefault()` is a no-op and the browser may follow
the toggle's `href="#"`. FR pages carry `<base href="/">` (EN/ES pages do not), so `#` resolves to
the site root and the visitor is thrown back to the home page. The hover-open handler binds **only**
when `991 < innerWidth` **and** `.navbar` lacks `collapsed`.

### Two consequences for testing

1. **Load the page already at desktop width.** Hover handlers bind once at init, so resizing after
   load leaves them unbound and the bug/fix becomes untestable. Same for the mobile path: load at
   ~485px, where the globe is opened by tapping the burger then the globe.
2. **"Hover the globe and watch the URL" is NOT a discriminating test in this Chrome.** Blink runs
   anchor link-activation only for real `MouseEvent`s, so the theme's plain `Event("click")` does
   **not** navigate here — an unfixed build looks identical to a fixed one under literal hover. The
   owner may still see it in another browser. To actually discriminate, fire the strictly stronger
   form of the same attack:

```js
toggle.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: false }));
```

On an unfixed build this navigates `/activities` → `/#` (home). On a build where `href` was removed
it cannot navigate at all. Use hover only to prove the menu still **opens** (FR/EN/ES visible in
pixels) and still closes on mouseout.

### Removing `href` fixes navigation but breaks keyboard activation

A common fix is to strip `href` from `.navbar-nav .dropdown-toggle[data-bs-toggle="dropdown"]` and
add `role="button"` / `tabindex="0"`. Always test the keyboard explicitly, because this **regresses
accessibility**: `tabindex="0"` restores Tab focus, but `role="button"` does **not** make the browser
synthesize a click, and `navbar-dropdown.js` listens only for `"click"`. Observed result: Tab reaches
the toggle, **Enter does nothing, Space scrolls the page**, whereas the unfixed build opened the menu
with Enter (a real Enter-generated click is cancelable, so `preventDefault()` worked). A complete fix
needs a `keydown` handler for Enter/Space that toggles the menu and calls `preventDefault()`.

The shipped fix uses exactly that, and it was verified in production:

```js
el.addEventListener("keydown", function (e) {
  if (e.key === "Enter" || e.key === " " || e.key === "Spacebar") {
    e.preventDefault();
    el.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true }));
  }
});
```

When validating such a handler, the discriminating number for the Space case is **`window.scrollY`
before vs after** the keypress (the old failure was Space scrolling instead of opening), not just
`aria-expanded`. Also assert `location.href` is unchanged: a `keydown` handler that re-dispatches a
click could in principle reintroduce navigation, so re-run the non-cancelable `MouseEvent` harness
after the keyboard fix.

### Escape does NOT close this dropdown — pre-existing, not a regression

`navbar-dropdown.js` has no `keydown`/Escape/keyCode-27 handler at all, so Escape leaves the menu
**visibly open**: `aria-expanded` flips to `false` but `li.open` stays, and the menu keeps
`display:block` (~165px tall). Measured identical on both the unfixed and fixed builds, so report it
as a pre-existing theme gap. Closing works via a **second activation** (Enter/Space/click) or
`mouseout`. Don't let this be mistaken for a regression from a language-switcher fix.

### Getting Tab focus to the navbar toggle reliably

Clicking a blank spot sets the sequential-focus starting point at **that** element, so clicking in
the hero/body and pressing Tab walks forward into the service cards and the floating FAB items
(`class="rm-fab-item"`) and never reaches the navbar. Click a blank area **inside the orange header
bar** (e.g. `(310, 103)` at 1600px wide, avoiding the links) and then Tab ~7 times. Verify you
arrived rather than assuming:

```js
var ae = document.activeElement;
JSON.stringify({ isToggle: /dropdown-toggle/.test(ae.className || ''), scrollY: window.scrollY })
```

If Tab escapes into the browser address bar, reload and restart from the header.

### `common-ui.js` lives on the host, not (historically) in the repo

`public_html/common-ui.js` injects the sticky CTA and the WhatsApp FAB and is loaded on **every**
page including pages absent from the repo. That is why a fix placed there also protects
repo-absent pages such as `/page19` and `/hammam-spa` — the proxy serves production HTML plus the
**local** `common-ui.js`, which is a good way to test a site-wide fix. Confirm which copy you are
exercising before trusting any result:

```bash
curl -s http://localhost:8099/common-ui.js  | grep -c fixLanguageSwitcher   # local  = fixed
curl -s https://riadmylaya.com/common-ui.js | grep -c fixLanguageSwitcher   # prod   = 0 if undeployed
```

**Production caches this file for 30 days** (`cache-control: public, max-age=2592000`), so when
validating a freshly deployed change you must load every page with a cache-busting query string
(`?cb=<n>`) and/or Ctrl+Shift+R, then confirm **in the live DOM** that the new build is active
(`hasHref === false`, `role === "button"`, `tabindex === "0"`) before asserting anything. If
`hasHref` is still `true` you are looking at a cached old script and the result is void. A quick way
to confirm the deployed file matches the repo is to download it with a cache-buster and compare
`md5sum` against `public_html/common-ui.js`.

Also read the toggle's attributes in both places (`hasHref`, `role`, `tabindex`): the asymmetry
proves you are not looking at a cached production copy. When regression-testing `common-ui.js`,
check the localized sticky CTA (`Réserver au meilleur prix` / `Book at the best price` /
`Reservar al mejor precio`), the `MYRIAD12` promo code, and the WhatsApp `href`
(`wa.me/212661351989`) in all three languages.

## Landing pages built from `gen_stay.py` (`preparer-mon-sejour`, `transferts`, + `en/`, `es/`)

These generated pages use their own `rm-ps-*` / `rm-lp-*` classes and behave differently from the
Mobirise pages. Learnings that saved time:

- **They all carry `<base href="/">`, including the EN/ES copies.** That means the root
  `assets/mbr-additional.css` is loaded, the FR navbar `cid` resolves, and the navbar renders the
  healthy orange `rgb(225,73,29)` with already-localized labels. Still judge it in pixels.
- **New images must be root-relative** (`/assets/images/...`): `/en/assets/**` and `/es/assets/**`
  are 404, so a relative path breaks EN/ES only.
- **Card images are lazy-loaded.** A first console read before scrolling reports
  `naturalWidth: 0` for below-the-fold cards, which looks like a broken image. Scroll the whole
  card grid, then re-read:
  ```js
  [...document.querySelectorAll('.rm-ps-card')].map(c => ({id: c.id, nw: c.querySelector('img').naturalWidth}))
  ```
- **The floating WhatsApp FAB is intentionally absent on these pages.** `common-ui.js`
  (`hasExistingWhatsApp()`) skips injecting `#rm-wa-btn` whenever the page already contains any
  `a[href*='wa.me']`, which these pages do. Do **not** report the missing FAB as a regression; the
  sticky CTA `#rm-sticky-cta` is still injected and localized.
- **TOC chips use smooth scrolling and do not update `location.hash`**, so asserting on the hash
  fails. The meaningful assertion is the `scroll-margin-top` clearance (160px desktop / 85px
  mobile):
  ```js
  const nb = document.querySelector('.navbar-fixed-top').getBoundingClientRect();
  const h = document.getElementById('<target>').querySelector('h2, h3').getBoundingClientRect();
  h.top - nb.bottom   // must be > 0
  ```
  Note a `.rm-ps-card` *container* top can legitimately be slightly negative (its photo runs behind
  the bar); judge the **heading**, not the container.
- **Never submit the FormSubmit form** unless the client explicitly asks for a real delivery test
  (see the FormSubmit section below). The discriminating no-submit test is to click the submit
  button empty and confirm the URL stays on the stay page with a native bubble on the first
  required field, then fill name+email and confirm it still blocks on the textarea. Count only
  user-visible fields — `_subject`, `_template`, `_captcha`, `_next` and the `_honey` honeypot are
  hidden and must not count towards the expected 5.

- Clicking a `wa.me` button opens a real WhatsApp landing page and Chrome may raise an
  **"Open xdg-open?"** dialog. Dismiss it; the landing page behind it already proves the number and
  decoded message. Prefer asserting the decoded `href` text (`decodeURIComponent`, `%0A` line
  breaks) for all buttons and clicking only one.
- Stay pages ship `<meta name="robots" content="noindex, follow">`; transfers pages ship **no**
  robots meta (`document.querySelector('meta[name=robots]') === null`). Assert both directions.

### FormSubmit deliveries (`_captcha`) — only with explicit client authorisation

Every form posts to `https://formsubmit.co/contact@riadmylaya.com`. The single setting that decides
whether the client ever receives the mail is the hidden `_captcha` field:

- `_captcha=true` ⇒ the visitor is shown FormSubmit's `Almost There — Please help us fight spam`
  reCAPTCHA interstitial and **nothing is delivered** unless they tick the box. This looks fine in
  every screenshot of the filled form, so it can silently break all mail for months.
- `_captcha=false` ⇒ the POST is accepted and the browser is redirected straight to `_next`.

If a real-submission test is authorised, do it **against production** (`https://riadmylaya.com`, hard
reload with `ctrl+shift+r`; the local proxy is useless here) and keep the volume bounded to the exact
list the client approved — each submission is a real e-mail. Use obvious sentinels
(`TEST DEVIN - NE PAS TRAITER`, an `.invalid` e-mail) and log the UTC time of each click so the owner
can find the messages; **never claim inbox receipt** — only the mailbox owner can confirm that.

Read the target form's hidden fields from the live DOM immediately before clicking, and the engine
recap too (an empty recap means the client gets a detail-free e-mail):

```js
const f = document.querySelector('form[action*="formsubmit"]');   // or details[i].querySelector('form')
const r = f.querySelector('[data-tq-recap],[data-bk-recap]');
JSON.stringify({next: f.querySelector('[name="_next"]').value,
                captcha: f.querySelector('[name="_captcha"]').value,
                captchaTrue: document.querySelectorAll('input[name="_captcha"][value="true"]').length,
                recapLen: r ? r.value.length : -1,
                total: (f.querySelector('[data-tq-total-field],[data-bk-total-field]')||{}).value})
```

The decisive observable after the click is the landing page: the riad's own thank-you card at the
form's `_next`, not a `formsubmit.co` screen. Known `_next` values and their real landing URLs:

| form | `_next` | actually lands on |
|---|---|---|
| guest area FR / EN / ES (6 forms each) | `/merci-sejour`, `/en/thank-you-stay`, `/es/gracias-estancia` | same |
| `/transferts` | `/merci` | `/merci` |
| `/contact`, `/rooms` | `/merci.html` | `/merci` (legitimate redirect to the extensionless route) |
| `/hammam-spa` | `/merci-spa.html` | `/merci-spa` |

Do **not** retry a form "just to be sure" after an odd result — that sends a second e-mail. Two
representation traps seen while doing this: the stripped-DOM dump can show a filled offscreen input
as empty (trust a visible screenshot or `.value` instead), and the spa page dump shows several
`src="undefined"` images (a lazy-loading artefact, not a confirmed defect).

### `<base href="/">` + fragment-only links = TOC navigates to the home page (check this FIRST)

The single highest-value check on any of these generated pages is: **click a TOC chip and look at
the URL.** `<base href="/">` makes a bare `<a href="#foo">` resolve against the base, not the
current document, so the browser navigates to `http://host/#foo` — the site **home page** — and the
guest page is lost entirely. This is invisible in the markup and looks like "the anchor just didn't
scroll" if you only watch the viewport.

It only works when something compensates. Two known compensators, either of which may be missing
after a regeneration:

- a theme smooth-scroll script (`assets/smoothscroll/smooth-scroll.js`), or
- a page-local anchor/`scrollIntoView` handler.

So before asserting on `scroll-margin-top` clearance, verify a compensator exists:

```bash
grep -n '<base' public_html/preparer-mon-sejour.html
grep -n '<script' public_html/preparer-mon-sejour.html
grep -c 'scrollIntoView\|hashchange\|smooth-scroll' public_html/preparer-mon-sejour.html
```

If the page carries `<base href="/">`, has **no** smooth-scroll script and **0** anchor handlers,
every internal TOC link is broken and no clearance assertion can pass — report the navigation, not
the clearance. This regressed exactly this way when the client pages were made standalone and the
Mobirise theme scripts were dropped: only `/ads-tracking.js` and `/transfer-quote.js` remained.
The defect is per-template, so reproduce it in one language and confirm the same
`<base>`-plus-no-handler pattern in the other two rather than clicking all 27 links.

### Self-referencing TOC links + inline reveal handler (the fix shape)

The repaired shape is: chips carry the **full path** (`/preparer-mon-sejour#groupe`,
`/en/prepare-your-stay#…`, `/es/preparar-mi-estancia#…`) *and* an inline IIFE at end of body
intercepts clicks, sets `target.open = true` for `<details>`, `scrollIntoView`s, calls
`preventDefault()` and `history.replaceState`s the hash; on load, `location.hash` triggers the same
reveal.

**Do not stop at "we did not land on the home page."** A self-referencing href is a same-document
hash navigation, so it stays on the page even if the JS is dead. The discriminating assertion is
that the targeted `<details>` is **open with its body visible in pixels** — a closed accordion
showing only its summary row means the handler did not run. Cheap cumulative proof: click all 9
chips in order, then read that **all 6** `<details>.open === true`, `location.pathname` is still
canonical and the hash equals the 9th chip. Add an anti-reload probe first — type a sentinel
(e.g. `NAV-PROBE`) into the quote-form name field; it survives `replaceState` but a real navigation
wipes it, so its survival is the proof that no document load happened.

The handler guard is `href.split("#")[0] !== location.pathname`, so it only engages on the
**canonical extensionless** route. Reaching the page as `/preparer-mon-sejour.html` disengages it
and chips become real navigations to the canonical URL (which then reveals the block on load via
the hash). Harmless, but test the extensionless routes or you will measure the wrong code path.

### `scroll-margin-top` is honoured for the sections but NOT for the `<details>` blocks

Measured on the client pages (header `.rm-cs-head` is `position:sticky`, ~68px desktop / ~52px
mobile; CSS declares `scroll-margin-top:96px`, `78px` under `max-width:600px`):

- `section.rm-ps-sec` targets (`arrivee`, `infos`, `contact`): element top lands at ~79px and the
  heading clears the sticky header by **+86/88px**. Correct.
- `details.rm-ps-acc` targets (`transfert diner cuisine excursions groupe hammam`): element top
  lands at **-1px** even with an `instant` re-scroll after layout settles, so the targeted service
  title sits **~-36px desktop / ~-30px mobile behind the sticky header**, despite
  `getComputedStyle(details).scrollMarginTop` reporting `78px`. The body copy below is readable, so
  this is easy to miss visually — measure `title.top - header.bottom` and require `> 0`.

So assert the **heading clearance number**, per target *type*, and never generalise a passing
section result to the accordions. If this is still failing, likely workarounds: apply the
scroll-margin to a wrapper/spacer element around each `<details>` (the `<details>` here is a flex
item with `overflow:hidden`, whose scroll-margin Chrome appears to ignore), or have the reveal
handler scroll manually with `window.scrollTo({top: rect.top + scrollY - headerHeight})` instead of
relying on `scrollIntoView`.

### Standalone "client space" pages (`body[data-rm-space="client"]`)

- `common-ui.js` returns early from `injectStickyCta()` for `data-rm-space="client"`, but the
  standalone pages **do not load `common-ui.js` at all**. So "no sticky CTA" here is *not* proof the
  suppression logic works — it is absent for a different reason. Assert the asymmetry honestly:
  `#rm-sticky-cta` absent on the client page while **present** on public `/transferts`, and report
  `script[src*="common-ui"]` as false rather than claiming suppression.
- The pages keep a small page-local `footer.rm-cs-foot`. The claim to test is "no *site* navbar or
  footer" (`.menu nav`, `.navbar-fixed-top`, `.mbr-footer` all count 0), not "no footer element".
- Services are `<details class="rm-ps-acc">` with ids `transfert diner cuisine excursions groupe
  hammam`; open/close by clicking the `<summary>` and read `details.open`.

### Deferred GetYourGuide loading inside the group accordion

Prove it as a transition, not a state: before touching `#groupe`,
`script[src*="gyg-affiliate"]` must be **absent** and `iframes=0`; after opening,
assert the script appeared plus `data-gyg-partner-id="PGMWEHF"`, `data-gyg-locale-code`
matching the page (`fr-FR`/`en-US`/`es-ES`), a real `widget.getyourguide.com` iframe and real
localized activity titles in pixels. Widget height varies a lot with viewport — ~950px at desktop
but ~2400px at 485px where cards stack — so assert "not the ~60px blocked state" rather than a
fixed number. Short-link button must be exactly `https://gyg.me/pieHSnog`.

### The sticky service bar: measure it at the page bottom, not at the top

The guest-area summary (`nav.rm-ps-toc`) is `position:sticky;top:var(--rm-head-h,68px)`, where an
inline `dockHeight()` writes the measured `.rm-cs-head` height into `--rm-head-h` (on load and on
`resize`) and returns header+toc height for the `reveal()` scroll offset. At the top of the page a
sticky and a non-sticky bar look **identical**, so every assertion must be taken deep in the
document:
```js
window.scrollTo(0, document.body.scrollHeight);   // press End twice via keyboard: the first stops short
const t = document.querySelector('.rm-ps-toc').getBoundingClientRect();
const h = document.querySelector('.rm-cs-head').getBoundingClientRect();
t.top - h.bottom   // must be 0 — also re-check after resizing the window (proves the resize handler)
```
Clearance of a chip target must be measured **through the page's own handler**: writing
`location.hash` bypasses `reveal()` and produces false failures. Install a click listener and read
the geometry ~900ms after the click:
```js
document.addEventListener('click', () => setTimeout(() => {
  const toc = document.querySelector('.rm-ps-toc').getBoundingClientRect();
  const el = document.getElementById(location.hash.slice(1));
  window.__c = el.querySelector('h2, h3, summary').getBoundingClientRect().top - toc.bottom;  // must be > 0
}, 900));
```
Below `max-width:720px` the chip list becomes a one-line horizontal scroller: assert
`flexWrap === 'nowrap'`, `list.scrollWidth > list.clientWidth` and that the page itself does **not**
overflow (`documentElement.scrollWidth === clientWidth`). A real touch swipe cannot be simulated
here — wheel/programmatic scrolling plus these numbers is the available proof.

### Cropping the widget's affiliate line: prove it by toggling the CSS live

The widget iframe ends with a `Proposé par GetYourGuide…` affiliate row that cannot be removed from
a cross-origin frame, so it is cropped with `.rm-cs-gyg-clip{overflow:hidden}` plus a negative
`margin-bottom` on `.rm-cs-gyg`. "The widget renders" screenshots look **identical** with and
without the crop, so the only discriminating test is to set `overflow:visible` and
`margin-bottom:0` in the live page and screenshot both states — the line must reappear.
The effective clip measures ~18px even though the declared margin is `-34px` (the widget div
carries trailing space), so measure `.rm-cs-gyg-clip` height against the iframe height rather than
trusting the CSS value, and check the last activity card keeps its title, duration and rating in
FR/EN/ES at both desktop and 485px.

### All prices come from `public_html/rm-services-data.js` — check it is loaded first

`transfer-quote.js`, `rm-booking-engine.js` and `spa-booking.js` hold **no** rates: they read
`window.RM_SERVICES` and log `rm-services-data.js must be loaded first` (and render nothing) when
the data script is missing. `gen_stay.py` also parses that same file, so a rate change there
propagates to the generated tables after `python3 /home/ubuntu/gen_stay.py`. Two consequences:

- Every generated page must ship `<script src="/rm-services-data.js" defer>` **before** the engine
  scripts — assert `!!window.RM_SERVICES` in the console before blaming an engine.
- `/hammam-spa` is a **host-only** page that does *not* include the data script, so `spa-booking.js`
  injects `/rm-services-data.js` itself and initialises in its `onload`. Test that path against the
  production HTML, not only against repo pages: the proxy serves that page's HTML from the
  production fallback while `spa-booking.js` and `/rm-services-data.js` come from `public_html/`, so
  check `/tmp/proxy.log` (`PROXY` vs `LOCAL` per request) to prove the local code path actually ran.

`<input type="time">` renders 12h here but stores 24h, so setting a time by clicking the spinner is
unreliable: type `1140PM` / `0400AM` into the visible field, then re-read the stored value.

A fast non-browser harness that covers all the arithmetic (jsdom, no server needed) is worth
building before any UI pass: load the generated page, `eval` the three scripts, dispatch
`DOMContentLoaded`, then set `[data-tq="…"]` / `[data-bk="…"]` values and read
`[data-tq-total-field]` / `[data-bk-total-field]`. `toLocaleString('fr-FR')` inserts a **narrow
no-break space** (U+202F) in `1 500 MAD`, so normalise whitespace before comparing strings.

### Transfer quote engine (`public_html/transfer-quote.js`)

Shared by the client-space `#transfert` block and public `#calculer`. Judge **exact euro totals**;
"a summary appeared" is not a pass. The round-trip discount applies to the **departure leg only**
(`round_trip_discount.leg` in the data), so airport/4/both is **35 €**, not 30 €. Cases with
arithmetically distinct results:
airport/4/both/10:00 = **35 €** (one `−5 €` line, arrival at full rate),
airport/4/arrival/23:40 = **25 €**,
station/2/arrival/04:00 = **12 €** (no surcharge), station/2/departure/04:00 = **17 €**,
airport/4/departure/10:00 = **20 €** (no discount), station/7 = contact-only message with
**no `€`** and `[data-tq-submit].hidden === true`. Selectors are `[data-tq-summary]`,
`[data-tq-total-field]`, `[data-tq-recap]`, `[data-tq-wa]` — there is no `[data-tq-total]`.
Time inputs render as 12-hour UI but keep 24h DOM values, so type `04:00AM` / `11:40PM`.
Verify the recap hidden field via `[data-tq-recap].value` (non-empty, contains the typed name and
`TOTAL : 30 €`) and **never** click the e-mail submit button.

## Devin Secrets Needed

None — the site is public and no login is required.
