#!/usr/bin/env python3
"""Generate Riad Mylaya blog article pages (FR/EN/ES) in the existing template,
and insert their cards into the blog index + category index of each language.

Usage: python3 tools/blog/blog_gen.py     # writes into public_html/ of this repo
"""
import html
import os
import re

from blog_content import ARTICLES
from blog_content2 import ARTICLES2

ARTICLES = dict(ARTICLES)
ARTICLES.update(ARTICLES2)

ROOT = os.path.join(
    os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__)))),
    "public_html")
SITE = "https://riadmylaya.com"
BOOK = ("https://portal.freetobook.com/reservations?w_id=45823&w_tkn="
        "WyeaTPwj6MSYcDxNHIPuXgqvOYtlIS2H086gviFbewghhARIYxSGLtJxULb49")
GA = "G-WY8GJP57WY"

CATS = {
    "discover": {
        "fr": ("decouvrir-marrakech", "Découvrir Marrakech"),
        "en": ("discover-marrakech", "Discover Marrakech"),
        "es": ("descubrir-marrakech", "Descubrir Marrakech"),
    },
    "tips": {
        "fr": ("conseils-pratiques", "Conseils pratiques"),
        "en": ("practical-tips", "Practical Tips"),
        "es": ("consejos-practicos", "Consejos prácticos"),
    },
    "experiences": {
        "fr": ("experiences-activites", "Expériences &amp; Activités"),
        "en": ("experiences-activities", "Experiences &amp; Activities"),
        "es": ("experiencias-actividades", "Experiencias y Actividades"),
    },
    "culture": {
        "fr": ("culture-traditions", "Culture &amp; Traditions"),
        "en": ("culture-traditions", "Culture &amp; Traditions"),
        "es": ("cultura-tradiciones", "Cultura y Tradiciones"),
    },
    "itineraries": {
        "fr": ("itineraires-programmes", "Itinéraires &amp; Programmes"),
        "en": ("itineraries-programs", "Itineraries &amp; Programs"),
        "es": ("itinerarios-programas", "Itinerarios y Programas"),
    },
}

