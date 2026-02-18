<?php

namespace App\Http\Controllers\Api;

use WP_REST_Request;
use WP_Query;
use function Roots\view;

class SearchController
{
    public function search(WP_REST_Request $request)
    {
        // Récupération des paramètres
        $sort = $request->get_param('sort');
        $page = $request->get_param('page') ? intval($request->get_param('page')) : 1; // Page courante
        $perPage = 12; // Nombre d'éléments par page

        // Les filtres arrivent sous forme de chaîne "12,14"
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

        // 1. Filtre par TAGS (cruise_tag)
        if (!empty($tags)) {
            $args['tax_query'][] = [
                'taxonomy' => 'cruise_tag',
                'field' => 'term_id',
                'terms' => $tags,
                'operator' => 'IN',
            ];
        }

        // 2. Filtre par TYPE (cruise_type)
        if (!empty($categories)) {
            $args['tax_query'][] = [
                'taxonomy' => 'cruise_type',
                'field' => 'term_id',
                'terms' => $categories,
                'operator' => 'IN',
            ];
        }

        // 3. Gestion du TRI
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

        // Rendu de la vue partielle
        $html = view('partials.cruise-grid', ['query' => $query])->render();

        return [
            'success' => true,
            'html' => $html,
            'count' => $query->found_posts,
            'max_pages' => $query->max_num_pages,
            'current_page' => $page
        ];
    }
}
