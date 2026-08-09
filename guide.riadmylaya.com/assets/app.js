const CONTENT_URL = "/content/guide.json";
const FORM_ENDPOINT = "https://formsubmit.co/contact@riadmylaya.com";
const FORM_CC = "riadmylaya@gmail.com";
const LANGUAGE_STORAGE_KEY = "riad-mylaya-guide-language";
const DEFAULT_LANGUAGE = "fr";

const state = {
  content: null,
  language: DEFAULT_LANGUAGE,
};

const fields = document.querySelectorAll("[data-field]");
const quickNav = document.querySelector("#quick-nav-items");
const sectionsContainer = document.querySelector("#sections");
const languageButtons = document.querySelectorAll("[data-lang]");

init();

async function init() {
  const response = await fetch(CONTENT_URL, { cache: "no-cache" });
  state.content = await response.json();
  state.language = getInitialLanguage(state.content.languages);
  render();
  bindLanguageSwitcher();
}

function getInitialLanguage(languages) {
  const savedLanguage = localStorage.getItem(LANGUAGE_STORAGE_KEY);
  if (savedLanguage && languages[savedLanguage]) {
    return savedLanguage;
  }

  const browserLanguage = navigator.language.slice(0, 2).toLowerCase();
  if (languages[browserLanguage]) {
    return browserLanguage;
  }

  return DEFAULT_LANGUAGE;
}

function bindLanguageSwitcher() {
  languageButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const language = button.dataset.lang;
      if (!language || !state.content.languages[language]) {
        return;
      }
      state.language = language;
      localStorage.setItem(LANGUAGE_STORAGE_KEY, language);
      render();
    });
  });
}

function render() {
  const copy = state.content.languages[state.language];
  document.documentElement.lang = state.language;
  document.title = copy.meta.title;

  fields.forEach((element) => {
    const value = readPath(copy, element.dataset.field);
    if (typeof value === "string") {
      element.textContent = value;
    }
  });

  languageButtons.forEach((button) => {
    const isActive = button.dataset.lang === state.language;
    button.setAttribute("aria-pressed", String(isActive));
  });

  quickNav.replaceChildren(...copy.sections.map(createNavLink));
  sectionsContainer.replaceChildren(...copy.sections.map((section) => createSection(section, copy.ui)));

  setupScrollSpy();
}

let scrollSpyObserver = null;

function setupScrollSpy() {
  if (scrollSpyObserver) {
    scrollSpyObserver.disconnect();
  }

  const links = new Map();
  quickNav.querySelectorAll("a[data-target]").forEach((link) => {
    links.set(link.dataset.target, link);
  });

  const scrollContainer = quickNav.querySelector("div") || quickNav;
  const setActive = (id) => {
    links.forEach((link, target) => {
      const isActive = target === id;
      link.classList.toggle("active", isActive);
      if (isActive) {
        const offset =
          link.offsetLeft - scrollContainer.clientWidth / 2 + link.offsetWidth / 2;
        scrollContainer.scrollTo({ left: offset, behavior: "smooth" });
      }
    });
  };

  const sections = Array.from(sectionsContainer.querySelectorAll("[id]"));
  if (!sections.length) {
    return;
  }

  scrollSpyObserver = new IntersectionObserver(
    (entries) => {
      const visible = entries
        .filter((entry) => entry.isIntersecting)
        .sort((a, b) => b.intersectionRatio - a.intersectionRatio);
      if (visible.length) {
        setActive(visible[0].target.id);
      }
    },
    { rootMargin: "-45% 0px -45% 0px", threshold: [0, 0.25, 0.5, 1] }
  );

  sections.forEach((section) => scrollSpyObserver.observe(section));
  setActive(sections[0].id);
}

function readPath(object, path) {
  return path.split(".").reduce((value, key) => value?.[key], object);
}

function createNavLink(section) {
  const link = document.createElement("a");
  link.href = `#${section.id}`;
  link.dataset.target = section.id;
  link.textContent = section.shortTitle || section.title;
  return link;
}

