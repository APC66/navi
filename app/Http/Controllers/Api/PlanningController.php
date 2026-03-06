<?php

namespace App\Http\Controllers\Api;

use App\Models\Cruise;
use App\Models\Sailing;
use WP_REST_Request;

class PlanningController
{
    /**
     * Récupère tous les départs (sailings) pour une plage de dates donnée
     * avec optimisation du chargement (Eager Loading) et Cache Hybride.
     */
    public function week(WP_REST_Request $request)
    {
        $start = $request->get_param('start'); // Format attendu: YYYY-MM-DD
        $end = $request->get_param('end');     // Format attendu: YYYY-MM-DD

        if (! $start || ! $end) {
            return new \WP_Error('missing_dates', 'Les dates de début et de fin sont requises.', ['status' => 400]);
        }

        // =================================================================
        // 1. LECTURE DU CACHE TRANSIENT (Bypass de l'exécution PHP lourde)
        // =================================================================
        $cacheKey = 'api_planning_week_'.md5($start.'_'.$end);
        $cachedData = get_transient($cacheKey);

        if ($cachedData !== false) {
            // Le lourd (modèles, ACF) est en cache.
            // On met juste à jour les stocks et le statut en temps réel (instantané via Redis)
            foreach ($cachedData as &$data) {
                $sailingId = $data['id'];

                // Vérification en direct (0.1ms)
                $booked = (int) get_post_meta($sailingId, 'sailing_config_booked_count', true) ?: 0;
                $available = max(0, $data['_raw_quota'] - $booked);
                $data['available'] = $available;

                $statusTerms = wp_get_post_terms($sailingId, 'sailing_status', ['fields' => 'names']);
                $apiStatus = ! is_wp_error($statusTerms) && ! empty($statusTerms) ? $statusTerms[0] : 'Actif';

                $status = 'Dispo';
                if ($apiStatus === 'Annulé') {
                    $status = 'Annulé';
                } elseif ($apiStatus === 'Reporté') {
                    $status = 'Reporté';
                } elseif ($apiStatus === 'Complet' || $available <= 0) {
                    $status = 'Complet';
                } elseif ($available > 0 && $available <= 5) {
                    $status = 'Limité';
                }
                $data['status'] = $status;
            }

            return rest_ensure_response($cachedData);
        }

        // =================================================================
        // 2. GÉNÉRATION LOURDE (Exécuté 1x toutes les 15 minutes)
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

        $cruiseIds = [];
        foreach ($sailings as $sailing) {
            if ($sailing->parentCruiseId) {
                $cruiseIds[] = $sailing->parentCruiseId;
            }
        }
        $cruiseIds = array_unique($cruiseIds);

        // Eager Loading
        if (! empty($cruiseIds)) {
            _prime_post_caches($cruiseIds, true, true);
        }

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

            // Récupération initiale (sera écrasée par la vérif live au prochain hit)
            $booked = (int) get_post_meta($sailing->ID, 'sailing_config_booked_count', true) ?: 0;
            $available = max(0, $sailing->quota - $booked);

            $statusTerms = wp_get_post_terms($sailing->ID, 'sailing_status', ['fields' => 'names']);
            $apiStatus = ! is_wp_error($statusTerms) && ! empty($statusTerms) ? $statusTerms[0] : 'Actif';

            $status = 'Dispo';
            if ($apiStatus === 'Annulé') {
                $status = 'Annulé';
            } elseif ($apiStatus === 'Reporté') {
                $status = 'Reporté';
            } elseif ($apiStatus === 'Complet' || $available <= 0) {
                $status = 'Complet';
            } elseif ($available > 0 && $available <= 5) {
                $status = 'Limité';
            }

            $harborId = $cruise->harbor->term_id ?? null;
            $harborName = $cruise->harbor->name ?? '';

            $typeTerms = wp_get_post_terms($cruiseId, 'cruise_type');
            $typeId = ! is_wp_error($typeTerms) && ! empty($typeTerms) ? $typeTerms[0]->term_id : null;

            $tagTerms = wp_get_post_terms($cruiseId, 'cruise_tag');
            $tagIds = ! is_wp_error($tagTerms) && ! empty($tagTerms) ? array_map('intval', wp_list_pluck($tagTerms, 'term_id')) : [];

            $sailingsData[] = [
                'id' => $sailing->ID,
                'datetime' => $sailing->start,
                'cruise_title' => $cruise->title,
                'cruise_url' => $cruise->permalink,
                'port' => $harborName,
                'port_id' => $harborId,
                'type_id' => $typeId,
                'tags' => $tagIds,
                'status' => $status,
                'available' => $available,
                '_raw_quota' => $sailing->quota, // Caché mais vital pour les calculs live
            ];
        }

        // 3. ENREGISTREMENT DU CACHE (Durée : 15 minutes)
        set_transient($cacheKey, $sailingsData, 15 * MINUTE_IN_SECONDS);

        return rest_ensure_response($sailingsData);
    }
}
