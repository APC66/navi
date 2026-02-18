<div class="wrap">
    <h1 class="wp-heading-inline">Calendrier des Croisières</h1>

    <div class="navi-calendar-container" style="margin-top: 20px; background: white; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); position: relative;">
        <div id="navi-cruise-calendar"></div>
    </div>
</div>

<!-- MODALE DE GESTION -->
<div id="event-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:white; width:500px; max-width:90%; border-radius:8px; padding:20px; box-shadow:0 4px 12px rgba(0,0,0,0.2);">
        <h2 id="modal-title" style="margin-top:0;">Détails du départ</h2>

        <div id="modal-content" style="margin: 20px 0;">
            <!-- Contenu dynamique -->
            Chargement...
        </div>

        <div id="modal-actions" style="display:flex; justify-content:flex-end; gap:10px; border-top:1px solid #eee; padding-top:15px;">
            <button class="button" onclick="document.getElementById('event-modal').style.display='none'">Fermer</button>
            <a id="btn-boarding" href="#" class="button button-primary" target="_blank">📋 Liste Embarquement</a>
            <button id="btn-cancel-mode" class="button button-link-delete">Changer le statut</button>
        </div>

        <!-- ZONE D'ACTION (Cachée par défaut) -->
        <div id="cancel-zone" style="display:none; margin-top:20px; background:#fff5f5; padding:15px; border:1px solid #fc8181; border-radius:4px;">
            <h3 style="color:#c53030; margin-top:0;">⚠️ Modifier le statut</h3>
            <p id="impact-analysis">Analyse en cours...</p>

            <div style="margin-bottom: 10px;">
                <label for="cancel-status-select" style="font-weight:bold; display:block; margin-bottom:5px;">Nouveau Statut :</label>
                <select id="cancel-status-select" style="width:100%;">
                    <option value="Annulé">Annulé</option>
                    <option value="Reporté">Reporté</option>
                    <option value="Actif">Actif (Réactiver)</option>
                    <option value="Complet">Complet</option>
                </select>
            </div>

            <textarea id="cancel-reason" placeholder="Raison (Météo, Panne...)" style="width:100%; margin-bottom:10px;"></textarea>

            <div style="display:flex; gap:10px; margin-top:10px;">
                <button id="btn-confirm-cancel" class="button button-primary" style="background:#c53030; border-color:#c53030;">Confirmer le changement</button>
            </div>
        </div>
    </div>
</div>

<style>
    .navi-calendar-container { min-height: 600px; }
    .fc-event { cursor: pointer; }
    #event-modal.open { display: flex !important; }
</style>
