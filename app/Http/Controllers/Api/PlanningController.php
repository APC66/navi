<?php

namespace App\Http\Controllers\Api;

use App\Models\Cruise;
use App\Models\Sailing;
use WP_REST_Request;

class PlanningController
{
    /**
     * Récupère tous les départs (sailings) pour une plage de dates donnée
     * en utilisant les modèles pour centraliser la logique.
     */
    public function week(WP_REST_Request $request)
    {
        $start = $request->get_param('start'); // Format attendu: YYYY-MM-DD
        $end = $request->get_param('end');     // Format attendu: YYYY-MM-DD

        if (! $start || ! $end) {
            return new \WP_Error('missing_dates', 'Les dates de début et de fin sont requises.', ['status' => 400]);
        }

        // 1. On utilise le modèle Sailing pour récupérer la collection de départs
        $sailings = Sailing::fetch([
            'posts_per_page' => -1,
            'post_status' => ['publish', 'cancelled'], // On inclut les annulés
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

        $sailingsData = [];

        foreach ($sailings as $sailing) {
            // 2. On instancie la Croisière parente via le modèle
            $cruiseId = $sailing->parentCruiseId;
            if (! $cruiseId) {
                continue;
            }

            $cruise = Cruise::find($cruiseId);
            if (! $cruise) {
                continue;
            }

            // 3. Récupération des données via les accesseurs des modèles
            $booked = (int) get_post_meta($sailing->ID, 'sailing_config_booked_count', true) ?: 0;
            $available = max(0, $sailing->quota - $booked);

            // Statuts (API vs Maquette)
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

            // Taxonomies depuis le modèle Cruise
            $harborId = $cruise->harbor->term_id ?? null;
            $harborName = $cruise->harbor->name ?? '';

            // Pour le type de croisière (utile pour le filtre)
            $typeTerms = wp_get_post_terms($cruiseId, 'cruise_type');
            $typeId = ! is_wp_error($typeTerms) && ! empty($typeTerms) ? $typeTerms[0]->term_id : null;

            // 4. On construit le tableau pour le front-end
            $sailingsData[] = [
                'id' => $sailing->ID,
                'datetime' => $sailing->start,
                'cruise_title' => $cruise->title,
                'cruise_url' => $cruise->permalink,
                'port' => $harborName,
                'port_id' => $harborId,
                'type_id' => $typeId,
                'status' => $status,
                'available' => $available,
            ];
        }

        return rest_ensure_response($sailingsData);
    }
}
