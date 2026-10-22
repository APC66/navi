<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use WP_Term;

/**
 * Données de la page des actualités (page des articles) et des pages de
 * catégorie : en-tête, filtre des catégories, nombre de résultats, pagination.
 *
 * La liste des articles vient de la requête principale de WordPress
 * (12 par page, voir ThemeServiceProvider).
 */
class NewsArchive extends Composer
{
    /**
     * Catégories par défaut de WordPress, masquées du filtre.
     */
    const HIDDEN_CATEGORY_SLUGS = ['non-classe', 'uncategorized'];

    protected static $views = [
        'partials.news.archive',
    ];

    public function with(): array
    {
        $postsPageId = (int) get_option('page_for_posts');
        $postsPageTitle = $postsPageId ? get_the_title($postsPageId) : __('Actualités', 'radicle');
        $postsPageUrl = $this->postsPageUrl();
        $pageHeader = $postsPageId ? get_field('page_header', $postsPageId) : null;
        $pageHeader = is_array($pageHeader) ? $pageHeader : [];

        $term = is_category() ? get_queried_object() : null;
        $term = $term instanceof WP_Term ? $term : null;

        if ($term) {
            // Catégorie : image de la page des actualités, titre de la catégorie.
            $header = [
                'group' => array_filter([
                    'header_image' => $pageHeader['header_image'] ?? null,
                    'header_highlight_color' => $pageHeader['header_highlight_color'] ?? null,
                ]),
                'title' => single_term_title('', false),
                'subtitle' => term_description($term),
                'showTitle' => true,
                'parentTitle' => $postsPageTitle,
                'parentUrl' => $postsPageUrl,
            ];
        } else {
            $header = [
                'group' => $pageHeader,
                'title' => ! empty($pageHeader['header_title']) ? $pageHeader['header_title'] : $postsPageTitle,
                'subtitle' => '',
                // Titre affiché par défaut (comme le réglage « Afficher titre ? »)
                // tant que l'en-tête de la page n'a jamais été enregistré.
                'showTitle' => array_key_exists('show_title', $pageHeader) ? ! empty($pageHeader['show_title']) : true,
                'parentTitle' => '',
                'parentUrl' => '',
            ];
        }

        global $wp_query;

        return [
            'header' => $header,
            'categories' => $this->categories($term),
            'allUrl' => $postsPageUrl,
            'allActive' => ! $term,
            'allCount' => (int) (wp_count_posts('post')->publish ?? 0),
            'resultsCount' => (int) $wp_query->found_posts,
            'pagination' => $this->pagination(),
        ];
    }

    /**
     * Adresse de la liste complète : la page des articles si elle est définie
     * (Réglages > Lecture), sinon l'accueil.
     */
    protected function postsPageUrl(): string
    {
        $postsPageId = (int) get_option('page_for_posts');

        return $postsPageId ? (string) get_permalink($postsPageId) : home_url('/');
    }

    /**
     * Catégories qui contiennent au moins une actualité publiée, dans l'ordre
     * alphabétique, chaque sous-catégorie juste sous sa catégorie parente.
     * Le nombre d'une catégorie inclut ses sous-catégories, comme sa page.
     */
    protected function categories(?WP_Term $current): array
    {
        $terms = get_terms([
            'taxonomy' => 'category',
            'hide_empty' => true,
            'pad_counts' => true,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        if (is_wp_error($terms)) {
            return [];
        }

        $terms = array_filter($terms, function (WP_Term $term) {
            return ! in_array($term->slug, self::HIDDEN_CATEGORY_SLUGS, true) && $term->count > 0;
        });

        $children = [];
        foreach ($terms as $term) {
            $children[$term->parent][] = $term;
        }

        // Racines : catégories sans parent, ou dont le parent n'est pas affiché.
        $ids = array_map(function (WP_Term $term) {
            return $term->term_id;
        }, $terms);
        $roots = array_filter($terms, function (WP_Term $term) use ($ids) {
            return ! in_array($term->parent, $ids, true);
        });

        $items = [];
        $add = function (array $branch, int $depth) use (&$add, &$items, $children, $current) {
            foreach ($branch as $term) {
                $items[] = [
                    'name' => $term->name,
                    'url' => get_term_link($term),
                    'count' => (int) $term->count,
                    'depth' => $depth,
                    'active' => $current && $current->term_id === $term->term_id,
                ];
                $add($children[$term->term_id] ?? [], $depth + 1);
            }
        };
        $add(array_values($roots), 0);

        return $items;
    }

    /**
     * Liens de pagination (tableau de balises HTML générées par WordPress).
     */
    protected function pagination(): array
    {
        $arrow = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="%s"></path></svg>';

        $links = paginate_links([
            'type' => 'array',
            'mid_size' => 1,
            'end_size' => 1,
            'prev_text' => '<span class="sr-only">'.esc_html__('Actualités plus récentes', 'radicle').'</span>'.sprintf($arrow, 'M15 19l-7-7 7-7'),
            'next_text' => '<span class="sr-only">'.esc_html__('Actualités plus anciennes', 'radicle').'</span>'.sprintf($arrow, 'M9 5l7 7-7 7'),
        ]);

        return is_array($links) ? $links : [];
    }
}
