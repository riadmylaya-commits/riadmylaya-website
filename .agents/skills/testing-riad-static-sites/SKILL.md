---
name: testing-riad-static-sites
description: How to run and test the Riad Mylaya / Riad Bilkis static pages, common-ui.js sticky bar, GetYourGuide affiliate script and the police (fiche de police) app locally, plus how to verify the live riadbilkis.com WordPress mu-plugin output (custom menu, excursions) in the browser without touching riadmylaya.com.
---

# Testing the Riad static assets locally

This repo only versions the files *added* to hosted WordPress sites (`riadmylaya.com/`,
`riadbilkis.com/`), plus a pre-built `police/` SPA + PHP API. There is no npm project at
the repo root (`npm install` / `npm run dev` from the blueprint do not apply), so testing is
done by serving the folders with simple local HTTP servers. Never test against the live sites.

## Serving the pieces

Assets such as `/common-ui.js`, `/gyg-affiliate.js`, `/gyg/activites.css` are referenced with
**absolute paths from the document root**, so you must serve the `public_html` folder itself:

```bash
cd <site>/public_html && python3 -m http.server 8080
# then open http://127.0.0.1:8080/gyg/fr.html  (also en.html / es.html)
```

The police app is a Vite SPA with client-side routes plus a PHP API:

```bash
cd <site>/police && php -S 127.0.0.1:8082
# / , /register , /staff , /qr all resolve (php -S falls back to index.html)
```

`php-cli` is already installed by the blueprint's initialize step.

Clean URLs (e.g. `/activites-groupe` → `gyg/fr.html`) come from `.htaccess` rewrites and
**cannot** be reproduced with `python3 -m http.server`; test the raw `.html` paths instead
and review the rewrite block by reading `.htaccess`.

## Mobile vs desktop viewport

`common-ui.js` shows its sticky booking bar only under a CSS media query `max-width:991px`,
so a real narrow window is the honest way to test it (device-emulation is not needed):

```bash
wmctrl -r :ACTIVE: -b remove,maximized_vert,maximized_horz
wmctrl -r :ACTIVE: -e 0,40,20,440,700     # ~500px inner width => mobile
wmctrl -r :ACTIVE: -b add,maximized_vert,maximized_horz   # back to desktop
```
Reload after resizing. Useful invariants to assert: `document.body.classList` contains
`rb-has-sticky`, `getComputedStyle(document.body).paddingBottom === '74px'`, and the bar's
`display` is `none` at desktop width.

## Gotchas seen in practice

- **Floating WhatsApp button may be missing on content pages.** `hasExistingWhatsApp()`
  returns true for *any* `a[href*='wa.me']` on the page, so pages that already have a
  WhatsApp CTA in their content (the GetYourGuide activity pages do) never get the floating
  button. If you need to see the floating button, test a page with no in-page `wa.me` link.
- **Booking-page suppression** is matched on the URL path (`/reservation/`, `/reservacion/`,
  `/booking/`). To test it, serve a throwaway page at `/reservation/index.html` from `/tmp`
  (symlink `common-ui.js` next to it) — do not add test pages to the repo. The floating
  WhatsApp button is the discriminator that proves the script actually ran.
- **Pre-built `police/assets/*.js` can lag behind the branding.** Grep the bundle for the
  other riad's name before trusting the UI (`grep -io ".\{80\}mylaya.\{80\}" assets/index-*.js`);
  hardcoded strings have been found on the `/register` header and the `/qr` poster.
- The police API needs a MySQL database that is not provisioned locally, so `install.php`,
  staff login, PDF and e-mail flows are not testable here — report them as untested rather
  than attempting installation.
- `favicon.ico` 404s on the `gyg/*.html` pages (no favicon is declared); harmless but expect
  it in the network log.
- The external GetYourGuide widget script (`widget.getyourguide.com`) does load from this
  environment; if it is blocked, `[data-gyg-fallback]` should become visible instead — check
  which of the two paths you are on before calling it a failure.

