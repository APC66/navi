<?php

namespace App\Http\Controllers;

use App\Models\Sailing;
use App\Services\ReservationService;
use WP_REST_Request;

class CartController
{
    protected $reservationService;

    public function __construct()
    {
        $this->reservationService = new ReservationService;
    }

    public function addToCart(WP_REST_Request $request)
    {
        $sailingId = (int) $request->get_param('sailing_id');
        $passengers = $request->get_param('passengers') ?: []; // ['term_id' => qty]

        // Options peut être [id, id] (vieux format checkbox) ou {id: qty} (nouveau format compteur)
        $optionsRaw = $request->get_param('options') ?: [];
        $options = [];

        foreach ($optionsRaw as $key => $val) {
            if (is_numeric($key)) {
                $options[$key] = (int) $val;
            } else {
                if (is_numeric($val)) {
                    $options[$val] = 1;
                }
            }
        }

        $sailing = Sailing::find($sailingId);

        if (! $sailing) {
            return ['success' => false, 'message' => 'Date invalide ou expirée.'];
        }

        $totalHumans = array_sum($passengers);

        try {
            // Le service doit gérer le format [id => qty] maintenant
            $isAvailable = $this->reservationService->checkAvailability($sailingId, $totalHumans, $options);

            if (! $isAvailable) {
                return ['success' => false, 'message' => 'Désolé, il n\'y a plus assez de places ou d\'options disponibles.'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $cruiseId = $sailing->parentCruiseId;
        $productId = get_post_meta($cruiseId, 'related_wc_product_id', true);

        if (! $productId) {
            return ['success' => false, 'message' => 'Erreur technique : Produit non configuré.'];
        }

        // Calcul du prix total avec les quantités d'options
        $totalPrice = $this->reservationService->calculateTotal($sailingId, $passengers, $options);

        // Résumé pour l'affichage
        $details = [];
        foreach ($passengers as $typeId => $qty) {
            if ($qty > 0) {
                $term = get_term($typeId, 'passenger_type');
                $name = ! is_wp_error($term) ? $term->name : 'Passager';
                $details[] = "$qty x $name";
            }
        }

        // Ajout des options au résumé
        foreach ($options as $optId => $qty) {
            if ($qty > 0) {
                $term = get_term($optId, 'extra_option_type');
                $name = ! is_wp_error($term) ? $term->name : 'Option';
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
                'details_string' => implode(', ', $details),
            ],
            'unique_key' => md5($sailingId.microtime()),
        ];

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

            // Force le rechargement de la session sinon le panier se met pas a jour avec le nouvel item
            WC()->cart->get_cart();

            // Écraser un éventuel item existant pour le même départ
            foreach (WC()->cart->get_cart() as $key => $item) {
                if (isset($item['booking_data']['sailing_id']) && (int) $item['booking_data']['sailing_id'] === $sailingId) {
                    WC()->cart->remove_cart_item($key);
                }
            }

            $cartKey = WC()->cart->add_to_cart($productId, 1, 0, [], $cartItemData);
            WC()->session->save_data();

            if ($cartKey === false) {
                $notices = wc_get_notices('error');
                $message = ! empty($notices) ? $notices[0]['notice'] : 'Erreur inconnue';
                wc_clear_notices();

                return ['success' => false, 'message' => $message];
            }

            return [
                'success' => true,
                'data' => [
                    'redirect' => wc_get_cart_url(),
                    'message' => 'Ajouté au panier !',
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erreur WooCommerce : '.$e->getMessage()];
        }
    }
}
