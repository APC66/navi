<?php

namespace App\Http\Controllers\Api;

use App\Models\Cruise;
use App\Models\Sailing;
use WP_REST_Request;

class PlanningController
{
    /**
     * Récupère tous les départs (sailings) pour une plage de dates donnée
     * avec optimisation du chargement (Eager Loading) en temps réel (sans cache).
     */
    public function week(WP_REST_Request $request)
    {
        $start = $request->get_param('start'); // Format attendu: YYYY-MM-DD
        $end = $request->get_param('end');     // Format attendu: YYYY-MM-DD

        if (! $start || ! $end) {
            return new \WP_Error('missing_dates', 'Les dates de début et de fin sont requises.', ['status' => 400]);
        }

        // =================================================================
        // 1. RÉCUPÉRATION DES DÉPARTS
        // =================================================================
        $sailings = Sailing::fetch([
            'posts_per_page' => -1,
            'post_status' => ['publish', 'cancelled'],
            'meta_query' => [
                [
                    'key' => 'sailing_config_departure_date',
                    'value' => [$start.' 00:00:00', $end.' 23:59:59'],
                    'compare' => 'BETWEEN',
                    'type' => 'DATETIME',
                ],
            ],
            'orderby' => 'meta_value',
            'meta_key' => 'sailing_config_departure_date',
            'order' => 'ASC',
        ]);

        // =================================================================
        // 2. OPTIMISATION (EAGER LOADING)
        // =================================================================
        $cruiseIds = [];
        foreach ($sailings as $sailing) {
            if ($sailing->parentCruiseId) {
                $cruiseIds[] = $sailing->parentCruiseId;
            }
        }
        $cruiseIds = array_unique($cruiseIds);

        // Charge toutes les croisières en RAM d'un coup pour éviter le N+1
        if (! empty($cruiseIds)) {
            _prime_post_caches($cruiseIds, true, true);
        }

        // =================================================================
        // 3. CONSTRUCTION DU TABLEAU (Temps réel)
        // =================================================================
        $sailingsData = [];

        foreach ($sailings as $sailing) {

            $cruiseId = $sailing->parentCruiseId;
            if (! $cruiseId) {
                continue;
            }

            $cruise = Cruise::find($cruiseId);
            if (! $cruise) {
                continue;
            }

            // Récupération des places restantes
            $booked = (int) get_post_meta($sailing->ID, 'sailing_config_booked_count', true) ?: 0;
            $available = max(0, $sailing->quota - $booked);

            // Vérification du booking cutoff
            $isCutoffPassed = false;
            $departureDate = $sailing->start;
            if ($departureDate) {
                try {
                    $departureTime = new \DateTime($departureDate);
                    $now = new \DateTime('now', new \DateTimeZone(wp_timezone_string()));
                    $bookingCutoff = (int) get_field('booking_cutoff', $cruiseId);

                    if ($bookingCutoff > 0) {
                        $cutoffTime = clone $departureTime;
                        $cutoffTime->modify("-{$bookingCutoff} minutes");
                        $isCutoffPassed = ($now >= $cutoffTime);
                    }
                } catch (\Exception $e) {
                    error_log('Erreur calcul cutoff dans PlanningController: '.$e->getMessage());
                }
            }

            // Gestion du statut
            $statusTerms = wp_get_post_terms($sailing->ID, 'sailing_status', ['fields' => 'names']);
            $apiStatus = ! is_wp_error($statusTerms) && ! empty($statusTerms) ? $statusTerms[0] : 'Actif';

            $status = 'Dispo';
            if ($apiStatus === 'Annulé') {
                $status = 'Annulé';
            } elseif ($apiStatus === 'Reporté') {
                $status = 'Reporté';
            } elseif ($isCutoffPassed) {
                $status = 'Complet'; // On affiche "Complet" si le délai est dépassé
            } elseif ($apiStatus === 'Complet' || $available <= 0) {
                $status = 'Complet';
            } elseif ($available > 0 && $available <= 5) {
                $status = 'Limité';
            }

            // Récupération des taxonomies liées à la croisière parente
            $harborId = $cruise->harbor->term_id ?? null;
            $harborName = $cruise->harbor->name ?? '';

            $typeTerms = wp_get_post_terms($cruiseId, 'cruise_type');
            $typeId = ! is_wp_error($typeTerms) && ! empty($typeTerms) ? $typeTerms[0]->term_id : null;

            $tagTerms = wp_get_post_terms($cruiseId, 'cruise_tag');
            $tagIds = ! is_wp_error($tagTerms) && ! empty($tagTerms) ? array_map('intval', wp_list_pluck($tagTerms, 'term_id')) : [];

            // Ajout au tableau de réponse
            $sailingsData[] = [
                'id' => $sailing->ID,
                'datetime' => $sailing->start,
                'end' => $sailing->end,
                'return_time' => $sailing->end ?? get_post_meta($sailing->ID, 'sailing_config_return_date', true),
                'cruise_title' => html_entity_decode($cruise->title, ENT_QUOTES),
                'cruise_url' => $cruise->permalink,
                'port' => $harborName,
                'port_id' => $harborId,
                'type_id' => $typeId,
                'tags' => $tagIds,
                'status' => $status,
                'available' => $available,
            ];
        }

        return rest_ensure_response($sailingsData);
    }
}
