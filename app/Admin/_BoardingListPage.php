<?php

namespace App\Admin;

use App\Models\Sailing;
use WC_Coupon;
use function Roots\view;

class BoardingListPage
{
    /**
     * Point d'entrée : Enregistrement du menu et des hooks
     */
    public function __invoke()
    {
        add_submenu_page(
            'navi-planning',
            'Liste d\'Embarquement',
            'Liste Embarquement',
            'edit_posts',
            'navi-boarding-list',
            [$this, 'render']
        );

        add_action('admin_init', [$this, 'handleCsvExport']);
        add_action('admin_init', [$this, 'handleFormActions']);
    }

    /**
     * Routeur d'affichage (Liste ou Édition)
     */
    public function render()
    {
        $action = $_GET['action'] ?? 'view';

        // --- VUE ÉDITION ---
        if ($action === 'edit' && isset($_GET['order_id'])) {
            $this->renderEdit((int)$_GET['order_id']);
            return;
        }

        // --- VUE LISTE (Défaut) ---
        $this->renderList();
    }

    /**
     * Affiche le formulaire d'édition d'une réservation
     */
    private function renderEdit($orderId)
    {
        $order = wc_get_order($orderId);
        if (!$order) {
            wp_die('Commande introuvable.', 'Erreur', ['response' => 404]);
        }

        // On récupère l'item de commande lié au booking
        $lineItem = null;
        $currentSailingId = null;

        foreach ($order->get_items() as $item) {
            if ($sid = $item->get_meta('_sailing_id')) {
                $currentSailingId = $sid;
                $lineItem = $item;
                break; // On gère un seul sailing par commande pour ce CRUD simplifié
            }
        }

        $currentSailing = $currentSailingId ? Sailing::find($currentSailingId) : null;

        // Récupération des données brutes pour pré-remplir le formulaire
        $bookingData = $lineItem ? json_decode($lineItem->get_meta('_booking_data_raw'), true) : [];

        // Liste des futurs départs pour le sélecteur de changement de date
        $futureSailings = Sailing::fetch([
            'posts_per_page' => 50,
            'meta_query' => [
                [
                    'key' => 'sailing_config_departure_date',
                    'value' => date('Y-m-d H:i:s'),
                    'compare' => '>='
                ]
            ],
            'orderby' => 'meta_value',
            'meta_key' => 'sailing_config_departure_date',
            'order' => 'ASC'
        ]);

        echo view('admin.boarding-edit', [
            'order' => $order,
            'item' => $lineItem,
            'bookingData' => $bookingData,
            'currentSailing' => $currentSailing,
            'futureSailings' => $futureSailings,
            'cancelUrl' => admin_url('admin.php?page=navi-boarding-list&sailing_id=' . $currentSailingId)
        ])->render();
    }

    /**
     * Affiche la liste standard
     */
    private function renderList()
    {
        $selectedSailingId = isset($_GET['sailing_id']) ? (int) $_GET['sailing_id'] : null;
        $passengersList = [];
        $sailing = null;

        if ($selectedSailingId) {
            $sailing = Sailing::find($selectedSailingId);
            if ($sailing) {
                $passengersList = $this->getPassengersForSailing($selectedSailingId);
            }
        }

        $allSailings = Sailing::fetch(['posts_per_page' => 100, 'meta_key' => 'sailing_config_departure_date', 'orderby' => 'meta_value', 'order' => 'DESC']);
        $futureSailings = Sailing::fetch(['posts_per_page' => 50, 'meta_query' => [['key' => 'sailing_config_departure_date', 'value' => date('Y-m-d H:i:s'), 'compare' => '>=']], 'orderby' => 'meta_value', 'meta_key' => 'sailing_config_departure_date', 'order' => 'ASC']);

        if (isset($_GET['message'])) {
            echo '<div class="notice notice-'.($_GET['msg_type'] ?? 'success').' is-dismissible"><p>'.esc_html($_GET['message']).'</p></div>';
        }

        echo view('admin.boarding-list', [
            'upcomingSailings' => $allSailings,
            'futureSailings' => $futureSailings,
            'selectedSailingId' => $selectedSailingId,
            'currentSailing' => $sailing,
            'passengers' => $passengersList
        ])->render();
    }

