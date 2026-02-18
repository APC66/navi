import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';

document.addEventListener('DOMContentLoaded', function() {
  const calendarEl = document.getElementById('navi-cruise-calendar');
  if (!calendarEl) return;

  const config = window.NaviCalendarConfig || {};
  const modal = document.getElementById('event-modal');
  let currentEventId = null;

  // --- LOGIQUE MODALE ---
  function openModal(event) {
    currentEventId = event.id;
    const props = event.extendedProps;

    // Titre et infos de base
    document.getElementById('modal-title').innerText = event.title;
    document.getElementById('modal-content').innerHTML = `
        <p><strong>Date :</strong> ${event.start.toLocaleString()}</p>
        <p><strong>Remplissage :</strong> ${props.booked} / ${props.quota}</p>
        <p><strong>Statut actuel :</strong> ${props.status}</p>
        <p><a href="${event.url}" target="_blank" class="button">Modifier le départ (WP) ↗</a></p>
    `;

    // Lien Liste embarquement
    const btnBoarding = document.getElementById('btn-boarding');
    if (btnBoarding) {
      btnBoarding.href = `admin.php?page=navi-boarding-list&sailing_id=${event.id}`;
    }

    // Reset UI Annulation
    const cancelZone = document.getElementById('cancel-zone');
    if (cancelZone) cancelZone.style.display = 'none';

    // Réinitialisation des boutons
    const btnCancel = document.getElementById('btn-cancel-mode');
    if (btnCancel) {
      btnCancel.style.display = 'inline-block';
      btnCancel.innerText = 'Gérer le statut (Annuler / Reporter)';
    }

    const impactAnalysis = document.getElementById('impact-analysis');
    if (impactAnalysis) impactAnalysis.innerHTML = 'Analyse en cours...';

    // On s'assure que le selecteur de statut est visible s'il existe
    const statusSelect = document.getElementById('cancel-status-select');
    if (statusSelect) {
      // Pré-sélectionner le statut actuel ou 'Annulé' par défaut
      statusSelect.value = props.status === 'Actif' ? 'Annulé' : props.status;
    }

    modal.style.display = 'flex';
  }

  // Bouton "Gérer le statut" (Ouvre la zone d'action)
  const btnCancelMode = document.getElementById('btn-cancel-mode');
  if (btnCancelMode) {
    btnCancelMode.addEventListener('click', function() {
      this.style.display = 'none';
      document.getElementById('cancel-zone').style.display = 'block';

      // Appel API Analyse (pour info impact)
      const analyzeUrl = config.apiUrl.replace('calendar/events', 'cancellation/analyze');

      fetch(analyzeUrl + `?sailing_id=${currentEventId}&_wpnonce=${config.nonce}`)
        .then(async res => {
          const text = await res.text();
          try {
            return JSON.parse(text);
          } catch (e) {
            console.error('API Error Response (Analyze):', text);
            throw new Error('Réponse invalide du serveur (JSON error).');
          }
        })
        .then(data => {
          if (data.code && data.message) {
            throw new Error(data.message);
          }
          const imp = data.impact;
          const impactAnalysis = document.getElementById('impact-analysis');
          if (impactAnalysis) {
            impactAnalysis.innerHTML = `
                        <strong>${imp.orders_count} commandes</strong> impactées
                        (${imp.passengers_count} passagers).<br>
                        Une note sera ajoutée aux commandes si le statut change.
                    `;
          }
        })
        .catch(err => {
          const impactAnalysis = document.getElementById('impact-analysis');
          if (impactAnalysis) {
            impactAnalysis.innerHTML = `<span style="color:red">Erreur: ${err.message}</span>`;
          }
        });
    });
  }

  // Bouton "Confirmer le changement"
  const btnConfirmCancel = document.getElementById('btn-confirm-cancel');
  if (btnConfirmCancel) {
    btnConfirmCancel.addEventListener('click', function() {
      // Récupération du statut choisi dans le selecteur (ajouté dans la vue précédemment)
      const statusSelect = document.getElementById('cancel-status-select');
      const newStatus = statusSelect ? statusSelect.value : 'Annulé'; // Fallback

      if (!confirm('Confirmer le passage au statut "' + newStatus + '" ?')) return;

      const reasonInput = document.getElementById('cancel-reason');
      const reason = reasonInput ? reasonInput.value : '';
      const confirmUrl = config.apiUrl.replace('calendar/events', 'cancellation/confirm');

      fetch(confirmUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce },
        // On envoie le nouveau statut
        body: JSON.stringify({
          sailing_id: currentEventId,
          reason: reason,
          status: newStatus
        })
      })
        .then(async res => {
          const text = await res.text();
          try {
            return JSON.parse(text);
          } catch (e) {
            console.error('API Error Response (Confirm):', text);
            throw new Error('Réponse invalide du serveur.');
          }
        })
        .then(data => {
          if (data.success) {
            alert(data.message);
            modal.style.display = 'none';
            location.reload(); // Recharger pour voir la nouvelle couleur
          } else {
            alert('Erreur: ' + (data.message || 'Inconnue'));
          }
        })
        .catch(err => {
          alert('Erreur: ' + err.message);
        });
    });
  }

  // --- CALENDRIER ---
  const calendar = new Calendar(calendarEl, {
    plugins: [ dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin ],
    locale: frLocale,
    initialView: 'dayGridMonth',
    height: 'auto',
    contentHeight: 650,
    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listMonth' },
    events: {
      url: config.apiUrl,
      extraParams: { context: 'admin', _wpnonce: config.nonce },
      failure: function() { console.error('Erreur chargement événements.'); }
    },
    eventTimeFormat: { hour: '2-digit', minute: '2-digit', meridiem: false },

    eventClick: function(info) {
      info.jsEvent.preventDefault();
      openModal(info.event);
    },

    eventContent: function(arg) {
      const props = arg.event.extendedProps;
      const available = props.available !== undefined ? props.available : '?';
      const quota = props.quota !== undefined ? props.quota : '?';

      let percent = 0;
      if (quota > 0 && props.booked !== undefined) {
        percent = Math.min(100, (props.booked / quota) * 100);
      }

      let barColor = '#a3e635';
      if (percent > 50) barColor = '#facc15';
      if (percent > 90) barColor = '#f87171';

      // Gestion visuelle simplifiée, le contrôleur PHP gère déjà titre et couleur

      return {
        html: `
                <div class="fc-content" style="padding: 2px;">
                    <div style="font-size: 0.85em; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        ${arg.timeText} ${arg.event.title.replace(/\[.*?\]/, '')}
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.75em; margin-top: 2px;">
                        <span>Dispo: <strong>${available}</strong> / ${quota}</span>
                    </div>
                    <div style="background: rgba(255,255,255,0.3); height: 4px; border-radius: 2px; margin-top: 2px; overflow: hidden;">
                        <div style="background: ${barColor}; height: 100%; width: ${percent}%;"></div>
                    </div>
                </div>
            `
      };
    }
  });

  calendar.render();
});
