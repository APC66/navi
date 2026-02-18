<?php

namespace App\Services;

class CruiseManagement
{
    public function init()
    {
        add_action('acf/save_post', [$this, 'generateSailingsFromBatch'], 20);
        add_filter('acf/load_field/key=field_cruise_existing_sailings', [$this, 'loadExistingSailingsTable']);
        add_action('save_post_cruise', [$this, 'syncCruiseToProduct'], 20, 3);
    }

    public function loadExistingSailingsTable($field)
    {
        global $post;
        if (!is_admin() || !$post || get_post_type($post) !== 'cruise') return $field;

        $sailings = get_posts([
            'post_type' => 'sailing',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'future', 'cancelled'], // On ajoute cancelled pour voir les annulés
            'meta_key' => 'sailing_config_departure_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => [[
                'key' => 'sailing_config_parent_cruise',
                'value' => $post->ID,
                'compare' => '='
            ]],
            'suppress_filters' => false
        ]);

        if (empty($sailings)) {
            $field['message'] = '<div class="notice notice-info inline"><p>Aucun départ programmé. Utilisez le générateur.</p></div>';
            return $field;
        }

        $html = '<div style="max-height: 400px; overflow: auto; border:1px solid #c3c4c7; border-radius:4px; box-sizing: border-box; width: 100%;">';
        $html .= '<table class="wp-list-table widefat fixed striped table-view-list" style="border:0; width: 100%; table-layout: auto;">';
        $html .= '<thead><tr>
            <th style="width: 50px;">ID</th>
            <th>Départ</th>
            <th>Retour</th>
            <th>Quota</th>
            <th>Statut</th>
            <th style="text-align:right;">Actions</th>
        </tr></thead>';
        $html .= '<tbody>';

        foreach ($sailings as $sailing) {
            $group = 'sailing_config_';
            $date_raw = get_post_meta($sailing->ID, $group.'departure_date', true);
            $end_raw = get_post_meta($sailing->ID, $group.'arrival_date', true);
            $quota = get_post_meta($sailing->ID, $group.'quota', true);

            // Récupération du statut via la taxonomie
            $status_terms = wp_get_post_terms($sailing->ID, 'sailing_status', ['fields' => 'names']);
            $status_label = !empty($status_terms) ? $status_terms[0] : 'Non défini';

            $status_color = 'green';
            if ($status_label === 'Annulé') $status_color = 'red';
            if ($status_label === 'Reporté') $status_color = 'orange';
            if ($status_label === 'Complet') $status_color = 'gray';

            if (!$date_raw) {
                $formatted_date = '-';
            } else {
                try {
                    $dt = new \DateTime($date_raw);
                    $formatted_date = $dt->format('d/m/Y H:i');
                } catch (\Exception $e) { $formatted_date = 'Err'; }
            }

            $formatted_end = '-';
            if ($end_raw) {
                try {
                    $dtEnd = new \DateTime($end_raw);
                    $formatted_end = $dtEnd->format('H:i');
                } catch (\Exception $e) {}
            }

            $edit_link = get_edit_post_link($sailing->ID);

            $html .= '<tr>';
            $html .= '<td>#' . $sailing->ID . '</td>';
            $html .= '<td><strong>' . $formatted_date . '</strong></td>';
            $html .= '<td>' . $formatted_end . '</td>';
            $html .= '<td>' . ($quota !== '' ? $quota : '-') . '</td>';
            $html .= '<td><span style="color:'.$status_color.'; font-weight:bold;">' . $status_label . '</span></td>';
            $html .= '<td style="text-align:right;"><a href="' . $edit_link . '" target="_blank" class="button button-small">Modifier ↗</a></td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        $field['message'] = $html;
        return $field;
    }

    public function generateSailingsFromBatch($post_id)
    {
        if (get_post_type($post_id) !== 'cruise') return;

        $gen_group = get_field('recurrence_generator', $post_id);

        if (!$gen_group || empty($gen_group['trigger_generation'])) return;

        $start = $gen_group['start_date'];
        $end = $gen_group['end_date'];
        $time = $gen_group['time'];
        $return_time = $gen_group['return_time'];
        $days = $gen_group['days'] ?: [];
        $batch_quota = $gen_group['batch_quota'];
        $batch_fares = $gen_group['batch_passenger_fares'] ?: [];
        $batch_options = $gen_group['batch_extra_options'] ?: [];

        $day_mapping = ['dimanche'=>'0','lundi'=>'1','mardi'=>'2','mercredi'=>'3','jeudi'=>'4','vendredi'=>'5','samedi'=>'6'];

        if ($start && $end && $time) {
            try {
                $begin = new \DateTime($start);
                $finish = new \DateTime($end);
                $finish->modify('+1 day');
                $interval = new \DateInterval('P1D');
                $period = new \DatePeriod($begin, $interval, $finish);

                foreach ($period as $dt) {
                    $w = $dt->format('w');
                    $is_selected = false;
                    foreach ($days as $d) {
                        $d_str = strtolower((string)$d);
                        $check_val = isset($day_mapping[$d_str]) ? $day_mapping[$d_str] : $d_str;
                        if ((string)$check_val === (string)$w) { $is_selected = true; break; }
                    }

                    if ($is_selected) {
                        $full_date = $dt->format('Y-m-d') . ' ' . $time;
                        $full_arrival_date = '';
                        if ($return_time) {
                            $arrival_dt = clone $dt;
                            if (strtotime($return_time) < strtotime($time)) {
                                $arrival_dt->modify('+1 day');
                            }
                            $full_arrival_date = $arrival_dt->format('Y-m-d') . ' ' . $return_time;
                        }

                        $title = get_the_title($post_id) . ' - ' . $dt->format('d/m/Y H:i');

                        $sailing_id = wp_insert_post([
                            'post_type' => 'sailing',
                            'post_title' => $title,
                            'post_status' => 'publish',
                            'post_parent' => $post_id
                        ]);

                        if ($sailing_id && !is_wp_error($sailing_id)) {
                            $group_data = [
                                'parent_cruise' => $post_id,
                                'departure_date' => $full_date,
                                'arrival_date' => $full_arrival_date,
                                'quota' => $batch_quota,
                                'passenger_fares' => $batch_fares,
                                'extra_options' => $batch_options,
                            ];

                            update_field('sailing_config', $group_data, $sailing_id);

                            // NOUVEAU : Assigner le statut "Actif" par défaut
                            wp_set_object_terms($sailing_id, 'Actif', 'sailing_status');
                        }
                    }
                }
            } catch (\Exception $e) {}

            update_field('recurrence_generator_trigger_generation', 0, $post_id);
        }
    }

    // (syncCruiseToProduct inchangé)
    public function syncCruiseToProduct($postId, $post, $update)
    {
        // ... (code existant) ...
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($postId)) return;
        if (!in_array($post->post_status, ['publish', 'draft', 'private'])) return;

        $productId = get_post_meta($postId, 'related_wc_product_id', true);
        $product = $productId ? wc_get_product($productId) : null;

        if (!$product) {
            $product = new \WC_Product_Simple();
            $product->set_slug($post->post_name . '-booking');
        }

        $product->set_name($post->post_title . ' (Réservation)');
        $product->set_status($post->post_status === 'publish' ? 'publish' : 'draft');
        $product->set_virtual(true);
        $product->set_catalog_visibility('hidden');
        $product->set_sold_individually(false);

        $basePrice = get_field('base_price', $postId) ?: 0;
        $product->set_regular_price($basePrice);
        $product->set_price($basePrice);

        $newProductId = $product->save();

        if (!$productId || $productId != $newProductId) {
            update_post_meta($postId, 'related_wc_product_id', $newProductId);
            update_post_meta($newProductId, '_linked_cruise_id', $postId);
        }
    }
}