function createSection(section, ui) {
  if (section.type === "intro") {
    const article = document.createElement("article");
    article.className = "section full intro-section";
    article.id = section.id;
    article.append(createIntroPanel(section));
    return article;
  }

  const article = document.createElement("article");
  article.className = `section ${section.fullWidth ? "full" : ""}`;
  article.id = section.id;

  const heading = document.createElement("div");
  heading.className = "section-heading";
  heading.append(createText("p", section.eyebrow, "eyebrow"), createText("h2", section.title));
  if (section.lead) {
    heading.append(createText("p", section.lead, "lead"));
  }
  if (section.image) {
    const img = document.createElement("img");
    img.className = "section-image";
    img.src = section.image;
    img.alt = section.imageAlt || section.title;
    img.loading = "lazy";
    heading.append(img);
  }

  const body = document.createElement("div");
  body.className = "section-body";

  if (section.type === "wifi") {
    body.append(createWifiPanel(section, ui));
  } else if (section.type === "times") {
    body.append(createTimes(section));
    if (section.transferForm) {
      body.append(createTransferPanel(section.transferForm));
    }
    if (section.items?.length) {
      body.append(createCards(section.items));
    }
  } else if (section.type === "dinner") {
    body.append(createDinnerPanel(section));
  } else if (section.type === "rules") {
    body.append(createRulesCards(section.items));
  } else if (section.type === "map") {
    body.append(createMapPanel(section));
  } else if (section.type === "reviews") {
    body.append(createReviewPanel(section));
  } else {
    if (section.body) {
      section.body.forEach((paragraph) => body.append(createText("p", paragraph, "lead")));
    }
    if (section.items?.length) {
      body.append(createCards(section.items));
    }
    if (section.list?.length) {
      body.append(createList(section.list));
    }
  }

  article.append(heading, body);
  return article;
}

function createIntroPanel(section) {
  const panel = document.createElement("div");
  panel.className = "intro-panel";

  const welcome = document.createElement("div");
  welcome.className = "intro-welcome";
  welcome.append(createText("p", section.eyebrow, "eyebrow"), createText("h2", section.title));
  if (section.lead) {
    welcome.append(createText("p", section.lead, "intro-welcome-lead"));
  }
  if (section.body) {
    section.body.forEach((paragraph) => welcome.append(createText("p", paragraph, "lead")));
  }
  panel.append(welcome);

  (section.blocks || []).forEach((block) => {
    const blockEl = document.createElement("div");
    blockEl.className = "intro-block";

    const media = document.createElement("div");
    media.className = "intro-block-media";
    if (block.image) {
      const img = document.createElement("img");
      img.src = block.image;
      img.alt = block.imageAlt || block.title;
      img.loading = "lazy";
      media.append(img);
    }

    const content = document.createElement("div");
    content.className = "intro-block-content";
    if (block.eyebrow) {
      content.append(createText("p", block.eyebrow, "eyebrow"));
    }
    content.append(createText("h3", block.title));
    if (block.lead) {
      content.append(createText("p", block.lead, "intro-block-lead"));
    }
    if (block.text) {
      content.append(createText("p", block.text));
    }
    if (block.features?.list?.length) {
      if (block.features.title) {
        content.append(createText("h4", block.features.title, "intro-features-title"));
      }
      content.append(createList(block.features.list));
    }

    blockEl.append(media, content);
    panel.append(blockEl);
  });

  return panel;
}

function createText(tagName, text, className = "") {
  const element = document.createElement(tagName);
  element.textContent = text;
  if (className) {
    element.className = className;
  }
  return element;
}

function createCards(items = []) {
  const grid = document.createElement("div");
  grid.className = "card-grid";
  items.forEach((item) => {
    const card = document.createElement("section");
    card.className = "card";
    if (item.eyebrow) {
      card.append(createText("p", item.eyebrow, "eyebrow"));
    }
    card.append(createText("h3", item.title));
    if (item.text) {
      const p = document.createElement("p");
      if (item.text.includes("<")) {
        p.innerHTML = item.text;
      } else {
        p.textContent = item.text;
      }
      card.append(p);
    }
    if (item.list?.length) {
      card.append(createList(item.list));
    }
    if (item.actions?.length) {
      card.append(createActions(item.actions));
    }
    grid.append(card);
  });
  return grid;
}

function createList(items) {
  const list = document.createElement("ul");
  list.className = "list";
  items.forEach((item) => list.append(createText("li", item)));
  return list;
}

function createActions(actions) {
  const wrapper = document.createElement("div");
  wrapper.className = "card-actions";
  actions.forEach((action) => {
    const link = document.createElement("a");
    link.className = "button primary";
    link.href = action.href;
    link.textContent = action.label;
    if (action.external) {
      link.target = "_blank";
      link.rel = "noopener noreferrer";
    }
    wrapper.append(link);
  });
  return wrapper;
}

