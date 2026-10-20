/**
 * APC Popup : affichage côté navigateur.
 *
 * Contrôles faits ici (et non côté serveur) pour rester corrects quand la page
 * est servie depuis un cache : appareil, fréquence (compteurs stockés dans le
 * navigateur), déclencheur, et dates de début/fin en complément du serveur.
 *
 * Une seule popup s'ouvre par page vue : la première dont le déclencheur se
 * produit, les popups étant armées par ordre de priorité.
 */
(function () {
  'use strict';

  var STORAGE_PREFIX = 'apc_popup_';
  var MOBILE_QUERY = '(max-width: 767px)';
  var FOCUSABLE = 'a[href], area[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), iframe, [tabindex]:not([tabindex="-1"])';
  var DAY_MS = 86400000;

  // WP Rocket « Retarder l'exécution du JavaScript » met en attente les
  // écouteurs clic/clavier/souris de toute la page jusqu'à la première
  // interaction. Ce marqueur, ignoré par les navigateurs, les en exempte.
  var LISTEN = { isRocket: true };
  var LISTEN_PASSIVE = { isRocket: true, passive: true };
  var LISTEN_CAPTURE = { isRocket: true, capture: true };

  var hasOpened = false;
  var current = null;
  var liveRegion = null;

  /* ---------- Stockage (localStorage, repli cookie) ---------- */

  function cookieGet(key) {
    var match = document.cookie.match(new RegExp('(?:^|; )' + key + '=([^;]*)'));
    if (!match) return null;
    try {
      return JSON.parse(decodeURIComponent(match[1]));
    } catch (e) {
      return null;
    }
  }

  // days = 0 : cookie de session (supprimé à la fermeture du navigateur).
  function cookieSet(key, value, days) {
    var expires = days ? '; max-age=' + Math.round(days * 86400) : '';
    document.cookie = key + '=' + encodeURIComponent(JSON.stringify(value)) + expires + '; path=/; SameSite=Lax';
  }

  function localGet(key) {
    var raw = null;
    try {
      raw = window.localStorage.getItem(key);
    } catch (e) {
      raw = null;
    }
    if (raw) {
      try {
        return JSON.parse(raw);
      } catch (e) {
        // Valeur illisible : on se rabat sur le cookie.
      }
    }
    // Stockage indisponible ou plein : la valeur a pu être écrite en cookie.
    return cookieGet(key);
  }

  function localSet(key, value, cookieDays) {
    try {
      window.localStorage.setItem(key, JSON.stringify(value));
    } catch (e) {
      cookieSet(key, value, cookieDays);
    }
  }

  /* ---------- Éligibilité ---------- */

  function isMobile() {
    return !!(window.matchMedia && window.matchMedia(MOBILE_QUERY).matches);
  }

  function deviceAllowed(config) {
    var devices = config.devices || [];
    return devices.indexOf(isMobile() ? 'mobile' : 'desktop') !== -1;
  }

  function scheduleAllowed(config, now) {
    if (config.start && now < config.start * 1000) return false;
    if (config.end && now >= config.end * 1000) return false;
    return true;
  }

  function frequencyAllowed(config, now) {
    var key = STORAGE_PREFIX + config.id;
    var entry;

    switch (config.frequency) {
      case 'always':
        return true;
      case 'session':
        // Cookie de session : partagé entre les onglets, effacé à la
        // fermeture du navigateur (sessionStorage ne vaut que pour un onglet).
        entry = cookieGet(key);
        return !(entry && entry.v === config.version);
      case 'once':
        entry = localGet(key);
        return !(entry && entry.v === config.version);
      case 'days':
      default:
        entry = localGet(key);
        if (!entry || entry.v !== config.version) return true;
        return now - entry.t >= (config.days || 1) * DAY_MS;
    }
  }

  function isEligible(config) {
    if (config.preview) return true;
    var now = Date.now();
    return deviceAllowed(config) && scheduleAllowed(config, now) && frequencyAllowed(config, now);
  }

  function markSeen(config) {
    if (config.preview || config.frequency === 'always') return;

    var key = STORAGE_PREFIX + config.id;
    if (config.frequency === 'session') {
      cookieSet(key, { v: config.version }, 0);
    } else {
      var keepDays = config.frequency === 'once' ? 3650 : config.days || 1;
      localSet(key, { v: config.version, t: Date.now() }, keepDays);
    }
  }

  /* ---------- Annonce aux lecteurs d'écran (popups discrètes) ---------- */

  // Créée dès l'initialisation : une zone ajoutée juste avant d'être remplie
  // n'est pas toujours lue par les lecteurs d'écran.
  function createLiveRegion() {
    if (liveRegion || !document.body) return;
    liveRegion = document.createElement('div');
    liveRegion.className = 'apc-popup-sr';
    liveRegion.setAttribute('role', 'status');
    liveRegion.setAttribute('aria-live', 'polite');
    document.body.appendChild(liveRegion);
  }

  function announce(text) {
    createLiveRegion();
    if (!liveRegion || !text) return;
    liveRegion.textContent = '';
    window.setTimeout(function () {
      liveRegion.textContent = text;
    }, 100);
  }

  /* ---------- Ouverture / fermeture ---------- */

  function focusables(dialog) {
    return Array.prototype.filter.call(dialog.querySelectorAll(FOCUSABLE), function (el) {
      return el.offsetWidth > 0 || el.offsetHeight > 0 || el === document.activeElement;
    });
  }

  function focusWithin(el) {
    return !!document.activeElement && el.contains(document.activeElement);
  }

  function onKeydown(event) {
    // Touches rejouées plus tard par WP Rocket (copies non fiables) : la touche
    // réelle a déjà été traitée.
    if (!current || event.isTrusted === false) return;

    if (event.key === 'Escape' || event.key === 'Esc') {
      // Popup discrète : Échap ne la ferme que si le visiteur est dedans, pour
      // ne pas interférer avec le menu ou une autre fenêtre du site.
      if (!current.config.modal && !focusWithin(current.el)) {
        return;
      }
      event.preventDefault();
      close();
      return;
    }

    if (event.key === 'Tab' && current.config.modal) {
      var items = focusables(current.dialog);
      if (!items.length) {
        event.preventDefault();
        current.dialog.focus();
        return;
      }
      var first = items[0];
      var last = items[items.length - 1];
      var active = document.activeElement;

      // Focus sorti de la fenêtre (clic sur le fond, par exemple) : on le ramène.
      if (!current.dialog.contains(active)) {
        event.preventDefault();
        (event.shiftKey ? last : first).focus();
      } else if (event.shiftKey && (active === first || active === current.dialog)) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && active === last) {
        event.preventDefault();
        first.focus();
      }
    }
  }

  function open(popup) {
    if (hasOpened) return;

    var config = popup.config;

    // Onglet en arrière-plan : on attend que le visiteur y revienne.
    if (!config.preview && document.visibilityState === 'hidden') {
      if (!popup.waitingVisible) {
        popup.waitingVisible = true;
        var onVisible = function () {
          if (document.visibilityState === 'hidden') return;
          window.removeEventListener('visibilitychange', onVisible, LISTEN_CAPTURE);
          popup.waitingVisible = false;
          open(popup);
        };
        // Sur window, en capture : passe avant l'écouteur de WP Rocket sur document.
        window.addEventListener('visibilitychange', onVisible, LISTEN_CAPTURE);
      }
      return;
    }

    // Nouvelle vérification au moment d'ouvrir : un autre onglet a pu afficher
    // la popup entre-temps, ou la période a pu se terminer.
    if (!config.preview) {
      var now = Date.now();
      if (!scheduleAllowed(config, now) || !frequencyAllowed(config, now)) return;
    }

    hasOpened = true;
    markSeen(config);

    current = popup;
    current.returnFocus = document.activeElement;

    popup.el.hidden = false;
    if (config.modal) {
      document.documentElement.classList.add('apc-popup-lock');
    }

    // Laisse le navigateur peindre l'état initial pour que la transition joue.
    window.requestAnimationFrame(function () {
      window.requestAnimationFrame(function () {
        popup.el.classList.add('is-open');
      });
    });

    document.addEventListener('keydown', onKeydown, LISTEN);

    if (config.modal) {
      // Focus sur la zone qui défile : flèches et barre d'espace font défiler
      // un contenu long, Tab mène aux liens et au bouton.
      var target = popup.el.querySelector('.apc-popup__body') || popup.dialog;
      try {
        target.focus({ preventScroll: true });
      } catch (e) {
        target.focus();
      }
    } else {
      announce(config.announce);
    }
  }

  function close() {
    if (!current) return;

    var popup = current;
    var hadFocus = focusWithin(popup.el);
    current = null;

    document.removeEventListener('keydown', onKeydown, LISTEN);
    popup.el.classList.remove('is-open');
    document.documentElement.classList.remove('apc-popup-lock');

    var hide = function () {
      popup.el.hidden = true;
    };
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduced) {
      hide();
    } else {
      window.setTimeout(hide, 250);
    }

    // Rend le focus là où il était, sans faire défiler la page, seulement
    // s'il avait été placé dans la popup.
    var target = popup.returnFocus;
    if (hadFocus && target && target !== document.body && document.contains(target) && typeof target.focus === 'function') {
      try {
        target.focus({ preventScroll: true });
      } catch (e) {
        // Élément devenu non focalisable : rien à faire.
      }
    }
  }

  /* ---------- Déclencheurs ---------- */

  function onScrollPercent(threshold, callback) {
    var done = false;

    function check() {
      if (done) return;
      var doc = document.documentElement;
      var max = Math.max(doc.scrollHeight, document.body ? document.body.scrollHeight : 0) - window.innerHeight;
      var percent = max <= 0 ? 100 : (window.pageYOffset / max) * 100;
      if (percent >= threshold) {
        done = true;
        window.removeEventListener('scroll', check, LISTEN_PASSIVE);
        callback();
      }
    }

    window.addEventListener('scroll', check, LISTEN_PASSIVE);
    // Premier contrôle au tour suivant : les popups prioritaires déclenchées
    // dès le chargement passent avant (page courte ou déjà défilée).
    window.setTimeout(check, 0);
  }

  function onExitIntent(fallbackSeconds, callback) {
    var finePointer = window.matchMedia && window.matchMedia('(pointer: fine)').matches;

    if (finePointer && !isMobile()) {
      var handler = function (event) {
        if (!event.relatedTarget && event.clientY <= 0) {
          document.removeEventListener('mouseout', handler, LISTEN);
          callback();
        }
      };
      document.addEventListener('mouseout', handler, LISTEN);
      return;
    }

    // Écrans tactiles : pas d'intention de sortie détectable, repli sur un délai.
    if (fallbackSeconds > 0) {
      window.setTimeout(callback, fallbackSeconds * 1000);
    }
  }

  function arm(popup) {
    var config = popup.config;
    var show = function () {
      open(popup);
    };

    if (config.preview) {
      window.setTimeout(show, 0);
      return;
    }

    switch (config.trigger) {
      case 'delay':
        window.setTimeout(show, Math.max(0, config.delay) * 1000);
        break;
      case 'scroll':
        onScrollPercent(config.scroll || 50, show);
        break;
      case 'exit':
        onExitIntent(config.exitFallback || 0, show);
        break;
      case 'load':
      default:
        window.setTimeout(show, 0);
    }
  }

  /* ---------- Initialisation ---------- */

  function bind(popup) {
    popup.el.addEventListener(
      'click',
      function (event) {
        var target = event.target;
        if (!(target instanceof Element)) return;

        if (target.closest('[data-apc-popup-close]')) {
          event.preventDefault();
          close();
          return;
        }

        if (target.closest('[data-apc-popup-cta]')) {
          // Le compteur est déjà enregistré à l'ouverture : on ferme et on laisse le lien agir.
          close();
          return;
        }

        if (target.hasAttribute('data-apc-popup-overlay') && popup.config.closeOnOverlay) {
          close();
        }
      },
      LISTEN
    );
  }

  function init() {
    var elements = document.querySelectorAll('[data-apc-popup]');

    Array.prototype.forEach.call(elements, function (el) {
      var config;
      try {
        config = JSON.parse(el.getAttribute('data-apc-popup'));
      } catch (e) {
        return;
      }

      var popup = {
        el: el,
        dialog: el.querySelector('.apc-popup__dialog'),
        config: config,
      };

      if (!popup.dialog || !isEligible(config)) return;

      if (!config.modal) createLiveRegion();

      // La popup peut s'ouvrir pour ce visiteur : ses images (en chargement
      // différé tant qu'elle est masquée) sont téléchargées dès maintenant
      // pour être prêtes au déclenchement.
      Array.prototype.forEach.call(el.querySelectorAll('img[loading="lazy"]'), function (img) {
        img.loading = 'eager';
      });

      bind(popup);
      arm(popup);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, LISTEN);
  } else {
    init();
  }
})();