L = {
    "fr": {
        "lang": "fr", "locale": "fr_FR", "prefix": "", "blog": "/blog/",
        "suffix": "Le Guide Marrakech",
        "nav": [("Accueil", "/"), ("Chambres", "/rooms"), ("Visite", "/visit"),
                ("Services", "/activities"), ("Blog", "/blog/"), ("Contacts", "/page11")],
        "book": "Réserver", "book_long": "Réserver votre séjour",
        "plan": "Organiser votre séjour", "plan_url": "/preparer-mon-sejour",
        "home_label": "Accueil", "toc": "Sommaire", "toc_aria": "Sommaire",
        "back_cat": "← Retour à la catégorie", "published": "Publié le", "read": "min de lecture",
        "cta_h": "Envie de vivre Marrakech comme dans cet article ?",
        "cta_p": "Le Riad Mylaya, au cœur de la Médina, est le point de départ idéal pour explorer la ville.",
        "cta_foot_h": "Prêt à préparer votre voyage ?",
        "cta_foot_p": "Réservez directement votre séjour au meilleur prix, ou laissez-nous organiser votre expérience sur mesure.",
        "related": "Vous aimerez aussi", "more": "Lire l'article →",
        "footer_addr": "163 Derb Bounba, Arset Ilhiri — Médina, Marrakech, Maroc",
        "footer_blog": "Le Guide Marrakech",
        "footer_links": [("Accueil", "/"), ("Chambres", "/rooms"), ("Visite", "/visit"),
                         ("Services", "/activities"), ("Hammam & Spa", "/hammam-spa"),
                         ("Contacts", "/page11")],
        "articles_count": lambda n: "%d article%s" % (n, "s" if n > 1 else ""),
    },
    "en": {
        "lang": "en", "locale": "en_US", "prefix": "/en", "blog": "/en/blog/",
        "suffix": "The Marrakech Guide",
        "nav": [("Home", "/en/"), ("Rooms", "/en/rooms"), ("Visit", "/en/visit"),
                ("Services", "/en/page10"), ("Blog", "/en/blog/"), ("Contact", "/en/page11")],
        "book": "Book", "book_long": "Book your stay",
        "plan": "Plan your stay", "plan_url": "/en/prepare-your-stay",
        "home_label": "Home", "toc": "Contents", "toc_aria": "Contents",
        "back_cat": "← Back to category", "published": "Published on", "read": "min read",
        "cta_h": "Want to experience the Marrakech of this article?",
        "cta_p": "Riad Mylaya, in the heart of the Medina, is the ideal base to explore the city.",
        "cta_foot_h": "Ready to plan your trip?",
        "cta_foot_p": "Book your stay directly at the best price, or let us craft a tailored experience for you.",
        "related": "You may also like", "more": "Read the article →",
        "footer_addr": "163 Derb Bounba, Arset Ilhiri — Medina, Marrakech, Morocco",
        "footer_blog": "The Marrakech Guide",
        "footer_links": [("Home", "/en/"), ("Rooms", "/en/rooms"), ("Visit", "/en/visit"),
                         ("Services", "/en/page10"), ("Hammam & Spa", "/en/hammam-spa"),
                         ("Contact", "/en/page11")],
        "articles_count": lambda n: "%d article%s" % (n, "s" if n != 1 else ""),
    },
    "es": {
        "lang": "es", "locale": "es_ES", "prefix": "/es", "blog": "/es/blog/",
        "suffix": "La Guía de Marrakech",
        "nav": [("Inicio", "/es/"), ("Habitaciones", "/es/rooms"), ("Visita", "/es/visit"),
                ("Servicios", "/es/activities"), ("Blog", "/es/blog/"), ("Contacto", "/es/page11")],
        "book": "Reservar", "book_long": "Reserve su estancia",
        "plan": "Organice su estancia", "plan_url": "/es/preparar-mi-estancia",
        "home_label": "Inicio", "toc": "Contenido", "toc_aria": "Contenido",
        "back_cat": "← Volver a la categoría", "published": "Publicado el", "read": "min de lectura",
        "cta_h": "¿Quiere vivir el Marrakech de este artículo?",
        "cta_p": "El Riad Mylaya, en el corazón de la Medina, es el punto de partida ideal para descubrir la ciudad.",
        "cta_foot_h": "¿Listo para preparar su viaje?",
        "cta_foot_p": "Reserve su estancia directamente al mejor precio, o déjenos organizar su experiencia a medida.",
        "related": "También le gustará", "more": "Leer el artículo →",
        "footer_addr": "163 Derb Bounba, Arset Ilhiri — Medina, Marrakech, Marruecos",
        "footer_blog": "La Guía de Marrakech",
        "footer_links": [("Inicio", "/es/"), ("Habitaciones", "/es/rooms"), ("Visita", "/es/visit"),
                         ("Servicios", "/es/activities"), ("Hammam & Spa", "/es/hammam-spa"),
                         ("Contacto", "/es/page11")],
        "articles_count": lambda n: "%d artículo%s" % (n, "s" if n != 1 else ""),
    },
}

CAT_ORDER = ["discover", "tips", "experiences", "culture", "itineraries"]


def cat_url(catkey, lang):
    return "%s%s/blog/%s/" % (SITE, L[lang]["prefix"], CATS[catkey][lang][0])


def art_url(lang, slug):
    return "%s%s/blog/%s" % (SITE, L[lang]["prefix"], slug)


def art_path(lang, slug):
    return "%s/blog/%s" % (L[lang]["prefix"].lstrip("/"), slug) if L[lang]["prefix"] \
        else "blog/%s" % slug