    /**
     * Gestionnaire des actions de formulaire (POST)
     */
    public function handleFormActions()
    {
        // 1. Gestion de la sauvegarde du formulaire d'ÉDITION (CRUD)
        if (isset($_POST['action']) && $_POST['action'] === 'update_reservation') {
            $this->processReservationUpdate();
            return;
        }

        // 2. Gestion des actions de masse (Liste)
        if ((!isset($_POST['action']) && !isset($_POST['single_action'])) || !isset($_POST['_wpnonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'], 'navi_bulk_action')) {
            return;
        }

        $action = $_POST['single_action'] ?: ($_POST['action'] != '-1' ? $_POST['action'] : $_POST['action2']);

        $orderIds = [];
        if (!empty($_POST['single_order_id'])) {
            $orderIds[] = $_POST['single_order_id'];
        } elseif (!empty($_POST['order_ids'])) {
            $orderIds = $_POST['order_ids'];
        }

        if (empty($orderIds) || $action == '-1') {
            return;
        }

        $sailingId = $_POST['sailing_id'];
        $message = '';
        $type = 'success';

        switch ($action) {
            case 'reschedule':
                $newSailingId = $_POST['new_sailing_id'] ?? null;
                $priceAdjustment = isset($_POST['price_adjustment']) ? (float) $_POST['price_adjustment'] : 0;
                $paxToRemove = isset($_POST['pax_to_remove']) ? (int) $_POST['pax_to_remove'] : 0;

                if ($newSailingId) {
                    $count = $this->processReschedule($orderIds, $sailingId, $newSailingId, $priceAdjustment, $paxToRemove);
                    $message = $count . ' commandes reprogrammées.';
                } else {
                    $message = 'ID manquant.';
                    $type = 'error';
                }
                break;

            case 'refund':
                $count = $this->processRefund($orderIds);
                $message = $count . ' commandes remboursées.';
                break;

            case 'credit':
                $count = $this->processCreditNote($orderIds);
                $message = $count . ' avoirs générés.';
                break;
        }

        if ($message) {
            wp_redirect(add_query_arg(['message' => urlencode($message), 'msg_type' => $type], wp_get_referer()));
            exit;
        }
    }

