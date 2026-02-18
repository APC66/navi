<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Sailing extends Post
{
    const POST_TYPE = 'sailing';

    protected $primaryKey = 'ID';

    protected static function booted()
    {
        static::addGlobalScope('sailing_type', function (Builder $builder) {
            $builder->where('post_type', self::POST_TYPE);
        });
    }

    public static function fetch(array $args = []): \Illuminate\Support\Collection
    {
        $defaults = [
            'post_type' => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ];
        $query = new \WP_Query(array_merge($defaults, $args));

        return collect($query->posts)->map(function ($wp_post) {
            $sailing = new self;
            $sailing->setRawAttributes((array) $wp_post, true);
            $sailing->ID = $wp_post->ID;
            $sailing->exists = true;

            return $sailing;
        });
    }

    public function cruise()
    {
        $parentId = $this->parent_cruise_id;
        if ($parentId) {
            return Cruise::find($parentId);
        }

        return null;
    }

    /**
     * Récupère les commandes WooCommerce actives liées à ce départ
     *
     * * @return array Liste des commandes formatées
     */
    public function getOrders()
    {
        global $wpdb;
        $table_items = $wpdb->prefix.'woocommerce_order_items';
        $table_meta = $wpdb->prefix.'woocommerce_order_itemmeta';

        // Requête pour trouver les items de commande liés à l'ID de ce sailing
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT i.order_id, m.order_item_id
            FROM {$table_items} i
            INNER JOIN {$table_meta} m ON i.order_item_id = m.order_item_id
            WHERE (m.meta_key = '_sailing_id' OR m.meta_key = 'sailing_id')
            AND m.meta_value = %s
        ", (string) $this->ID));

        $orders = [];
        $seen = []; // Pour éviter les doublons si plusieurs lignes concernent le même sailing

        foreach ($results as $row) {
            if (in_array($row->order_id, $seen)) {
                continue;
            }

            $order = wc_get_order($row->order_id);
            // On ignore les commandes qui ne sont pas pertinentes pour l'exploitation
            if (! $order || in_array($order->get_status(), ['cancelled', 'failed', 'refunded', 'trash'])) {
                continue;
            }

            $item = $order->get_item($row->order_item_id);
            $pax = 0;

            if ($item) {
                $rawData = $item->get_meta('_booking_data_raw');
                $data = json_decode($rawData, true) ?: [];
                $pax = isset($data['passengers']) ? array_sum($data['passengers']) : 0;
            }

            $orders[] = [
                'id' => $order->get_id(),
                'client' => $order->get_formatted_billing_full_name() ?: 'Client Invité',
                'passenger_count' => $pax,
                'total' => $order->get_total(),
                'status' => $order->get_status(),
            ];

            $seen[] = $row->order_id;
        }

        return $orders;
    }

    // --- ACCESSEURS ---

    public function getTitleAttribute()
    {
        return get_the_title($this->ID);
    }

    public function getQuotaAttribute()
    {
        $val = get_post_meta($this->ID, 'sailing_config_quota', true);
        if ($val === '') {
            $group = get_post_meta($this->ID, 'sailing_config', true);
            $val = $group['quota'] ?? 0;
        }

        return (int) $val;
    }

    public function getParentCruiseIdAttribute()
    {
        $val = get_post_meta($this->ID, 'sailing_config_parent_cruise', true);
        if (! $val) {
            $group = get_post_meta($this->ID, 'sailing_config', true);
            $val = $group['parent_cruise'] ?? null;
        }

        return $val;
    }

    public function getStartAttribute()
    {
        $date = get_post_meta($this->ID, 'sailing_config_departure_date', true);
        if (! $date) {
            $group = get_post_meta($this->ID, 'sailing_config', true);
            $date = $group['departure_date'] ?? null;
        }
        if ($date) {
            try {
                return (new \DateTime($date))->format('Y-m-d\TH:i:s');
            } catch (\Exception $e) {
                return $date;
            }
        }

        return null;
    }

    public function getEndAttribute()
    {
        $date = get_post_meta($this->ID, 'sailing_config_arrival_date', true);
        if (! $date) {
            $group = get_post_meta($this->ID, 'sailing_config', true);
            $date = $group['arrival_date'] ?? null;
        }
        if ($date) {
            try {
                return (new \DateTime($date))->format('Y-m-d\TH:i:s');
            } catch (\Exception $e) {
                return $date;
            }
        }

        return null;
    }

    public function getFaresAttribute()
    {
        return get_field('sailing_config_passenger_fares', $this->ID);
    }

    public function getOptionsAttribute()
    {
        return get_field('sailing_config_extra_options', $this->ID);
    }

    public function getEditUrlAttribute()
    {
        return get_edit_post_link($this->ID, 'raw');
    }
}