def head(lang, a):
    t = L[lang]
    seo = "%s | %s" % (a["seo_title"], t["suffix"])
    desc = html.escape(a.get("meta") or a["description"], quote=True)
    url = art_url(lang, a["slug"])
    img = SITE + a["image"]
    alts = []
    for lg in ("fr", "en", "es"):
        if lg in a["all"]:
            alts.append('  <link rel="alternate" hreflang="%s" href="%s">' % (lg, art_url(lg, a["all"][lg])))
    alts.append('  <link rel="alternate" hreflang="x-default" href="%s">' % art_url("fr", a["all"]["fr"]))
    return """<!DOCTYPE html>
<html lang="{lang}">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
<title>{seo}</title>
<meta name="description" content="{desc}">
<link rel="canonical" href="{url}">
{alts}
<meta property="og:type" content="article">
<meta property="og:title" content="{seo}">
<meta property="og:description" content="{desc}">
<meta property="og:url" content="{url}">
<meta property="og:image" content="{img}">
<meta property="og:locale" content="{locale}">
<meta property="og:site_name" content="Riad Mylaya">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{seo}">
<meta name="twitter:description" content="{desc}">
<meta name="twitter:image" content="{img}">
<link rel="shortcut icon" href="/assets/images/logo-notxt.webp" type="image/webp">
<link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="/assets/bootstrap/css/bootstrap-grid.min.css">
<link rel="stylesheet" href="/assets/dropdown/css/style.css">
<link rel="preload" href="https://fonts.googleapis.com/css?family=Yanone+Kaffeesatz:300,400,500,600,700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Yanone+Kaffeesatz:300,400,500,600,700&display=swap"></noscript>
<link rel="preload" href="https://fonts.googleapis.com/css?family=Quicksand:300,400,500,600,700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Quicksand:300,400,500,600,700&display=swap"></noscript>
<link rel="stylesheet" href="/blog-assets/blog.css?v=20260927-1">
<script async src="https://www.googletagmanager.com/gtag/js?id={ga}"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){{dataLayer.push(arguments);}}gtag('js',new Date());gtag('config','{ga}');</script>
<script type="application/ld+json">{{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "mainEntityOfPage": {{"@type": "WebPage", "@id": "{url}"}},
  "headline": "{h1}",
  "description": "{desc}",
  "image": "{img}",
  "author": {{"@type": "Organization", "name": "Riad Mylaya"}},
  "publisher": {{
    "@type": "Organization",
    "name": "Riad Mylaya",
    "logo": {{"@type": "ImageObject", "url": "https://riadmylaya.com/assets/images/logo-notxt.png"}}
  }},
  "datePublished": "{date}",
  "dateModified": "{date}",
  "inLanguage": "{lang}",
  "articleSection": "{cat}"
}}</script>
{faq}
</head>
""".format(lang=lang, seo=html.escape(seo, quote=True), desc=desc, url=url, img=img,
           alts="\n".join(alts), locale=t["locale"], ga=GA,
           h1=html.escape(a["h1"], quote=True), date=a["date"],
           cat=CATS[a["cat"]][lang][1], faq=faq_jsonld(a))


FAQ_TITLE = {"fr": "Questions fréquentes", "en": "Frequently asked questions",
             "es": "Preguntas frecuentes"}


def faq_section(lang, a):
    """Visible FAQ, unless the body already carries one written by hand."""
    if not a.get("faq") or 'id="faq"' in a["body"]:
        return ""
    out = ['<h2 id="faq">%s</h2>' % FAQ_TITLE[lang]]
    for i, (q, ans) in enumerate(a["faq"], 1):
        out.append('<h3 id="faq-%d">%s</h3>' % (i, q))
        out.append("<p>%s</p>" % ans)
    return "\n" + "\n".join(out) + "\n"


def faq_jsonld(a):
    if not a.get("faq"):
        return ""
    items = []
    for q, ans in a["faq"]:
        items.append('    {"@type": "Question", "name": "%s", "acceptedAnswer": {"@type": "Answer", "text": "%s"}}'
                     % (html.escape(q, quote=True), html.escape(ans, quote=True)))
    return ('<script type="application/ld+json">{\n  "@context": "https://schema.org",\n'
            '  "@type": "FAQPage",\n  "mainEntity": [\n' + ",\n".join(items) + "\n  ]\n}</script>\n")