    /**
     * Traitement de la sauvegarde du formulaire d'édition
     */
    private function processReservationUpdate()
    {
        if (!check_admin_referer('update_reservation_action', '_wpnonce')) return;

        $orderId = (int) $_POST['order_id'];
        $itemId = (int) $_POST['item_id'];
        $originalSailingId = (int) $_POST['original_sailing_id'];

        // Champs modifiables
        $newSailingId = (int) $_POST['sailing_id'];
        $privateNote = sanitize_textarea_field($_POST['private_note']);

        $order = wc_get_order($orderId);
        $item = $order->get_item($itemId);

        if (!$order || !$item) wp_die('Erreur de commande');

        // Récupération des données existantes
        $rawData = $item->get_meta('_booking_data_raw');
        $data = json_decode($rawData, true) ?: [];
        $oldPassengers = $data['passengers'] ?? [];
        $oldTotalPax = array_sum($oldPassengers);

        // 1. Mise à jour des PASSAGERS
        // On s'attend à recevoir un tableau 'passengers' depuis le formulaire : [ type_id => qty ]
        $newPassengers = $_POST['passengers'] ?? $oldPassengers;
        // Nettoyage et conversion en entiers
        $newPassengers = array_map('intval', $newPassengers);
        $newTotalPax = array_sum($newPassengers);

        // Mise à jour de la note interne
        $order->update_meta_data('_private_boarding_note', $privateNote);

        // Mise à jour des données JSON
        $data['passengers'] = $newPassengers;

        // Reconstruction de la chaîne de détails pour l'affichage
        $details = [];
        foreach ($newPassengers as $typeId => $qty) {
            if ($qty > 0) {
                $term = get_term($typeId, 'passenger_type');
                $name = !is_wp_error($term) ? $term->name : 'Passager';
                $details[] = "$qty x $name";
            }
        }
        // On remet les options (inchangées ici, mais on pourrait faire pareil)
        if (isset($data['options'])) {
            // Logique pour réafficher les options... pour l'instant on garde l'ancien texte des options
            // Idéalement on devrait reconstruire tout le string
        }
        $data['details_string'] = implode(', ', $details); // Simplifié : on écrase avec les passagers

        // 2. Gestion du changement de date ET/OU changement de passagers
        $targetSailingId = ($newSailingId && $newSailingId !== $originalSailingId) ? $newSailingId : $originalSailingId;

        // Mise à jour des quotas
        // Cas 1 : Changement de date
        if ($targetSailingId !== $originalSailingId) {
            $newSailing = Sailing::find($targetSailingId);

            $item->update_meta_data('_sailing_id', $targetSailingId);

            if ($newSailing->start) {
                try {
                    $dt = new \DateTime($newSailing->start);
                    $item->update_meta_data('Date de départ', $dt->format('d/m/Y à H:i'));
                    $data['sailing_id'] = $targetSailingId;
                    $data['date'] = $newSailing->start;
                } catch (\Exception $e) {}
            }

            // On libère tout l'ancien quota
            $this->updateSailingBookedCount($originalSailingId, -$oldTotalPax);
            // On prend le nouveau quota total sur la nouvelle date
            $this->updateSailingBookedCount($targetSailingId, $newTotalPax);

            $order->add_order_note("📝 ÉDITION : Départ changé (#$originalSailingId -> #$targetSailingId). Pax : $oldTotalPax -> $newTotalPax.");
        }
        // Cas 2 : Même date, mais changement de nombre de passagers
        elseif ($newTotalPax !== $oldTotalPax) {
            $diff = $newTotalPax - $oldTotalPax;
            $this->updateSailingBookedCount($originalSailingId, $diff);
            $order->add_order_note("📝 ÉDITION : Modification du nombre de passagers ($oldTotalPax -> $newTotalPax).");
        }

        // Sauvegarde des données mises à jour
        $item->update_meta_data('_booking_data_raw', json_encode($data));
        $item->update_meta_data('Détails', $data['details_string']); // Mise à jour visible

        $item->save();
        $order->save();

        // Redirection vers la liste
        wp_redirect(admin_url('admin.php?page=navi-boarding-list&sailing_id=' . $targetSailingId . '&message=Réservation mise à jour'));
        exit;
    }

    /**
     * Traite le report (Action de masse)
     */
    private function processReschedule($orderIds, $oldSailingId, $newSailingId, $priceAdjustment = 0, $paxToRemove = 0)
    {
        $newSailing = Sailing::find($newSailingId);
        if (!$newSailing) return 0;
        $processedCount = 0;

        foreach ($orderIds as $orderId) {
            $order = wc_get_order($orderId);
            if (!$order) continue;
            $orderProcessed = false;

            foreach ($order->get_items() as $item) {
                if ($item->get_meta('_sailing_id') == $oldSailingId) {
                    $rawData = $item->get_meta('_booking_data_raw');
                    $data = json_decode($rawData, true) ?: [];
                    $currentPaxCount = isset($data['passengers']) ? array_sum($data['passengers']) : 0;

                    $paxToKeep = max(0, $currentPaxCount - $paxToRemove);

                    // Mise à jour JSON passagers
                    if ($paxToRemove > 0 && isset($data['passengers'])) {
                        $remainingToRemove = $paxToRemove;
                        foreach ($data['passengers'] as $type => $qty) {
                            if ($remainingToRemove <= 0) break;
                            $canRemove = min($qty, $remainingToRemove);
                            $data['passengers'][$type] = $qty - $canRemove;
                            $remainingToRemove -= $canRemove;
                        }
                    }

                    $item->update_meta_data('_sailing_id', $newSailingId);
                    if ($newSailing->start) {
                        try {
                            $dt = new \DateTime($newSailing->start);
                            $item->update_meta_data('Date de départ', $dt->format('d/m/Y à H:i'));
                            if ($data) {
                                $data['sailing_id'] = $newSailingId;
                                $data['date'] = $newSailing->start;
                                $item->update_meta_data('_booking_data_raw', json_encode($data));
                            }
                        } catch (\Exception $e) {}
                    }
                    $item->save();

                    $this->updateSailingBookedCount($oldSailingId, -$currentPaxCount);
                    $this->updateSailingBookedCount($newSailingId, $paxToKeep);
                    $orderProcessed = true;
                }
            }

            if ($orderProcessed) {
                $note = "🔄 REPROGRAMMATION (Admin) : Client déplacé du départ #$oldSailingId au #$newSailingId.";
                if ($paxToRemove > 0) $note .= "\n⚠️ MODIFICATION : $paxToRemove passager(s) supprimé(s).";
                if ($priceAdjustment > 0) {
                    $note .= "\n💰 AJUSTEMENT : Supplément de {$priceAdjustment}€ dû.";
                    $order->update_meta_data('_balance_due', $priceAdjustment);
                } elseif ($priceAdjustment < 0) {
                    $refundAmount = abs($priceAdjustment);
                    $code = $this->createCouponForOrder($order, $refundAmount, "Avoir partiel");
                    $note .= "\n🎟️ AJUSTEMENT : Avoir de {$refundAmount}€ généré (Code : {$code})";
                }
                $order->add_order_note($note);
                $order->save();
                $processedCount++;
            }
        }
        return $processedCount;
    }

