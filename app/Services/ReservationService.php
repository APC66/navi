<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

class ReservationService
{
    /**
     * Vérifie la disponibilité pour un départ donné
     */
    public function checkAvailability(int $sailingId, int $passengerCount, array $options = []): bool
    {
        clean_post_cache($sailingId);
        $sailing = \App\Models\Sailing::find($sailingId);

        if (! $sailing) {
            throw new Exception('Départ non trouvé.');
        }

        // 1. Vérification du délai de réservation (Cutoff)
        $this->validateBookingCutoff($sailing);

        // 2. Vérification du Quota Global (Passagers)
        $totalQuota = $sailing->quota;
        $bookedCount = (int) get_post_meta($sailingId, 'sailing_config_booked_count', true);

        if (($bookedCount + $passengerCount) > $totalQuota) {
            return false;
        }

        // 3. Vérification des Quotas d'Options
        return $this->checkOptionsAvailability($sailing, $options);
    }

    /**
     * Logique isolée pour le Cutoff
     *
     * @throws Exception
     */
    private function validateBookingCutoff($sailing): void
    {
        $departureDate = $sailing->start;
        if (! $departureDate) {
            return; // Ou throw Exception si la date est obligatoire
        }

        try {
            $timezone = new DateTimeZone(wp_timezone_string() ?: 'UTC');
            $departureTime = new DateTimeImmutable($departureDate, $timezone);
            $now = new DateTimeImmutable('now', $timezone);

            $cruiseId = $sailing->parentCruiseId;
            if (! $cruiseId) {
                return;
            }

            $bookingCutoff = (int) get_field('booking_cutoff', $cruiseId);

            if ($bookingCutoff > 0) {
                $cutoffLimit = $departureTime->modify("-{$bookingCutoff} minutes");

                if ($now >= $cutoffLimit) {
                    throw new Exception('Le délai de réservation pour ce départ est dépassé.');
                }
            }
        } catch (Exception $e) {
            // On ne re-throw que si c'est notre message métier
            if ($e->getMessage() === 'Le délai de réservation pour ce départ est dépassé.') {
                throw $e;
            }
            // Erreur technique de parsing : on log et on laisse passer (ou on bloque, au choix)
            error_log('Erreur technique Cutoff : '.$e->getMessage());
        }
    }

    /**
     * Vérification isolée des options
     */
    private function checkOptionsAvailability($sailing, array $optionsRequested): bool
    {
        $optionsConfig = $sailing->options;
        $optionsSold = get_post_meta($sailing->id, 'sailing_options_booked_counts', true) ?: [];

        if (empty($optionsRequested) || ! is_array($optionsConfig)) {
            return true;
        }

        foreach ($optionsConfig as $optConfig) {
            $typeId = $optConfig['option_type'] ?? null;

            if ($typeId && ! empty($optConfig['has_quota']) && isset($optionsRequested[$typeId])) {
                $qtyRequested = (int) $optionsRequested[$typeId];

                if ($qtyRequested > 0) {
                    $maxQuota = (int) ($optConfig['quota'] ?? 0);
                    $alreadySold = (int) ($optionsSold[$typeId] ?? 0);

                    if (($alreadySold + $qtyRequested) > $maxQuota) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function calculateTotal(int $sailingId, array $passengers, array $options): float
    {
        $sailing = \App\Models\Sailing::find($sailingId);
        if (! $sailing) {
            return 0.0;
        }

        $total = 0.0;
        $faresConfig = $sailing->fares;
        $optionsConfig = $sailing->options;

        // Prix Passagers
        if ($faresConfig && ! empty($passengers)) {
            foreach ($faresConfig as $fare) {
                $typeId = $fare['passenger_type'] ?? null;
                if ($typeId && isset($passengers[$typeId])) {
                    $total += (int) $passengers[$typeId] * (float) ($fare['price'] ?? 0);
                }
            }
        }

        // Prix Options
        if ($optionsConfig && ! empty($options)) {
            foreach ($optionsConfig as $opt) {
                $typeId = $opt['option_type'] ?? null;
                if ($typeId && isset($options[$typeId]) && (int) $options[$typeId] > 0) {
                    $total += (int) $options[$typeId] * (float) ($opt['price'] ?? 0);
                }
            }
        }

        return $total;
    }
}