def header(lang, a=None):
    t = L[lang]
    nav = []
    for label, url in t["nav"]:
        cls = ' class="rm-nav-current"' if url == t["blog"] else ""
        nav.append('      <a href="%s%s"%s>%s</a>' % (SITE, url, cls, label))
    langs = []
    for code in ("fr", "en", "es"):
        if a and code in a.get("all", {}):
            href = art_url(code, a["all"][code])
        else:
            href = "%s%s" % (SITE, L[code]["blog"])
        cur = ' class="rm-lang-current" aria-current="true"' if code == lang else ""
        langs.append('        <a href="%s"%s>%s</a>' % (href, cur, code.upper()))
    return """<header class="rm-blog-header">
  <div class="rm-blog-header__inner">
    <a class="rm-blog-header__brand" href="{site}{home}" aria-label="Riad Mylaya">
      <img src="/assets/images/logo-notxt.webp" alt="Riad Mylaya" width="56" height="56" loading="eager">
      <span>Riad Mylaya</span>
    </a>
    <input type="checkbox" id="rm-nav-toggle" class="rm-nav-toggle">
    <label for="rm-nav-toggle" class="rm-nav-burger" aria-label="Menu">
      <span></span><span></span><span></span>
    </label>
    <nav class="rm-blog-nav" aria-label="Navigation principale">
{nav}
      <div class="rm-lang-switch">
{langs}
      </div>
      <a class="rm-blog-book-btn" href="{book}" target="_blank" rel="noopener">{blabel}</a>
    </nav>
  </div>
</header>
""".format(site=SITE, home=t["nav"][0][1], nav="\n".join(nav), langs="\n".join(langs),
           book=BOOK, blabel=t["book"])


def footer(lang):
    t = L[lang]
    cats = "".join('<li><a href="%s">%s</a></li>' % (cat_url(c, lang), CATS[c][lang][1])
                   for c in CAT_ORDER)
    links = "\n".join('        <li><a href="%s%s">%s</a></li>' % (SITE, u, lb)
                      for lb, u in t["footer_links"])
    return """<footer class="rm-blog-footer">
  <div class="rm-blog-footer__inner">
    <div class="rm-blog-footer__col">
      <img src="/assets/images/logo-white-wide.webp" alt="Riad Mylaya" width="180" height="45" loading="lazy">
      <p>{addr}<br>
         Tel: <a href="tel:+212808644081">+212 808 644 081</a><br>
         Mobile: <a href="tel:+212661351989">+212 661 351 989</a></p>
      <a class="rm-blog-footer__cta" href="{book}" target="_blank" rel="noopener">{blong}</a>
    </div>
    <div class="rm-blog-footer__col">
      <h4>Blog</h4>
      <ul>
        <li><a href="{site}{blog}">{fblog}</a></li>
        {cats}
      </ul>
    </div>
    <div class="rm-blog-footer__col">
      <h4>Riad Mylaya</h4>
      <ul>
{links}
      </ul>
    </div>
  </div>
  <div class="rm-blog-footer__bottom">
    <p>© 2026 Riad Mylaya — Maison d'hôtes de charme à Marrakech</p>
  </div>
</footer>
<script src="/promo-config.js" defer></script>
<script src="/common-ui.js" defer></script>
</body>
</html>
""".format(addr=t["footer_addr"], book=BOOK, blong=t["book_long"], site=SITE,
           blog=t["blog"], fblog=t["footer_blog"], cats=cats, links=links)


def cta_inline(lang):
    t = L[lang]
    return """
<aside class="rm-cta-inline">
  <h3>{h}</h3>
  <p>{p}</p>
  <div class="rm-cta-inline__btns">
    <a class="rm-btn-primary" href="{book}" target="_blank" rel="noopener">{blong}</a>
    <a class="rm-btn-secondary" href="{site}{plan}">{plabel}</a>
  </div>
</aside>
""".format(h=t["cta_h"], p=t["cta_p"], book=BOOK, blong=t["book_long"], site=SITE,
           plan=t["plan_url"], plabel=t["plan"])


