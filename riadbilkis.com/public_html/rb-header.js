/* Barre de navigation principale des pages statiques :
   ouverture du menu mobile et des sous-menus (survol sur ordinateur,
   clic sur mobile), comme sur les pages WordPress. */
(function () {
  var bar = document.querySelector('.rb-mainbar');
  if (!bar) return;
  var burger = bar.querySelector('.rb-mainbar__burger');

  function isMobile() { return window.matchMedia('(max-width:921px)').matches; }

  function toggleItem(li) {
    var open = li.classList.toggle('rb-open');
    var btn = li.querySelector('.rb-mainbar__toggle');
    if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  if (burger) {
    burger.addEventListener('click', function () {
      var open = bar.classList.toggle('rb-nav-open');
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  bar.addEventListener('click', function (e) {
    if (!e.target.closest) return;
    var btn = e.target.closest('.rb-mainbar__toggle');
    if (btn) {
      e.preventDefault();
      toggleItem(btn.parentNode);
      return;
    }
    // Services et Activités ouvrent leur sous-menu au lieu de naviguer.
    var link = e.target.closest('.rb-has-children > a');
    if (!link) return;
    var li = link.parentNode;
    if (!isMobile() && !li.classList.contains('rb-nolink')) return;
    e.preventDefault();
    toggleItem(li);
  });

  document.addEventListener('click', function (e) {
    if (!bar.contains(e.target)) bar.classList.remove('rb-nav-open');
  });
})();
