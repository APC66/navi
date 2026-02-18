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
        add_action('woocommerce_order_status_cancelled', [$this, 'cancelOrderBooking'], 10, 1);
        add_action('woocommerce_order_status_refunded', [$this, 'cancelOrderBooking'], 10, 1);

        add_action('woocommerce_admin_order_data_after_order_details', [$this, 'addAdminOrderNoteField']);
        add_action('woocommerce_process_shop_order_meta', [$this, 'saveAdminOrderNoteField']);
        add_action('woocommerce_update_order', [$this, 'saveAdminOrderNoteField'], 10, 1);

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
}