def cta_footer(lang):
    t = L[lang]
    return """  <section class="rm-cta-footer">
  <h3>{h}</h3>
  <p>{p}</p>
  <div class="rm-cta-inline__btns" style="justify-content:center">
    <a class="rm-btn-primary" href="{book}" target="_blank" rel="noopener">{blong}</a>
    <a class="rm-btn-secondary" href="{site}{plan}">{plabel}</a>
  </div>
</section>
""".format(h=t["cta_foot_h"], p=t["cta_foot_p"], book=BOOK, blong=t["book_long"],
           site=SITE, plan=t["plan_url"], plabel=t["plan"])


def card(lang, meta):
    """Article card used in the grids (blog index, category index, related)."""
    url = "%s/blog/%s" % (L[lang]["prefix"], meta["slug"])
    return """<article class="rm-article-card">
  <a href="{url}" class="rm-article-card__img" aria-hidden="true" tabindex="-1">
    <img src="{img}" alt="" loading="lazy" width="600" height="375">
  </a>
  <div class="rm-article-card__body">
    <div class="rm-article-card__meta">{cat} · {read} {readlabel}</div>
    <h3><a href="{url}">{title}</a></h3>
    <p>{desc}</p>
    <a href="{url}" class="rm-article-card__more">{more}</a>
  </div>
</article>
""".format(url=url, img=meta["image"], cat=CATS[meta["cat"]][lang][1], read=meta["read"],
           readlabel=L[lang]["read"], title=meta["h1"],
           desc=html.escape(meta["description"], quote=True), more=L[lang]["more"])


def toc(lang, body):
    items = re.findall(r'<h2 id="([^"]+)">(.*?)</h2>', body, re.S)
    lis = "\n".join('<li><a href="#%s">%s</a></li>' % (i, re.sub(r"<[^>]+>", "", txt))
                    for i, txt in items)
    return """  <nav class="rm-article-toc" aria-label="{aria}">
  <div class="rm-article-toc__title">{title}</div>
  <ul>{lis}</ul>
</nav>
""".format(aria=L[lang]["toc_aria"], title=L[lang]["toc"], lis=lis)


def article_page(lang, a, related):
    t = L[lang]
    d = a["date"].split("-")
    pretty = "%s/%s/%s" % (d[2], d[1], d[0])
    body = a["body"].replace("[[CTA]]", cta_inline(lang))
    faq = faq_section(lang, a)
    rel = "\n".join(card(lang, r) for r in related)
    return (head(lang, a) + """<body class="rm-blog-body">
<script type="application/ld+json">{{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {{"@type":"ListItem","position":1,"name":"{home}","item":"{site}{homeurl}"}},
    {{"@type":"ListItem","position":2,"name":"Blog","item":"{site}{blog}"}},
    {{"@type":"ListItem","position":3,"name":"{cat}","item":"{caturl}"}},
    {{"@type":"ListItem","position":4,"name":"{h1}"}}
  ]
}}</script>
""".format(home=t["home_label"], site=SITE, homeurl=t["nav"][0][1], blog=t["blog"],
           cat=CATS[a["cat"]][lang][1], caturl=cat_url(a["cat"], lang),
           h1=html.escape(a["h1"], quote=True))
            + header(lang, a) + """
<main role="main">
<article>
<header class="rm-article-hero" style="background-image:url('{img}');">
  <div class="rm-article-hero__inner">
    <a href="{caturl}" class="rm-article-hero__cat">{cat}</a>
    <h1>{h1}</h1>
    <div class="rm-article-hero__meta">
      <span>📅 {pub} {date}</span>
      <span>⏱ {read} {readlabel}</span>
    </div>
  </div>
</header>
<div class="rm-article-container">
  <a class="rm-article-back" href="{caturl}">{back}</a>
{toc}
  <div class="rm-article-body">
    {body}
{credit}{faq}  </div>
{ctafoot}
</div>
</article>
<section class="rm-related">
  <div class="rm-related__inner">
    <h2>{relh}</h2>
    <div class="rm-article-grid">{rel}</div>
  </div>
</section>

</main>
""".format(img=a["image"], caturl=cat_url(a["cat"], lang), cat=CATS[a["cat"]][lang][1],
           h1=a["h1"], pub=t["published"], date=pretty, read=a["read"],
           readlabel=t["read"], back=t["back_cat"], toc=toc(lang, body + faq),
           body=body.strip(), credit=photo_credit(lang, a), faq=faq,
           ctafoot=cta_footer(lang),
           relh=t["related"], rel=rel)
            + footer(lang))