## Testing the live riadbilkis.com WordPress site

The `riadbilkis.com/wp-content/plugins/riad-bilkis-frontend/` mu-plugin (menu, excursions,
sticky bar) can only be exercised on the deployed site: `https://riadbilkis.com` (FR),
`/en/` (EN), `/es/` (ES). Never touch `riadmylaya.com`. Testing there is read-only — browse,
assert, screenshot; do not log into wp-admin or change content.

### Custom menu (`riad_bilkis_menu_items_html` + inline `#rb-menu-js`)

- Parents that should open their submenu instead of navigating carry the class `rb-nolink`
  (currently `/nos-services`, `/activites-groupe`, `/en/group-activities`, `/es/actividades`).
  Check the rendered `class` attribute first with
  `curl -s https://riadbilkis.com/ | grep -o 'class="[^"]*rb-nolink[^"]*"'` to confirm the
  deploy actually shipped, then prove the behaviour in the browser.
- The click handler intercepts **all** `.rb-has-children>.menu-link` on mobile
  (`max-width:921px`) but only `.rb-nolink` parents on desktop. So a good regression check is
  that a parent *with* children but *without* `rb-nolink` (e.g. `Blog`) still navigates on
  desktop while being non-navigating on mobile.
- Desktop submenus are pure CSS (`:hover` / `:focus-within`) — use a real `mouse_move` over
  the parent, not JS, and screenshot the open dropdown.
- Mobile submenus toggle the `rb-open` class; `aria-expanded` on the sibling
  `.rb-submenu-toggle` button is the reliable state signal in the DOM. Tap the **text label**,
  not the chevron, otherwise you only test the button branch of the handler and never prove the
  `<a href>` was intercepted.

### Practical browser gotchas on this site

- Chrome's omnibox aggressively autocompletes to previously visited deep URLs, so typing
  `https://riadbilkis.com/en/` can land you on `/en/excursions/imlil/`. Type the URL with a
  throwaway query string (`https://riadbilkis.com/en/?t=1`) and press `Delete` before `Return`
  to dismiss the inline autocompletion. The query string is harmless and also busts cache.
- If `computer` actions seem to have no effect, the Chrome window may not be focused:
  `xdotool getactivewindow` / `xdotool windowactivate <id>`.
- Expect `JQMIGRATE: Migrate is installed` log lines in the console on every page — they are
  informational, not errors.
- Standalone static pages (e.g. `/es/blog`, `/activites-groupe`, `/diner-marocain`) do **not**
  render the WordPress menu — they ship their own copy of the bar (see next section). A change
  to the mu-plugin menu therefore does not propagate to them automatically, and vice versa;
  always check both families when the navigation changes.

### Shared static navigation bar (`.rb-mainbar`, `/rb-header.css`, `/rb-header.js`)

The ~21 static pages (`sejour/*.html`, `experiences/*.html`, `gyg/*.html`) mirror the WordPress
menu through a generated `<div class="rb-mainbar">`. The generator lives outside the repo
(`/home/ubuntu/rb_header_patch.py`), so the menu data (labels, routes, `NOLINK` set) is the
source of truth to read before testing.

- Cheap deploy check across all pages before opening a browser:
  ```bash
  for u in /diner-marocain /cours-de-cuisine /en/moroccan-dinner /es/blog; do
    h=$(curl -s "https://riadbilkis.com$u")
    echo "$u mainbar=$(grep -c 'rb-mainbar' <<<"$h") oldnav=$(grep -c 'class="rb-nav"' <<<"$h")"
  done
  curl -sI https://riadbilkis.com/rb-header.css | head -1
  ```
  Also diff the live assets against the repo copies to be sure the deploy is current.
