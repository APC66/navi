<?php

namespace App\Admin;

/**
 * Gestion de l'export CSV des commandes WooCommerce avec filtre par Agent créateur
 */
class OrdersExportPage
{
    /**
     * Initialise les hooks pour le filtre et l'export
     */
    public function init(): void
    {
        // Ajout du filtre par Agent créateur sur la page des commandes
        add_action('restrict_manage_posts', [$this, 'addAgencyCreatorFilter'], 10, 2);
        add_filter('request', [$this, 'filterOrdersByAgencyCreator']);

        // Support HPOS (High-Performance Order Storage)
        add_action('woocommerce_order_list_table_restrict_manage_orders', [$this, 'addAgencyCreatorFilterHPOS']);
        add_filter('woocommerce_orders_table_query_clauses', [$this, 'filterOrdersByAgencyCreatorHPOS'], 10, 2);

        // Ajout de l'action d'export en masse
        add_filter('bulk_actions-edit-shop_order', [$this, 'addBulkExportAction']);
        add_filter('bulk_actions-woocommerce_page_wc-orders', [$this, 'addBulkExportAction']);

        add_filter('handle_bulk_actions-edit-shop_order', [$this, 'handleBulkExport'], 10, 3);
        add_filter('handle_bulk_actions-woocommerce_page_wc-orders', [$this, 'handleBulkExport'], 10, 3);
    }

    /**
     * Ajoute un filtre dropdown pour l'Agent créateur (Legacy)
     */
    public function addAgencyCreatorFilter(string $post_type, string $which): void
    {
        if ($post_type !== 'shop_order') {
            return;
        }

        $this->renderAgencyCreatorDropdown();
    }

    /**
     * Ajoute un filtre dropdown pour l'Agent créateur (HPOS)
     */
    public function addAgencyCreatorFilterHPOS(): void
    {
        $this->renderAgencyCreatorDropdown();
    }