function createTimes(section) {
  const grid = document.createElement("div");
  grid.className = "time-grid";
  section.times.forEach((time) => {
    const card = document.createElement("section");
    card.className = "card time-card";
    card.append(createText("span", time.label), createText("strong", time.value), createText("p", time.detail));
    grid.append(card);
  });
  return grid;
}

function createWifiPanel(section, ui) {
  const panel = document.createElement("div");
  panel.className = "wifi-panel";

  const code = document.createElement("div");
  code.className = "wifi-code";
  code.append(createText("span", section.networkLabel), createText("strong", section.network));
  const password = createText("code", section.password);
  code.append(createText("span", section.passwordLabel), password);

  const button = document.createElement("button");
  button.className = "button primary";
  button.type = "button";
  button.textContent = ui.copyPassword;

  const feedback = createText("p", "", "copy-feedback");
  button.addEventListener("click", async () => {
    try {
      await navigator.clipboard.writeText(section.password);
      feedback.textContent = ui.passwordCopied;
    } catch {
      feedback.textContent = section.password;
    }
  });

  code.append(feedback);
  panel.append(code, button);
  return panel;
}

function createMapPanel(section) {
  const panel = document.createElement("div");
  panel.className = "map-panel";
  panel.append(createText("p", section.address, "lead"));

  const placeholder = document.createElement("div");
  placeholder.className = "map-placeholder";
  placeholder.textContent = section.mapText;

  const actions = createActions(section.actions);
  panel.append(placeholder, actions);
  return panel;
}