- Selectors differ from the WordPress menu: submenu `.rb-submenu`, chevron
  `.rb-mainbar__toggle`, burger `.rb-mainbar__burger`, open mobile panel = class `rb-nav-open`
  on `.rb-mainbar`, open parent = `rb-open` on the `<li>`. Mobile breakpoint is
  `max-width:921px` (not 991px like `common-ui.js`), so a ~532px window is safely mobile.
- Same behavioural contract as WordPress: desktop intercepts only `.rb-nolink` parents
  (Services, Activités), mobile intercepts every `.rb-has-children`. `Blog` (children, no
  `rb-nolink`) is the discriminator: it must navigate on desktop and open its submenu on mobile.
- The best end-to-end proof is the cross-page journey: open Services on the home page, land on
  `/diner-marocain`, then reach `/cours-de-cuisine` **from that page's own bar** without going
  back home. That is exactly what the old hard-coded nav made impossible.
- `rb-header.js` logs nothing; a clean console on a static page (no JQMIGRATE either, since
  jQuery is not loaded there) is the expected result.

## Trilingual FR/EN/ES i18n module (riad-bilkis-i18n.php)

- The FR/EN/ES selector is present on **both** static pages and WordPress pages (the WordPress
  menu appends `<li class="rb-menu-item rb-menu-lang">`). Older notes claiming it is
  static-only are obsolete — do not treat its presence on a WordPress page as a defect.
- Every page has a key with three URLs (`riad_bilkis_i18n_page_map`), e.g.
  `rooms` = `/chambres/` | `/en/rooms/` | `/es/habitaciones/`; `services` = `/nos-services/` |
  `/en/services/` | `/es/servicios/`; `dinner` = `/diner-marocain` | `/en/moroccan-dinner` |
  `/es/cena-marroqui`; `activities` = `/activites-groupe` | `/en/group-activities` |
  `/es/actividades`.
- `parse_request` serves the French WordPress page under the EN/ES URL and disables
  `redirect_canonical` + `pll_check_canonical_url`. The strongest single check that the module
  is live: `/es/habitaciones/` must stay on that URL and show `Nuestras Habitaciones`. If it
  redirects to `/chambres/` or 404s, the routing hack has broken.
- The selector must resolve to the **equivalent** page, not the homepage. On excursion detail
  pages it goes through `riad_bilkis_exc_url($slug, $code)`, so the slug must survive:
  `/es/excursiones/ouzoud/` → `/en/excursions/ouzoud/`. A deep page whose selector points at
  `/`, `/en/` or `/es/` is a bug.
- Translation of rendered output is a `strtr` pass over `the_content` / `the_title`, so any
  string missing from the dictionary silently stays French. Always scan rendered EN/ES pages
  for leftover French rather than trusting the menu alone.
- Contact Form 7 `placeholder` attributes are a leakage class of their own: they only change if
  the exact placeholder text is in the dictionary, so check `/en/contact/` and `/es/contacto/`
  with `curl -s <url> | grep -o 'placeholder="[^"]*"' | sort -u` before browsing.
- Hard-coded absolute links inside static page bodies are **not** translated by the module.
  Check body CTAs, not just the nav (activity pages and the Marrakech pages have had CTAs
  pointing at French routes while the page itself was EN/ES). When testing i18n, click body
  CTAs and verify the language of the landing page.
- Watch the static bar's width in Spanish: Spanish labels are the longest and have pushed the
  selector past the viewport. Sweep inner widths ~1000 / 1100 / 1280 / 1440px and compare
  `document.documentElement.scrollWidth` with `window.innerWidth`, plus the selector links'
  `getBoundingClientRect().right`. EN/FR are shorter, so the ES desktop case is the one to
  test explicitly.
- **Always hard-reload (`Ctrl+Shift+R`) before measuring static header layout on the live
  site.** `/rb-header.css` and `/rb-header.js` are served with `max-age=2592000`; a normal load
  can silently reproduce an already-fixed CSS bug, and a "pass" measured without a hard reload
  proves nothing about first-time visitors. The static pages reference the assets with a
  version query (`/rb-header.css?v=…`) — if that query is missing from the HTML, the deploy of
  the pages themselves is stale.
