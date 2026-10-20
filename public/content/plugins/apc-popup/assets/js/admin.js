/**
 * APC Popup : écran d'administration.
 *
 * - affichage conditionnel des options (data-apc-show="champ=valeur") ;
 * - sélecteurs de contenus et de termes avec recherche (data-apc-picker) ;
 * - confirmation avant la remise à zéro des compteurs (data-apc-confirm).
 */
(function () {
  'use strict';

  var settings = window.apcPopupAdmin || { i18n: {} };
  var i18n = settings.i18n || {};

  /* ---------- Affichage conditionnel ---------- */

  function currentValue(field) {
    var controls = document.querySelectorAll('[name="apc_popup[' + field + ']"]');
    for (var i = 0; i < controls.length; i++) {
      var control = controls[i];
      if (control.type === 'radio' || control.type === 'checkbox') {
        if (control.checked) return control.value;
      } else {
        return control.value;
      }
    }
    return '';
  }

  function refreshConditions() {
    var targets = document.querySelectorAll('[data-apc-show]');
    Array.prototype.forEach.call(targets, function (el) {
      var rule = el.getAttribute('data-apc-show').split('=');
      el.hidden = currentValue(rule[0]) !== rule[1];
    });
  }

  function initConditions() {
    if (!document.querySelector('[data-apc-show]')) return;

    document.addEventListener('change', function (event) {
      var name = event.target && event.target.name;
      if (name && name.indexOf('apc_popup[') === 0) refreshConditions();
    });

    refreshConditions();
  }

  /* ---------- Sélecteurs avec recherche ---------- */

  function debounce(fn, wait) {
    var timer;
    return function () {
      var args = arguments;
      window.clearTimeout(timer);
      timer = window.setTimeout(function () {
        fn.apply(null, args);
      }, wait);
    };
  }

  function format(template, value) {
    return String(template || '%s').replace(/%[sd]/, value);
  }

  function initPicker(picker) {
    var source = picker.getAttribute('data-apc-picker');
    var name = picker.getAttribute('data-name');
    var chips = picker.querySelector('.apc-picker__chips');
    var input = picker.querySelector('.apc-picker__input');
    var list = picker.querySelector('.apc-picker__results');
    var note = picker.querySelector('.apc-picker__message');
    var status = picker.querySelector('.apc-picker__status');
    var active = -1;
    var request = 0;

    function announce(text) {
      if (status) status.textContent = text;
    }

    function selectedIds() {
      return Array.prototype.map.call(chips.querySelectorAll('input[type="hidden"]'), function (hidden) {
        return String(hidden.value);
      });
    }

    function resetActive() {
      active = -1;
      input.removeAttribute('aria-activedescendant');
    }

    function closeList() {
      list.hidden = true;
      list.innerHTML = '';
      if (note) note.hidden = true;
      resetActive();
    }

    // Message (recherche, aucun résultat, erreur) affiché hors de la liste
    // des options et annoncé aux lecteurs d'écran.
    function message(text) {
      list.hidden = true;
      list.innerHTML = '';
      resetActive();
      if (note) {
        note.textContent = text;
        note.hidden = false;
      }
      announce(text);
    }

    function addChip(item) {
      if (selectedIds().indexOf(String(item.id)) !== -1) return;

      var li = document.createElement('li');
      li.className = 'apc-picker__chip';

      var label = document.createElement('span');
      label.textContent = item.label;

      var remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'apc-picker__remove';
      remove.setAttribute('aria-label', format(i18n.remove, item.label));
      remove.innerHTML = '&times;';

      var hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = name;
      hidden.value = item.id;

      li.appendChild(label);
      li.appendChild(remove);
      li.appendChild(hidden);
      chips.appendChild(li);
    }

    function highlight(index) {
      var options = list.querySelectorAll('[role="option"]');
      if (!options.length) return;

      active = (index + options.length) % options.length;
      Array.prototype.forEach.call(options, function (option, i) {
        option.setAttribute('aria-selected', i === active ? 'true' : 'false');
      });
      input.setAttribute('aria-activedescendant', options[active].id);
      options[active].scrollIntoView({ block: 'nearest' });
    }

    function render(items) {
      var taken = selectedIds();
      items = items.filter(function (item) {
        return taken.indexOf(String(item.id)) === -1;
      });

      if (!items.length) {
        message(i18n.noResult);
        return;
      }

      resetActive();
      list.innerHTML = '';
      items.forEach(function (item, i) {
        var li = document.createElement('li');
        li.id = picker.id + '-option-' + i;
        li.setAttribute('role', 'option');
        li.setAttribute('aria-selected', 'false');
        li.className = 'apc-picker__option';
        li.textContent = item.label;
        li.addEventListener('mousedown', function (event) {
          // mousedown : se produit avant la perte de focus du champ.
          event.preventDefault();
          choose(item);
        });
        li._apcItem = item;
        list.appendChild(li);
      });
      if (note) note.hidden = true;
      list.hidden = false;
      announce(format(i18n.results, items.length));
    }

    function choose(item) {
      addChip(item);
      input.value = '';
      request++;
      closeList();
      input.focus();
      announce(format(i18n.added, item.label));
    }

    var search = debounce(function (term) {
      var id = ++request;
      var url = settings.ajaxUrl
        + '?action=apc_popup_search'
        + '&nonce=' + encodeURIComponent(settings.nonce)
        + '&source=' + encodeURIComponent(source)
        + '&q=' + encodeURIComponent(term);

      window.fetch(url, { credentials: 'same-origin' })
        .then(function (response) {
          return response.json();
        })
        .then(function (json) {
          if (id !== request) return;
          if (!json || !json.success) throw new Error('search');
          render(json.data || []);
        })
        .catch(function () {
          if (id === request) message(i18n.error);
        });
    }, 250);

    input.addEventListener('input', function () {
      var term = input.value.trim();
      if (term.length < 2 && !/^\d+$/.test(term)) {
        request++;
        closeList();
        return;
      }
      message(i18n.searching);
      search(term);
    });

    input.addEventListener('keydown', function (event) {
      var options = list.hidden ? [] : list.querySelectorAll('[role="option"]');

      switch (event.key) {
        case 'ArrowDown':
          if (options.length) {
            event.preventDefault();
            highlight(active + 1);
          }
          break;
        case 'ArrowUp':
          if (options.length) {
            event.preventDefault();
            highlight(active - 1);
          }
          break;
        case 'Enter':
          // Ne jamais envoyer le formulaire de la popup depuis ce champ.
          event.preventDefault();
          if (options.length) choose(options[active >= 0 ? active : 0]._apcItem);
          break;
        case 'Escape':
          if (!list.hidden) {
            event.preventDefault();
            closeList();
          }
          break;
      }
    });

    input.addEventListener('blur', function () {
      window.setTimeout(closeList, 150);
    });

    chips.addEventListener('click', function (event) {
      var button = event.target.closest('.apc-picker__remove');
      if (!button) return;
      var chip = button.closest('.apc-picker__chip');
      if (chip) {
        var label = chip.querySelector('span');
        chip.parentNode.removeChild(chip);
        announce(format(i18n.removed, label ? label.textContent : ''));
      }
      input.focus();
    });
  }

  function initPickers() {
    var pickers = document.querySelectorAll('[data-apc-picker]');
    Array.prototype.forEach.call(pickers, function (picker, i) {
      if (!picker.id) picker.id = 'apc-picker-' + i;
      var list = picker.querySelector('.apc-picker__results');
      var input = picker.querySelector('.apc-picker__input');
      if (list && input) {
        list.id = picker.id + '-results';
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-controls', list.id);
        input.setAttribute('aria-expanded', 'false');
        new MutationObserver(function () {
          input.setAttribute('aria-expanded', list.hidden ? 'false' : 'true');
        }).observe(list, { attributes: true, attributeFilter: ['hidden'] });
        initPicker(picker);
      }
    });
  }

  /* ---------- Confirmation ---------- */

  // Liens (liste des popups) et bouton d'envoi (fiche d'une popup).
  function initConfirm() {
    document.addEventListener('click', function (event) {
      var link = event.target.closest && event.target.closest('[data-apc-confirm]');
      if (link && !window.confirm(link.getAttribute('data-apc-confirm'))) {
        event.preventDefault();
      }
    });
  }

  /* ---------- Enregistrer et réinitialiser les compteurs ---------- */

  // Ajoute la demande au formulaire puis passe par le bouton d'enregistrement
  // de WordPress (brouillon : « Enregistrer », sinon « Mettre à jour »), pour
  // garder son comportement (pas d'alerte de départ, pas de double envoi).
  function initResetSave() {
    var button = document.querySelector('[data-apc-reset-save]');
    var form = document.getElementById('post');
    if (!button || !form) return;

    button.addEventListener('click', function () {
      if (!window.confirm(button.getAttribute('data-apc-reset-save'))) return;
      if (form.reportValidity && !form.reportValidity()) return;

      var submit = document.getElementById('save-post') || document.getElementById('publish');
      if (!submit) return;

      if (!form.querySelector('input[name="apc_popup_reset_on_save"]')) {
        var flag = document.createElement('input');
        flag.type = 'hidden';
        flag.name = 'apc_popup_reset_on_save';
        flag.value = '1';
        form.appendChild(flag);
      }

      submit.click();
    });
  }

  /* ---------- Aperçu du bouton d'action ---------- */

  function initCtaPreview() {
    var preview = document.querySelector('[data-apc-cta-preview]');
    if (!preview) return;

    var vars = {
      cta_bg_color: '--cta-bg',
      cta_text_color: '--cta-text',
      cta_hover_bg_color: '--cta-hover-bg',
      cta_hover_text_color: '--cta-hover-text',
      cta_border_color: '--cta-border',
      cta_hover_border_color: '--cta-hover-border',
    };
    var fallbackLabel = preview.textContent.trim();
    var labelInput = document.querySelector('[name="apc_popup[cta_label]"]');

    document.addEventListener('input', function (event) {
      var target = event.target;
      var key = target && target.getAttribute && target.getAttribute('data-apc-cta-color');
      var size = target && target.getAttribute && target.getAttribute('data-apc-cta-size');
      if (key && vars[key]) {
        preview.style.setProperty(vars[key], target.value);
      } else if (size) {
        var max = parseInt(target.getAttribute('max'), 10) || 999;
        var value = Math.min(max, Math.max(0, parseInt(target.value, 10) || 0));
        preview.style.setProperty(size, value + 'px');
      } else if (target === labelInput) {
        preview.textContent = labelInput.value.trim() || fallbackLabel;
      }
    });
  }

  function init() {
    initConditions();
    initPickers();
    initConfirm();
    initResetSave();
    initCtaPreview();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
