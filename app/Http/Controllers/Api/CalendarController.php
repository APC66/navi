<?php

namespace App\Http\Controllers\Api;

use App\Models\Sailing;
use App\Services\LangService;
use WP_REST_Request;

class CalendarController
{
    private const SOURCE_LANG = 'fr';

    private const SUPPORTED_LANGS = ['en', 'de', 'es'];

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
            error_log('DeepL API error (Calendar): '.$response->get_error_message());

            return $texts;
        }

        $decoded = json_decode(wp_remote_retrieve_body($response), true);
        $translations = $decoded['translations'] ?? [];

        if (empty($translations)) {
            error_log('DeepL API (Calendar): réponse vide — '.wp_remote_retrieve_body($response));

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
     * $lang est injecté automatiquement par ApiServiceProvider via réflexion.
     */
    public function index(WP_REST_Request $request, string $lang = '')
    {
        $context = $request->get_param('context');
        $is_admin = $context === 'admin' && current_user_can('edit_posts');

        $cruiseId = (int) $request->get_param('cruise_id');
        $portName = null;

        if ($cruiseId) {
            $terms = wp_get_post_terms($cruiseId, 'harbor', ['fields' => 'names']);
            if (! is_wp_error($terms) && ! empty($terms)) {
                $portName = $terms[0];
            }
        }

        $post_statuses = ['publish'];
        if ($is_admin) {
            $post_statuses = ['publish', 'draft', 'future', 'pending'];
        }

        $args = [
            'post_status' => $post_statuses,
            'meta_query' => ['relation' => 'AND'],
        ];

        $start = $request->get_param('start');
        $end = $request->get_param('end');

        if ($start && $end) {
            try {
                $startFormatted = (new \DateTime($start))->format('Y-m-d H:i:s');
                $endFormatted = (new \DateTime($end))->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $startFormatted = $start;
                $endFormatted = $end;
            }

            $args['meta_query'][] = [
                'key' => 'sailing_config_departure_date',
                'value' => [$startFormatted, $endFormatted],
                'compare' => 'BETWEEN',
                'type' => 'DATETIME',
            ];
        }

        if ($request->get_param('cruise_id')) {
            $args['meta_query'][] = [
                'key' => 'sailing_config_parent_cruise',
                'value' => $request->get_param('cruise_id'),
                'compare' => '=',
            ];
        }

        $sailings = Sailing::fetch($args);

        if ($sailings->isEmpty()) {
            return [];
        }

        // =================================================================
        // CACHE — uniquement pour les langues étrangères et hors admin
        // =================================================================
        $needsTranslation = ! $is_admin && in_array($lang, self::SUPPORTED_LANGS, true);
        $cacheKey = null;

        if ($needsTranslation) {
            $cacheKey = 'calendar_'.$cruiseId.'_'.$lang;
            $cached = get_transient($cacheKey);
            if ($cached !== false) {
                return $cached;
            }
        }

        // =================================================================
        // CONSTRUCTION DES EVENTS
        // =================================================================
        $events = $sailings->map(function ($sailing) use ($is_admin, $portName) {

            $quota = $sailing->quota;
            $start = $sailing->start;
            $end = $sailing->end;
            $booked = (int) get_post_meta($sailing->ID, 'sailing_config_booked_count', true);
            $remaining = $quota - $booked;
            $title = html_entity_decode($sailing->title, ENT_QUOTES);

            // Tarifs
            $fares = $sailing->fares ?: [];
            $formattedFares = array_map(function ($f) {
                $termId = $f['passenger_type'] ?? 0;
                $termName = 'Standard';
                if ($termId) {
                    $term = get_term($termId, 'passenger_type');
                    if ($term && ! is_wp_error($term)) {
                        $termName = $term->name;
                    }
                }

                return [
                    'id' => $termId,
                    'name' => $termName,
                    'price' => (float) ($f['price'] ?? 0),
                ];
            }, $fares);

            // Options
            $options = $sailing->options ?: [];
            $formattedOptions = array_map(function ($o) {
                $termId = $o['option_type'] ?? 0;
                $termName = 'Option';
                if ($termId) {
                    $term = get_term($termId, 'extra_option_type');
                    if ($term && ! is_wp_error($term)) {
                        $termName = $term->name;
                    }
                }

                return [
                    'id' => $termId,
                    'name' => $termName,
                    'price' => (float) ($o['price'] ?? 0),
                    'has_quota' => ! empty($o['has_quota']),
                    'quota' => (int) ($o['quota'] ?? 0),
                ];
            }, $options);

            // Statut
            $status_terms = wp_get_post_terms($sailing->ID, 'sailing_status', ['fields' => 'names']);
            $status_label = ! empty($status_terms) ? $status_terms[0] : 'Actif';

            $color = '#3788d8';
            $classNames = [];
            $isSelectable = true;

            if ($status_label === 'Annulé') {
                $color = '#718096';
                $title = $is_admin ? '❌ ANNULÉ - '.$title : 'Annulé';
                $remaining = 0;
                $classNames[] = 'evt-cancelled';
                $isSelectable = false;
            } elseif ($status_label === 'Reporté') {
                $color = '#d69e2e';
                $title = $is_admin ? '⚠️ REPORTÉ - '.$title : 'Reporté';
                $remaining = 0;
                $classNames[] = 'evt-postponed';
                $isSelectable = false;
            } elseif ($status_label === 'Complet' || $remaining <= 0) {
                $color = '#dc2626';
                $title = $is_admin ? $title.' (Complet)' : 'Complet';
                $remaining = 0;
                $classNames[] = 'evt-full';
                $isSelectable = false;
            } elseif ($sailing->post_status === 'draft') {
                $color = '#9ca3af';
                $title .= ' (Brouillon)';
                $isSelectable = false;
            }

            // Surbooking : places réservées au-delà du quota (hors annulé / reporté).
            $overbook = in_array($status_label, ['Annulé', 'Reporté'], true) ? 0 : max(0, $booked - $quota);

            $event = [
                'id' => $sailing->ID,
                'title' => $is_admin ? "$title [$remaining/$quota]" : 'Disponible',
                'start' => $start,
                'end' => $end,
                'allDay' => false,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'port' => $portName,
                'classNames' => $classNames,
                'extendedProps' => [
                    'quota' => $quota,
                    'booked' => $booked,
                    'available' => $remaining,
                    'overbook' => $overbook,
                    'cruise_id' => $sailing->parent_cruise_id,
                    'status' => $status_label, // toujours en français — logique JS
                    'fares' => $formattedFares,
                    'options' => $formattedOptions,
                    'is_selectable' => $isSelectable,
                ],
            ];

            if ($is_admin) {
                $event['url'] = get_edit_post_link($sailing->ID, 'raw');
                $event['editable'] = ($status_label === 'Actif');
            }

            return $event;
        })->toArray();

        // =================================================================
        // TRADUCTION EN BATCH
        // =================================================================
        if ($needsTranslation && ! empty($events)) {

            // Charge les statuts traduits via fichier de langue
            $statusTranslations = LangService::get('planning', $lang, 'statuses') ?? [];

            // Collecte tous les noms de tarifs et options à traduire via DeepL
            // (noms de taxonomies custom — pas de traduction statique possible)
            $textsToTranslate = [];
            foreach ($events as $event) {
                foreach ($event['extendedProps']['fares'] as $fare) {
                    $textsToTranslate[] = $fare['name'];
                }
                foreach ($event['extendedProps']['options'] as $option) {
                    $textsToTranslate[] = $option['name'];
                }
            }

            $translated = $this->translateBatch($textsToTranslate, $lang);

            // Réinjecte les traductions
            $i = 0;
            foreach ($events as &$event) {
                foreach ($event['extendedProps']['fares'] as &$fare) {
                    $fare['name'] = $translated[$i++];
                }
                unset($fare);

                foreach ($event['extendedProps']['options'] as &$option) {
                    $option['name'] = $translated[$i++];
                }
                unset($option);

                // Statut via fichier de langue
                $status = $event['extendedProps']['status'];
                $event['extendedProps']['status_label'] = $statusTranslations[$status] ?? $status;
            }
            unset($event);

            // Cache sans expiration fixe — invalidé à la sauvegarde d'un sailing
            set_transient($cacheKey, $events, 12 * HOUR_IN_SECONDS);
        }

        return $events;
    }
}