- The CookieAdmin cookie banner is French on every language and is not translatable from the
  code; exclude it from French-leakage reports.

## Deployment pitfall (when a fix seems not to land)

The live document root of riadbilkis.com is `/home/riaductd/riadbilkis.com` — the repo's
`riadbilkis.com/public_html/` maps to that directory **directly**, not to a `public_html`
subfolder (see `/home/ubuntu/deploy_header.py`). Uploading to
`/home/riaductd/riadbilkis.com/public_html/` silently succeeds and serves nothing (and creates
publicly reachable duplicate URLs). WordPress code is loaded from
`/home/riaductd/riadbilkis.com/wp-content/mu-plugins/`, not from `wp-content/plugins/`.
After any upload, verify remotely (`Fileman/get_file_content`) *and* over HTTP with a cache
buster.

## WordPress typography: `#wp-custom-css` outranks the mu-plugin CSS

The mu-plugins inject their typography through `wp_add_inline_style` on the Astra handle, which
emits `<style id="astra-theme-css-inline-css">`. WordPress prints the Customizer's *Additional
CSS* (`<style id="wp-custom-css">`) **after** it. Same specificity, later wins — so any element
whose rule is only as specific as the Customizer's wins the Customizer value instead.

Observed on live Bilkis: the Customizer contains `body { font-family:"Cormorant Garamond";
color:#3D3D3D }` and `p { font-family: Raleway,"Segoe UI",sans-serif }`. Consequence: a
typography pass that sets `body`/`p` to Montserrat in the mu-plugin **does not visibly apply**.
Class-scoped selectors (`.rb-feature p`, `.rb-section-text`, `.rb-room-info p`,
`.rb-exc-card__desc`, …) are more specific and *do* win, so pages built from those classes look
correct while plain-prose pages (room detail bodies, generic `entry-content` paragraphs) silently
keep the old font.

So when testing a font/colour change on this site, **never accept a `curl` grep of the emitted
CSS as proof** — always read `getComputedStyle` in the browser, and check a plain-prose page
(e.g. `/chambre-babouche/`), not only the class-heavy homepage. To attribute a mismatch, walk the
stylesheets and print every rule that matches the element:

```js
const el = document.querySelector('p');
[...document.styleSheets].forEach(ss => { let rs; try { rs = ss.cssRules } catch(e) { return }
  [...rs].forEach(r => { if (r.selectorText && /font-family/.test(r.cssText))
    r.selectorText.split(',').forEach(sel => { try { if (el.matches(sel.trim()))
      console.log((ss.ownerNode&&ss.ownerNode.id)||ss.href, '=>', r.selectorText, r.style.fontFamily);
    } catch(e){} }); }); });
```

If `wp-custom-css` shows up as the winner, the fix is either raising specificity in the
mu-plugin or removing the Customizer rule — the latter needs wp-admin, which testing must not
touch, so report it rather than attempting it.

## Dropdown panels are fixed-width: check label fit after any font change

The WP submenu panel is a fixed `250px`. The longest label,
`Activités en groupe et en privé`, needs ~276px in Montserrat (and already ~260px in the older
Raleway), so it overflows its panel. Enlarging body text or switching family is exactly the
change that makes this visible, and a `scrollWidth > clientWidth` sweep catches it:

```js
document.querySelectorAll('.rb-mainbar a, nav a').forEach(a => {
  if (a.getBoundingClientRect().height && a.scrollWidth > a.clientWidth + 1)
    console.log('CLIPPED', JSON.stringify(a.textContent.trim()), a.scrollWidth, '>', a.clientWidth);
});
```

Two known false positives to filter out of that sweep: the visually-collapsed skip link
(`Aller au contenu` / `Skip to content` / `Ir al contenido`) reports `52 > 1` because it is
clipped by design, and `<option>` elements inside `<select>`. Filter by text and tag before
reporting a failure.