PHOTO_LABEL = {"fr": "Photo :", "en": "Photo:", "es": "Foto:"}


def photo_credit(lang, a):
    """Attribution line for articles illustrated with a third-party photo."""
    c = a.get("credit")
    if not c:
        return ""
    return ('    <p class="rm-article-credit">' + PHOTO_LABEL[lang] + ' <a href="%s" rel="nofollow noopener" '
            'target="_blank">%s</a> — %s, <a href="%s" rel="license nofollow noopener" '
            'target="_blank">%s</a></p>\n'
            % (c["page"], html.escape(c["title"]), html.escape(c["author"]),
               c["license_url"], html.escape(c["license"][lang])))


# ---------------------------------------------------------------- index update

def insert_cards(path, cards, count_label=None):
    """Insert article cards at the top of the first rm-article-grid of a page."""
    with open(path, encoding="utf-8") as fh:
        doc = fh.read()
    marker = '<div class="rm-article-grid">'
    i = doc.index(marker) + len(marker)
    new = ""
    for c in cards:
        slug_href = re.search(r'href="([^"]+)"', c).group(1)
        existing = re.search(
            r'<article class="rm-article-card">(?:(?!</article>).)*?href="%s".*?</article>'
            % re.escape(slug_href), doc, flags=re.S)
        if existing:
            doc = doc[:existing.start()] + c.strip() + doc[existing.end():]
            i = doc.index(marker) + len(marker)
            continue  # refresh in place
        new += c + "\n"
    doc = doc[:i] + new + doc[i:]
    if '<article class="rm-article-card">' in doc:
        doc = re.sub(r'\n?<div class="rm-empty">.*?</div>', "", doc, flags=re.S)
    if count_label:
        doc = re.sub(r'<p class="rm-subhead">[^<]*</p>',
                     '<p class="rm-subhead">%s</p>' % count_label, doc, count=1)
    with open(path, "w", encoding="utf-8") as fh:
        fh.write(doc)
    return bool(new)


def count_cards(path):
    with open(path, encoding="utf-8") as fh:
        return fh.read().count('<article class="rm-article-card">')


def main():
    written = []
    for lang in ("fr", "en", "es"):
        metas = []
        for key, per_lang in ARTICLES.items():
            a = dict(per_lang[lang])
            a["all"] = {lg: per_lang[lg]["slug"] for lg in per_lang}
            a["key"] = key
            metas.append(a)
        for i, a in enumerate(metas):
            others = metas[i + 1:] + metas[:i]
            related = others[:2]
            out = os.path.join(ROOT, art_path(lang, a["slug"]) + ".html")
            os.makedirs(os.path.dirname(out), exist_ok=True)
            with open(out, "w", encoding="utf-8") as fh:
                fh.write(article_page(lang, a, related))
            written.append(out)

        # blog index (newest first)
        blog_index = os.path.join(ROOT, L[lang]["blog"].strip("/"), "index.html")
        insert_cards(blog_index, [card(lang, m) for m in reversed(metas)])
        # category indexes
        for a in metas:
            cpath = os.path.join(ROOT, L[lang]["blog"].strip("/"),
                                 CATS[a["cat"]][lang][0], "index.html")
            insert_cards(cpath, [card(lang, a)])
            n = count_cards(cpath)
            insert_cards(cpath, [], L[lang]["articles_count"](n))
    for w in written:
        print("wrote", os.path.relpath(w, ROOT))


if __name__ == "__main__":
    main()
