<?php

namespace App\Http\Controllers\Api;

use WP_Query;
use WP_REST_Request;

use function Roots\view;

class SearchController
{
    public function liveSearch(WP_REST_Request $request): array
    {
        $q = sanitize_text_field($request->get_param('q') ?? '');

        if (strlen($q) < 2) {
            return [];
        }

        $query = new WP_Query([
            'post_type' => 'cruise',
            'post_status' => 'publish',
            'posts_per_page' => 6,
            's' => $q,
            'orderby' => 'relevance',
        ]);

        $results = [];

        foreach ($query->posts as $post) {
            $id = $post->ID;

            $thumbnail = get_the_post_thumbnail_url($id, 'thumbnail') ?: null;

            $harbor = null;
            $terms = get_the_terms($id, 'harbor');
            if (! empty($terms) && ! is_wp_error($terms)) {
                $harbor = $terms[0]->name;
            }

            $results[] = [
                'id' => $id,
                'title' => get_the_title($id),
                'url' => get_permalink($id),
                'thumbnail' => $thumbnail,
                'harbor' => $harbor,
            ];
        }

        return $results;
    }

    public function search(WP_REST_Request $request)
    {
        $sort = $request->get_param('sort');
        $page = $request->get_param('page') ? intval($request->get_param('page')) : 1; // Page courante
        $perPage = 12; // Nombre d'éléments par page

        $tagsStr = $request->get_param('tags');
        $tags = $tagsStr ? explode(',', $tagsStr) : [];

        $categoriesStr = $request->get_param('categories');
        $categories = $categoriesStr ? explode(',', $categoriesStr) : [];

        $args = [
            'post_type' => 'cruise',
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'paged' => $page,
            'tax_query' => ['relation' => 'AND'],
        ];

        if (! empty($tags)) {
            $args['tax_query'][] = [
                'taxonomy' => 'cruise_tag',
                'field' => 'term_id',
                'terms' => $tags,
                'operator' => 'IN',
            ];
        }

        if (! empty($categories)) {
            $args['tax_query'][] = [
                'taxonomy' => 'cruise_type',
                'field' => 'term_id',
                'terms' => $categories,
                'operator' => 'IN',
            ];
        }

        switch ($sort) {
            case 'price_asc':
                $args['meta_key'] = 'base_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
            case 'price_desc':
                $args['meta_key'] = 'base_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'title_asc':
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
            default: // date desc par défaut
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
        }

        $query = new WP_Query($args);

        $html = view('partials.cruise-grid', ['query' => $query])->render();

        return [
            'success' => true,
            'html' => $html,
            'count' => $query->found_posts,
            'max_pages' => $query->max_num_pages,
            'current_page' => $page,
        ];
    }
}