    private function updateSailingBookedCount($sailingId, $change) {
        $current = (int) get_post_meta($sailingId, 'sailing_config_booked_count', true);
        $new = max(0, $current + $change);
        update_post_meta($sailingId, 'sailing_config_booked_count', $new);
    }

    private function createCouponForOrder($order, $amount, $description) {
        $code = 'AV-' . strtoupper(substr(md5(uniqid() . $order->get_id()), 0, 6));
        $email = $order->get_billing_email();
        $coupon = new WC_Coupon();
        $coupon->set_code($code);
        $coupon->set_discount_type('fixed_cart');
        $coupon->set_amount($amount);
        $coupon->set_individual_use(true);
        $coupon->set_usage_limit(1);
        if ($email) $coupon->set_email_restrictions([$email]);
        $coupon->set_description($description . " (Commande #" . $order->get_id() . ")");
        $coupon->save();
        return $code;
    }

    private function processRefund($orderIds) {
        $count = 0;
        foreach ($orderIds as $orderId) {
            $order = wc_get_order($orderId);
            if ($order) {
                $order->update_status('refunded', "Action manuelle depuis la liste d'embarquement.");
                $order->update_meta_data('_boarding_status', 'refunded_manual');
                $order->save();
                $count++;
            }
        }
        return $count;
    }

    private function processCreditNote($orderIds) {
        $count = 0;
        foreach ($orderIds as $orderId) {
            $order = wc_get_order($orderId);
            if ($order) {
                if ($order->get_meta('_credit_coupon_code')) continue;
                $amount = $order->get_total();
                $code = $this->createCouponForOrder($order, $amount, "Avoir total pour annulation");
                $order->update_meta_data('_credit_coupon_code', $code);
                $order->update_meta_data('_boarding_status', 'credited');
                $order->add_order_note("🎟️ AVOIR GÉNÉRÉ : Coupon créé automatiquement.\nCode : {$code}\nMontant : {$amount}€");
                $order->save();
                $count++;
            }
        }
        return $count;
    }

    public function handleCsvExport()
    {
        if (isset($_GET['page'], $_GET['action'], $_GET['sailing_id']) && $_GET['page'] === 'navi-boarding-list' && $_GET['action'] === 'download_csv') {
            if (!current_user_can('edit_posts')) return;
            $sailingId = (int) $_GET['sailing_id'];
            $sailing = Sailing::find($sailingId);
            if ($sailing) {
                if (ob_get_length()) ob_end_clean();
                $passengersList = $this->getPassengersForSailing($sailingId);
                $this->generateCsv($sailing, $passengersList);
                exit;
            }
        }
    }

    private function generateCsv($sailing, $passengers)
    {
        $filename = 'boarding-' . $sailing->ID . '-' . date('Y-m-d') . '.csv';
        header('Content-Description: File Transfer');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, [
            'ID Commande', 'Client', 'Email', 'Téléphone',
            'Détail Passagers', 'Options', 'Total Pers.',
            'Montant (€)',
            'Statut Paiement',
            'Note Client', 'Note Interne', 'Log Système'
        ], ';');