## GetYourGuide affiliate links: what can and cannot be proven, and how

The activity pages (`gyg/{fr,en,es}.html`) use per-card short links `https://gyg.me/XXXXXXXX`.
Counting them in the source proves nothing about where they go. Three separate failure modes
exist and need three different checks.

**1. Are the links still bare at runtime?** `/gyg-affiliate.js` only rewrites
`[data-gyg-link]` elements (adding `?partner_id=&cmp=`). The card CTAs deliberately carry no
such attribute, so they must stay bare. Prove it in the *live DOM* (the script is `defer`), and
prove the script actually ran — otherwise "links are bare" is a reassuring false negative:

```js
const a = [...document.querySelectorAll('.rb-act__cta')].map(x => x.href);
console.log(JSON.stringify({
  n: a.length, distinct: new Set(a).size,
  allBare: a.every(h => /^https:\/\/gyg\.me\/[A-Za-z0-9]+$/.test(h)),
  dataGygLinks: document.querySelectorAll('[data-gyg-link]').length,   // expect 0
  scriptRan: performance.getEntriesByType('resource')
    .filter(r => /gyg-affiliate\.js/.test(r.name)).map(r => r.responseStatus) // expect [200]
}));
```

**2. Do the short links resolve to the right activity?** Sweep the redirect targets in the
shell first — it is fast and finds dead links immediately. A `redirect_url` whose path is `/`
means the short link is **dead and silently lands on the GetYourGuide homepage**, which looks
like a working link to a casual click-test:

```bash
for id in hHN8hT4m poQY0DZX ...; do
  loc=$(curl -s -o /dev/null -w '%{redirect_url}' --max-time 20 "https://gyg.me/$id")
  echo "$id ${loc:-DEAD}"
done
```

**Do not trust the redirect slug as the activity identity.** GetYourGuide slugs go stale while
the numeric tour id still resolves correctly: `UC6eYFqj` redirects to an
`elite-experience-atlas-mountains-hiking` slug but renders *"Marrakech to Agafay: Sunset, Quad,
Camel & Dinner"*, and `ohTXhaBI` redirects to an `imsouane-4-hour-surf-coaching` slug but
renders *"Bahia Palace, Saadian Tombs and Medina Souks Tour"*. Both are correct. A slug/label
mismatch is only a lead — confirm the rendered `<h1>` **in the browser**.

**Also do not try to read the destination title with `curl`.** GetYourGuide bot protection
returns `<title>GetYourGuide – Error</title>` even for links that work fine in a real browser,
so a curl'd title is worthless as evidence either way. Open the URL in the browser.

**3. Is the affiliate attribution right?** The resolved URL carries `cmp=`. On this repo the
card links were generated under the *other* riad's account and resolve with
`cmp=riadmylaya_<activity>-short-url`, while the GetYourGuide *widget* iframe correctly carries
`cmp=riadbilkis`. That inconsistency means commissions may be credited to the wrong property —
always report the `cmp=` value per link rather than assuming, and note that the short links can
only be regenerated in the GetYourGuide partner dashboard, not in this repo.

Layout note: the cards use `.rb-acts__grid` with `repeat(3,1fr)`, `@media (max-width:992px)`
→ 2 columns and `@media (max-width:560px)` → 1 column. Measure the real column count by
grouping `Math.round(getBoundingClientRect().left)` rather than reading the CSS. Bottom-aligned
CTAs depend on `flex:1 1 auto` on `.rb-act p`; verify by comparing each row's
`cta.getBoundingClientRect().bottom`. `.rb-act:hover` applies `translateY(-4px)`, so **park the
cursor outside the grid before measuring geometry** or one card will look 4px off.

## Devin Secrets Needed

None for local static/SPA testing. cPanel/WordPress credentials (`CPANEL_USERNAME`,
`CPANEL_PASSWORD`) would only be needed for a real deployment, which testing must not do.
