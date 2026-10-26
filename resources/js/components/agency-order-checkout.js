/**
 * Gestion du formulaire de commande pour compte tiers (agences).
 *
 * Ce script gère :
 * - La recherche AJAX de clients existants
 * - Le préremplissage automatique des champs de facturation
 * - Le basculement entre client existant et nouveau client
 */

jQuery(document).ready(function($) {
    var searchTimeout = null;
    var selectedClientId = null;

    /**
     * Type de client (particulier ou pro) coché par le serveur au chargement de la page :
     * pour un agent, c'est le type par défaut du plugin APC Billing. Mémorisé AVANT toute
     * remise à zéro, pour être recoché quand le client choisi n'a pas de type enregistré.
     * Vide si rien n'est coché ou si les champs pro ne sont pas affichés.
     */
    var defaultCustomerType = $('input[name="apc_customer_type"]:checked').val() || '';

    /**
     * Pays proposés pour un nouveau client ou un client sans pays : celui de la boutique
     * (agencyOrderData.defaultCountry), sinon celui affiché au chargement. Un pays vide
     * masquerait les champs SIRET et N° TVA tant qu'aucun pays n'est choisi.
     */
    var loadedCountry = String($('#billing_country').val() || '');

    function defaultBillingCountry() {
        var $country = $('#billing_country');
        var shopCountry = (window.agencyOrderData && agencyOrderData.defaultCountry) ? String(agencyOrderData.defaultCountry) : '';
        var candidates = [shopCountry, loadedCountry];
        for (var i = 0; i < candidates.length; i++) {
            var code = candidates[i];
            if (code === '') {
                continue;
            }
            // Liste déroulante : seulement un pays qu'elle propose (vente limitée à certains pays).
            if ($country.is('select') && !$country.find('option').filter(function() { return this.value === code; }).length) {
                continue;
            }
            return code;
        }
        return '';
    }

    /**
     * Message d'erreur renvoyé par le serveur (wp_send_json_error), sinon le message donné.
     *
     * @param {Object} jqXHR - Requête jQuery
     * @param {string} fallback - Message par défaut
     * @return {string}
     */
    function serverMessage(jqXHR, fallback) {
        var res = jqXHR && jqXHR.responseJSON;
        return (res && res.data && res.data.message) ? String(res.data.message) : fallback;
    }

    /**
     * Applique les données pro d'un client au bloc « Le client est » (plugin APC Billing).
     *
     * Coche le type enregistré sur le compte du client, sinon le type mémorisé au chargement,
     * sinon aucun choix. Les boutons radio sont cochés avec .prop('checked') : un .val(valeur)
     * sur le groupe réécrirait l'attribut value des deux boutons et le formulaire posterait
     * toujours la même valeur. SIRET et N° TVA prennent les valeurs du client, vides sinon,
     * pour ne jamais garder celles du client précédent. Sans champs pro sur la page (plugin
     * absent, champs désactivés ou mode test), ne fait rien.
     *
     * @param {Object} pro - Données pro du client ({ type, siret, vat }), vide pour un nouveau client
     */
    function applyProFields(pro) {
        pro = (pro && typeof pro === 'object') ? pro : {};
        var $types = $('input[name="apc_customer_type"]');

        // Décocher « Je n'ai pas de SIRET » AVANT de changer le type : cocher cette case
        // déclenche une alerte du plugin. Aucun événement ici, la case est seulement décochée.
        $('#apc_no_siret').prop('checked', false);

        if ($types.length) {
            var wanted = String(pro.type || '');
            var $target = $types.filter(function() {
                return wanted !== '' && this.value === wanted;
            });
            if (!$target.length && defaultCustomerType !== '') {
                $target = $types.filter(function() {
                    return this.value === defaultCustomerType;
                });
            }

            $types.prop('checked', false);
            if ($target.length) {
                $target.first().prop('checked', true).trigger('change');
            } else {
                // Aucun choix : le plugin masque le bloc pro sur cet événement.
                $types.first().trigger('change');
            }
        }

        // Résultats de vérification SIRET / TVA du client précédent.
        $('#apc_siret_field .apc-siret-result, #apc_vat_field .apc-vies-result').empty();

        $('#apc_siret').val(String(pro.siret || '')).trigger('input').trigger('change');
        $('#apc_vat').val(String(pro.vat || '')).trigger('input').trigger('change');
    }

    /**
     * Bascule l'affichage entre les champs "client existant" et "nouveau client".
     */
    function toggleClientFields() {
        var selectedType = $('input[name="agency_client_type"]:checked').val();

        if (selectedType === 'existing') {
            $('#existing-client-field').show();
            $('#new-client-field').hide();
            $('#agency_existing_client_id').prop('required', true);
            $('#agency_new_client_email').prop('required', false);
        } else if (selectedType === 'new') {
            $('#existing-client-field').hide();
            $('#new-client-field').show();
            $('#agency_existing_client_id').prop('required', false);
            $('#agency_new_client_email').prop('required', true);
            // Reset client selection et vider les champs de facturation
            resetClientSelection();
            clearBillingFields();
        }
    }

    /**
     * Réinitialise la sélection du client.
     */
    function resetClientSelection() {
        selectedClientId = null;
        $('#agency_existing_client_id').val('');
        $('#agency-selected-client').addClass('hidden').html('');
        $('#agency-client-results').addClass('hidden').html('');
    }

    /**
     * Recherche des clients via AJAX.
     *
     * @param {string} term - Terme de recherche (nom, email ou username)
     */
    function searchCustomers(term) {
        if (term.length < 2) {
            $('#agency-client-results').addClass('hidden').html('');
            return;
        }

        $.ajax({
            url: agencyOrderData.ajaxUrl,
            type: 'GET',
            data: {
                action: 'agency_search_customers',
                nonce: agencyOrderData.nonce,
                term: term
            },
            success: function(response) {
                var $results = $('#agency-client-results').removeClass('hidden').empty();
                if (response && response.success && Array.isArray(response.data) && response.data.length > 0) {
                    // Nom et e-mail saisis par les clients : insérés comme texte et attributs,
                    // jamais comme HTML (un guillemet ou une balise ne peut rien exécuter).
                    var $list = $('<ul class="divide-y divide-gray-200"></ul>');
                    response.data.forEach(function(customer) {
                        var name = String(customer.name || '');
                        var email = String(customer.email || '');
                        $('<li class="p-3 hover:bg-blue-50 cursor-pointer customer-result"></li>')
                            .attr({
                                'data-id': String(parseInt(customer.id, 10) || ''),
                                'data-name': name,
                                'data-email': email
                            })
                            .append($('<div class="font-medium text-gray-900"></div>').text(name))
                            .append($('<div class="text-sm text-gray-600"></div>').text(email))
                            .appendTo($list);
                    });
                    $results.append($list);
                } else {
                    $results.append($('<div class="p-3 text-gray-500 text-center"></div>').text('Aucun client trouvé'));
                }
            },
            error: function(jqXHR) {
                $('#agency-client-results').removeClass('hidden').empty()
                    .append($('<div class="p-3 text-red-500 text-center"></div>').text(serverMessage(jqXHR, 'Erreur de recherche')));
            }
        });
    }

    /**
     * Sélectionne un client et affiche ses informations.
     *
     * @param {number} id - ID du client
     * @param {string} name - Nom du client
     * @param {string} email - Email du client
     */
    function selectCustomer(id, name, email) {
        selectedClientId = id;
        $('#agency_existing_client_id').val(id);
        $('#agency_existing_client_search').val('');
        $('#agency-client-results').addClass('hidden').html('');

        // Nom et e-mail insérés comme texte, jamais comme HTML.
        var $info = $('<div></div>')
            .append($('<div class="font-medium text-gray-900"></div>').text('✓ Client sélectionné : ' + String(name || '')))
            .append($('<div class="text-sm text-gray-600"></div>').text(String(email || '')));
        var $row = $('<div class="flex items-center justify-between"></div>')
            .append($info)
            .append('<button type="button" class="text-red-600 hover:text-red-800 font-medium" id="clear-client-selection">✕ Changer</button>');

        $('#agency-selected-client').removeClass('hidden').empty().append($row);

        // Charger les données de facturation du client
        loadCustomerBillingData(id);
    }

    /**
     * Charge les données de facturation d'un client via AJAX et prérempli les champs.
     *
     * @param {number} customerId - ID du client
     */
    function loadCustomerBillingData(customerId) {
        $.ajax({
            url: agencyOrderData.ajaxUrl,
            type: 'GET',
            data: {
                action: 'agency_get_customer_billing',
                nonce: agencyOrderData.nonce,
                customer_id: customerId
            },
            success: function(response) {
                // Réponse d'un client qui n'est plus celui sélectionné (changement rapide de
                // client ou sélection annulée) : ne rien appliquer.
                if (customerId !== selectedClientId) {
                    return;
                }

                if (response.success && response.data) {
                    var billingData = response.data;

                    // Le SIRET du thème ne doit jamais rester celui du client précédent :
                    // vidé si la réponse ne le fournit pas.
                    if (!Object.prototype.hasOwnProperty.call(billingData, 'billing_siret')) {
                        billingData.billing_siret = '';
                    }

                    // Préremplir tous les champs de facturation
                    $.each(billingData, function(key, value) {
                        // Champs pro du plugin APC Billing traités à part (applyProFields) :
                        // jamais de .val() sur le groupe de boutons radio.
                        if (key === 'pro' || key.indexOf('apc_') === 0) {
                            return;
                        }
                        // Client sans pays enregistré : pays par défaut, pas un pays vide.
                        if (key === 'billing_country' && !value) {
                            value = defaultBillingCountry();
                        }

                        var field = $('[name="' + key + '"]');
                        if (!field.length) {
                            field = $('#' + key);
                        }
                        if (field.length) {
                            field.val(value).trigger('input').trigger('change');
                        }
                    });

                    // Type, SIRET et N° TVA du client, après le pays (déjà appliqué ci-dessus)
                    applyProFields(billingData.pro);

                    // Déclencher l'événement update_checkout pour recalculer les frais
                    $('body').trigger('update_checkout');
                }
            },
            error: function(jqXHR) {
                if (customerId !== selectedClientId) {
                    return;
                }
                console.error('Erreur lors du chargement des données de facturation');
                $('#agency-selected-client')
                    .append($('<div class="text-sm text-red-600 mt-1"></div>').text(serverMessage(jqXHR, 'Données du client indisponibles.')));
            }
        });
    }

    /**
     * Vide tous les champs de facturation.
     */
    function clearBillingFields() {
        // Liste des champs de facturation à vider
        var billingFields = [
            'billing_first_name',
            'billing_last_name',
            'billing_company',
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_postcode',
            'billing_country',
            'billing_state',
            'billing_phone',
            'billing_email',
            'billing_siret'
        ];

        billingFields.forEach(function(fieldName) {
            var field = $('[name="' + fieldName + '"]');
            if (!field.length) {
                field = $('#' + fieldName);
            }
            if (field.length) {
                // Pays : celui de la boutique plutôt qu'un pays vide (champs SIRET et N° TVA).
                field.val(fieldName === 'billing_country' ? defaultBillingCountry() : '').trigger('input').trigger('change');
            }
        });

        // Bloc pro : type mémorisé au chargement, SIRET et N° TVA vides
        applyProFields({});

        // Déclencher l'événement update_checkout
        $('body').trigger('update_checkout');
    }

    // ========== Event Listeners ==========

    // Initialisation
    toggleClientFields();

    // Écoute des changements de type de client
    $('input[name="agency_client_type"]').on('change', toggleClientFields);

    // Recherche avec debounce
    $('#agency_existing_client_search').on('input', function() {
        var term = $(this).val();
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            searchCustomers(term);
        }, 300);
    });

    // Sélection d'un client
    $(document).on('click', '.customer-result', function() {
        var id = $(this).data('id');
        // attr() et non data() : texte brut, sans conversion (nombre, JSON) par jQuery.
        var name = $(this).attr('data-name') || '';
        var email = $(this).attr('data-email') || '';
        selectCustomer(id, name, email);
    });

    // Réinitialiser la sélection
    $(document).on('click', '#clear-client-selection', function(e) {
        e.preventDefault();
        resetClientSelection();
        $('#agency_existing_client_search').focus();
    });

    // Cacher les résultats si on clique ailleurs
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#existing-client-field').length) {
            $('#agency-client-results').addClass('hidden');
        }
    });
});
