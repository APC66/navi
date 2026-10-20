<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Affichage des popups sur le site.
 *
 * Répartition des contrôles, pensée pour les pages servies depuis un cache :
 * - côté serveur : ciblage par URL, visiteur connecté ou non, dates de début
 *   et de fin (une purge du cache est programmée à ces dates) ;
 * - côté navigateur (assets/js/popup.js) : appareil, fréquence (compteurs
 *   stockés dans le navigateur), déclencheur, et à nouveau les dates pour
 *   les pages mises en cache juste avant l'une d'elles.
 */
final class APC_Popup_Frontend
{
    const HANDLE = 'apc-popup';

    const PREVIEW_PARAM = 'apc_popup_preview';

    const CONTENT_CONTEXT = 'apc_popup';

    /** @var array<int, array{post: WP_Post, settings: array, preview: bool}> */
    private $popups = [];

    public function hooks(): void
    {
        add_action('wp', [$this, 'collect']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
        // Avant l'impression des scripts du pied de page (priorité 20) et des
        // modèles de playlist (priorité 0) : les ressources demandées par le
        // contenu (vidéo, audio) sont ainsi bien chargées.
        add_action('wp_footer', [$this, 'render'], -1);
        add_filter('script_loader_tag', [$this, 'script_attributes'], 10, 2);
        add_filter('wp_lazy_loading_enabled', [$this, 'disable_lazy_loading'], 10, 3);
        add_filter('rocket_delay_js_exclusions', [$this, 'rocket_delay_js_exclusions']);
        add_filter('rocket_rucss_external_exclusions', [$this, 'rocket_rucss_exclusions']);
    }

    /**
     * Sélectionne les popups concernées par la page demandée.
     */
    public function collect(): void
    {
        if (is_admin() || wp_doing_ajax() || is_feed() || is_embed() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        $preview_id = $this->preview_id();
        if ($preview_id) {
            $post = get_post($preview_id);
            if ($post && $post->post_type === APC_Popup_Settings::POST_TYPE) {
                $this->popups[] = [
                    'post' => $post,
                    'settings' => APC_Popup_Settings::get($post->ID),
                    'preview' => true,
                ];
            }

            return;
        }

        $posts = get_posts([
            'post_type' => APC_Popup_Settings::POST_TYPE,
            'post_status' => 'publish',
            // Une popup protégée par mot de passe n'est jamais affichée.
            'has_password' => false,
            'posts_per_page' => -1,
            'no_found_rows' => true,
        ]);

        foreach ($posts as $post) {
            $settings = APC_Popup_Settings::get($post->ID);

            if (! $this->audience_matches($settings) || ! APC_Popup_Settings::in_period($settings)) {
                continue;
            }

            if (! APC_Popup_Targeting::matches($settings)) {
                continue;
            }

            $this->popups[] = ['post' => $post, 'settings' => $settings, 'preview' => false];
        }

        // Priorité croissante (1 passe avant 10), puis la plus récente d'abord.
        usort($this->popups, function ($a, $b) {
            $by_priority = $a['settings']['priority'] <=> $b['settings']['priority'];

            return $by_priority !== 0 ? $by_priority : strcmp($b['post']->post_date_gmt, $a['post']->post_date_gmt);
        });
    }

    public function enqueue(): void
    {
        if (! $this->popups) {
            return;
        }

        wp_enqueue_style(self::HANDLE, APC_POPUP_URL.'assets/css/popup.css', [], APC_POPUP_VERSION);
        wp_enqueue_script(self::HANDLE, APC_POPUP_URL.'assets/js/popup.js', [], APC_POPUP_VERSION, [
            'in_footer' => true,
            'strategy' => 'defer',
        ]);
    }

    public function render(): void
    {
        foreach ($this->popups as $popup) {
            echo $this->markup($popup['post'], $popup['settings'], $popup['preview']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }

    /**
     * Empêche l'optimiseur JS des extensions de cache de retarder le script,
     * ce qui fausserait les déclencheurs (délai, défilement, sortie).
     */
    public function script_attributes(string $tag, string $handle): string
    {
        if ($handle !== self::HANDLE) {
            return $tag;
        }

        return str_replace(' src=', ' data-no-optimize="1" data-cfasync="false" src=', $tag);
    }

    public function rocket_delay_js_exclusions($exclusions): array
    {
        $exclusions = (array) $exclusions;
        $exclusions[] = '/apc-popup/assets/js/popup';

        return $exclusions;
    }

    /**
     * WP Rocket « Supprimer les CSS inutilisés » : la feuille de style des
     * popups doit rester entière, ses règles d'ouverture ne s'appliquent qu'à
     * des classes ajoutées par le script (absentes du HTML analysé).
     */
    public function rocket_rucss_exclusions($exclusions): array
    {
        $exclusions = (array) $exclusions;
        $exclusions[] = '/apc-popup/assets/css/popup.css';

        return $exclusions;
    }

    /**
     * Images : WordPress n'ajoute pas son propre attribut loading (ni
     * fetchpriority), content_html() le gère (voir popup.js pour le passage en
     * chargement immédiat). Vidéos intégrées : loading="lazy" de WordPress,
     * elles ne se chargent qu'à l'ouverture de la popup.
     */
    public function disable_lazy_loading($default, $tag_name, $context)
    {
        return $context === self::CONTENT_CONTEXT && $tag_name === 'img' ? false : $default;
    }

    private function preview_id(): int
    {
        if (empty($_GET[self::PREVIEW_PARAM])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return 0;
        }

        $id = absint($_GET[self::PREVIEW_PARAM]); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        return $id && current_user_can('edit_post', $id) ? $id : 0;
    }

    private function audience_matches(array $settings): bool
    {
        if ($settings['audience'] === 'logged_in') {
            return is_user_logged_in();
        }

        if ($settings['audience'] === 'logged_out') {
            return ! is_user_logged_in();
        }

        return true;
    }

    private function content_html(WP_Post $post): string
    {
        global $wp_embed;

        $html = $post->post_content;

        // Vidéos et contenus intégrés (lien seul sur une ligne, [embed]) comme
        // dans l'éditeur. Le cache oEmbed est rattaché à la popup elle-même.
        if ($wp_embed instanceof WP_Embed) {
            $previous = $wp_embed->post_ID;
            $wp_embed->post_ID = $post->ID;
            $html = $wp_embed->run_shortcode($html);
            $html = $wp_embed->autoembed($html);
            $wp_embed->post_ID = $previous;
        }

        $html = wptexturize($html);
        $html = wpautop($html);
        $html = shortcode_unautop($html);
        $html = do_shortcode($html);
        $html = wp_filter_content_tags($html, self::CONTENT_CONTEXT);

        // Images : chargement natif différé tant que la popup est masquée (rien
        // n'est téléchargé pour un visiteur qui l'a déjà vue), passé en
        // immédiat par popup.js dès que la popup peut s'ouvrir. data-no-lazy
        // écarte le chargement différé de WP Rocket, qui attendrait l'ouverture.
        return (string) preg_replace_callback('/<img\b[^>]*>/i', function ($match) {
            $tag = $match[0];
            if (! preg_match('/\sloading=/i', $tag)) {
                $tag = preg_replace('/^<img\b/i', '<img loading="lazy"', $tag);
            }
            if (! preg_match('/\sdata-no-lazy\b/i', $tag)) {
                $tag = preg_replace('/^<img\b/i', '<img data-no-lazy="1"', $tag);
            }

            return $tag;
        }, $html);
    }

    private function markup(WP_Post $post, array $s, bool $preview): string
    {
        $dom_id = 'apc-popup-'.$post->ID;
        $title = get_the_title($post);
        $modal = $s['position'] === 'center';
        $has_title = $s['show_title'] && $title !== '';
        $name = $title !== '' ? $title : __('Information', 'apc-popup');

        $config = [
            'id' => $post->ID,
            'version' => APC_Popup_Settings::version($post->ID),
            'trigger' => $s['trigger'],
            'delay' => $s['delay'],
            'scroll' => $s['scroll'],
            'exitFallback' => $s['exit_fallback'],
            'frequency' => $s['frequency'],
            'days' => $s['frequency_days'],
            'start' => APC_Popup_Settings::timestamp($s['start']),
            'end' => APC_Popup_Settings::timestamp($s['end']),
            'devices' => $s['devices'],
            'modal' => $modal,
            'closeOnOverlay' => $s['close_on_overlay'],
            'preview' => $preview,
            // Annonce des popups discrètes (sans fond) aux lecteurs d'écran.
            /* translators: %s: titre de la popup */
            'announce' => sprintf(__('%s : message affiché en bas de l’écran.', 'apc-popup'), wp_strip_all_tags(html_entity_decode($name, ENT_QUOTES, 'UTF-8'))),
        ];

        $style = sprintf(
            '--apc-popup-bg:%s;--apc-popup-text:%s;--apc-popup-cta-bg:%s;--apc-popup-cta-text:%s;--apc-popup-cta-hover-bg:%s;--apc-popup-cta-hover-text:%s;'
            .'--apc-popup-cta-border-width:%dpx;--apc-popup-cta-border:%s;--apc-popup-cta-hover-border:%s;--apc-popup-cta-radius:%dpx;--apc-popup-overlay:%s;',
            $s['bg_color'],
            $s['text_color'],
            $s['cta_bg_color'],
            $s['cta_text_color'],
            $s['cta_hover_bg_color'],
            $s['cta_hover_text_color'],
            $s['cta_border_width'],
            $s['cta_border_color'],
            $s['cta_hover_border_color'],
            $s['cta_radius'],
            number_format($s['overlay_opacity'] / 100, 2, '.', '')
        );

        $classes = [
            'apc-popup',
            'apc-popup--'.$s['position'],
            'apc-popup--'.$s['size'],
        ];
        if (! $has_title) {
            $classes[] = 'apc-popup--no-title';
        }

        $dialog_label = $has_title
            ? sprintf('aria-labelledby="%s"', esc_attr($dom_id.'-title'))
            : sprintf('aria-label="%s"', esc_attr($name));

        ob_start();
        ?>
<div class="<?php echo esc_attr(implode(' ', $classes)); ?>" id="<?php echo esc_attr($dom_id); ?>" style="<?php echo esc_attr($style); ?>" data-apc-popup="<?php echo esc_attr(wp_json_encode($config)); ?>" hidden>
    <?php if ($modal) { ?>
        <div class="apc-popup__overlay" data-apc-popup-overlay></div>
    <?php } ?>
    <div class="apc-popup__dialog" role="dialog" aria-modal="<?php echo $modal ? 'true' : 'false'; ?>" <?php echo $dialog_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> tabindex="-1">
        <button type="button" class="apc-popup__close" data-apc-popup-close aria-label="<?php esc_attr_e('Fermer', 'apc-popup'); ?>">
            <span aria-hidden="true">&times;</span>
        </button>
        <div class="apc-popup__body" tabindex="-1">
            <?php if ($has_title) { ?>
                <h2 class="apc-popup__title apc-popup__title--<?php echo esc_attr($s['title_align']); ?>" id="<?php echo esc_attr($dom_id.'-title'); ?>"><?php echo esc_html($title); ?></h2>
            <?php } ?>
            <div class="apc-popup__content">
                <?php echo $this->content_html($post); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
            <?php if ($s['cta_label'] !== '' && $s['cta_url'] !== '') { ?>
                <p class="apc-popup__actions">
                    <a class="apc-popup__cta" href="<?php echo esc_url($s['cta_url']); ?>" data-apc-popup-cta<?php echo $s['cta_new_tab'] ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html($s['cta_label']); ?></a>
                </p>
            <?php } ?>
        </div>
    </div>
</div>
        <?php

        return (string) ob_get_clean();
    }
}
