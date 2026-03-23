<?php

namespace App\Http\Controllers\Api;

use WP_REST_Request;

class GiftCardController
{
    /**
     * GET gift-card/pricing/{id}
     * Retourne les pricing_rows et options_pricing du cruise
     */
    public function pricing(int $id): array
    {
        $id = absint($id);

        $post = get_post($id);

        if (! $post || $post->post_type !== 'cruise' || $post->post_status !== 'publish') {
            return ['success' => false, 'message' => 'Croisière introuvable.'];
        }

        $pricingRows = get_field('pricing_rows', $id) ?: [];
        $optionsPricing = get_field('options_pricing', $id) ?: [];

        $passengers = [];
        foreach ($pricingRows as $row) {
            $termId = absint($row['passenger_type'] ?? 0);
            if (! $termId) {
                continue;
            }
            $term = get_term($termId, 'passenger_type');
            $passengers[] = [
                'id' => $termId,
                'name' => ! is_wp_error($term) ? $term->name : 'Passager',
                'price_low' => floatval($row['price_low_season'] ?? 0),
                'price_high' => floatval($row['price_high_season'] ?? 0),
            ];
        }

        $options = [];
        foreach ($optionsPricing as $row) {
            $termId = absint($row['option_type'] ?? 0);
            if (! $termId) {
                continue;
            }
            $term = get_term($termId, 'extra_option_type');
            $options[] = [
                'id' => $termId,
                'name' => ! is_wp_error($term) ? $term->name : 'Option',
                'price_low' => floatval($row['option_price_low'] ?? 0),
                'price_high' => floatval($row['option_price_high'] ?? 0),
            ];
        }

        $lowSeasonLabel = get_field('low_season_label', $id) ?: '';
        $highSeasonLabel = get_field('high_season_label', $id) ?: '';
        $giftCardDescription = get_field('gift_card_description', $id) ?: '';

        return [
            'success' => true,
            'cruise_title' => get_the_title($id),
            'pricing_rows' => $passengers,
            'options_pricing' => $options,
            'low_season_label' => $lowSeasonLabel,
            'high_season_label' => $highSeasonLabel,
            'gift_card_description' => $giftCardDescription,
        ];
    }

    /**
     * POST gift-card/add-to-cart
     * Crée un produit WC virtuel et l'ajoute au panier
     */
    public function addToCart(WP_REST_Request $request): array
    {
        // --- Récupération et sanitisation des paramètres ---
        $cruiseId = absint($request->get_param('cruise_id') ?? 0);
        $season = sanitize_text_field($request->get_param('season') ?? '');
        $passengersRaw = $request->get_param('passengers') ?: [];
        $optionsRaw = $request->get_param('options') ?: [];
        $amount = floatval($request->get_param('amount') ?? 0);
        $recipientEmail = sanitize_email($request->get_param('recipient_email') ?? '');
        $recipientMessage = sanitize_textarea_field($request->get_param('recipient_message') ?? '');
        $sendToSelf = (bool) $request->get_param('send_to_self');
        $mode = sanitize_text_field($request->get_param('mode') ?? 'cruise'); // 'cruise' ou 'free'

        // Sanitisation des tableaux
        $passengers = [];
        if (is_array($passengersRaw)) {
            foreach ($passengersRaw as $typeId => $qty) {
                $passengers[absint($typeId)] = absint($qty);
            }
        }

        $options = [];
        if (is_array($optionsRaw)) {
            foreach ($optionsRaw as $typeId => $qty) {
                $options[absint($typeId)] = absint($qty);
            }
        }

        // --- Validation ---
        if ($mode === 'cruise') {
            if (! $cruiseId) {
                return ['success' => false, 'message' => 'Croisière non spécifiée.'];
            }
            $post = get_post($cruiseId);
            if (! $post || $post->post_type !== 'cruise') {
                return ['success' => false, 'message' => 'Croisière invalide.'];
            }
            if (! in_array($season, ['low', 'high'], true)) {
                return ['success' => false, 'message' => 'Saison invalide.'];
            }
            if (empty($passengers) || array_sum($passengers) <= 0) {
                return ['success' => false, 'message' => 'Veuillez sélectionner au moins un passager.'];
            }
        } else {
            if ($amount <= 0) {
                return ['success' => false, 'message' => 'Montant invalide.'];
            }
        }

        if (! is_email($recipientEmail) && ! $sendToSelf) {
            return ['success' => false, 'message' => 'Email destinataire invalide.'];
        }

        // --- Calcul du montant total ---
        if ($mode === 'cruise') {
            $amount = $this->calculateAmount($cruiseId, $season, $passengers, $options);
        }

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Le montant calculé est invalide.'];
        }

