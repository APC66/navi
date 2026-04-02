<?php

namespace App\Services;

class WoocommerceBridge
{
    protected bool $isSaving = false;

    public function init()
    {
        add_action('save_post_cruise', [$this, 'syncCruiseToProduct'], 20, 3);
        add_action('woocommerce_before_calculate_totals', [$this, 'overrideCartItemPrice'], 20, 1);
        add_filter('woocommerce_get_item_data', [$this, 'displayBookingDataInCart'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'addBookingDataToOrder'], 10, 4);
        add_action('woocommerce_check_cart_items', [$this, 'validateCartAvailability'], 10);
        add_action('woocommerce_order_status_processing', [$this, 'processOrderBooking'], 10, 1);
        add_action('woocommerce_order_status_completed', [$this, 'processOrderBooking'], 10, 1);
        add_action('woocommerce_order_status_completed', [$this, 'processGiftCardItems'], 20, 1);
        add_action('woocommerce_order_status_cancelled', [$this, 'cancelOrderBooking'], 10, 1);
        add_action('woocommerce_order_status_refunded', [$this, 'cancelOrderBooking'], 10, 1);

        add_action('woocommerce_admin_order_data_after_order_details', [$this, 'addAdminOrderNoteField']);
        add_action('woocommerce_process_shop_order_meta', [$this, 'saveAdminOrderNoteField']);
        add_action('woocommerce_update_order', [$this, 'saveAdminOrderNoteField'], 10, 1);
        add_filter('woocommerce_cart_id', [$this, 'distinctProductSailing'], 10, 5);

        add_filter('woocommerce_cart_item_quantity', [$this, 'set_quantity_to_unique_product'], 10, 3);

        add_filter('woocommerce_checkout_fields', [$this, 'addCompanyFields']);
        add_action('woocommerce_checkout_create_order', [$this, 'saveCompanyFields'], 10, 2);
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'displayCompanyFieldsInAdmin'], 10, 1);

        // Affichage lisible des metas carte cadeau dans le backoffice
        add_filter('woocommerce_hidden_order_itemmeta', [$this, 'hideRawGiftCardMeta']);
        add_filter('woocommerce_order_item_get_formatted_meta_data', [$this, 'formatGiftCardMeta'], 10, 2);

        add_action('manage_posts_extra_tablenav', [$this, 'addCleanupGiftCardButton']);
        add_action('admin_action_cleanup_gift_card_products', [$this, 'cleanupGiftCardProducts']);
    }

    /**
     * Masque les clés brutes _gc_* dans le détail de commande admin.
     */
    public function hideRawGiftCardMeta(array $hidden): array
    {
        return array_merge($hidden, [
            '_gc_cruise_id',
            '_gc_season',
            '_gc_passengers',
            '_gc_options',
            '_gc_amount',
            '_gc_recipient_email',
            '_gc_recipient_message',
            '_gc_send_to_self',
            '_gc_mode',
            '_gc_product_title',
            '_gc_coupon_expiry',
            '_gc_processed',
        ]);
    }

    /**
     * Injecte les données lisibles de la carte cadeau dans le détail de commande admin.
     */
    public function formatGiftCardMeta(array $formattedMeta, $item): array
    {
        $couponCode = $item->get_meta('_gc_coupon_code');
        if (! $couponCode) {
            return $formattedMeta;
        }

        $mode = $item->get_meta('_gc_mode') ?: 'cruise';
        $amount = floatval($item->get_meta('_gc_amount'));
        $expiry = $item->get_meta('_gc_coupon_expiry');
        $message = $item->get_meta('_gc_recipient_message');
        $email = $item->get_meta('_gc_recipient_email');
        $sendToSelf = $item->get_meta('_gc_send_to_self') === '1';

        $idBase = count($formattedMeta) + 100;
        $inject = [];

        // Type
        $inject[] = (object) [
            'id' => $idBase++,
            'key' => '_gc_display_type',
            'display_key' => 'Type',
            'value' => $mode === 'cruise' ? 'Croisière spécifique' : 'Montant libre',
            'display_value' => '<strong>'.($mode === 'cruise' ? 'Croisière spécifique' : 'Montant libre').'</strong>',
        ];

        // Croisière
        if ($mode === 'cruise') {
            $cruiseId = absint($item->get_meta('_gc_cruise_id'));
            $season = $item->get_meta('_gc_season');

            if ($cruiseId) {
                $inject[] = (object) [
                    'id' => $idBase++,
                    'key' => '_gc_display_cruise',
                    'display_key' => 'Croisière',
                    'value' => get_the_title($cruiseId),
                    'display_value' => get_the_title($cruiseId),
                ];
            }

            $inject[] = (object) [
                'id' => $idBase++,
                'key' => '_gc_display_season',
                'display_key' => 'Saison',
                'value' => $season === 'high' ? 'Haute Saison' : 'Basse Saison',
                'display_value' => $season === 'high' ? 'Haute Saison' : 'Basse Saison',
            ];

            // Passagers
            $passengersRaw = $item->get_meta('_gc_passengers');
            $passengers = $passengersRaw ? json_decode($passengersRaw, true) : [];
            $passengerLines = [];
            foreach ($passengers as $typeId => $qty) {
                if ($qty <= 0) {
                    continue;
                }
                $term = get_term(absint($typeId), 'passenger_type');
                $name = (! is_wp_error($term) && $term) ? $term->name : "Type #{$typeId}";
                $passengerLines[] = "{$name} × {$qty}";
            }
            if ($passengerLines) {
                $inject[] = (object) [
                    'id' => $idBase++,
                    'key' => '_gc_display_passengers',
                    'display_key' => 'Passagers',
                    'value' => implode(', ', $passengerLines),
                    'display_value' => implode('<br>', $passengerLines),
                ];
            }

            // Options
            $optionsRaw = $item->get_meta('_gc_options');
            $options = $optionsRaw ? json_decode($optionsRaw, true) : [];
            $optionLines = [];
            foreach ($options as $typeId => $qty) {
                if ($qty <= 0) {
                    continue;
                }
                $term = get_term(absint($typeId), 'extra_option_type');
                $name = (! is_wp_error($term) && $term) ? $term->name : "Option #{$typeId}";
                $optionLines[] = "{$name} × {$qty}";
            }
            if ($optionLines) {
                $inject[] = (object) [
                    'id' => $idBase++,
                    'key' => '_gc_display_options',
                    'display_key' => 'Options',
                    'value' => implode(', ', $optionLines),
                    'display_value' => implode('<br>', $optionLines),
                ];
            }
        }

        // Montant
        $inject[] = (object) [
            'id' => $idBase++,
            'key' => '_gc_display_amount',
            'display_key' => 'Valeur',
            'value' => number_format($amount, 2, ',', ' ').' €',
            'display_value' => '<strong>'.number_format($amount, 2, ',', ' ').' €</strong>',
        ];

        // Destinataire
        $recipientDisplay = $sendToSelf ? 'Envoi à soi-même' : esc_html($email);
        $inject[] = (object) [
            'id' => $idBase++,
            'key' => '_gc_display_recipient',
            'display_key' => 'Destinataire',
            'value' => $recipientDisplay,
            'display_value' => $recipientDisplay,
        ];

        // Message
        if ($message) {
            $inject[] = (object) [
                'id' => $idBase++,
                'key' => '_gc_display_message',
                'display_key' => 'Message',
                'value' => $message,
                'display_value' => esc_html($message),
            ];
        }

        // Expiration
        if ($expiry) {
            $expiryFormatted = \DateTime::createFromFormat('Y-m-d', $expiry)?->format('d/m/Y') ?? $expiry;
            $inject[] = (object) [
                'id' => $idBase++,
                'key' => '_gc_display_expiry',
                'display_key' => 'Expire le',
                'value' => $expiryFormatted,
                'display_value' => $expiryFormatted,
            ];
        }

        return array_merge($formattedMeta, $inject);
    }

    public function validateCartAvailability()
    {
        if (is_admin() && ! defined('DOING_AJAX')) {
            return;
        }
        $reservationService = new ReservationService;

        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['booking_data'])) {
                $data = $cart_item['booking_data'];
                $sailingId = $data['sailing_id'];
                $passengers = $data['passengers'] ?? [];
                $options = $data['options'] ?? [];
                $totalHumans = array_sum($passengers);

                try {
                    if (! $reservationService->checkAvailability($sailingId, $totalHumans, $options)) {
                        wc_add_notice(sprintf(
                            'Désolé, la disponibilité pour le départ du %s a changé (places ou options épuisées).',
                            $data['date']
                        ), 'error');
                    }
                } catch (\Exception $e) {
                    wc_add_notice($e->getMessage(), 'error');
                }
            }
        }
    }

    public function processOrderBooking($order_id)
    {
        $this->updateBookingCount($order_id, 'increment');
    }

    public function cancelOrderBooking($order_id)
    {
        $this->updateBookingCount($order_id, 'decrement');
    }

    private function updateBookingCount($order_id, $action)
    {
        $order = wc_get_order($order_id);
        if (! $order) {
            return;
        }

        $isBooked = $order->get_meta('_booking_processed');
        if ($action === 'increment' && $isBooked) {
            return;
        }
        if ($action === 'decrement' && ! $isBooked) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $sailingId = $item->get_meta('_sailing_id');
            $rawData = $item->get_meta('_booking_data_raw');

            if ($sailingId && $rawData) {
                $data = json_decode($rawData, true);
                $passengers = $data['passengers'] ?? [];
                $options = $data['options'] ?? []; // Format attendu: ['opt_id' => qty]

                // 1. Mise à jour COMPTEUR PASSAGERS
                $count = array_sum($passengers);
                $currentBooked = (int) get_post_meta($sailingId, 'sailing_config_booked_count', true);
                $newCount = ($action === 'increment') ? $currentBooked + $count : max(0, $currentBooked - $count);
                update_post_meta($sailingId, 'sailing_config_booked_count', $newCount);

                // 2. Mise à jour COMPTEUR OPTIONS
                $optionsBooked = get_post_meta($sailingId, 'sailing_options_booked_counts', true) ?: [];
                if (! is_array($optionsBooked)) {
                    $optionsBooked = [];
                }

                foreach ($options as $optId => $qtyRequested) {
                    $qty = (int) $qtyRequested;
                    if ($qty <= 0) {
                        continue;
                    }

                    $currentOptCount = isset($optionsBooked[$optId]) ? (int) $optionsBooked[$optId] : 0;

                    if ($action === 'increment') {
                        $optionsBooked[$optId] = $currentOptCount + $qty;
                    } else {
                        $optionsBooked[$optId] = max(0, $currentOptCount - $qty);
                    }
                }
                update_post_meta($sailingId, 'sailing_options_booked_counts', $optionsBooked);
            }
        }

        if ($action === 'increment') {
            $order->update_meta_data('_booking_processed', '1');
        } else {
            $order->delete_meta_data('_booking_processed');
        }
        $order->save();
    }

    // ... (Reste des méthodes overrideCartItemPrice, displayBookingDataInCart, etc. inchangées) ...
    public function overrideCartItemPrice($cart)
    {
        if (is_admin() && ! defined('DOING_AJAX')) {
            return;
        }
        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['booking_data']['price_override'])) {
                $price = (float) $cart_item['booking_data']['price_override'];
                $cart_item['data']->set_price($price);
            }
        }
    }

    public function displayBookingDataInCart($item_data, $cart_item)
    {
        if (isset($cart_item['booking_data'])) {
            $data = $cart_item['booking_data'];
            if (! empty($data['date'])) {
                try {
                    $dt = new \DateTime($data['date']);
                    $formattedDate = $dt->format('d/m/Y à H:i');
                    $item_data[] = ['key' => '📅 Date de départ', 'value' => $formattedDate, 'display' => $formattedDate];
                } catch (\Exception $e) {
                }
            }
            if (! empty($data['details_string'])) {
                $item_data[] = ['key' => '👥 Détails', 'value' => $data['details_string'], 'display' => $data['details_string']];
            }
        }

        return $item_data;
    }

    public function addBookingDataToOrder($item, $cart_item_key, $values, $order)
    {
        if (isset($values['booking_data'])) {
            $data = $values['booking_data'];
            if (! empty($data['date'])) {
                $item->add_meta_data('Date de départ', $data['date']);
            }
            if (! empty($data['sailing_id'])) {
                $item->add_meta_data('_sailing_id', $data['sailing_id']);
            }
            if (! empty($data['details_string'])) {
                $item->add_meta_data('Détails', $data['details_string']);
            }
            $item->add_meta_data('_booking_data_raw', json_encode($data));
        }

        if (isset($values['gift_card_data'])) {
            $gc = $values['gift_card_data'];
            foreach ($gc as $key => $value) {
                $item->add_meta_data($key, $value);
            }
        }
    }

    public function syncCruiseToProduct($postId, $post, $update)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (wp_is_post_revision($postId)) {
            return;
        }
        if (! in_array($post->post_status, ['publish', 'draft', 'private'])) {
            return;
        }

        $productId = get_post_meta($postId, 'related_wc_product_id', true);
        $product = $productId ? wc_get_product($productId) : null;

        if (! $product) {
            $product = new \WC_Product_Simple;
            $product->set_slug($post->post_name.'-booking');
        }

        $product->set_name($post->post_title.' (Réservation)');
        $product->set_status($post->post_status === 'publish' ? 'publish' : 'draft');
        $product->set_virtual(true);
        $product->set_catalog_visibility('hidden');
        $product->set_sold_individually(false);

        $basePrice = get_field('base_price', $postId) ?: 0;
        $product->set_regular_price($basePrice);
        $product->set_price($basePrice);
        $product->set_short_description('Réservation pour la croisière : '.$post->post_title);

        $newProductId = $product->save();

        if (! $productId || $productId != $newProductId) {
            update_post_meta($postId, 'related_wc_product_id', $newProductId);
            update_post_meta($newProductId, '_linked_cruise_id', $postId);
        }
    }

    public function addAdminOrderNoteField($order)
    {
        echo '<br class="clear" />';
        echo '<h3>Note Interne (Liste Embarquement)</h3>';

        woocommerce_wp_textarea_input([
            'id' => '_private_boarding_note',
            'label' => 'Note privée :',
            'style' => 'width:100%;height:100px;',
            'value' => $order->get_meta('_private_boarding_note'),
            'wrapper_class' => 'form-field-wide',
        ]);
    }

    public function saveAdminOrderNoteField($order_id_or_object)
    {
        if ($this->isSaving) {
            return;
        }

        if (is_numeric($order_id_or_object)) {
            $order = wc_get_order($order_id_or_object);
        } else {
            $order = $order_id_or_object;
        }

        if (! $order || ! isset($_POST['_private_boarding_note'])) {
            return;
        }

        $note = sanitize_textarea_field($_POST['_private_boarding_note']);

        $this->isSaving = true;

        $order->update_meta_data('_private_boarding_note', $note);
        $order->save(); // HPOS + legacy

        $this->isSaving = false;
    }

    public function distinctProductSailing($cart_id, $product_id, $quantity, $variation_id, $variation)
    {
        if (isset($cart_item_data['booking_data']['sailing_id'])) {
            return md5($cart_id.$cart_item_data['booking_data']['sailing_id']);
        }

        return $cart_id;
    }

    /**
     * Traitement des items carte cadeau lors du passage en statut "Terminée".
     * Génère le coupon WooCommerce et envoie l'email avec le PDF.
     */
    public function processGiftCardItems(int $order_id): void
    {
        $order = wc_get_order($order_id);
        if (! $order) {
            return;
        }

        $giftCardService = new GiftCardService;

        foreach ($order->get_items() as $item) {

            // Identifier les items carte cadeau par la meta _gc_amount
            if (! $item->get_meta('_gc_amount')) {
                continue;
            }

            // Éviter le double traitement
            if ($item->get_meta('_gc_processed')) {
                continue;
            }

            // 1. Générer le coupon
            $couponCode = $giftCardService->generateCoupon($order, $item);

            if ($couponCode) {
                // 2. Envoyer l'email avec le PDF
                $giftCardService->sendGiftCardEmail($order, $item);

                // Marquer l'item comme traité
                $item->update_meta_data('_gc_processed', '1');
                $item->save();
            }
        }
    }

    public function set_quantity_to_unique_product($quantity, $cart_item_key, $cart_item)
    {
        if (isset($cart_item['gift_card_data'])) {
            return '<span class="text-white">1</span>';
        }

        return $quantity;
    }

    public function addCompanyFields($fields): array
    {
        // Rendre le champ société visible et optionnel
        $fields['billing']['billing_company']['class'] = ['form-row-wide'];
        $fields['billing']['billing_company']['label'] = 'Entreprise';
        $fields['billing']['billing_company']['priority'] = 34;

        // Ajouter SIRET juste après
        $fields['billing']['billing_siret'] = [
            'label' => 'Numéro SIRET',
            'placeholder' => '123 456 789 00012',
            'required' => false,
            'class' => ['form-row-wide'],
            'priority' => 35,
        ];

        return $fields;
    }

    public function saveCompanyFields($order, $data): void
    {
        if (! empty($_POST['billing_siret'])) {
            $order->update_meta_data('_billing_siret', sanitize_text_field($_POST['billing_siret']));
        }
    }

    public function displayCompanyFieldsInAdmin($order): void
    {
        $siret = $order->get_meta('_billing_siret');
        $company = $order->get_billing_company();

        if ($company || $siret) {
            echo '<div style="margin-top: 12px;">';
            if ($company) {
                echo '<p><strong>Société :</strong> '.esc_html($company).'</p>';
            }
            if ($siret) {
                echo '<p><strong>SIRET :</strong> '.esc_html($siret).'</p>';
            }
            echo '</div>';
        }
    }

    public function addCleanupGiftCardButton(string $which): void
    {
        $screen = get_current_screen();
        if ($screen->post_type !== 'product' || $which !== 'top') {
            return;
        }

        $url = wp_nonce_url(
            admin_url('admin.php?action=cleanup_gift_card_products'),
            'cleanup_gift_cards'
        );

        echo '<div class="alignleft actions">';
        echo '<a href="'.esc_url($url).'" class="button" onclick="return confirm(\'Supprimer les produits carte cadeau non liés à une commande active ?\')">🎁 Nettoyer les cartes cadeaux</a>';
        echo '</div>';
    }

    public function cleanupGiftCardProducts(): void
    {
        check_admin_referer('cleanup_gift_cards');

        if (! current_user_can('manage_woocommerce')) {
            wp_die('Accès refusé.');
        }

        add_filter('posts_where', function ($where, $query) {
            if ($query->get('_title_like')) {
                global $wpdb;
                $where .= $wpdb->prepare(
                    ' AND '.$wpdb->posts.'.post_title LIKE %s',
                    '%'.$wpdb->esc_like($query->get('_title_like')).'%'
                );
            }

            return $where;
        }, 10, 2);

        $products = get_posts([
            'post_type' => 'product',
            'post_status' => 'any',
            'numberposts' => -1,
            'fields' => 'ids',
            '_title_like' => 'Carte Cadeau',
        ]);

        $deleted = 0;
        foreach ($products as $id) {
            $orders = wc_get_orders([
                'status' => ['processing', 'on-hold', 'pending'],
                'product_id' => $id,
                'limit' => 1,
            ]);

            if (empty($orders)) {
                wp_delete_post($id, true);
                $deleted++;
            }
        }

        wp_redirect(admin_url('edit.php?post_type=product&cleaned='.$deleted));
        exit;
    }
}
