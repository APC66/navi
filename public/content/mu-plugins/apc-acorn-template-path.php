<?php
/**
 * Plugin Name: APC - Compat Acorn / WordPress (vues hors dossier du theme)
 * Description: Retablit la resolution des vues Blade de Radicle, situees hors du dossier du theme, bloquee par _wp_is_template_path_allowed() introduit dans le coeur de WordPress. A retirer des que roots/acorn gere le cas nativement.
 * Version: 1.0
 * Author: Agence Point Com
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('_wp_is_template_path_allowed')) {
    return;
}

if (! function_exists('apc_acorn_view_roots')) {
    /**
     * Dossiers de vues declares dans config/view.php, resolus en chemins reels.
     */
    function apc_acorn_view_roots()
    {
        static $dirs = null;

        if ($dirs !== null) {
            return $dirs;
        }

        $dirs = [];

        if (! function_exists('Roots\\app')) {
            return $dirs;
        }

        try {
            $paths = \Roots\app('config')->get('view.paths', []);
        } catch (\Throwable $e) {
            return $dirs;
        }

        foreach ((array) $paths as $path) {
            $real = realpath($path);

            if ($real) {
                $dirs[] = trailingslashit(wp_normalize_path($real));
            }
        }

        return $dirs;
    }
}

if (! function_exists('apc_acorn_locate_template')) {
    /**
     * Rejoue la hierarchie de templates en acceptant les vues qui resolvent
     * dans un dossier de vues declare par Acorn, et uniquement celles-la.
     */
    function apc_acorn_locate_template($template, $type, $templates)
    {
        $roots = apc_acorn_view_roots();

        if (! $roots) {
            return $template;
        }

        $bases = array_unique([get_stylesheet_directory(), get_template_directory()]);

        foreach ((array) $templates as $name) {
            if (! $name) {
                continue;
            }

            foreach ($bases as $base) {
                $candidate = $base . '/' . $name;

                if (! file_exists($candidate)) {
                    continue;
                }

                // Candidat deja accepte par le coeur : on ne change rien.
                if (_wp_is_template_path_allowed($candidate)) {
                    return $template;
                }

                $real = realpath($candidate);

                if (! $real) {
                    continue;
                }

                $real = wp_normalize_path($real);

                foreach ($roots as $dir) {
                    if (str_starts_with($real, $dir)) {
                        return $real;
                    }
                }
            }
        }

        return $template;
    }
}

foreach ([
    'index', '404', 'archive', 'author', 'category', 'tag', 'taxonomy',
    'date', 'embed', 'home', 'frontpage', 'privacypolicy', 'page',
    'paged', 'search', 'single', 'singular', 'attachment',
] as $apc_tpl_type) {
    add_filter("{$apc_tpl_type}_template", 'apc_acorn_locate_template', 5, 3);
}

unset($apc_tpl_type);
