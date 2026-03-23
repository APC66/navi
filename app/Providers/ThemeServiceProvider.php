<?php

namespace App\Providers;

use App\Admin\BoardingListPage;
use App\Admin\CalendarPage;
use App\Admin\LoyaltySettingsPage;
use App\Admin\OrdersExportPage;
use App\Services\AgencyOrderService;
use App\Services\CruiseManagement;
use App\Services\LoyaltyService;
use App\Services\WoocommerceBridge;
use Illuminate\Support\Collection;
use Roots\Acorn\Sage\SageServiceProvider;

class ThemeServiceProvider extends SageServiceProvider
{
    /**
     * Register theme services.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        /**
         * Register theme support and navigation menus from the theme config.
         *
         * @return void
         */
        add_action('after_setup_theme', function (): void {
            Collection::make(config('theme.support'))
                ->map(fn ($params, $feature) => is_array($params) ? [$feature, $params] : [$params])
                ->each(fn ($params) => add_theme_support(...$params));

            Collection::make(config('theme.remove'))
                ->map(fn ($entry) => is_string($entry) ? [$entry] : $entry)
                ->each(fn ($params) => remove_theme_support(...$params));

            register_nav_menus(config('theme.menus'));

            Collection::make(config('theme.image_sizes'))
                ->each(fn ($params, $name) => add_image_size($name, ...$params));
        }, 20);

        add_action('after_setup_theme', function () {
            add_theme_support('editor-styles');
        });

        /**
         * Register sidebars from the theme config.
         *
         * @return void
         */
        add_action('widgets_init', function (): void {
            Collection::make(config('theme.sidebar.register'))
                ->map(fn ($instance) => register_sidebar(
                    array_merge(config('theme.sidebar.config'), $instance)
                ));
        });

        add_filter('upload_mimes', function ($mimes) {
            foreach (config('mimes.mimes') as $mime) {
                if ($mime === 'svg') {
                    $mimes['svg'] = 'image/svg+xml';
                }
            }

            return $mimes;
        });

        add_filter('show_admin_bar', '__return_false');

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        (new CruiseManagement)->init();
        (new WoocommerceBridge)->init();
        (new AgencyOrderService)->init();
        (new LoyaltyService)->init();
        (new OrdersExportPage)->init();
        (new LoyaltySettingsPage)->init();

        add_action('admin_menu', new CalendarPage);
        add_action('admin_menu', new BoardingListPage);

        add_action('acf/init', function () {
            if (function_exists('acf_add_options_page')) {
                acf_add_options_page([
                    'page_title' => 'Options du Thème',
                    'menu_title' => 'Options Thème',
                    'menu_slug' => 'theme-options',
                    'capability' => 'edit_theme_options',
                    'redirect' => false,
                ]);
            }
        });

        add_action('init', function () {
            register_post_status('cancelled', [
                'label' => _x('Annulé', 'post'),
                'public' => false,
                'exclude_from_search' => true,
                'show_in_admin_all_list' => true,
                'show_in_admin_status_list' => true,
                'label_count' => _n_noop('Annulé <span class="count">(%s)</span>', 'Annulés <span class="count">(%s)</span>'),
            ]);
        });

        add_action('init', function () {
            $taxonomy = 'sailing_status';
            if (! taxonomy_exists($taxonomy)) {
                return;
            }

            $statuses = ['Actif', 'Annulé', 'Reporté', 'Complet'];
            foreach ($statuses as $status) {
                $term = term_exists($status, $taxonomy);
                if (! $term) {
                    wp_insert_term($status, $taxonomy);
                }
            }
        }, 100);

        // Disable wp block
        add_filter('allowed_block_types_all', function ($allowed_blocks, $editor_context) {
            return [
                'acf/hero-video',
                'acf/cruise-carousel',
                'acf/reassurance',
                'acf/text-images-cards',
                'acf/text-image',
                'acf/simple-cta',
                'acf/image-carousel',
                'acf/text-with-icon',
                'acf/contact',
            ];
        }, 10, 2);

        // Disable preview
        add_filter('acf/register_block_type_args', function ($args) {
            $args['mode'] = 'edit'; // Force l'affichage des champs
            $args['supports']['mode'] = false; // Cache le bouton pour switcher en preview

            return $args;
        });

        // Active guttenberg que pour le template specifique et la page accueil
        add_filter('use_block_editor_for_post', function ($use_block_editor, $post) {
            if ($post->post_type === 'page') {
                if ((int) get_option('page_on_front') === $post->ID) {
                    return true;
                }
                $template = get_post_meta($post->ID, '_wp_page_template', true);
                if ($template === 'template-gutenberg.blade.php') {
                    return true;
                }

                return false;
            }

            return $use_block_editor;
        }, 10, 2);

        // Disable autosave
        add_action('admin_init', function () {
            wp_deregister_script('autosave');
        });

        add_filter('tiny_mce_before_init', function ($init) {
            $custom_colors = [
                '"BFCAE6"', '"Primary 100"',
                '"9AA7CB"', '"Primary 200"',
                '"5B6C9F"', '"Primary 400"',
                '"1C3787"', '"Primary 600"',
                '"101F4D"', '"Primary 800"',
                '"0A173D"', '"Primary 900"',
                '"070E22"', '"Primary 1000"',

                '"FFD21F"', '"Secondary"',
                '"ECBA16"', '"Secondary Hover"',
                '"FFE785"', '"Secondary 600"',
                '"FFDC4C"', '"Secondary 800"',
                '"58A4B0"', '"Tertiary 800"',

                '"C33149"', '"Erreur / Rouge"',
                '"FBF8F0"', '"Blanc Cassé"',
                '"FFFFFF"', '"Blanc pur"',
                '"000000"', '"Noir absolu"',
            ];

            $init['textcolor_map'] = '['.implode(', ', $custom_colors).']';

            $init['textcolor_rows'] = 3;

            return $init;
        });

        add_action('init', function () {
            // Attribuer la capability à un rôle spécifique
            $role = get_role('shop_manager');
            if ($role) {
                $role->add_cap('place_agency_orders');
            }

            $user = get_user_by('email', 'julien@agencepoint.com');
            if ($user) {
                $user->add_cap('place_agency_orders');
            }
        });

        add_action('pre_get_posts', function ($query) {
            if (! is_admin() && $query->is_main_query() && is_post_type_archive('cruise')) {
                $search = sanitize_text_field($_GET['cruise_search'] ?? '');
                if (! empty($search)) {
                    $query->set('s', $search);
                }
            }
        });

        // Enqueue JS carte cadeau uniquement sur le template dédié
        add_action('wp_enqueue_scripts', function () {
            if (is_page_template('template-gift-card.blade.php')) {
                wp_enqueue_script(
                    'gift-card-js',
                    asset('resources/js/components/gift-card.js')->uri(),
                    [],
                    null,
                    true
                );
            }
        });

    }
}