function createTransferPanel(tf) {
  const panel = document.createElement("div");
  panel.className = "transfer-panel";

  const card = document.createElement("div");
  card.className = "transfer-card";

  const header = document.createElement("div");
  header.className = "transfer-card-header";
  header.innerHTML = `<div class="transfer-card-header-text"><strong>${tf.bookButton}</strong><span>${tf.subtitle}</span></div><span class="transfer-card-icon">✈</span>`;
  card.append(header);

  const featureList = document.createElement("div");
  featureList.className = "transfer-features";
  tf.features.forEach((f) => {
    const row = document.createElement("div");
    row.className = "transfer-feature-row";
    row.innerHTML = `<span class="transfer-feature-icon">${f.icon}</span><div class="transfer-feature-text"><strong>${f.label}</strong><span>${f.detail}</span></div><span class="transfer-feature-arrow">›</span>`;
    featureList.append(row);
  });
  card.append(featureList);

  const ctaBtn = document.createElement("button");
  ctaBtn.className = "transfer-cta";
  ctaBtn.type = "button";
  ctaBtn.innerHTML = `📅 ${tf.cta}`;
  card.append(ctaBtn);

  const badge = document.createElement("div");
  badge.className = "transfer-badge";
  badge.innerHTML = `✅ ${tf.badge}`;
  card.append(badge);

  panel.append(card);

  const modal = document.createElement("div");
  modal.className = "dinner-modal";
  modal.setAttribute("aria-hidden", "true");

  const overlay = document.createElement("div");
  overlay.className = "dinner-modal-overlay";

  const dialog = document.createElement("div");
  dialog.className = "dinner-modal-dialog";
  dialog.setAttribute("role", "dialog");

  const closeBtn = document.createElement("button");
  closeBtn.className = "dinner-modal-close";
  closeBtn.type = "button";
  closeBtn.textContent = "\u00d7";
  closeBtn.setAttribute("aria-label", tf.close);

  const title = createText("h3", tf.title);
  const form = document.createElement("form");
  form.action = FORM_ENDPOINT;
  form.method = "POST";

  const hiddenSubject = document.createElement("input");
  hiddenSubject.type = "hidden";
  hiddenSubject.name = "_subject";
  hiddenSubject.value = "Nouvelle demande de transfert - Riad Mylaya";
  form.append(hiddenSubject);

  const hiddenNext = document.createElement("input");
  hiddenNext.type = "hidden";
  hiddenNext.name = "_next";
  hiddenNext.value = window.location.href;
  form.append(hiddenNext);

  const hiddenCaptcha = document.createElement("input");
  hiddenCaptcha.type = "hidden";
  hiddenCaptcha.name = "_captcha";
  hiddenCaptcha.value = "false";
  const hiddenCc = document.createElement("input");
  hiddenCc.type = "hidden";
  hiddenCc.name = "_cc";
  hiddenCc.value = FORM_CC;
  form.append(hiddenCc);
  form.append(hiddenCaptcha);

  const fields = tf.fields;

  function addField(name, label, type, required, parent, attrs) {
    const group = document.createElement("div");
    group.className = "form-group";
    const lbl = document.createElement("label");
    lbl.textContent = label;
    lbl.setAttribute("for", "transfer-" + name);
    group.append(lbl);
    const input = document.createElement("input");
    input.type = type;
    input.name = name;
    input.id = "transfer-" + name;
    input.required = required;
    if (attrs) {
      Object.keys(attrs).forEach((key) => input.setAttribute(key, attrs[key]));
    }
    group.append(input);
    (parent || form).append(group);
    return input;
  }

  addField("name", fields.name, "text", true);
  addField("email", fields.email, "email", true);
  addField("phone", fields.phone, "tel", true);
  addField("guests", fields.guests, "number", true, form, { min: "1", max: "6" });

  if (tf.transferTypeLabel && tf.transferTypeOptions) {
    const typeGroup = document.createElement("div");
    typeGroup.className = "form-group";
    const typeLbl = document.createElement("label");
    typeLbl.textContent = tf.transferTypeLabel;
    typeLbl.setAttribute("for", "transfer-transferType");
    typeGroup.append(typeLbl);
    const typeSelect = document.createElement("select");
    typeSelect.name = "transferType";
    typeSelect.id = "transfer-transferType";
    typeSelect.required = true;
    [
      ["both", tf.transferTypeOptions.both],
      ["arrival", tf.transferTypeOptions.arrival],
      ["departure", tf.transferTypeOptions.departure],
    ].forEach(([value, label]) => {
      const opt = document.createElement("option");
      opt.value = value;
      opt.textContent = label;
      typeSelect.append(opt);
    });
    typeGroup.append(typeSelect);
    form.append(typeGroup);

    var transferTypeSelect = typeSelect;
  }

  const arrivalBlock = document.createElement("div");
  const arrHeading = document.createElement("h4");
  arrHeading.className = "form-section-heading";
  arrHeading.textContent = tf.arrivalHeading;
  arrivalBlock.append(arrHeading);
  const arrivalInputs = [
    addField("arrivalDate", fields.arrivalDate, "date", true, arrivalBlock),
    addField("arrivalTime", fields.arrivalTime, "time", true, arrivalBlock),
    addField("arrivalFlight", fields.arrivalFlight, "text", true, arrivalBlock),
  ];
  form.append(arrivalBlock);

  const departureBlock = document.createElement("div");
  const depHeading = document.createElement("h4");
  depHeading.className = "form-section-heading";
  depHeading.textContent = tf.departureHeading;
  departureBlock.append(depHeading);
  const departureInputs = [
    addField("departureDate", fields.departureDate, "date", true, departureBlock),
    addField("departureTime", fields.departureTime, "time", true, departureBlock),
    addField("departureFlight", fields.departureFlight, "text", false, departureBlock),
  ];
  form.append(departureBlock);

  if (typeof transferTypeSelect !== "undefined") {
    const applyTransferType = () => {
      const value = transferTypeSelect.value;
      const showArrival = value === "both" || value === "arrival";
      const showDeparture = value === "both" || value === "departure";
      arrivalBlock.style.display = showArrival ? "" : "none";
      departureBlock.style.display = showDeparture ? "" : "none";
      arrivalInputs.forEach((input) => {
        input.disabled = !showArrival;
        input.required = showArrival && input.name !== "arrivalFlight";
      });
      departureInputs.forEach((input) => {
        input.disabled = !showDeparture;
        input.required = showDeparture && input.name !== "departureFlight";
      });
    };
    transferTypeSelect.addEventListener("change", applyTransferType);
    applyTransferType();
  }

  const submitBtn = document.createElement("button");
  submitBtn.className = "button primary";
  submitBtn.type = "submit";
  submitBtn.textContent = tf.submit;
  form.append(submitBtn);

  dialog.append(closeBtn, title, form);
  modal.append(overlay, dialog);
  document.body.append(modal);

  ctaBtn.addEventListener("click", () => {
    modal.classList.add("active");
    modal.setAttribute("aria-hidden", "false");
  });
  function closeModal() {
    modal.classList.remove("active");
    modal.setAttribute("aria-hidden", "true");
  }
  closeBtn.addEventListener("click", closeModal);
  overlay.addEventListener("click", closeModal);

  return panel;
}

