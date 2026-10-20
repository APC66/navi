<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Type de contenu "Popup" : chaque popup est un contenu non public, édité
 * avec l'éditeur classique (titre + contenu riche avec images).
 */
final class APC_Popup_Post_Type
{
    public function hooks(): void
    {
        // La purge du cache des pages après modification est gérée par APC_Popup_Cache.
        add_action('init', [$this, 'register']);
        add_filter('ure_set_cpt_own_caps', [$this, 'keep_page_capabilities'], 10, 2);
    }

    /**
     * User Role Editor Pro (option « Force custom post types to use their own
     * capabilities ») remplace les droits des types de contenu personnalisés
     * par des droits dédiés (edit_apc_popups...) qu'aucun rôle ne possède :
     * la liste des popups devenait inaccessible. Les popups gardent les droits
     * des pages : qui gère les pages gère les popups.
     */
    public function keep_page_capabilities($do_it, $post_type)
    {
        return $post_type === APC_Popup_Settings::POST_TYPE ? false : $do_it;
    }

    public function register(): void
    {
        register_post_type(APC_Popup_Settings::POST_TYPE, [
            'labels' => [
                'name' => __('Popups', 'apc-popup'),
                'singular_name' => __('Popup', 'apc-popup'),
                'menu_name' => __('Popups', 'apc-popup'),
                'add_new' => __('Ajouter', 'apc-popup'),
                'add_new_item' => __('Ajouter une popup', 'apc-popup'),
                'edit_item' => __('Modifier la popup', 'apc-popup'),
                'new_item' => __('Nouvelle popup', 'apc-popup'),
                'view_item' => __('Voir la popup', 'apc-popup'),
                'search_items' => __('Rechercher une popup', 'apc-popup'),
                'not_found' => __('Aucune popup', 'apc-popup'),
                'not_found_in_trash' => __('Aucune popup dans la corbeille', 'apc-popup'),
                'all_items' => __('Toutes les popups', 'apc-popup'),
            ],
            'public' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => false,
            'show_in_admin_bar' => false,
            'show_in_rest' => false,
            'menu_position' => 26,
            'menu_icon' => 'dashicons-format-chat',
            // Droits des pages : seuls éditeurs et administrateurs gèrent les popups.
            'capability_type' => 'page',
            'map_meta_cap' => true,
            'hierarchical' => false,
            'supports' => ['title', 'editor', 'revisions'],
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
        ]);
    }
}