        // --- Titre du produit ---
        if ($mode === 'cruise') {
            $cruiseTitle = get_the_title($cruiseId);
            $productTitle = 'Carte Cadeau – '.$cruiseTitle;
        } else {
            $productTitle = 'Carte Cadeau – Montant libre';
        }

        // --- Création du produit WC virtuel à la volée ---
        $product = new \WC_Product_Simple;
        $product->set_name($productTitle);
        $product->set_status('publish');
        $product->set_virtual(true);
        $product->set_catalog_visibility('hidden');
        $product->set_sold_individually(true);
        $product->set_regular_price($amount);
        $product->set_price($amount);
        $productId = $product->save();

        if (! $productId) {
            return ['success' => false, 'message' => 'Erreur lors de la création du produit.'];
        }

        // --- Données du cart item ---
        $cartItemData = [
            'gift_card_data' => [
                '_gc_cruise_id' => $cruiseId ?: 0,
                '_gc_season' => $season,
                '_gc_passengers' => wp_json_encode($passengers),
                '_gc_options' => wp_json_encode($options),
                '_gc_amount' => $amount,
                '_gc_recipient_email' => $recipientEmail,
                '_gc_recipient_message' => $recipientMessage,
                '_gc_send_to_self' => $sendToSelf ? '1' : '0',
                '_gc_mode' => $mode,
                '_gc_product_title' => $productTitle,
            ],
            'unique_key' => md5('gc_'.microtime()),
        ];

        // --- Ajout au panier WC ---
        try {
            if (WC()->session === null) {
                $session = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');
                WC()->session = new $session;
                WC()->session->init();
            }
            if (WC()->customer === null) {
                WC()->customer = new \WC_Customer(get_current_user_id(), true);
            }
            if (WC()->cart === null) {
                WC()->cart = new \WC_Cart;
            }

            WC()->cart->get_cart();

            $cartKey = WC()->cart->add_to_cart($productId, 1, 0, [], $cartItemData);
            WC()->session->save_data();

            if ($cartKey === false) {
                $notices = wc_get_notices('error');
                $message = ! empty($notices) ? $notices[0]['notice'] : 'Erreur inconnue lors de l\'ajout au panier.';
                wc_clear_notices();

                return ['success' => false, 'message' => $message];
            }

            return [
                'success' => true,
                'data' => [
                    'redirect' => wc_get_cart_url(),
                    'message' => 'Carte cadeau ajoutée au panier !',
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erreur WooCommerce : '.$e->getMessage()];
        }
    }

    /**
     * Calcule le montant total de la carte cadeau selon la croisière, la saison, les passagers et les options
     */
    private function calculateAmount(int $cruiseId, string $season, array $passengers, array $options): float
    {
        $total = 0.0;
        $priceKey = $season === 'high' ? 'price_high_season' : 'price_low_season';
        $optionPriceKey = $season === 'high' ? 'option_price_high' : 'option_price_low';

        $pricingRows = get_field('pricing_rows', $cruiseId) ?: [];
        foreach ($pricingRows as $row) {
            $typeId = absint($row['passenger_type'] ?? 0);
            if ($typeId && isset($passengers[$typeId]) && $passengers[$typeId] > 0) {
                $total += $passengers[$typeId] * floatval($row[$priceKey] ?? 0);
            }
        }

        $optionsPricing = get_field('options_pricing', $cruiseId) ?: [];
        foreach ($optionsPricing as $row) {
            $typeId = absint($row['option_type'] ?? 0);
            if ($typeId && isset($options[$typeId]) && $options[$typeId] > 0) {
                $total += $options[$typeId] * floatval($row[$optionPriceKey] ?? 0);
            }
        }

        return $total;
    }
}
