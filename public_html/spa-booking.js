/* Mythic Oriental Spa — Dynamic booking form (Riad Mylaya)
 * Renders services grid + per-person dropdowns + live price calculator.
 * Locale-aware via window.RM_SPA_LOCALE ('fr' | 'en' | 'es'). Defaults to 'fr'.
 *
 * The treatment catalogue lives in rm-services-data.js (window.RM_SERVICES.spa),
 * shared with the client-space booking engine. This file loads it on demand so
 * pages that predate the shared source keep working unchanged.
 */
(function(){
  'use strict';

  var LOCALE = (window.RM_SPA_LOCALE || 'fr').toLowerCase();
  var DATA_SRC = '/rm-services-data.js';

  var I18N = {
    fr: {
      person: 'Personne',
      chooseTreatment: 'Choisir un soin',
      noTreatment: '— Aucun soin —',
      extrasTitle: 'Extras (optionnel)',
      personName: 'Prénom (facultatif)',
      treatment: 'Soin principal',
      empty: 'Sélectionnez un soin pour voir le récapitulatif.',
      perPerson: '/ pers',
      mad: 'MAD',
      duration: 'Durée',
      oil: 'Huile',
      slot: function(t){return t;}
    },
    en: {
      person: 'Person',
      chooseTreatment: 'Choose a treatment',
      noTreatment: '— No treatment —',
      extrasTitle: 'Extras (optional)',
      personName: 'First name (optional)',
      treatment: 'Main treatment',
      empty: 'Select a treatment to see the summary.',
      perPerson: '/ pax',
      mad: 'MAD',
      duration: 'Duration',
      oil: 'Oil',
      slot: function(t){return t.replace('h',':');}
    },
    es: {
      person: 'Persona',
      chooseTreatment: 'Elegir un tratamiento',
      noTreatment: '— Sin tratamiento —',
      extrasTitle: 'Extras (opcional)',
      personName: 'Nombre (opcional)',
      treatment: 'Tratamiento principal',
      empty: 'Seleccione un tratamiento para ver el resumen.',
      perPerson: '/ pers',
      mad: 'MAD',
      duration: 'Duración',
      oil: 'Aceite',
      slot: function(t){return t.replace('h',':');}
    }
  };
  var T = I18N[LOCALE] || I18N.fr;

  var DATA, MAIN_CATS, EXTRAS, ALL_ITEMS;

  function readCatalogue(){
    DATA = window.RM_SERVICES && window.RM_SERVICES.spa;
    if(!DATA) return false;
    MAIN_CATS = DATA.categories.filter(function(c){return !c.extras;});
    EXTRAS = (DATA.categories.filter(function(c){return c.extras;})[0] || {items:[]}).items;
    ALL_ITEMS = {};
    DATA.categories.forEach(function(cat){
      cat.items.forEach(function(it){
        ALL_ITEMS[it.code] = Object.assign({_category: cat.id}, it);
      });
    });
    return true;
  }

  function catName(cat){
    return (cat.name && (cat.name[LOCALE] || cat.name.fr)) || cat.id;
  }

  // ---------- Helpers ----------
  function e(tag, attrs, children){
    var el = document.createElement(tag);
    if(attrs){Object.keys(attrs).forEach(function(k){
      if(k === 'class') el.className = attrs[k];
      else if(k === 'html') el.innerHTML = attrs[k];
      else if(k.indexOf('on') === 0) el.addEventListener(k.slice(2), attrs[k]);
      else el.setAttribute(k, attrs[k]);
    });}
    if(children){(Array.isArray(children) ? children : [children]).forEach(function(c){
      if(c == null || c === false) return;
      el.appendChild(typeof c === 'string' ? document.createTextNode(c) : c);
    });}
    return el;
  }
  function fmtPrice(p){return p.toLocaleString('fr-FR') + ' ' + T.mad;}

  // ---------- Render services grid (display only, read-only) ----------
  function renderServicesGrid(){
    var host = document.getElementById('rm-spa-services-grid');
    if(!host) return;
    host.innerHTML = '';
    DATA.categories.forEach(function(cat){
      var catEl = e('div', {class:'rm-spa-cat'});
      catEl.appendChild(e('h3', {class:'rm-spa-cat__title'}, catName(cat)));
      var grid = e('div', {class:'rm-spa-cat__grid'});
      cat.items.forEach(function(it){
        var item = e('div', {class:'rm-spa-item'});
        var head = e('div', {class:'rm-spa-item__head'}, [
          e('span', {class:'rm-spa-item__name'}, it.name),
          e('span', {class:'rm-spa-item__price'}, fmtPrice(it.price_mad))
        ]);
        var meta = e('div', {class:'rm-spa-item__meta'}, [
          e('span', null, '⏱ ' + it.duration),
          it.oil ? e('span', null, '🌿 ' + it.oil) : null,
          e('span', null, '#' + it.code)
        ]);
        item.appendChild(head);
        item.appendChild(meta);
        if(it.description) item.appendChild(e('div', {class:'rm-spa-item__desc'}, it.description));
        grid.appendChild(item);
      });
      catEl.appendChild(grid);
      host.appendChild(catEl);
    });
  }

  // ---------- Time slots (from the shared opening hours) ----------
  function slotList(){
    var parts = String(DATA.opening || '10:30 - 18:30').split('-');
    var from = parts[0].trim().split(':');
    var to = parts[1].trim().split(':');
    var step = DATA.slot_step_minutes || 30;
    var cur = parseInt(from[0], 10) * 60 + parseInt(from[1], 10);
    var end = parseInt(to[0], 10) * 60 + parseInt(to[1], 10);
    var slots = [];
    for(; cur <= end; cur += step){
      slots.push(('0' + Math.floor(cur / 60)).slice(-2) + 'h' + ('0' + (cur % 60)).slice(-2));
    }
    return slots;
  }

  function renderSlots(){
    var slots = slotList();
    ['rm-spa-slot', 'rm-spa-slot-alt'].forEach(function(id){
      var sel = document.getElementById(id);
      if(!sel) return;
      slots.forEach(function(s){
        var opt = document.createElement('option');
        opt.value = s;
        opt.textContent = T.slot(s);
        sel.appendChild(opt);
      });
    });
  }

  // ---------- Treatment dropdown populated with grouped optgroup ----------
  function buildTreatmentSelect(name){
    var sel = e('select', {name: name, class:'rm-spa-person__treatment'});
    sel.appendChild(e('option', {value:''}, T.noTreatment));
    MAIN_CATS.forEach(function(cat){
      var group = document.createElement('optgroup');
      group.label = catName(cat);
      cat.items.forEach(function(it){
        var opt = document.createElement('option');
        opt.value = it.code;
        opt.textContent = it.name + ' · ' + it.duration + ' · ' + fmtPrice(it.price_mad);
        group.appendChild(opt);
      });
      sel.appendChild(group);
    });
    return sel;
  }

  // ---------- Render person blocks ----------
  function renderPersons(n){
    var host = document.getElementById('rm-spa-persons-list');
    if(!host) return;
    host.innerHTML = '';
    var max = DATA.max_people || 8;
    n = Math.min(n, max);
    for(var i = 1; i <= n; i++){
      var block = e('div', {class:'rm-spa-person', 'data-person-index': String(i)});
      var head = e('h4', {class:'rm-spa-person__head'}, [
        e('span', {class:'num'}, String(i)),
        e('span', null, T.person + ' ' + i)
      ]);
      block.appendChild(head);

      var row = e('div', {class:'rm-spa-person__row'});
      var selLabel = e('label', null, [
        document.createTextNode(T.treatment + ' *'),
        buildTreatmentSelect('Personne' + i + '_Soin')
      ]);
      var nameLabel = e('label', null, [
        document.createTextNode(T.personName),
        e('input', {type:'text', name:'Personne' + i + '_Prenom', placeholder:''})
      ]);
      row.appendChild(selLabel);
      row.appendChild(nameLabel);
      block.appendChild(row);

      // Extras
      var extrasWrap = e('div', {class:'rm-spa-person__extras'});
      extrasWrap.appendChild(e('div', {class:'rm-spa-person__extras-title'}, T.extrasTitle));
      var extrasList = e('div', {class:'rm-spa-person__extras-list'});
      EXTRAS.forEach(function(ext){
        var lbl = e('label', null, [
          e('input', {type:'checkbox', name:'Personne' + i + '_Extras', value: ext.code, 'data-person': String(i)}),
          document.createTextNode(ext.name + ' (+' + fmtPrice(ext.price_mad) + ')')
        ]);
        extrasList.appendChild(lbl);
      });
      extrasWrap.appendChild(extrasList);
      block.appendChild(extrasWrap);

      host.appendChild(block);
    }
    // Bind change listeners for live recalc
    host.querySelectorAll('select.rm-spa-person__treatment, input[type=checkbox]').forEach(function(el){
      el.addEventListener('change', updateSummary);
    });
    updateSummary();
  }

  // ---------- Live summary + total ----------
  function updateSummary(){
    var host = document.getElementById('rm-spa-summary-content');
    var totalEl = document.getElementById('rm-spa-total');
    var totalHidden = document.getElementById('rm-spa-total-hidden');
    var recapHidden = document.getElementById('rm-spa-recap-detail');
    if(!host) return;
    host.innerHTML = '';
    var total = 0;
    var lines = [];
    var recapTxt = [];
    var persons = document.querySelectorAll('#rm-spa-persons-list .rm-spa-person');
    persons.forEach(function(block, idx){
      var num = idx + 1;
      var sel = block.querySelector('select.rm-spa-person__treatment');
      var nameInput = block.querySelector('input[name^="Personne"][name$="_Prenom"]');
      var code = sel ? sel.value : '';
      var item = code ? ALL_ITEMS[code] : null;
      var personLabel = T.person + ' ' + num + (nameInput && nameInput.value ? ' (' + nameInput.value + ')' : '');
      if(item){
        total += item.price_mad;
        lines.push({label: personLabel + ' — ' + item.name + ' · ' + item.duration, price: item.price_mad});
        recapTxt.push(personLabel + ': ' + item.name + ' [' + item.code + '] ' + item.duration + ' = ' + item.price_mad + ' MAD');
      } else {
        lines.push({label: personLabel + ' — ' + T.empty, price: null, empty:true});
      }
      var extras = block.querySelectorAll('input[type=checkbox]:checked');
      extras.forEach(function(cb){
        var ext = ALL_ITEMS[cb.value];
        if(ext){
          total += ext.price_mad;
          lines.push({label: '   ↳ ' + ext.name, price: ext.price_mad});
          recapTxt.push('   + ' + ext.name + ' [' + ext.code + '] = ' + ext.price_mad + ' MAD');
        }
      });
    });
    if(!persons.length){
      host.appendChild(e('div', {class:'rm-spa-form__summary-empty'}, T.empty));
    } else {
      lines.forEach(function(l){
        var row = e('div', {class:'rm-spa-form__summary-line'});
        row.appendChild(e('span', {class:'label'}, l.label));
        if(l.price !== null && l.price !== undefined){
          row.appendChild(e('span', {class:'price'}, fmtPrice(l.price)));
        }
        host.appendChild(row);
      });
    }
    if(totalEl) totalEl.textContent = total.toLocaleString('fr-FR');
    if(totalHidden) totalHidden.value = total;
    if(recapHidden) recapHidden.value = recapTxt.join('\n');
  }

  // ---------- Init ----------
  function init(){
    if(!readCatalogue()){
      console.error('spa-booking.js: rm-services-data.js could not be loaded.');
      return;
    }
    renderServicesGrid();
    renderSlots();

    // Default date = today (min tomorrow for UX)
    var dateInput = document.getElementById('rm-spa-date');
    if(dateInput){
      var today = new Date();
      var min = new Date(today.getTime() + 24*60*60*1000);
      dateInput.min = min.toISOString().split('T')[0];
    }

    // Person count listener
    var personsSel = document.getElementById('rm-spa-persons');
    if(personsSel){
      personsSel.addEventListener('change', function(){
        renderPersons(parseInt(this.value, 10));
      });
      renderPersons(parseInt(personsSel.value, 10));
    }

    // Update summary when prenom typed
    document.addEventListener('input', function(ev){
      if(ev.target.matches && ev.target.matches('input[name^="Personne"][name$="_Prenom"]')){
        updateSummary();
      }
    });
  }

  function withCatalogue(done){
    if(window.RM_SERVICES && window.RM_SERVICES.spa) return done();
    var s = document.createElement('script');
    s.src = DATA_SRC;
    s.onload = done;
    s.onerror = done;
    document.head.appendChild(s);
  }

  function boot(){ withCatalogue(init); }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