function createDinnerPanel(section) {
  const panel = document.createElement("div");
  panel.className = "dinner-panel";

  const card = document.createElement("div");
  card.className = "dinner-premium-card";

  const header = document.createElement("div");
  header.className = "dinner-card-header";
  header.innerHTML = `<span class="dinner-card-header-icon">🍽️</span><div class="dinner-card-header-text"><strong>${section.dinnerHeader.title}</strong><span>${section.dinnerHeader.subtitle}</span></div>`;
  card.append(header);

  const menuGrid = document.createElement("div");
  menuGrid.className = "dinner-menu-grid";
  section.menus.forEach((menu) => {
    const menuCard = document.createElement("div");
    menuCard.className = "dinner-menu-card";
    const perPerson = section.perPerson ? `<span class="dinner-menu-perperson">${section.perPerson}</span>` : "";
    menuCard.innerHTML = `<span class="dinner-menu-icon">${menu.icon}</span><strong>${menu.label}</strong><span class="dinner-menu-detail">${menu.detail || ""}</span><span class="dinner-menu-price">${menu.price}${perPerson}</span>`;
    menuGrid.append(menuCard);
  });
  card.append(menuGrid);

  const ctaBtn = document.createElement("button");
  ctaBtn.className = "dinner-cta";
  ctaBtn.type = "button";
  ctaBtn.innerHTML = `🍽️ ${section.bookButton}`;
  card.append(ctaBtn);

  if (section.badges) {
    const badgeRow = document.createElement("div");
    badgeRow.className = "dinner-badges";
    section.badges.forEach((b) => {
      const badge = document.createElement("div");
      badge.className = "dinner-badge-item";
      badge.innerHTML = `<span>${b.icon}</span><span>${b.text}</span>`;
      badgeRow.append(badge);
    });
    card.append(badgeRow);
  }

  panel.append(card);

  const modal = document.createElement("div");
  modal.className = "dinner-modal";
  modal.setAttribute("aria-hidden", "true");

  const overlay = document.createElement("div");
  overlay.className = "dinner-modal-overlay";

  const dialog = document.createElement("div");
  dialog.className = "dinner-modal-dialog";
  dialog.setAttribute("role", "dialog");

  const closeBtn = document.createElement("button");
  closeBtn.className = "dinner-modal-close";
  closeBtn.type = "button";
  closeBtn.textContent = "\u00d7";
  closeBtn.setAttribute("aria-label", section.form.close);

  const title = createText("h3", section.form.title);
  const form = document.createElement("form");
  form.action = FORM_ENDPOINT;
  form.method = "POST";

  const hiddenSubject = document.createElement("input");
  hiddenSubject.type = "hidden";
  hiddenSubject.name = "_subject";
  hiddenSubject.value = "Nouvelle réservation - Riad Mylaya";
  form.append(hiddenSubject);

  const hiddenNext = document.createElement("input");
  hiddenNext.type = "hidden";
  hiddenNext.name = "_next";
  hiddenNext.value = window.location.href;
  form.append(hiddenNext);

  const hiddenCaptcha = document.createElement("input");
  hiddenCaptcha.type = "hidden";
  hiddenCaptcha.name = "_captcha";
  hiddenCaptcha.value = "false";
  const hiddenCc = document.createElement("input");
  hiddenCc.type = "hidden";
  hiddenCc.name = "_cc";
  hiddenCc.value = FORM_CC;
  form.append(hiddenCc);
  form.append(hiddenCaptcha);

  const fields = section.form.fields;
  const formCfg = section.form;
  const currency = formCfg.currency || "€";
  const perPersonLabel = formCfg.perPerson || "";
  function formatPrice(amount) {
    return formCfg.currencyBefore ? `${currency}${amount}` : `${amount} ${currency}`;
  }

  function addField(name, label, type, required) {
    const group = document.createElement("div");
    group.className = "form-group";
    const lbl = document.createElement("label");
    lbl.textContent = label;
    lbl.setAttribute("for", "dinner-" + name);
    group.append(lbl);
    if (name === "menu") {
      const select = document.createElement("select");
      select.name = name;
      select.id = "dinner-" + name;
      select.required = true;
      const placeholder = document.createElement("option");
      placeholder.value = "";
      placeholder.textContent = "—";
      select.append(placeholder);
      section.form.menuOptions.forEach((opt) => {
        const option = document.createElement("option");
        const priceText = `${formatPrice(opt.price)}${perPersonLabel ? " " + perPersonLabel : ""}`;
        option.value = `${opt.label} (${priceText})`;
        option.textContent = `${opt.label} — ${priceText}`;
        option.dataset.price = String(opt.price);
        select.append(option);
      });
      group.append(select);
    } else if (name === "message") {
      const textarea = document.createElement("textarea");
      textarea.name = name;
      textarea.id = "dinner-" + name;
      textarea.rows = 3;
      group.append(textarea);
    } else {
      const input = document.createElement("input");
      input.type = type;
      input.name = name;
      input.id = "dinner-" + name;
      input.required = required;
      if (type === "number") {
        input.min = "1";
        input.value = "1";
      }
      group.append(input);
    }
    form.append(group);
  }

  addField("name", fields.name, "text", true);
  addField("email", fields.email, "email", true);
  addField("date", fields.date, "date", true);
  addField("time", fields.time, "time", true);
  addField("guests", fields.guests, "number", true);
  addField("menu", fields.menu, "select", true);
  addField("message", fields.message, "textarea", false);

  const summaryCfg = formCfg.summary;
  const summaryBox = document.createElement("div");
  summaryBox.className = "dinner-summary";
  const summaryPlaceholder = summaryCfg ? summaryCfg.placeholder : "";
  summaryBox.innerHTML = `<p class="dinner-summary-empty">${summaryPlaceholder}</p>`;
  form.append(summaryBox);

  const hiddenTotal = document.createElement("input");
  hiddenTotal.type = "hidden";
  hiddenTotal.name = "Total estimé";
  form.append(hiddenTotal);

  const guestsInput = form.querySelector("#dinner-guests");
  const menuSelect = form.querySelector("#dinner-menu");

  function updateSummary() {
    const selected = menuSelect.options[menuSelect.selectedIndex];
    const unit = selected && selected.dataset.price ? Number(selected.dataset.price) : NaN;
    const guests = parseInt(guestsInput.value, 10);
    if (!summaryCfg || Number.isNaN(unit) || Number.isNaN(guests) || guests < 1) {
      summaryBox.innerHTML = `<p class="dinner-summary-empty">${summaryPlaceholder}</p>`;
      hiddenTotal.value = "";
      return;
    }
    const total = unit * guests;
    const unitText = `${formatPrice(unit)}${perPersonLabel ? " " + perPersonLabel : ""}`;
    summaryBox.innerHTML =
      `<div class="dinner-summary-row"><span>${summaryCfg.guestsLabel}</span><span>${guests}</span></div>` +
      `<div class="dinner-summary-row"><span>${summaryCfg.priceLabel}</span><span>${unitText}</span></div>` +
      `<div class="dinner-summary-row dinner-summary-total"><span>${summaryCfg.totalLabel}</span><span>${formatPrice(total)}</span></div>`;
    hiddenTotal.value = `${formatPrice(total)} (${guests} × ${formatPrice(unit)})`;
  }

  guestsInput.addEventListener("input", updateSummary);
  menuSelect.addEventListener("change", updateSummary);

  const submitBtn = document.createElement("button");
  submitBtn.className = "button primary";
  submitBtn.type = "submit";
  submitBtn.textContent = section.form.submit;
  form.append(submitBtn);

  dialog.append(closeBtn, title, form);
  modal.append(overlay, dialog);
  document.body.append(modal);

  ctaBtn.addEventListener("click", () => {
    modal.classList.add("active");
    modal.setAttribute("aria-hidden", "false");
  });
  function closeModal() {
    modal.classList.remove("active");
    modal.setAttribute("aria-hidden", "true");
  }
  closeBtn.addEventListener("click", closeModal);
  overlay.addEventListener("click", closeModal);

  return panel;
}

function createRulesCards(items) {
  const grid = document.createElement("div");
  grid.className = "rules-grid";
  items.forEach((item) => {
    const card = document.createElement("section");
    card.className = "rules-card rules-" + (item.color || "gray");
    card.innerHTML = `<div class="rules-icon-circle">${item.icon || ""}</div><h3>${item.title}</h3><p>${item.text}</p>`;
    grid.append(card);
  });
  return grid;
}

function createReviewPanel(section) {
  const panel = document.createElement("div");
  panel.className = "review-panel";
  panel.append(createText("h3", section.heading), createText("p", section.text), createActions(section.actions));
  return panel;
}
