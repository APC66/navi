@if(!isset($order) || !$order)
    <div class="wrap">
        <div class="notice notice-error"><p>Erreur : Commande introuvable ou variable manquante.</p></div>
    </div>
@else
    <div class="wrap">
        <h1 class="wp-heading-inline">✏️ Modifier la réservation #{{ $order->get_id() }}</h1>

        <div style="margin-top: 20px; max-width: 1000px;">

            <form id="edit-booking-form" method="POST" action="{{ admin_url('admin.php?page=navi-boarding-list') }}" class="card" style="padding: 20px; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                @csrf
                    <?php wp_nonce_field('update_reservation_action', '_wpnonce'); ?>
                <input type="hidden" name="action" value="update_reservation">
                <input type="hidden" name="order_id" value="{{ $order->get_id() }}">
                <input type="hidden" name="item_id" value="{{ $item->get_id() }}">
                <input type="hidden" name="original_sailing_id" value="{{ $currentSailing->ID }}">

                <!-- Champs pour actions spéciales -->
                <input type="hidden" name="special_action" id="special_action" value="">

                <div style="display: flex; flex-wrap: wrap; gap: 30px;">

                    <!-- COLONNE GAUCHE : Infos & Actions -->
                    <div style="flex: 1; min-width: 300px;">
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; border: 1px solid #ddd; margin-bottom: 20px;">
                            <h2 class="title" style="margin-top:0;">👤 Client</h2>
                            <p style="margin-bottom: 5px;">
                                <strong>{{ $order->get_formatted_billing_full_name() }}</strong>
                            </p>
                            <p style="margin-bottom: 5px;">
                                <span class="dashicons dashicons-email" style="color:#666;"></span>
                                <a href="mailto:{{ $order->get_billing_email() }}">{{ $order->get_billing_email() }}</a>
                            </p>
                            <p style="margin-bottom: 0;">
                                <span class="dashicons dashicons-phone" style="color:#666;"></span>
                                {{ $order->get_billing_phone() }}
                            </p>
                        </div>

                        <!-- Actions Rapides -->
                        <div style="margin-bottom: 20px;">
                            <h3 style="margin-top:0; font-size: 1.1em;">⚡ Actions Rapides</h3>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <button type="button" class="button action-trigger" data-action="credit" title="Générer un avoir du montant total">
                                    🎟️ Générer Avoir
                                </button>
                                <button type="button" class="button action-trigger" data-action="refund" style="color: #b32d2e; border-color: #b32d2e;" title="Marquer comme remboursé">
                                    💸 Rembourser
                                </button>
                            </div>
                            <p class="description" style="margin-top: 8px; font-size: 11px;">Ces actions s'exécutent lors de la sauvegarde.</p>
                        </div>

                        <!-- Notes -->
                        <h3 style="font-size: 1.1em;">📝 Notes</h3>
                        <label for="private_note" style="font-weight: bold; display: block; margin-bottom: 5px;">Note Interne (Admin) :</label>
                        <textarea name="private_note" id="private_note" rows="4" style="width: 100%; border:1px solid #ccc;">{{ $order->get_meta('_private_boarding_note') }}</textarea>
                        <p class="description">Visible uniquement par l'équipe.</p>

                        @if($noteClient = $order->get_meta('_boarding_notes'))
                            <div style="margin-top: 15px; background: #fff8e1; padding: 10px; border: 1px solid #ffe0b2; border-radius: 3px;">
                                <strong>Note Client (Au checkout) :</strong><br>
                                <em style="color:#e65100;">"{{ $noteClient }}"</em>
                            </div>
                        @endif
                    </div>

                    <!-- COLONNE DROITE : Détails Réservation -->
                    <div style="flex: 1.5; min-width: 400px; border-left: 1px solid #eee; padding-left: 30px;">
                        <h2 class="title" style="margin-top:0;">📅 Détails du départ</h2>

                        <!-- Sélecteur de date -->
                        <div style="margin-bottom: 25px; background: #e6f7ff; padding: 15px; border-radius: 4px; border: 1px solid #b3e0ff;">
                            <label for="sailing_id" style="font-weight: bold; display: block; margin-bottom: 8px;">Date de départ :</label>
                            <select name="sailing_id" id="sailing_id" style="width: 100%; max-width: 400px;">
                                <option value="{{ $currentSailing->ID }}" selected>
                                    (Actuel) {{ $currentSailing->start }} - {{ $currentSailing->title }}
                                </option>
                                @foreach($futureSailings as $sailing)
                                    @if($sailing->ID != $currentSailing->ID)
                                        <option value="{{ $sailing->ID }}">
                                            {{ $sailing->start }} - {{ $sailing->title }} ({{ $sailing->quota }} places)
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <p class="description" style="color: #005a87;">Changer la date déplacera automatiquement les quotas.</p>
                        </div>

                        <div style="display: flex; gap: 30px;">

                            <!-- PASSAGERS -->
                            <div style="flex: 1;">
                                <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">👥 Passagers</h3>
                                <div class="passenger-list">
                                    @php
                                        $passengers = $bookingData['passengers'] ?? [];
                                        // Récupération de tous les types de passagers
                                        $allTypes = get_terms([
                                            'taxonomy' => 'passenger_type',
                                            'hide_empty' => false
                                        ]);
                                    @endphp

                                    @if(!empty($allTypes) && !is_wp_error($allTypes))
                                        @foreach($allTypes as $term)
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #f0f0f0;">
                                                <label for="pax_{{ $term->term_id }}" style="font-weight: 500;">{{ $term->name }}</label>
                                                <input type="number"
                                                       id="pax_{{ $term->term_id }}"
                                                       name="passengers[{{ $term->term_id }}]"
                                                       value="{{ $passengers[$term->term_id] ?? 0 }}"
                                                       min="0"
                                                       style="width: 70px;">
                                            </div>
                                        @endforeach
                                    @else
                                        <p>Aucun type de passager configuré.</p>
                                    @endif
                                </div>
                            </div>

                            <!-- OPTIONS -->
                            <div style="flex: 1;">
                                <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">➕ Options</h3>
                                <div class="options-list">
                                    @php
                                        // Normalisation des options (Ancien format [id] vs Nouveau [id=>qty])
                                        $optionsData = $bookingData['options'] ?? [];
                                        $normalizedOptions = [];
                                        foreach($optionsData as $k => $v) {
                                            if (is_numeric($k)) $normalizedOptions[$k] = $v;
                                            elseif (is_numeric($v)) $normalizedOptions[$v] = 1;
                                        }

                                        $allOptions = get_terms([
                                            'taxonomy' => 'extra_option_type',
                                            'hide_empty' => false
                                        ]);
                                    @endphp

                                    @if(!empty($allOptions) && !is_wp_error($allOptions))
                                        @foreach($allOptions as $term)
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #f0f0f0;">
                                                <label for="opt_{{ $term->term_id }}" style="font-weight: 500;">{{ $term->name }}</label>
                                                <input type="number"
                                                       id="opt_{{ $term->term_id }}"
                                                       name="options[{{ $term->term_id }}]"
                                                       value="{{ $normalizedOptions[$term->term_id] ?? 0 }}"
                                                       min="0"
                                                       style="width: 70px;">
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="description">Aucune option configurée.</p>
                                    @endif
                                </div>
                            </div>

                        </div>

                        <!-- AJOUT ICI : AJUSTEMENT PRIX -->
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                            @php
                                $currentBalance = (float) $order->get_meta('_balance_due', true);
                                $balanceColor = $currentBalance > 0 ? 'red' : ($currentBalance < 0 ? 'green' : 'black');
                                $balanceText = $currentBalance > 0 ? 'Reste à payer' : ($currentBalance < 0 ? 'Remboursement dû' : 'Équilibré');
                            @endphp

                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                                <label for="manual_price_adjustment" style="font-weight: bold; font-size:1.1em;">💰 Ajustement Prix (€) :</label>

                                <!-- Affichage du Solde Actuel -->
                                <div style="font-weight:bold; color:{{ $balanceColor }}; font-size:1.1em; background:#f0f0f0; padding:5px 10px; border-radius:4px;">
                                    Solde actuel : {{ number_format($currentBalance, 2) }}€ <small>({{ $balanceText }})</small>
                                </div>
                            </div>

                            <input type="number" name="manual_price_adjustment" id="manual_price_adjustment" value="0" step="0.01" style="width: 150px; font-size:1.1em;">
                            <p class="description">
                                Entrez un montant pour <strong>AJOUTER</strong> à la balance.<br>
                                Positif (ex: 20) = Supplément ajouté.<br>
                                Négatif (ex: -20) = Avoir ajouté.
                            </p>
                        </div>

                    </div>
                </div>

                <!-- Footer Actions -->
                <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; text-align: right; display: flex; justify-content: space-between; align-items: center;">
                    <a href="{{ $cancelUrl }}" class="button">← Annuler et retour</a>
                    <div>
                        <button type="submit" class="button button-primary button-large" id="save-btn" style="min-width: 150px;">
                            Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const specialActionInput = document.getElementById('special_action');
            const btns = document.querySelectorAll('.action-trigger');
            const saveBtn = document.getElementById('save-btn');
            const sailingSelect = document.getElementById('sailing_id');
            const originalSailingId = "{{ $currentSailing->ID }}";

            // Gestion des boutons d'action rapide (Avoir / Rembourser)
            btns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const action = this.dataset.action;
                    let label = action === 'refund' ? 'rembourser' : 'générer un avoir pour';

                    // On reset d'abord
                    specialActionInput.value = '';
                    saveBtn.innerText = 'Enregistrer les modifications';
                    saveBtn.classList.remove('button-hero');

                    // Si l'utilisateur clique, on prépare l'action
                    if (confirm('Cette action sera exécutée en même temps que la sauvegarde.\n\nVoulez-vous ' + label + ' cette commande ?')) {
                        specialActionInput.value = action;

                        // Feedback visuel sur le bouton principal
                        saveBtn.innerText = '💾 Enregistrer + ' + (action === 'refund' ? 'Rembourser' : 'Créer Avoir');
                        saveBtn.classList.add('button-hero'); // Style WP plus gros

                        // On pourrait aussi surligner le bouton cliqué pour montrer qu'il est actif
                        btns.forEach(b => b.style.opacity = '0.5');
                        this.style.opacity = '1';
                        this.style.border = '2px solid #2271b1';
                    } else {
                        // Annulation visuelle
                        btns.forEach(b => b.style.opacity = '1');
                        this.style.border = '';
                    }
                });
            });

            // Feedback visuel changement de date
            if (sailingSelect) {
                sailingSelect.addEventListener('change', function() {
                    if (this.value != originalSailingId) {
                        this.style.backgroundColor = '#fff8e1'; // Jaune clair
                        this.style.borderColor = '#e65100';
                        this.parentElement.style.borderLeft = '4px solid #e65100';
                    } else {
                        this.style.backgroundColor = '';
                        this.style.borderColor = '';
                        this.parentElement.style.borderLeft = '';
                    }
                });
            }
        });
    </script>
@endif
