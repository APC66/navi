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

        (new \App\Shortcodes\Button)->register();

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
                'acf/logo-grid',
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

        add_filter('user_has_cap', function ($allcaps, $caps, $args, $user) {
            if (isset($user->user_email) && $user->user_email === 'julien@agencepoint.com') {
                $allcaps['place_agency_orders'] = false;
            }

            return $allcaps;
        }, 999, 4);

        add_action('pre_get_posts', function ($query) {
            if (! is_admin() && $query->is_main_query() && is_post_type_archive('cruise')) {
                $search = sanitize_text_field($_GET['cruise_search'] ?? '');
                if (! empty($search)) {
                    $query->set('s', $search);
                }
            }
        });

        // Preview PDF carte cadeau (admin uniquement)
        add_action('wp_ajax_preview_gift_card_pdf', function () {
            if (! current_user_can('manage_options')) {
                wp_die('Non autorisé', 403);
            }

            $cruiseId = 182;
            $season = 'high';
            $passengersRaw = [17 => 3, 18 => 3];
            $optionsRaw = [20 => 2];

            $passengersData = [];
            foreach ($passengersRaw as $typeId => $qty) {
                $term = get_term($typeId, 'passenger_type');
                $passengersData[] = [
                    'name' => (! is_wp_error($term) && $term) ? $term->name : 'Passager',
                    'qty' => $qty,
                ];
            }

            $optionsData = [];
            foreach ($optionsRaw as $typeId => $qty) {
                $term = get_term($typeId, 'extra_option_type');
                $optionsData[] = [
                    'name' => (! is_wp_error($term) && $term) ? $term->name : 'Option',
                    'qty' => $qty,
                ];
            }

            $viewData = [
                'mode' => 'cruise',
                'cruise_title' => get_the_title($cruiseId) ?: 'Observation du Grand dauphin au lever du soleil',
                'season_label' => $season === 'high' ? 'Haute Saison' : 'Basse Saison',
                'passengers' => $passengersData,
                'options' => $optionsData,
                'amount' => 170.00,
                'coupon_code' => 'CC-69CE68F0A4A61',
                'expiry_date' => '2027-04-02',
                'recipient_message' => 'Carte cadeau test Carte cadeau test Carte cadeau test Carte cadeau test Carte cadeau test Carte cadeau test Carte cadeau test Carte cadeau test Carte cadeau test Carte cadeau test',
                'logo_url' => get_theme_file_uri('public/images/logo.png'),
                'site_name' => get_bloginfo('name'),
                'bg_image_url' => get_field('gift_card_bg_image', 'option') ?: '',
            ];

            $html = \Roots\view('pdf.gift-card', $viewData)->render();

            $options = new \Dompdf\Options;
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $dompdf->stream('preview-carte-cadeau.pdf', ['Attachment' => false]);
            exit;
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

        add_action('phpmailer_init', function (\PHPMailer\PHPMailer\PHPMailer $phpmailer) {
            $host = $_SERVER['HTTP_HOST'] ?? '';

            if (! str_ends_with($host, '.test')) {
                return;
            }

            $phpmailer->isSMTP();
            $phpmailer->Host = env('MAILPIT_HOST', '127.0.0.1');
            $phpmailer->Port = env('MAILPIT_PORT', 1025);
            $phpmailer->SMTPAuth = false;
            $phpmailer->SMTPSecure = '';
        });

        // Phone mandatory
        add_filter('woocommerce_billing_fields', function ($fields) {
            $fields['billing_phone']['required'] = true;

            return $fields;
        });

        // Duplicate posts
        add_action('admin_action_duplicate_post', function () {
            if (empty($_GET['post'])) {
                return;
            }

            $post_id = absint($_GET['post']);
            check_admin_referer('duplicate_post_'.$post_id);

            $post = get_post($post_id);
            if (! $post) {
                return;
            }

            $new_id = wp_insert_post([
                'post_title' => $post->post_title.' (copie)',
                'post_content' => $post->post_content,
                'post_excerpt' => $post->post_excerpt,
                'post_status' => 'draft',
                'post_type' => $post->post_type,
                'post_author' => get_current_user_id(),
            ]);

            foreach (get_post_meta($post->ID) as $key => $values) {
                foreach ($values as $value) {
                    update_post_meta($new_id, $key, maybe_unserialize($value));
                }
            }

            foreach (get_object_taxonomies($post->post_type) as $taxonomy) {
                $terms = wp_get_object_terms($post->ID, $taxonomy, ['fields' => 'ids']);
                wp_set_object_terms($new_id, $terms, $taxonomy);
            }

            wp_redirect(admin_url('post.php?action=edit&post='.$new_id));
            exit;
        });

        add_filter('post_row_actions', [$this, 'addDuplicateLink'], 10, 2);
        add_filter('page_row_actions', [$this, 'addDuplicateLink'], 10, 2);
    }

    public function addDuplicateLink(array $actions, \WP_Post $post): array
    {
        if (! current_user_can('edit_posts')) {
            return $actions;
        }

        $url = wp_nonce_url(
            admin_url('admin.php?action=duplicate_post&post='.$post->ID),
            'duplicate_post_'.$post->ID
        );
        $actions['duplicate'] = '<a href="'.$url.'">Dupliquer</a>';

        return $actions;
    }
}