    /**
     * Rendu du dropdown de filtre
     */
    private function renderAgencyCreatorDropdown(): void
    {
        global $wpdb;

        // Récupération des agents ayant créé des commandes (table HPOS)
        $agents = $wpdb->get_results("
            SELECT DISTINCT om.meta_value as user_id, u.display_name, u.user_login
            FROM {$wpdb->prefix}wc_orders_meta om
            INNER JOIN {$wpdb->users} u ON om.meta_value = u.ID
            WHERE om.meta_key = '_agency_creator_id'
            AND om.meta_value != ''
            ORDER BY u.display_name ASC
        ");

        $selected = isset($_GET['agency_creator_filter']) ? (int) $_GET['agency_creator_filter'] : 0;

        echo '<select name="agency_creator_filter" id="agency_creator_filter">';
        echo '<option value="">Tous les agents créateurs</option>';
        echo '<option value="-1"'.selected($selected, -1, false).'>Client Direct (sans agent)</option>';

        foreach ($agents as $agent) {
            $label = $agent->display_name ?: $agent->user_login;
            echo '<option value="'.esc_attr($agent->user_id).'"'.selected($selected, $agent->user_id, false).'>';
            echo esc_html($label);
            echo '</option>';
        }

        echo '</select>';
    }

    /**
     * Filtre les commandes par Agent créateur (Legacy)
     */
    public function filterOrdersByAgencyCreator(array $vars): array
    {
        global $typenow;

        if ($typenow !== 'shop_order') {
            return $vars;
        }

        if (! isset($_GET['agency_creator_filter']) || $_GET['agency_creator_filter'] === '') {
            return $vars;
        }

        $filter_value = (int) $_GET['agency_creator_filter'];

        if ($filter_value === -1) {
            // Commandes sans agent (Client Direct)
            $vars['meta_query'] = [
                'relation' => 'OR',
                [
                    'key' => '_agency_creator_id',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key' => '_agency_creator_id',
                    'value' => '',
                    'compare' => '=',
                ],
            ];
        } else {
            // Commandes d'un agent spécifique
            $vars['meta_key'] = '_agency_creator_id';
            $vars['meta_value'] = $filter_value;
        }

        return $vars;
    }

    /**
     * Filtre les commandes par Agent créateur (HPOS)
     */
    public function filterOrdersByAgencyCreatorHPOS(array $clauses, \Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableQuery $query): array
    {
        if (! isset($_GET['agency_creator_filter']) || $_GET['agency_creator_filter'] === '') {
            return $clauses;
        }

        global $wpdb;
        $filter_value = (int) $_GET['agency_creator_filter'];

        if ($filter_value === -1) {
            // Commandes sans agent
            $clauses['join'] .= " LEFT JOIN {$wpdb->prefix}wc_orders_meta AS om_agency ON {$wpdb->prefix}wc_orders.id = om_agency.order_id AND om_agency.meta_key = '_agency_creator_id'";
            $clauses['where'] .= " AND (om_agency.meta_value IS NULL OR om_agency.meta_value = '')";
        } else {
            // Commandes d'un agent spécifique
            $clauses['join'] .= " INNER JOIN {$wpdb->prefix}wc_orders_meta AS om_agency ON {$wpdb->prefix}wc_orders.id = om_agency.order_id";
            $clauses['where'] .= $wpdb->prepare(" AND om_agency.meta_key = '_agency_creator_id' AND om_agency.meta_value = %d", $filter_value);
        }

        return $clauses;
    }

    /**
     * Ajoute l'action d'export CSV dans le menu des actions en masse
     */
    public function addBulkExportAction(array $actions): array
    {
        $actions['export_orders_csv'] = '📊 Exporter en CSV';

        return $actions;
    }

    /**
     * Gère l'export CSV des commandes sélectionnées
     */
    public function handleBulkExport(string $redirect_to, string $action, array $order_ids): string
    {
        if ($action !== 'export_orders_csv') {
            return $redirect_to;
        }

        if (empty($order_ids)) {
            return $redirect_to;
        }

        // Génération du CSV
        $this->generateOrdersCsv($order_ids);
        exit;
    }

    /**
     * Génère le fichier CSV avec les colonnes demandées
     */
    private function generateOrdersCsv(array $order_ids): void
    {
        $filename = 'export-commandes-'.date('Y-m-d-His').'.csv';

        // Headers pour le téléchargement
        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename='.$filename);

        $output = fopen('php://output', 'w');

        // BOM UTF-8 pour Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // En-têtes des colonnes
        fputcsv($output, [
            'Statut',
            'Nom Client',
            'Email',
            'N° Commande',
            'Téléphone',
            'Montant',
            'Nom de Croisière',
            'Jour de Croisière',
            'Nb de Participants (Détail)',
            'Restant à Payer',
            'Agent Créateur',
            'Commentaire',
        ], ';');

        // Données des commandes
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);

            if (! $order) {
                continue;
            }

            // Récupération des informations de base
            $status = wc_get_order_status_name($order->get_status());
            $customer_name = $order->get_formatted_billing_full_name() ?: 'Client Invité';
            $email = $order->get_billing_email();
            $order_number = $order->get_order_number();
            $phone = $order->get_billing_phone();
            $total = $order->get_total();

            // Récupération de l'agent créateur
            $creator_id = $order->get_meta('_agency_creator_id');
            $agent_name = 'Client Direct';

            if ($creator_id) {
                $creator = get_user_by('id', (int) $creator_id);
                if ($creator) {
                    $agent_name = $creator->display_name ?: $creator->user_login;
                }
            }

            // Récupération du restant à payer
            $balance_due = (float) $order->get_meta('_balance_due', true);

            // Récupération des informations de croisière et participants
            $cruise_name = '';
            $cruise_date = '';
            $participants_detail = '';

            foreach ($order->get_items() as $item) {
                $sailing_id = $item->get_meta('_sailing_id');

                if ($sailing_id) {
                    // Nom de la croisière (depuis le produit)
                    $product = $item->get_product();
                    if ($product) {
                        $cruise_name = $product->get_name();
                    }

                    // Date de départ
                    $cruise_date = $item->get_meta('Date de départ') ?: '';

                    // Détail des participants
                    $raw_data = $item->get_meta('_booking_data_raw');
                    $data = json_decode($raw_data, true) ?: [];

                    if (isset($data['passengers']) && is_array($data['passengers'])) {
                        $participants = [];
                        foreach ($data['passengers'] as $type_id => $qty) {
                            if ($qty > 0) {
                                $term = get_term($type_id, 'passenger_type');
                                $type_name = ($term && ! is_wp_error($term)) ? $term->name : 'Passager';
                                $participants[] = "{$qty}x {$type_name}";
                            }
                        }
                        $participants_detail = implode(', ', $participants);
                    }

                    // On prend seulement le premier sailing (une commande = une croisière généralement)
                    break;
                }
            }

            // Commentaire (note client + note privée)
            $customer_note = $order->get_customer_note();
            $private_note = $order->get_meta('_private_boarding_note');
            $comment = trim($customer_note.($customer_note && $private_note ? ' | ' : '').$private_note);

            // Écriture de la ligne CSV
            fputcsv($output, [
                $status,
                $customer_name,
                $email,
                $order_number,
                $phone,
                $total,
                $cruise_name,
                $cruise_date,
                $participants_detail,
                $balance_due,
                $agent_name,
                $comment,
            ], ';');
        }

        fclose($output);
    }
}
