<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Cruise extends Post
{
    const string POST_TYPE = 'cruise';

    protected static function booted()
    {
        static::addGlobalScope('cruise_type', function (Builder $builder) {
            $builder->where('post_type', self::POST_TYPE);
        });
    }

    public function getAcfAttribute(string $key): mixed
    {
        return get_field($key, $this->ID);
    }

    public function sailings()
    {
        $ids = get_posts([
            'post_type' => 'sailing',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'sailing_config_parent_cruise',
                    'value' => $this->ID,
                    'compare' => '=',
                ],
            ],
        ]);

        if (empty($ids)) {
            return collect();
        }

        // Retourne une Collection Eloquent de modèles Sailing hydratés
        // Cela permet d'enchainer des méthodes Eloquent ensuite si besoin
        return Sailing::whereIn('ID', $ids)->get();
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
            $cruise = new self;
            $cruise->setRawAttributes((array) $wp_post, true);
            $cruise->ID = $wp_post->ID;
            $cruise->exists = true;

            return $cruise;
        });
    }

    public function getDurationAttribute()
    {
        return get_field('duration', $this->ID);
    }

    public function getBasePriceAttribute()
    {
        return get_field('base_price', $this->ID);
    }

    public function getThumbnailUrlAttribute()
    {
        return get_the_post_thumbnail_url($this->ID, 'large');
    }

    public function getPermalinkAttribute()
    {
        return get_permalink($this->ID);
    }

    public function getTitleAttribute()
    {
        return get_the_title($this->ID);
    }

     public function getTagsAttribute()
    {
        $types = wp_get_post_terms($this->ID, 'cruise_tag');
        return collect($types)->map(function ($type) {
            return [
                'id' => $type->term_id,
                'name' => $type->name,
                'slug' => $type->slug,
            ];
        });
    }

    public function getTypesAttribute()
    {
        $types = wp_get_post_terms($this->ID, 'cruise_type');
        return collect($types)->map(function ($type) {
            return [
                'id' => $type->term_id,
                'name' => $type->name,
                'slug' => $type->slug,
            ];
        });
    }

    public function getHarborAttribute()
    {
        $harbor = wp_get_post_terms($this->ID, 'harbor');
        return collect($harbor)->first();

    }
}