        foreach ($passengers as $pax) {
            $summaryClean = strip_tags(str_replace(['<br>', '<br/>'], ' | ', $pax['passengers_summary']));
            $optionsClean = strip_tags($pax['options_summary']);

            fputcsv($output, [
                $pax['order_id'],
                $pax['customer_name'],
                $pax['customer_email'],
                $pax['phone'],
                $summaryClean,
                $optionsClean,
                $pax['total_seats'],
                $pax['total_amount'],
                $pax['status_label'],
                $pax['boarding_notes'],
                $pax['private_note'],
                $pax['notes']
            ], ';');
        }
        fclose($output);
    }

    private function getPassengersForSailing($sailingId)
    {
        global $wpdb;
        $table_items = $wpdb->prefix . 'woocommerce_order_items';
        $table_meta = $wpdb->prefix . 'woocommerce_order_itemmeta';

        $results = $wpdb->get_results($wpdb->prepare("
            SELECT i.order_id, m.order_item_id
            FROM {$table_items} i
            INNER JOIN {$table_meta} m ON i.order_item_id = m.order_item_id
            WHERE (m.meta_key = '_sailing_id' OR m.meta_key = 'sailing_id')
            AND m.meta_value = %s
        ", (string)$sailingId));

        $list = [];
        foreach ($results as $row) {
            $order = wc_get_order($row->order_id);
            if (!$order) continue;

            $item = $order->get_item($row->order_item_id);
            if (!$item) continue;

            $rawData = $item->get_meta('_booking_data_raw');
            $data = json_decode($rawData, true) ?: [];

            $passengers = $data['passengers'] ?? [];
            $totalSeats = 0;
            if (is_array($passengers)) {
                foreach ($passengers as $qty) $totalSeats += (int)$qty;
            }

            $customStatus = $order->get_meta('_boarding_status');
            $couponCode = $order->get_meta('_credit_coupon_code');
            $statusLabel = wc_get_order_status_name($order->get_status());
            if ($customStatus === 'credited') {
                $statusLabel = '🎟️ Avoir (' . $couponCode . ')';
            } elseif ($customStatus === 'refunded_manual' || $order->get_status() === 'refunded') {
                $statusLabel = '💸 Remboursé';
            }

            $boardingNotes = $order->get_meta('_boarding_notes');
            $privateNote = $order->get_meta('_private_boarding_note');

            $list[] = [
                'order_id' => $order->get_id(),
                'order_link' => get_edit_post_link($order->get_id()),
                'edit_booking_url' => admin_url('admin.php?page=navi-boarding-list&action=edit&order_id=' . $order->get_id()),
                'customer_name' => $order->get_formatted_billing_full_name() ?: 'Client Invité',
                'customer_email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'status' => $order->get_status(),
                'status_label' => $statusLabel,
                'passengers_summary' => $this->formatPassengers($passengers),
                'options_summary' => $this->formatOptions($data['options'] ?? []),
                'total_seats' => $totalSeats,
                'total_amount' => $order->get_total(),
                'boarding_notes' => $boardingNotes,
                'private_note' => $privateNote,
                'notes' => $order->get_customer_note()
            ];
        }
        return $list;
    }

    private function formatPassengers($passengers)
    {
        if (!is_array($passengers)) return '-';
        $output = [];
        foreach ($passengers as $typeId => $qty) {
            if ($qty > 0) {
                $term = get_term($typeId, 'passenger_type');
                $name = ($term && !is_wp_error($term)) ? $term->name : 'Passager';
                $output[] = "<strong>{$qty}</strong> x {$name}";
            }
        }
        return implode(', ', $output);
    }

    private function formatOptions($options)
    {
        if (!is_array($options)) return '-';
        $output = [];
        foreach ($options as $key => $value) {
            $optId = is_numeric($value) ? $value : $key;
            if (is_numeric($key)) $optId = $value;
            $term = get_term($optId, 'extra_option_type');
            if ($term && !is_wp_error($term)) $output[] = $term->name;
        }
        return implode(', ', $output);
    }
}
