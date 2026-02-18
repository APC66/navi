<?php

namespace App\Services;

use App\Models\Sailing;
use Exception;

class ReservationService
{
    /**
     * Vérifie la disponibilité pour un départ donné
     * @param array $options Tableau [option_term_id => quantity]
     */
    public function checkAvailability(int $sailingId, int $passengerCount, array $options = []): bool
    {
        clean_post_cache($sailingId);
        $sailing = \App\Models\Sailing::find($sailingId);

        if (!$sailing) {
            throw new Exception("Départ non trouvé.");
        }

        // 1. Vérification du Quota Global (Passagers)
        $totalQuota = $sailing->quota;
        $bookedCount = (int) get_post_meta($sailingId, 'sailing_config_booked_count', true);

        if (($bookedCount + $passengerCount) > $totalQuota) {
            return false;
        }

        // 2. Vérification des Quotas d'Options
        $optionsConfig = $sailing->options;
        $optionsSold = get_post_meta($sailingId, 'sailing_options_booked_counts', true) ?: [];

        if (!empty($options) && is_array($optionsConfig)) {
            foreach ($optionsConfig as $optConfig) {
                $typeId = $optConfig['option_type'] ?? null;

                // CORRECTION : On vérifie si l'option est dans le tableau (clé) et si la quantité > 0
                if ($typeId && !empty($optConfig['has_quota']) && isset($options[$typeId])) {

                    $qtyRequested = (int) $options[$typeId];
                    if ($qtyRequested > 0) {
                        $maxQuota = (int) ($optConfig['quota'] ?? 0);
                        $alreadySold = isset($optionsSold[$typeId]) ? (int)$optionsSold[$typeId] : 0;

                        if (($alreadySold + $qtyRequested) > $maxQuota) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Calcule le prix total de la réservation
     * @param array $options Tableau [option_term_id => quantity]
     */
    public function calculateTotal(int $sailingId, array $passengers, array $options): float
    {
        $sailing = \App\Models\Sailing::find($sailingId);
        if (!$sailing) return 0.0;

        $total = 0.0;

        $faresConfig = $sailing->fares;
        $optionsConfig = $sailing->options;

        // 1. Prix Passagers
        if ($faresConfig && !empty($passengers)) {
            foreach ($faresConfig as $fare) {
                $typeId = $fare['passenger_type'] ?? null;
                if ($typeId && isset($passengers[$typeId])) {
                    $qty = (int) $passengers[$typeId];
                    $price = (float) ($fare['price'] ?? 0);
                    $total += $qty * $price;
                }
            }
        }

        // 2. Prix Options
        if ($optionsConfig && !empty($options)) {
            foreach ($optionsConfig as $opt) {
                $typeId = $opt['option_type'] ?? null;

                // CORRECTION : Gestion de la quantité
                if ($typeId && isset($options[$typeId])) {
                    $qty = (int) $options[$typeId];
                    if ($qty > 0) {
                        $price = (float) ($opt['price'] ?? 0);
                        $total += $qty * $price;
                    }
                }
            }
        }

        return $total;
    }
}
