<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Détermine si une popup doit être présente sur la page demandée.
 *
 * Le ciblage ne dépend que de l'URL demandée (jamais du visiteur) : il reste
 * donc correct lorsque la page est servie depuis un cache.
 */
final class APC_Popup_Targeting
{
    public static function matches(array $settings): bool
    {
        $current_id = self::current_content_id();

        if ($current_id && in_array($current_id, $settings['exclude_ids'], true)) {
            return false;
        }

        if ($settings['display_mode'] === 'all') {
            return true;
        }

        if ($settings['include_front'] && is_front_page()) {
            return true;
        }

        if ($settings['include_blog'] && is_home() && APC_Popup_Settings::has_posts_page()) {
            return true;
        }

        if ($current_id && in_array($current_id, $settings['include_ids'], true)) {
            return true;
        }

        // Le type du contenu affiché, y compris pour la page boutique et la
        // page des articles : cocher « Pages » couvre aussi ces deux pages.
        if ($current_id) {
            $post_type = get_post_type($current_id);
            if ($post_type && in_array($post_type, $settings['include_post_types'], true)) {
                return true;
            }
        }

        if (is_post_type_archive()) {
            foreach ((array) get_query_var('post_type') as $post_type) {
                if (in_array($post_type, $settings['include_archives'], true)) {
                    return true;
                }
            }
        }

        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term instanceof WP_Term) {
                if (in_array($term->taxonomy, $settings['include_taxonomies'], true)) {
                    return true;
                }
                if (in_array((int) $term->term_id, $settings['include_terms'], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Identifiant du contenu affiché, y compris pour les pages spéciales
     * (page d'accueil statique, page des articles, page boutique WooCommerce),
     * afin qu'elles puissent être choisies ou exclues comme n'importe quel contenu.
     */
    public static function current_content_id(): int
    {
        if (function_exists('is_shop') && is_shop() && function_exists('wc_get_page_id')) {
            return max(0, (int) wc_get_page_id('shop'));
        }

        if (is_singular()) {
            return (int) get_queried_object_id();
        }

        if (is_home() && get_option('show_on_front') === 'page') {
            return (int) get_option('page_for_posts');
        }

        return 0;
    }
}
