<?php

namespace App\Http\Controllers\Api;

use App\Models\Cruise;
use App\Models\Sailing;
use App\Services\LangService;
use WP_REST_Request;

class PlanningController
{
    private const SOURCE_LANG = 'fr';

    private const SUPPORTED_LANGS = ['en', 'de', 'es'];

    /**
     * Clé API DeepL
     * À définir dans wp-config.php : define('DEEPL_API_KEY', 'ta-clé:fx');
     */
    private function getApiKey(): string
    {
        return defined('DEEPL_API_KEY') ? DEEPL_API_KEY : '';
    }

    /**
     * Traduit un tableau de strings via DeepL API.
     * Envoie toutes les strings en une seule requête.
     */
    private function translateBatch(array $texts, string $target): array
    {
        $apiKey = $this->getApiKey();

        if (empty($apiKey) || empty($texts)) {
            return $texts;
        }

        $targetUpper = strtoupper($target);
        if ($targetUpper === 'EN') {
            $targetUpper = 'EN-GB';
        }

        $toTranslate = array_filter($texts, fn ($t) => ! empty(trim($t)));

        if (empty($toTranslate)) {
            return $texts;
        }

        $endpoint = str_ends_with($apiKey, ':fx')
            ? 'https://api-free.deepl.com/v2/translate'
            : 'https://api.deepl.com/v2/translate';

        $body = http_build_query([
            'source_lang' => strtoupper(self::SOURCE_LANG),
            'target_lang' => $targetUpper,
        ]);
        foreach (array_values($toTranslate) as $text) {
            $body .= '&text='.urlencode($text);
        }

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Authorization' => 'DeepL-Auth-Key '.$apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => $body,
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            error_log('DeepL API error: '.$response->get_error_message());

            return $texts;
        }

        $decoded = json_decode(wp_remote_retrieve_body($response), true);
        $translations = $decoded['translations'] ?? [];

        if (empty($translations)) {
            error_log('DeepL API: réponse vide — '.wp_remote_retrieve_body($response));

            return $texts;
        }

        $result = $texts;
        $keys = array_keys($toTranslate);
        foreach ($translations as $i => $t) {
            if (isset($keys[$i])) {
                $result[$keys[$i]] = $t['text'];
            }
        }

        return $result;
    }

    /**
     * Récupère tous les départs pour une plage de dates donnée.
     * $lang est injecté automatiquement par ApiServiceProvider via réflexion.
     */
    public function week(WP_REST_Request $request, string $start = '', string $end = '', string $lang = '')
    {
        if (! $start || ! $end) {
            return new \WP_Error('missing_dates', 'Les dates de début et de fin sont requises.', ['status' => 400]);
        }

        $needsTranslation = in_array($lang, self::SUPPORTED_LANGS, true);

        // =================================================================
        // CACHE
        // =================================================================
        if ($needsTranslation) {
            $cacheKey = "planning_{$start}_{$end}_{$lang}";
            $cached = get_transient($cacheKey);
            if ($cached !== false) {
                return rest_ensure_response($cached);
            }
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
        // 2. EAGER LOADING
        // =================================================================
        $cruiseIds = [];
        foreach ($sailings as $sailing) {
            if ($sailing->parentCruiseId) {
                $cruiseIds[] = $sailing->parentCruiseId;
            }
        }
        $cruiseIds = array_unique($cruiseIds);

        if (! empty($cruiseIds)) {
            _prime_post_caches($cruiseIds, true, true);
        }

        // =================================================================
        // 3. CONSTRUCTION DU TABLEAU
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

            $booked = (int) get_post_meta($sailing->ID, 'sailing_config_booked_count', true) ?: 0;
            $available = max(0, $sailing->quota - $booked);

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

            $statusTerms = wp_get_post_terms($sailing->ID, 'sailing_status', ['fields' => 'names']);
            $apiStatus = ! is_wp_error($statusTerms) && ! empty($statusTerms) ? $statusTerms[0] : 'Actif';

            $status = 'Dispo';
            if ($apiStatus === 'Annulé') {
                $status = 'Annulé';
            } elseif ($apiStatus === 'Reporté') {
                $status = 'Reporté';
            } elseif ($isCutoffPassed) {
                $status = 'Complet';
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
            $tagIds = ! is_wp_error($tagTerms) && ! empty($tagTerms)
                ? array_map('intval', wp_list_pluck($tagTerms, 'term_id'))
                : [];

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
                'status' => $status,       // toujours en français — logique JS
                'status_label' => $status,       // sera traduit via fichier de langue
                'available' => $available,
            ];
        }

        // =================================================================
        // 4. TRADUCTION
        // =================================================================
        if ($needsTranslation && ! empty($sailingsData)) {

            // Charge les fichiers de langue uniquement si nécessaire
            $statusTranslations = LangService::get('planning', $lang, 'statuses') ?? [];
            $labels = LangService::get('planning', $lang, 'labels') ?? [];

            // Collecte uniquement cruise_title et port pour DeepL
            $textsToTranslate = [];
            foreach ($sailingsData as $item) {
                $textsToTranslate[] = $item['cruise_title'];
                $textsToTranslate[] = $item['port'];
            }

            // Une seule requête DeepL pour tous les titres et ports
            $translated = $this->translateBatch($textsToTranslate, $lang);

            // Réinjecte les traductions
            $i = 0;
            foreach ($sailingsData as &$item) {
                $item['cruise_title'] = $translated[$i++];
                $item['port'] = $translated[$i++];
                // Statut via fichier de langue, fallback sur la valeur française
                $item['status_label'] = $statusTranslations[$item['status']] ?? $item['status'];
            }
            unset($item);

            // Réponse avec sailings + labels traduits
            $response = [
                'sailings' => $sailingsData,
                'labels' => $labels,
            ];

            set_transient($cacheKey, $response, 12 * HOUR_IN_SECONDS);

            return rest_ensure_response($response);
        }

        // Langue source (français) — retourne uniquement les sailings, pas de labels
        return rest_ensure_response($sailingsData);
    }
}
