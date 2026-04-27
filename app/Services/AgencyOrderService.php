<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service gérant les commandes pour compte tiers (B2B/Agences).
 *
 * Permet aux utilisateurs avec la capability 'place_agency_orders' de passer
 * des commandes pour le compte de clients finaux sans paiement immédiat.
 */
class AgencyOrderService
{
    /**
     * Initialise les hooks WooCommerce pour la fonctionnalité agence.
     */
    public function init(): void
    {
        // Bypass du paiement pour les utilisateurs avec capability
        add_filter('woocommerce_cart_needs_payment', [$this, 'bypassPaymentForAgency'], 10, 2);

        // Ajout des champs de sélection client au checkout
        add_action('woocommerce_before_checkout_billing_form', [$this, 'addClientSelectionFields']);

        // Validation des données de sélection client
        add_action('woocommerce_after_checkout_validation', [$this, 'validateClientSelection'], 10, 2);

        // Attribution de la commande au client sélectionné
        add_action('woocommerce_checkout_create_order', [$this, 'assignOrderToClient'], 10, 2);

        // Enregistrement de l'agent créateur dans les meta-données
        add_action('woocommerce_checkout_order_created', [$this, 'trackAgencyCreator'], 10, 1);

        // Ajout d'une colonne admin pour afficher l'agent créateur
        add_filter('manage_edit-shop_order_columns', [$this, 'addAgencyCreatorColumn'], 20);
        add_action('manage_shop_order_posts_custom_column', [$this, 'displayAgencyCreatorColumn'], 10, 2);

        // Support HPOS (High-Performance Order Storage)
        add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'addAgencyCreatorColumn'], 20);
        add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'displayAgencyCreatorColumnHPOS'], 10, 2);

        // Enqueue des scripts pour le checkout
        add_action('wp_enqueue_scripts', [$this, 'enqueueCheckoutScripts']);

        // Support Vite : ajout de l'attribut type="module" au script
        add_filter('script_loader_tag', [$this, 'addModuleTypeToViteScript'], 10, 2);

        // AJAX endpoint pour la recherche de clients
        add_action('wp_ajax_agency_search_customers', [$this, 'ajaxSearchCustomers']);

        // AJAX endpoint pour récupérer les données de facturation d'un client
        add_action('wp_ajax_agency_get_customer_billing', [$this, 'ajaxGetCustomerBillingData']);
    }

    /**
     * Vérifie si l'utilisateur actuel possède la capability 'place_agency_orders'.
     */
    protected function currentUserCanPlaceAgencyOrders(): bool
    {
        return current_user_can('place_agency_orders');
    }

    /**
     * Bypass du paiement si l'utilisateur a la capability 'place_agency_orders'.
     */
    public function bypassPaymentForAgency(bool $needs_payment, \WC_Cart $cart): bool
    {
        if ($this->currentUserCanPlaceAgencyOrders()) {
            return false;
        }

        return $needs_payment;
    }

    /**
     * Ajoute les champs de sélection client au checkout pour les agents.
     */
    public function addClientSelectionFields(\WC_Checkout $checkout): void
    {
        if (! $this->currentUserCanPlaceAgencyOrders()) {
            return;
        }

        echo '<div class="agency-client-selection border border-primary-400 rounded-lg p-6 mb-8">';
        echo '<h3 class="text-xl font-bold mb-4">🏢 Commande pour le compte d\'un client</h3>';

        // Champ de sélection : client existant ou nouveau
        woocommerce_form_field('agency_client_type', [
            'type' => 'radio',
            'class' => ['form-row-wide', 'agency-client-type-field'],
            'label' => 'Type de client',
            'required' => true,
            'options' => [
                'existing' => 'Client existant',
                'new' => 'Nouveau client',
            ],
            'default' => 'existing',
        ], $checkout->get_value('agency_client_type') ?: 'existing');

        // Champ de recherche pour client existant (recherche AJAX)
        echo '<div id="existing-client-field" class="existing-client-wrapper">';
        woocommerce_form_field('agency_existing_client_search', [
            'type' => 'text',
            'class' => ['form-row-wide', 'agency-client-search-input'],
            'label' => 'Rechercher un client',
            'placeholder' => 'Tapez l\'email du client...',
            'required' => false,
            'custom_attributes' => [
                'autocomplete' => 'off',
            ],
        ], $checkout->get_value('agency_existing_client_search'));

        echo '<input type="hidden" name="agency_existing_client_id" id="agency_existing_client_id" value="'.esc_attr($checkout->get_value('agency_existing_client_id')).'" />';
        echo '<div id="agency-client-results" class="hidden mt-2 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"></div>';
        echo '<div id="agency-selected-client" class="hidden mt-2 p-3 bg-green-50 border border-green-300 rounded-lg"></div>';
        echo '</div>';

        // Champ email pour nouveau client
        echo '<div id="new-client-field" class="new-client-wrapper" style="display:none;">';
        woocommerce_form_field('agency_new_client_email', [
            'type' => 'email',
            'class' => ['form-row-wide'],
            'label' => 'Email du nouveau client',
            'placeholder' => 'client@example.com',
            'required' => true,
        ], $checkout->get_value('agency_new_client_email'));
        echo '<p class="text-sm text-gray-600 mt-2">ℹ️ Un compte client sera créé automatiquement avec les informations de facturation saisies ci-dessous.</p>';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Cherche une valeur peu importe comment WooCommerce l'a postée.
     */
    protected function getCheckoutFieldValue(string $field_name): string
    {
        // 1. Cas classique (Standard POST)
        if (isset($_POST[$field_name])) {
            return sanitize_text_field(wp_unslash($_POST[$field_name]));
        }

        // 2. Cas "post_data" (Quand le JS de WooCommerce sérialise tout)
        if (isset($_POST['post_data'])) {
            parse_str(wp_unslash($_POST['post_data']), $post_data);
            if (isset($post_data[$field_name])) {
                return sanitize_text_field($post_data[$field_name]);
            }
        }

        return '';
    }

    /**
     * Valide les données de sélection client au checkout.
     */
    public function validateClientSelection(array $data, \WP_Error $errors): void
    {
        if (! $this->currentUserCanPlaceAgencyOrders()) {
            return;
        }

        $client_type = $this->getCheckoutFieldValue('agency_client_type');

        if (empty($client_type)) {
            $errors->add('validation', 'Veuillez sélectionner le type de client (existant ou nouveau).');

            return;
        }

        if ($client_type === 'existing') {
            $client_id = $this->getCheckoutFieldValue('agency_existing_client_id');

            if (empty($client_id) || ! is_numeric($client_id)) {
                $errors->add('validation', 'Veuillez sélectionner un client existant.');

                return;
            }

            $user = get_user_by('id', (int) $client_id);
            if (! $user || ! in_array('customer', $user->roles, true)) {
                $errors->add('validation', 'Le client sélectionné n\'est pas valide.');
            }
        } elseif ($client_type === 'new') {
            $email = sanitize_email($this->getCheckoutFieldValue('agency_new_client_email'));

            if (empty($email) || ! is_email($email)) {
                $errors->add('validation', 'Veuillez fournir un email valide pour le nouveau client.');

                return;
            }

            if (email_exists($email)) {
                $errors->add('validation', 'Un compte avec cet email existe déjà. Veuillez sélectionner "Client existant".');
            }
        }
    }

    /**
     * Attribue la commande au client sélectionné ou crée un nouveau client.
     */
    public function assignOrderToClient(\WC_Order $order, array $data): void
    {
        if (! $this->currentUserCanPlaceAgencyOrders()) {
            return;
        }

        $client_type = $this->getCheckoutFieldValue('agency_client_type');

        if ($client_type === 'existing') {
            $client_id = (int) $this->getCheckoutFieldValue('agency_existing_client_id');
            if ($client_id > 0) {
                $order->set_customer_id($client_id);
                $order->update_meta_data('_agency_final_customer_id', $client_id);
                $this->updateExistingCustomerBilling($client_id, $data);
            }
        } elseif ($client_type === 'new') {
            $email = sanitize_email($this->getCheckoutFieldValue('agency_new_client_email'));
            if (! empty($email) && is_email($email)) {
                $client_id = $this->createNewCustomer($email, $data);
                if ($client_id > 0) {
                    $order->set_customer_id($client_id);
                    $order->update_meta_data('_agency_final_customer_id', $client_id);
                }
            }
        }
    }

    /**
     * Met à jour les données de facturation d'un client existant.
     */
    protected function updateExistingCustomerBilling(int $client_id, array $data): void
    {
        $billing_fields = [
            'first_name' => $data['billing_first_name'] ?? '',
            'last_name' => $data['billing_last_name'] ?? '',
            'company' => $data['billing_company'] ?? '',
            'address_1' => $data['billing_address_1'] ?? '',
            'address_2' => $data['billing_address_2'] ?? '',
            'city' => $data['billing_city'] ?? '',
            'postcode' => $data['billing_postcode'] ?? '',
            'country' => $data['billing_country'] ?? '',
            'state' => $data['billing_state'] ?? '',
            'phone' => $data['billing_phone'] ?? '',
            'email' => $data['billing_email'] ?? '',
        ];

        foreach ($billing_fields as $key => $value) {
            if (! empty($value)) {
                update_user_meta($client_id, 'billing_'.$key, sanitize_text_field($value));
            }
        }

        $display_name = trim(($billing_fields['first_name']).' '.($billing_fields['last_name']));
        if (! empty($display_name)) {
            wp_update_user([
                'ID' => $client_id,
                'display_name' => $display_name,
                'first_name' => $billing_fields['first_name'],
                'last_name' => $billing_fields['last_name'],
            ]);
        }
    }

    /**
     * Crée un nouveau compte client WordPress.
     */
    protected function createNewCustomer(string $email, array $data): int
    {
        $username = sanitize_user(current(explode('@', $email)), true);
        $username_base = $username;
        $counter = 1;

        while (username_exists($username)) {
            $username = $username_base.$counter;
            $counter++;
        }

        $password = wp_generate_password(12, true, true);
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wc_add_notice('Erreur lors de la création du compte client : '.$user_id->get_error_message(), 'error');

            return 0;
        }

        $user = new \WP_User($user_id);
        $user->set_role('customer');

        $billing_fields = [
            'first_name' => $data['billing_first_name'] ?? '',
            'last_name' => $data['billing_last_name'] ?? '',
            'company' => $data['billing_company'] ?? '',
            'address_1' => $data['billing_address_1'] ?? '',
            'address_2' => $data['billing_address_2'] ?? '',
            'city' => $data['billing_city'] ?? '',
            'postcode' => $data['billing_postcode'] ?? '',
            'country' => $data['billing_country'] ?? '',
            'state' => $data['billing_state'] ?? '',
            'phone' => $data['billing_phone'] ?? '',
            'email' => $email,
        ];

        foreach ($billing_fields as $key => $value) {
            update_user_meta($user_id, 'billing_'.$key, sanitize_text_field($value));
        }

        $display_name = trim(($billing_fields['first_name'] ?? '').' '.($billing_fields['last_name'] ?? ''));
        if (! empty($display_name)) {
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $display_name,
            ]);
        }

        wp_new_user_notification($user_id, null, 'user');

        return $user_id;
    }

    /**
     * Enregistre l'ID de l'agent créateur dans les meta-données de la commande.
     * Ré-applique également le customer_id du client final pour garantir qu'il
     * n'a pas été écrasé par WooCommerce entre les deux hooks.
     */
    public function trackAgencyCreator(\WC_Order $order): void
    {
        if (! $this->currentUserCanPlaceAgencyOrders()) {
            return;
        }

        // Ré-appliquer le customer_id du client final (protection contre écrasement WooCommerce)
        $final_customer_id = (int) $order->get_meta('_agency_final_customer_id');
        if ($final_customer_id > 0) {
            $order->set_customer_id($final_customer_id);
            $order->delete_meta_data('_agency_final_customer_id');
        }

        $current_user_id = get_current_user_id();
        if ($current_user_id > 0) {
            $order->update_meta_data('_agency_creator_id', $current_user_id);
            $order->save();
        }
    }

    /**
     * Ajoute une colonne "Agent Créateur" dans la liste des commandes (admin).
     */
    public function addAgencyCreatorColumn(array $columns): array
    {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'order_status') {
                $new_columns['agency_creator'] = '🏢 Agent Créateur';
            }
        }

        return $new_columns;
    }

    /**
     * Affiche le contenu de la colonne "Agent Créateur" (legacy).
     */
    public function displayAgencyCreatorColumn(string $column, int $post_id): void
    {
        if ($column !== 'agency_creator') {
            return;
        }

        $order = wc_get_order($post_id);
        if (! $order) {
            echo '—';

            return;
        }

        $this->renderAgencyCreatorCell($order);
    }

    /**
     * Affiche le contenu de la colonne "Agent Créateur" (HPOS).
     */
    public function displayAgencyCreatorColumnHPOS(string $column, \WC_Order $order): void
    {
        if ($column !== 'agency_creator') {
            return;
        }

        $this->renderAgencyCreatorCell($order);
    }

    /**
     * Rendu du contenu de la cellule "Agent Créateur".
     */
    protected function renderAgencyCreatorCell(\WC_Order $order): void
    {
        $creator_id = $order->get_meta('_agency_creator_id');

        if (empty($creator_id)) {
            echo '<span class="text-gray-500">Client Direct</span>';

            return;
        }

        $creator = get_user_by('id', (int) $creator_id);
        if (! $creator) {
            echo '<span class="text-gray-500">—</span>';

            return;
        }

        printf(
            '<strong>%s</strong><br><small class="text-gray-600">%s</small>',
            esc_html($creator->display_name ?: $creator->user_login),
            esc_html($creator->user_email)
        );
    }

    /**
     * Recherche AJAX de clients par nom, prénom, email ou username.
     */
    public function ajaxSearchCustomers(): void
    {
        if (! $this->currentUserCanPlaceAgencyOrders()) {
            wp_send_json_error(['message' => 'Accès non autorisé.'], 403);
        }

        $search_term = sanitize_text_field($_GET['term'] ?? '');

        if (strlen($search_term) < 2) {
            wp_send_json_success([]);

            return;
        }

        global $wpdb;

        $search_like = '%'.$wpdb->esc_like($search_term).'%';

        $query = "
            SELECT DISTINCT u.ID, u.user_email, u.display_name, u.user_login,
                   MAX(CASE WHEN um2.meta_key = 'billing_first_name' THEN um2.meta_value END) as first_name,
                   MAX(CASE WHEN um2.meta_key = 'billing_last_name' THEN um2.meta_value END) as last_name
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id
                AND um2.meta_key IN ('billing_first_name', 'billing_last_name')
            WHERE um.meta_key = '{$wpdb->prefix}capabilities'
            AND um.meta_value LIKE '%customer%'
            AND (
                u.display_name LIKE %s
                OR u.user_email LIKE %s
                OR u.user_login LIKE %s
                OR um2.meta_value LIKE %s
            )
            GROUP BY u.ID
            ORDER BY u.display_name ASC
            LIMIT 20
        ";

        $results = $wpdb->get_results(
            $wpdb->prepare($query, $search_like, $search_like, $search_like, $search_like)
        );

        $customers = [];
        foreach ($results as $user) {
            $full_name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
            $display_name = ! empty($full_name) ? $full_name : ($user->display_name ?: $user->user_login);

            $customers[] = [
                'id' => (int) $user->ID,
                'name' => $display_name,
                'email' => $user->user_email,
                'label' => sprintf('%s (%s)', $display_name, $user->user_email),
            ];
        }

        wp_send_json_success($customers);
    }

    /**
     * Récupère les données de facturation d'un client via AJAX.
     */
    public function ajaxGetCustomerBillingData(): void
    {
        if (! $this->currentUserCanPlaceAgencyOrders()) {
            wp_send_json_error(['message' => 'Accès non autorisé.'], 403);
        }

        $customer_id = (int) ($_GET['customer_id'] ?? 0);

        if ($customer_id <= 0) {
            wp_send_json_error(['message' => 'ID client invalide.'], 400);
        }

        $user = get_user_by('id', $customer_id);

        if (! $user || ! in_array('customer', $user->roles, true)) {
            wp_send_json_error(['message' => 'Client non trouvé.'], 404);
        }

        // Récupération avec fallback sur les infos WordPress classiques si billing est vide
        $billing_data = [
            'billing_first_name' => get_user_meta($customer_id, 'billing_first_name', true) ?: get_user_meta($customer_id, 'first_name', true),
            'billing_last_name' => get_user_meta($customer_id, 'billing_last_name', true) ?: get_user_meta($customer_id, 'last_name', true),
            'billing_company' => get_user_meta($customer_id, 'billing_company', true),
            'billing_address_1' => get_user_meta($customer_id, 'billing_address_1', true),
            'billing_address_2' => get_user_meta($customer_id, 'billing_address_2', true),
            'billing_city' => get_user_meta($customer_id, 'billing_city', true),
            'billing_postcode' => get_user_meta($customer_id, 'billing_postcode', true),
            'billing_country' => get_user_meta($customer_id, 'billing_country', true),
            'billing_state' => get_user_meta($customer_id, 'billing_state', true),
            'billing_phone' => get_user_meta($customer_id, 'billing_phone', true),
            'billing_email' => $user->user_email,
        ];

        wp_send_json_success($billing_data);
    }

    /**
     * Enqueue les scripts pour Vite/Radicle.
     */
    public function enqueueCheckoutScripts(): void
    {
        if (! is_checkout() || ! $this->currentUserCanPlaceAgencyOrders()) {
            return;
        }

        $asset_path = 'resources/js/components/agency-order-checkout.js';
        $asset_url = \Roots\asset($asset_path)->uri();

        wp_enqueue_script(
            'agency-order-checkout',
            $asset_url,
            ['jquery'],
            null,
            true
        );

        wp_localize_script('agency-order-checkout', 'agencyOrderData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('agency_search_customers'),
        ]);
    }

    /**
     * Ajoute l'attribut type="module" au script chargé par Vite.
     */
    public function addModuleTypeToViteScript(string $tag, string $handle): string
    {
        if ($handle === 'agency-order-checkout') {
            return str_replace(' src', ' type="module" src', $tag);
        }

        return $tag;
    }
}
