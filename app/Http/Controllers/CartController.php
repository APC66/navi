<?php

namespace App\Http\Controllers;

use App\Services\ReservationService;
use App\Models\Sailing;
use WP_REST_Request;
use WC_Cart;

class CartController
{
    protected $reservationService;

    public function __construct()
    {
        $this->reservationService = new ReservationService();
    }

    public function addToCart(WP_REST_Request $request)
    {
        $sailingId = (int) $request->get_param('sailing_id');
        $passengers = $request->get_param('passengers') ?: []; // ['term_id' => qty]

        // Options peut être [id, id] (vieux format checkbox) ou {id: qty} (nouveau format compteur)
        $optionsRaw = $request->get_param('options') ?: [];
        $options = [];

        // Normalisation des options au format [id => qty]
        foreach ($optionsRaw as $key => $val) {
            if (is_numeric($key)) {
                // Format { 12: 1, 15: 2 } (Correct)
                $options[$key] = (int) $val;
            } else {
                // Format [12, 15] (Vieux format liste) -> on convertit en qté 1
                // Dans ce cas $val est l'ID
                if (is_numeric($val)) {
                    $options[$val] = 1;
                }
            }
        }

        $sailing = Sailing::find($sailingId);

        if (!$sailing) {
            return ['success' => false, 'message' => 'Date invalide ou expirée.'];
        }

        $totalHumans = array_sum($passengers);

        try {
            // Le service doit gérer le format [id => qty] maintenant
            $isAvailable = $this->reservationService->checkAvailability($sailingId, $totalHumans, $options);

            if (!$isAvailable) {
                return ['success' => false, 'message' => 'Désolé, il n\'y a plus assez de places ou d\'options disponibles.'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $cruiseId = $sailing->parentCruiseId;
        $productId = get_post_meta($cruiseId, 'related_wc_product_id', true);

        if (!$productId) {
            return ['success' => false, 'message' => 'Erreur technique : Produit non configuré.'];
        }

        // Calcul du prix total avec les quantités d'options
        $totalPrice = $this->reservationService->calculateTotal($sailingId, $passengers, $options);

        // Résumé pour l'affichage
        $details = [];
        foreach ($passengers as $typeId => $qty) {
            if ($qty > 0) {
                $term = get_term($typeId, 'passenger_type');
                $name = !is_wp_error($term) ? $term->name : 'Passager';
                $details[] = "$qty x $name";
            }
        }

        // Ajout des options au résumé
        foreach ($options as $optId => $qty) {
            if ($qty > 0) {
                $term = get_term($optId, 'extra_option_type');
                $name = !is_wp_error($term) ? $term->name : 'Option';
                $details[] = "$qty x $name";
            }
        }

        $cartItemData = [
            'booking_data' => [
                'sailing_id' => $sailingId,
                'date' => $sailing->start,
                'passengers' => $passengers,
                'options' => $options, // On stocke le format normalisé [id => qty]
                'price_override' => $totalPrice,
                'details_string' => implode(', ', $details)
            ]
        ];

        try {
            if (null === WC()->session) {
                $session = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');
                WC()->session = new $session();
                WC()->session->init();
            }
            if (null === WC()->customer) {
                WC()->customer = new \WC_Customer(get_current_user_id(), true);
            }
            if (null === WC()->cart) {
                WC()->cart = new \WC_Cart();
            }

            WC()->cart->add_to_cart($productId, 1, 0, [], $cartItemData);

            return [
                'success' => true,
                'data' => [
                    'redirect' => wc_get_cart_url(),
                    'message' => 'Ajouté au panier !'
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erreur WooCommerce : ' . $e->getMessage()];
        }
    }
}
