<?php

namespace App\Http\Controllers\Api;

use App\Models\Sailing;
use WP_REST_Request;

class CalendarController
{
    public function index(WP_REST_Request $request)
    {
        $context = $request->get_param('context');
        $is_admin = $context === 'admin' && current_user_can('edit_posts');

        // LOGIQUE DE RÉCUPÉRATION (Back-end / Disponibilité technique)
        // On utilise les statuts natifs WP.
        // En front, on ne veut voir que ce qui est "publié".
        // Si un départ est "Annulé" via la taxonomie mais toujours "Publié" WP, il sera récupéré (ce qu'on veut pour l'afficher en gris).
        // Si un départ est "Brouillon", il est caché du front.
        $post_statuses = ['publish'];

        if ($is_admin) {
            $post_statuses = ['publish', 'draft', 'future', 'pending'];
        }

        $args = [
            'post_status' => $post_statuses,
            'meta_query'  => ['relation' => 'AND'],
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
                'key'     => 'sailing_config_departure_date',
                'value'   => [$startFormatted, $endFormatted],
                'compare' => 'BETWEEN',
                'type'    => 'DATETIME'
            ];
        }

        if ($request->get_param('cruise_id')) {
            $args['meta_query'][] = [
                'key'   => 'sailing_config_parent_cruise',
                'value' => $request->get_param('cruise_id'),
                'compare' => '='
            ];
        }

        $sailings = Sailing::fetch($args);

        if ($sailings->isEmpty()) {
            return [];
        }

        $events = $sailings->map(function ($sailing) use ($is_admin) {

            $quota = $sailing->quota;
            $start = $sailing->start;
            $end = $sailing->end;

            $booked = (int) get_post_meta($sailing->ID, 'sailing_config_booked_count', true);
            $remaining = $quota - $booked;

            $title = $sailing->post_title;

            // --- RÉCUPÉRATION DONNÉES COMMERCIALES ---
            $fares = $sailing->fares ?: [];
            $formattedFares = array_map(function($f) {
                $termId = $f['passenger_type'] ?? 0;
                $termName = 'Standard';
                if ($termId) {
                    $term = get_term($termId, 'passenger_type');
                    if ($term && !is_wp_error($term)) $termName = $term->name;
                }
                return [
                    'id' => $termId,
                    'name' => $termName,
                    'price' => (float) ($f['price'] ?? 0)
                ];
            }, $fares);

            $options = $sailing->options ?: [];
            $formattedOptions = array_map(function($o) {
                $termId = $o['option_type'] ?? 0;
                $termName = 'Option';
                if ($termId) {
                    $term = get_term($termId, 'extra_option_type');
                    if ($term && !is_wp_error($term)) $termName = $term->name;
                }
                return [
                    'id' => $termId,
                    'name' => $termName,
                    'price' => (float) ($o['price'] ?? 0),
                    'has_quota' => !empty($o['has_quota']),
                    'quota' => (int) ($o['quota'] ?? 0)
                ];
            }, $options);

            // --- LOGIQUE VISUELLE (Front-end / Affichage) ---
            // On se base sur la taxonomie 'sailing_status' pour déterminer l'aspect et la sélection.
            $status_terms = wp_get_post_terms($sailing->ID, 'sailing_status', ['fields' => 'names']);
            $status_label = !empty($status_terms) ? $status_terms[0] : 'Actif';

            $color = '#3788d8'; // Bleu (Actif)
            $classNames = [];
            $isSelectable = true;

            // 1. Statut "Annulé" (Taxonomie)
            if ($status_label === 'Annulé') {
                $color = '#718096'; // Gris
                $title = $is_admin ? '❌ ANNULÉ - ' . $title : 'Annulé';
                $remaining = 0; // Visuellement 0
                $classNames[] = 'evt-cancelled';
                $isSelectable = false; // Bloque le clic en front
            }
            // 2. Statut "Reporté" (Taxonomie)
            elseif ($status_label === 'Reporté') {
                $color = '#d69e2e'; // Orange
                $title = $is_admin ? '⚠️ REPORTÉ - ' . $title : 'Reporté';
                $remaining = 0;
                $classNames[] = 'evt-postponed';
                $isSelectable = false;
            }
            // 3. Statut "Complet" (Taxonomie explicite OU Calculé)
            elseif ($status_label === 'Complet' || $remaining <= 0) {
                $color = '#dc2626'; // Rouge
                $title = $is_admin ? $title . ' (Complet)' : 'Complet';
                $remaining = 0;
                $classNames[] = 'evt-full';
                $isSelectable = false;
            }
            // 4. Statut "Brouillon" (WP natif - Admin seulement)
            elseif ($sailing->post_status === 'draft') {
                $color = '#9ca3af';
                $title .= ' (Brouillon)';
                $isSelectable = false;
            }

            $event = [
                'id'              => $sailing->ID,
                'title'           => $is_admin ? "$title [$remaining/$quota]" : 'Disponible',
                'start'           => $start,
                'end'             => $end,
                'allDay'          => false,
                'backgroundColor' => $color,
                'borderColor'     => $color,
                'classNames'      => $classNames,
                'extendedProps'   => [
                    'quota'     => $quota,
                    'booked'    => $booked,
                    'available' => $remaining,
                    'cruise_id' => $sailing->parent_cruise_id,
                    'status'    => $status_label, // Info pour le front
                    'fares'     => $formattedFares,
                    'options'   => $formattedOptions,
                    'is_selectable' => $isSelectable // Info critique pour le widget JS
                ]
            ];

            if ($is_admin) {
                $event['url'] = get_edit_post_link($sailing->ID, 'raw');
                $event['editable'] = ($status_label === 'Actif');
            }

            return $event;
        });

        return $events->toArray();
    }
}
