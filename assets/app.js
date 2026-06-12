const CONTENT_URL = "/content/guide.json";
const LANGUAGE_STORAGE_KEY = "riad-bilkis-guide-language";
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
}

function readPath(object, path) {
  return path.split(".").reduce((value, key) => value?.[key], object);
}

function createNavLink(section) {
  const link = document.createElement("a");
  link.href = `#${section.id}`;
  link.textContent = section.shortTitle || section.title;
  return link;
}

function createSection(section, ui) {
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
    body.append(createTimes(section), createCards(section.items));
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
      card.append(createText("p", item.text));
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

function createReviewPanel(section) {
  const panel = document.createElement("div");
  panel.className = "review-panel";
  panel.append(createText("h3", section.heading), createText("p", section.text), createActions(section.actions));
  return panel;
}
