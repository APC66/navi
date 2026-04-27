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
    console.log('vjios');

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
                term: term
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    var html = '<ul class="divide-y divide-gray-200">';
                    response.data.forEach(function(customer) {
                        html += '<li class="p-3 hover:bg-blue-50 cursor-pointer customer-result" data-id="' + customer.id + '" data-name="' + customer.name + '" data-email="' + customer.email + '">';
                        html += '<div class="font-medium text-gray-900">' + customer.name + '</div>';
                        html += '<div class="text-sm text-gray-600">' + customer.email + '</div>';
                        html += '</li>';
                    });
                    html += '</ul>';
                    $('#agency-client-results').removeClass('hidden').html(html);
                } else {
                    $('#agency-client-results').removeClass('hidden').html('<div class="p-3 text-gray-500 text-center">Aucun client trouvé</div>');
                }
            },
            error: function() {
                $('#agency-client-results').removeClass('hidden').html('<div class="p-3 text-red-500 text-center">Erreur de recherche</div>');
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

        var html = '<div class="flex items-center justify-between">';
        html += '<div>';
        html += '<div class="font-medium text-gray-900">✓ Client sélectionné : ' + name + '</div>';
        html += '<div class="text-sm text-gray-600">' + email + '</div>';
        html += '</div>';
        html += '<button type="button" class="text-red-600 hover:text-red-800 font-medium" id="clear-client-selection">✕ Changer</button>';
        html += '</div>';

        $('#agency-selected-client').removeClass('hidden').html(html);

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
                customer_id: customerId
            },
            success: function(response) {
                if (response.success && response.data) {
                    var billingData = response.data;

                    // Préremplir tous les champs de facturation
                    $.each(billingData, function(key, value) {
                        var field = $('[name="' + key + '"]');
                        if (!field.length) {
                            field = $('#' + key);
                        }
                        if (field.length) {
                            field.val(value).trigger('input').trigger('change');
                        }
                    });

                    // Déclencher l'événement update_checkout pour recalculer les frais
                    $('body').trigger('update_checkout');
                }
            },
            error: function() {
                console.error('Erreur lors du chargement des données de facturation');
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
            'billing_email'
        ];

        billingFields.forEach(function(fieldName) {
            var field = $('[name="' + fieldName + '"]');
            if (!field.length) {
                field = $('#' + fieldName);
            }
            if (field.length) {
                field.val('').trigger('input').trigger('change');
            }
        });

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
        var name = $(this).data('name');
        var email = $(this).data('email');
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
