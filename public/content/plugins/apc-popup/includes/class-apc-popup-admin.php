<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Administration des popups : réglages, recherche de contenus et de termes,
 * remise à zéro des compteurs, colonnes de la liste, aperçu.
 */
final class APC_Popup_Admin
{
    const NONCE_SAVE = 'apc_popup_save';

    const NONCE_SEARCH = 'apc_popup_search';

    const ACTION_RESET = 'apc_popup_reset';

    const NOTICE_PARAM = 'apc_popup_reset';

    /** @var bool Compteurs réinitialisés pendant l'enregistrement en cours. */
    private $reset_on_save = false;

    public function hooks(): void
    {
        $type = APC_Popup_Settings::POST_TYPE;

        add_action('add_meta_boxes_'.$type, [$this, 'add_meta_boxes']);
        add_action('save_post_'.$type, [$this, 'save'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        add_action('wp_ajax_apc_popup_search', [$this, 'ajax_search']);
        add_action('admin_post_'.self::ACTION_RESET, [$this, 'handle_reset']);
        add_action('admin_notices', [$this, 'notices']);
        add_filter('removable_query_args', [$this, 'removable_query_args']);
        add_action('load-edit.php', [$this, 'strip_notice_param']);
        add_filter('redirect_post_location', [$this, 'redirect_after_save'], 10, 2);

        add_filter('manage_'.$type.'_posts_columns', [$this, 'columns']);
        add_action('manage_'.$type.'_posts_custom_column', [$this, 'column_content'], 10, 2);
        add_filter('post_row_actions', [$this, 'row_actions'], 10, 2);
        add_filter('bulk_actions-edit-'.$type, [$this, 'bulk_actions']);
        add_filter('handle_bulk_actions-edit-'.$type, [$this, 'handle_bulk_action'], 10, 3);
        add_filter('enter_title_here', [$this, 'title_placeholder'], 10, 2);
    }

    /* ------------------------------------------------------------------ */
    /* Écran d'édition                                                     */
    /* ------------------------------------------------------------------ */

    public function add_meta_boxes(): void
    {
        add_meta_box('apc-popup-settings', __('Réglages de la popup', 'apc-popup'), [$this, 'render_settings_box'], APC_Popup_Settings::POST_TYPE, 'normal', 'high');
        add_meta_box('apc-popup-tools', __('Compteurs et aperçu', 'apc-popup'), [$this, 'render_tools_box'], APC_Popup_Settings::POST_TYPE, 'side', 'default');
    }

    public function title_placeholder(string $placeholder, WP_Post $post): string
    {
        return $post->post_type === APC_Popup_Settings::POST_TYPE
            ? __('Titre de la popup (affiché aux visiteurs)', 'apc-popup')
            : $placeholder;
    }

    public function render_settings_box(WP_Post $post): void
    {
        $s = APC_Popup_Settings::get($post->ID);
        wp_nonce_field(self::NONCE_SAVE, 'apc_popup_nonce');

        $warnings = $this->warnings($s);
        if ($post->post_password !== '') {
            $warnings[] = __('Cette popup est protégée par mot de passe (bloc Publier) : elle ne sera jamais affichée aux visiteurs.', 'apc-popup');
        }
        ?>
        <div class="apc-popup-admin">
            <?php foreach ($warnings as $warning) { ?>
                <div class="notice notice-warning inline"><p><?php echo esc_html($warning); ?></p></div>
            <?php } ?>

            <p class="apc-popup-admin__intro">
                <?php esc_html_e("Le contenu de la popup (texte, images) se saisit dans l'éditeur ci-dessus. Le titre de la page sert de titre à la popup.", 'apc-popup'); ?>
            </p>

            <?php $this->section_content($s); ?>
            <?php $this->section_location($s); ?>
            <?php $this->section_audience($s); ?>
            <?php $this->section_timing($s); ?>
            <?php $this->section_appearance($s); ?>
        </div>
        <?php
    }

    private function section_content(array $s): void
    {
        ?>
        <h3 class="apc-popup-admin__heading"><?php esc_html_e('Contenu', 'apc-popup'); ?></h3>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Titre', 'apc-popup'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="apc_popup[show_title]" value="1" <?php checked($s['show_title']); ?>>
                        <?php esc_html_e('Afficher le titre dans la popup', 'apc-popup'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="apc-popup-cta-label"><?php esc_html_e("Bouton d'action", 'apc-popup'); ?></label></th>
                <td>
                    <p>
                        <input type="text" id="apc-popup-cta-label" class="regular-text" name="apc_popup[cta_label]" value="<?php echo esc_attr($s['cta_label']); ?>" placeholder="<?php esc_attr_e('Libellé, ex. Je réserve', 'apc-popup'); ?>">
                    </p>
                    <p>
                        <input type="url" class="regular-text" name="apc_popup[cta_url]" value="<?php echo esc_attr($s['cta_url']); ?>" placeholder="https://" aria-label="<?php esc_attr_e('Lien du bouton', 'apc-popup'); ?>">
                    </p>
                    <p>
                        <label>
                            <input type="checkbox" name="apc_popup[cta_new_tab]" value="1" <?php checked($s['cta_new_tab']); ?>>
                            <?php esc_html_e('Ouvrir dans un nouvel onglet', 'apc-popup'); ?>
                        </label>
                    </p>
                    <p class="description"><?php esc_html_e("Facultatif. Le bouton s'affiche si le libellé et le lien sont renseignés. Un clic ferme la popup.", 'apc-popup'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    private function section_location(array $s): void
    {
        $post_types = APC_Popup_Settings::targetable_post_types();
        $archives = APC_Popup_Settings::archivable_post_types();
        $taxonomies = APC_Popup_Settings::targetable_taxonomies();
        ?>
        <h3 class="apc-popup-admin__heading"><?php esc_html_e("Où l'afficher", 'apc-popup'); ?></h3>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Pages concernées', 'apc-popup'); ?></th>
                <td>
                    <fieldset>
                        <label class="apc-popup-admin__line">
                            <input type="radio" name="apc_popup[display_mode]" value="all" <?php checked($s['display_mode'], 'all'); ?>>
                            <?php esc_html_e('Partout sur le site', 'apc-popup'); ?>
                        </label>
                        <label class="apc-popup-admin__line">
                            <input type="radio" name="apc_popup[display_mode]" value="include" <?php checked($s['display_mode'], 'include'); ?>>
                            <?php esc_html_e('Uniquement sur les emplacements choisis ci-dessous', 'apc-popup'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr data-apc-show="display_mode=include">
                <th scope="row"><?php esc_html_e('Pages spéciales', 'apc-popup'); ?></th>
                <td>
                    <label class="apc-popup-admin__line">
                        <input type="checkbox" name="apc_popup[include_front]" value="1" <?php checked($s['include_front']); ?>>
                        <?php esc_html_e("Page d'accueil", 'apc-popup'); ?>
                    </label>
                    <?php if (APC_Popup_Settings::has_posts_page()) { ?>
                        <label class="apc-popup-admin__line">
                            <input type="checkbox" name="apc_popup[include_blog]" value="1" <?php checked($s['include_blog']); ?>>
                            <?php esc_html_e('Page des articles (liste des actualités)', 'apc-popup'); ?>
                        </label>
                    <?php } else { ?>
                        <p class="description"><?php esc_html_e("Aucune page des articles n'est définie (Réglages > Lecture) : si les actualités sont listées sur une page du site, choisissez-la dans « Contenus précis ».", 'apc-popup'); ?></p>
                    <?php } ?>
                </td>
            </tr>
            <tr data-apc-show="display_mode=include">
                <th scope="row"><?php esc_html_e('Contenus précis', 'apc-popup'); ?></th>
                <td>
                    <?php $this->picker('posts', 'include_ids', $s['include_ids'], __('Rechercher une page, une actualité, une croisière…', 'apc-popup')); ?>
                    <p class="description"><?php esc_html_e("N'importe quel contenu : page, actualité, croisière, produit…", 'apc-popup'); ?></p>
                </td>
            </tr>
            <tr data-apc-show="display_mode=include">
                <th scope="row"><?php esc_html_e('Tous les contenus de type', 'apc-popup'); ?></th>
                <td>
                    <?php $this->checkboxes('include_post_types', $post_types, $s['include_post_types']); ?>
                    <p class="description"><?php esc_html_e('Chaque page individuelle de ces types (ex. toutes les fiches croisières).', 'apc-popup'); ?></p>
                </td>
            </tr>
            <?php if ($archives) { ?>
                <tr data-apc-show="display_mode=include">
                    <th scope="row"><?php esc_html_e('Pages de liste (archives)', 'apc-popup'); ?></th>
                    <td>
                        <?php $this->checkboxes('include_archives', $archives, $s['include_archives']); ?>
                        <p class="description"><?php esc_html_e('La page qui liste tous les contenus de ce type (ex. la liste des croisières).', 'apc-popup'); ?></p>
                    </td>
                </tr>
            <?php } ?>
            <?php if ($taxonomies) { ?>
                <tr data-apc-show="display_mode=include">
                    <th scope="row"><?php esc_html_e('Archives de taxonomie', 'apc-popup'); ?></th>
                    <td>
                        <?php $this->checkboxes('include_taxonomies', $taxonomies, $s['include_taxonomies']); ?>
                        <p class="description"><?php esc_html_e('Toutes les pages de ces classements (ex. chaque page de catégorie).', 'apc-popup'); ?></p>
                    </td>
                </tr>
                <tr data-apc-show="display_mode=include">
                    <th scope="row"><?php esc_html_e('Termes précis', 'apc-popup'); ?></th>
                    <td>
                        <?php $this->picker('terms', 'include_terms', $s['include_terms'], __('Rechercher une catégorie, une étiquette…', 'apc-popup')); ?>
                        <p class="description"><?php esc_html_e("Uniquement la page d'archive de ces termes. Seuls les classements qui ont leur propre page sur le site sont proposés.", 'apc-popup'); ?></p>
                    </td>
                </tr>
            <?php } ?>
            <tr>
                <th scope="row"><?php esc_html_e('Exclure', 'apc-popup'); ?></th>
                <td>
                    <?php $this->picker('posts', 'exclude_ids', $s['exclude_ids'], __('Rechercher un contenu à exclure…', 'apc-popup')); ?>
                    <p class="description"><?php esc_html_e("La popup ne s'affichera jamais sur ces contenus, même en mode « Partout ».", 'apc-popup'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    private function section_audience(array $s): void
    {
        ?>
        <h3 class="apc-popup-admin__heading"><?php esc_html_e('Pour qui', 'apc-popup'); ?></h3>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Appareils', 'apc-popup'); ?></th>
                <td>
                    <label class="apc-popup-admin__inline">
                        <input type="checkbox" name="apc_popup[devices][]" value="desktop" <?php checked(in_array('desktop', $s['devices'], true)); ?>>
                        <?php esc_html_e('Ordinateur et tablette', 'apc-popup'); ?>
                    </label>
                    <label class="apc-popup-admin__inline">
                        <input type="checkbox" name="apc_popup[devices][]" value="mobile" <?php checked(in_array('mobile', $s['devices'], true)); ?>>
                        <?php esc_html_e('Mobile', 'apc-popup'); ?>
                    </label>
                    <p class="description"><?php esc_html_e("Mobile = écran de moins de 768 pixels de large.", 'apc-popup'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Visiteurs', 'apc-popup'); ?></th>
                <td>
                    <fieldset>
                        <?php
                        $audiences = [
                            'all' => __('Tous les visiteurs', 'apc-popup'),
                            'logged_out' => __('Visiteurs non connectés uniquement', 'apc-popup'),
                            'logged_in' => __('Visiteurs connectés uniquement', 'apc-popup'),
                        ];
                        foreach ($audiences as $value => $label) { ?>
                            <label class="apc-popup-admin__line">
                                <input type="radio" name="apc_popup[audience]" value="<?php echo esc_attr($value); ?>" <?php checked($s['audience'], $value); ?>>
                                <?php echo esc_html($label); ?>
                            </label>
                        <?php } ?>
                    </fieldset>
                </td>
            </tr>
        </table>
        <?php
    }

    private function section_timing(array $s): void
    {
        ?>
        <h3 class="apc-popup-admin__heading"><?php esc_html_e("Quand l'afficher", 'apc-popup'); ?></h3>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="apc-popup-trigger"><?php esc_html_e('Déclenchement', 'apc-popup'); ?></label></th>
                <td>
                    <select id="apc-popup-trigger" name="apc_popup[trigger]" data-apc-control="trigger">
                        <option value="load" <?php selected($s['trigger'], 'load'); ?>><?php esc_html_e("Dès l'arrivée sur la page", 'apc-popup'); ?></option>
                        <option value="delay" <?php selected($s['trigger'], 'delay'); ?>><?php esc_html_e('Après un délai', 'apc-popup'); ?></option>
                        <option value="scroll" <?php selected($s['trigger'], 'scroll'); ?>><?php esc_html_e('Après avoir fait défiler la page', 'apc-popup'); ?></option>
                        <option value="exit" <?php selected($s['trigger'], 'exit'); ?>><?php esc_html_e("Quand le visiteur s'apprête à quitter la page", 'apc-popup'); ?></option>
                    </select>
                    <p data-apc-show="trigger=delay">
                        <label>
                            <?php esc_html_e('Délai :', 'apc-popup'); ?>
                            <input type="number" class="small-text" min="0" max="3600" name="apc_popup[delay]" value="<?php echo esc_attr($s['delay']); ?>">
                            <?php esc_html_e('secondes', 'apc-popup'); ?>
                        </label>
                    </p>
                    <p data-apc-show="trigger=scroll">
                        <label>
                            <?php esc_html_e('Après', 'apc-popup'); ?>
                            <input type="number" class="small-text" min="1" max="100" name="apc_popup[scroll]" value="<?php echo esc_attr($s['scroll']); ?>">
                            <?php esc_html_e('% de la page parcourue', 'apc-popup'); ?>
                        </label>
                    </p>
                    <div data-apc-show="trigger=exit">
                        <p>
                            <label>
                                <?php esc_html_e('Sur mobile et tablette tactile, afficher après', 'apc-popup'); ?>
                                <input type="number" class="small-text" min="0" max="3600" name="apc_popup[exit_fallback]" value="<?php echo esc_attr($s['exit_fallback']); ?>">
                                <?php esc_html_e('secondes', 'apc-popup'); ?>
                            </label>
                        </p>
                        <p class="description"><?php esc_html_e("L'intention de sortie se détecte avec la souris (ordinateur) : sur écran tactile, un délai la remplace. Mettez 0 pour ne rien afficher sur ces écrans.", 'apc-popup'); ?></p>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="apc-popup-frequency"><?php esc_html_e('Fréquence', 'apc-popup'); ?></label></th>
                <td>
                    <select id="apc-popup-frequency" name="apc_popup[frequency]" data-apc-control="frequency">
                        <option value="always" <?php selected($s['frequency'], 'always'); ?>><?php esc_html_e('À chaque chargement de page', 'apc-popup'); ?></option>
                        <option value="session" <?php selected($s['frequency'], 'session'); ?>><?php esc_html_e('Une fois par visite (session du navigateur)', 'apc-popup'); ?></option>
                        <option value="days" <?php selected($s['frequency'], 'days'); ?>><?php esc_html_e('Une fois tous les X jours', 'apc-popup'); ?></option>
                        <option value="once" <?php selected($s['frequency'], 'once'); ?>><?php esc_html_e('Une seule fois', 'apc-popup'); ?></option>
                    </select>
                    <p data-apc-show="frequency=days">
                        <label>
                            <?php esc_html_e('Tous les', 'apc-popup'); ?>
                            <input type="number" class="small-text" min="1" max="365" name="apc_popup[frequency_days]" value="<?php echo esc_attr($s['frequency_days']); ?>">
                            <?php esc_html_e('jours (7 = une fois par semaine)', 'apc-popup'); ?>
                        </label>
                    </p>
                    <p class="description"><?php esc_html_e('Le compteur est mémorisé dans le navigateur de chaque visiteur. Le bouton « Réinitialiser les compteurs » réaffiche la popup à tout le monde.', 'apc-popup'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Période', 'apc-popup'); ?></th>
                <td>
                    <p>
                        <label>
                            <span class="apc-popup-admin__date-label"><?php esc_html_e('Début', 'apc-popup'); ?></span>
                            <input type="datetime-local" name="apc_popup[start]" value="<?php echo esc_attr($s['start']); ?>">
                        </label>
                    </p>
                    <p>
                        <label>
                            <span class="apc-popup-admin__date-label"><?php esc_html_e('Fin', 'apc-popup'); ?></span>
                            <input type="datetime-local" name="apc_popup[end]" value="<?php echo esc_attr($s['end']); ?>">
                        </label>
                    </p>
                    <p class="description">
                        <?php
                        printf(
                            /* translators: %s: fuseau horaire du site */
                            esc_html__('Facultatif. Heure du site (%s). Laissez vide pour une diffusion sans limite.', 'apc-popup'),
                            esc_html(wp_timezone_string())
                        );
                        ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="apc-popup-priority"><?php esc_html_e('Priorité', 'apc-popup'); ?></label></th>
                <td>
                    <input type="number" id="apc-popup-priority" class="small-text" min="-999" max="999" name="apc_popup[priority]" value="<?php echo esc_attr($s['priority']); ?>">
                    <p class="description"><?php esc_html_e("Une seule popup s'affiche par page vue. Si plusieurs sont possibles au même moment, la plus petite valeur passe en premier.", 'apc-popup'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    private function section_appearance(array $s): void
    {
        $colors = [
            'bg_color' => __('Fond', 'apc-popup'),
            'text_color' => __('Texte', 'apc-popup'),
        ];
        $cta_colors = [
            __('Normal', 'apc-popup') => [
                'cta_bg_color' => __('Fond', 'apc-popup'),
                'cta_text_color' => __('Texte', 'apc-popup'),
                'cta_border_color' => __('Liseré', 'apc-popup'),
            ],
            __('Au survol', 'apc-popup') => [
                'cta_hover_bg_color' => __('Fond', 'apc-popup'),
                'cta_hover_text_color' => __('Texte', 'apc-popup'),
                'cta_hover_border_color' => __('Liseré', 'apc-popup'),
            ],
        ];
        $preview_style = sprintf(
            '--cta-bg:%s;--cta-text:%s;--cta-hover-bg:%s;--cta-hover-text:%s;--cta-border-width:%dpx;--cta-border:%s;--cta-hover-border:%s;--cta-radius:%dpx;',
            $s['cta_bg_color'],
            $s['cta_text_color'],
            $s['cta_hover_bg_color'],
            $s['cta_hover_text_color'],
            $s['cta_border_width'],
            $s['cta_border_color'],
            $s['cta_hover_border_color'],
            $s['cta_radius']
        );
        ?>
        <h3 class="apc-popup-admin__heading"><?php esc_html_e('Apparence', 'apc-popup'); ?></h3>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="apc-popup-position"><?php esc_html_e('Position', 'apc-popup'); ?></label></th>
                <td>
                    <select id="apc-popup-position" name="apc_popup[position]" data-apc-control="position">
                        <option value="center" <?php selected($s['position'], 'center'); ?>><?php esc_html_e('Au centre, avec fond assombri', 'apc-popup'); ?></option>
                        <option value="bottom-right" <?php selected($s['position'], 'bottom-right'); ?>><?php esc_html_e('En bas à droite, discrète', 'apc-popup'); ?></option>
                        <option value="bottom-left" <?php selected($s['position'], 'bottom-left'); ?>><?php esc_html_e('En bas à gauche, discrète', 'apc-popup'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="apc-popup-size"><?php esc_html_e('Largeur', 'apc-popup'); ?></label></th>
                <td>
                    <select id="apc-popup-size" name="apc_popup[size]">
                        <option value="small" <?php selected($s['size'], 'small'); ?>><?php esc_html_e('Petite (420 px)', 'apc-popup'); ?></option>
                        <option value="medium" <?php selected($s['size'], 'medium'); ?>><?php esc_html_e('Moyenne (600 px)', 'apc-popup'); ?></option>
                        <option value="large" <?php selected($s['size'], 'large'); ?>><?php esc_html_e('Grande (820 px)', 'apc-popup'); ?></option>
                        <option value="xlarge" <?php selected($s['size'], 'xlarge'); ?>><?php esc_html_e('Très grande (image de 1280 px affichée en taille réelle)', 'apc-popup'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e("Une image plus large que la popup est réduite à sa largeur. Sur un écran plus petit, la popup s'adapte à la largeur de l'écran.", 'apc-popup'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Couleurs de la popup', 'apc-popup'); ?></th>
                <td class="apc-popup-admin__colors">
                    <?php foreach ($colors as $key => $label) { ?>
                        <label class="apc-popup-admin__inline">
                            <input type="color" name="apc_popup[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($s[$key]); ?>">
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e("Couleurs du bouton d'action", 'apc-popup'); ?></th>
                <td class="apc-popup-admin__colors">
                    <?php foreach ($cta_colors as $state => $fields) { ?>
                        <div class="apc-popup-admin__color-row">
                            <span class="apc-popup-admin__color-state"><?php echo esc_html($state); ?></span>
                            <?php foreach ($fields as $key => $label) { ?>
                                <label class="apc-popup-admin__inline">
                                    <input type="color" name="apc_popup[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($s[$key]); ?>" data-apc-cta-color="<?php echo esc_attr($key); ?>">
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <div class="apc-popup-admin__color-row">
                        <span class="apc-popup-admin__color-state"><?php esc_html_e('Forme', 'apc-popup'); ?></span>
                        <label class="apc-popup-admin__inline">
                            <?php esc_html_e('Épaisseur du liseré', 'apc-popup'); ?>
                            <input type="number" class="small-text" min="0" max="10" name="apc_popup[cta_border_width]" value="<?php echo esc_attr($s['cta_border_width']); ?>" data-apc-cta-size="--cta-border-width"> px
                        </label>
                        <label class="apc-popup-admin__inline">
                            <?php esc_html_e('Arrondi des angles', 'apc-popup'); ?>
                            <input type="number" class="small-text" min="0" max="999" name="apc_popup[cta_radius]" value="<?php echo esc_attr($s['cta_radius']); ?>" data-apc-cta-size="--cta-radius"> px
                        </label>
                    </div>
                    <p class="description"><?php esc_html_e('Liseré : 0 = sans liseré, 2 = comme les boutons du site. Arrondi : 0 = angles droits, 8 = légèrement arrondis, 999 = forme pilule.', 'apc-popup'); ?></p>
                    <p class="apc-popup-admin__cta-preview-wrap">
                        <span class="apc-popup-admin__cta-preview" style="<?php echo esc_attr($preview_style); ?>" data-apc-cta-preview aria-hidden="true">
                            <?php echo esc_html($s['cta_label'] !== '' ? $s['cta_label'] : __('Aperçu du bouton', 'apc-popup')); ?>
                        </span>
                        <span class="description"><?php esc_html_e('Passez la souris sur le bouton pour voir les couleurs au survol.', 'apc-popup'); ?></span>
                    </p>
                </td>
            </tr>
            <tr data-apc-show="position=center">
                <th scope="row"><label for="apc-popup-overlay"><?php esc_html_e('Fond assombri', 'apc-popup'); ?></label></th>
                <td>
                    <p>
                        <label>
                            <?php esc_html_e('Opacité :', 'apc-popup'); ?>
                            <input type="number" id="apc-popup-overlay" class="small-text" min="0" max="95" name="apc_popup[overlay_opacity]" value="<?php echo esc_attr($s['overlay_opacity']); ?>"> %
                        </label>
                    </p>
                    <p>
                        <label>
                            <input type="checkbox" name="apc_popup[close_on_overlay]" value="1" <?php checked($s['close_on_overlay']); ?>>
                            <?php esc_html_e('Fermer la popup en cliquant sur le fond', 'apc-popup'); ?>
                        </label>
                    </p>
                    <p class="description"><?php esc_html_e('La croix et la touche Échap ferment toujours la popup.', 'apc-popup'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function render_tools_box(WP_Post $post): void
    {
        $s = APC_Popup_Settings::get($post->ID);
        $saved = $post->post_status !== 'auto-draft';
        ?>
        <div class="apc-popup-admin apc-popup-admin--side">
            <p>
                <strong><?php esc_html_e('Statut :', 'apc-popup'); ?></strong>
                <?php echo esc_html(self::status_label($post, $s)); ?>
            </p>

            <h4><?php esc_html_e('Compteurs des visiteurs', 'apc-popup'); ?></h4>
            <p>
                <?php
                printf(
                    /* translators: %d: numéro de version des compteurs */
                    esc_html__('Version actuelle : %d', 'apc-popup'),
                    (int) APC_Popup_Settings::version($post->ID)
                );
                ?>
            </p>
            <?php if ($saved) { ?>
                <p>
                    <?php // admin.js ajoute la demande de remise à zéro au formulaire puis clique sur le bouton d'enregistrement de WordPress. ?>
                    <button type="button" class="button" data-apc-reset-save="<?php esc_attr_e('Enregistrer la popup et la réafficher à tous les visiteurs, y compris ceux qui l’ont déjà vue ?', 'apc-popup'); ?>">
                        <?php esc_html_e('Enregistrer et réinitialiser les compteurs', 'apc-popup'); ?>
                    </button>
                </p>
                <p class="description"><?php esc_html_e('Tous les visiteurs reverront la popup, selon sa fréquence. Vos modifications sont enregistrées en même temps et le cache des pages est vidé automatiquement.', 'apc-popup'); ?></p>
            <?php } ?>

            <h4><?php esc_html_e('Aperçu', 'apc-popup'); ?></h4>
            <?php if ($saved) { ?>
                <p>
                    <a class="button" href="<?php echo esc_url(self::preview_url($post->ID)); ?>" target="_blank" rel="noopener">
                        <?php esc_html_e("Prévisualiser sur l'accueil", 'apc-popup'); ?>
                    </a>
                </p>
                <p class="description"><?php esc_html_e("Affiche la dernière version enregistrée, immédiatement, sans tenir compte du ciblage, des dates ni de la fréquence. Visible par vous seul.", 'apc-popup'); ?></p>
            <?php } else { ?>
                <p class="description"><?php esc_html_e("Enregistrez la popup (brouillon ou publication) pour pouvoir la prévisualiser.", 'apc-popup'); ?></p>
            <?php } ?>
        </div>
        <?php
    }

    /**
     * Champ de recherche avec sélection multiple (contenus ou termes).
     */
    private function picker(string $source, string $field, array $ids, string $placeholder): void
    {
        $name = 'apc_popup['.$field.'][]';
        ?>
        <div class="apc-picker" data-apc-picker="<?php echo esc_attr($source); ?>" data-name="<?php echo esc_attr($name); ?>">
            <ul class="apc-picker__chips">
                <?php foreach ($ids as $id) {
                    $label = $source === 'terms' ? self::term_label((int) $id) : self::post_label((int) $id);
                    if ($label === '') {
                        continue;
                    }
                    ?>
                    <li class="apc-picker__chip">
                        <span><?php echo esc_html($label); ?></span>
                        <button type="button" class="apc-picker__remove" aria-label="<?php echo esc_attr(sprintf(__('Retirer %s', 'apc-popup'), $label)); ?>">&times;</button>
                        <input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr((int) $id); ?>">
                    </li>
                <?php } ?>
            </ul>
            <div class="apc-picker__field">
                <input type="search" class="apc-picker__input regular-text" placeholder="<?php echo esc_attr($placeholder); ?>" aria-label="<?php echo esc_attr($placeholder); ?>" autocomplete="off">
                <p class="apc-picker__message" hidden></p>
                <ul class="apc-picker__results" role="listbox" aria-label="<?php esc_attr_e('Résultats', 'apc-popup'); ?>" hidden></ul>
            </div>
            <p class="apc-picker__status screen-reader-text" role="status" aria-live="polite"></p>
        </div>
        <?php
    }

    /**
     * @param  array<string, WP_Post_Type|WP_Taxonomy>  $objects
     */
    private function checkboxes(string $field, array $objects, array $selected): void
    {
        foreach ($objects as $key => $object) {
            $label = $object instanceof WP_Taxonomy ? APC_Popup_Settings::taxonomy_label($object) : $object->labels->name;
            ?>
            <label class="apc-popup-admin__inline">
                <input type="checkbox" name="apc_popup[<?php echo esc_attr($field); ?>][]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $selected, true)); ?>>
                <?php echo esc_html($label); ?>
            </label>
        <?php }
    }

    private function warnings(array $s): array
    {
        $warnings = [];
        $blog = $s['include_blog'] && APC_Popup_Settings::has_posts_page();

        if ($s['display_mode'] === 'include'
            && ! $s['include_front'] && ! $blog
            && ! $s['include_ids'] && ! $s['include_post_types'] && ! $s['include_archives']
            && ! $s['include_taxonomies'] && ! $s['include_terms']) {
            $warnings[] = __("Aucun emplacement n'est choisi : la popup ne s'affichera nulle part.", 'apc-popup');
        }

        if (! $s['devices']) {
            $warnings[] = __("Aucun appareil n'est coché : la popup ne s'affichera sur aucun écran.", 'apc-popup');
        }

        $start = APC_Popup_Settings::timestamp($s['start']);
        $end = APC_Popup_Settings::timestamp($s['end']);
        if ($start !== null && $end !== null && $end <= $start) {
            $warnings[] = __('La date de fin est antérieure à la date de début : la popup ne s’affichera jamais.', 'apc-popup');
        }

        if (($s['cta_label'] === '') !== ($s['cta_url'] === '')) {
            $warnings[] = __("Le bouton d'action n'apparaîtra pas : il faut à la fois un libellé et un lien.", 'apc-popup');
        }

        if ($s['trigger'] === 'exit' && $s['exit_fallback'] === 0 && ! in_array('desktop', $s['devices'], true)) {
            $warnings[] = __("Intention de sortie sur mobile seul, sans délai de repli : la popup ne s'affichera jamais.", 'apc-popup');
        }

        return $warnings;
    }

    public function save(int $post_id, WP_Post $post): void
    {
        if (! isset($_POST['apc_popup_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['apc_popup_nonce'])), self::NONCE_SAVE)) {
            return;
        }

        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $raw = isset($_POST['apc_popup']) && is_array($_POST['apc_popup']) ? $_POST['apc_popup'] : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé champ par champ ci-dessous.

        update_post_meta($post_id, APC_Popup_Settings::META_SETTINGS, APC_Popup_Settings::sanitize($raw));

        // Bouton « Enregistrer et réinitialiser les compteurs ». Le cache est
        // vidé par l'enregistrement lui-même si la popup est publiée.
        if (! empty($_POST['apc_popup_reset_on_save'])) {
            APC_Popup_Settings::bump_version($post_id);
            $this->reset_on_save = true;
        }
    }

    /**
     * Après « Enregistrer et réinitialiser » : message de confirmation.
     */
    public function redirect_after_save(string $location, int $post_id): string
    {
        return $this->reset_on_save && get_post_type($post_id) === APC_Popup_Settings::POST_TYPE
            ? add_query_arg(self::NOTICE_PARAM, 1, $location)
            : $location;
    }

    /* ------------------------------------------------------------------ */
    /* Recherche AJAX (contenus et termes)                                 */
    /* ------------------------------------------------------------------ */

    public function ajax_search(): void
    {
        check_ajax_referer(self::NONCE_SEARCH, 'nonce');

        if (! current_user_can('edit_pages')) {
            wp_send_json_error(['message' => __('Accès refusé.', 'apc-popup')], 403);
        }

        $term = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
        $source = isset($_GET['source']) ? sanitize_key($_GET['source']) : 'posts';

        if (mb_strlen($term) < 2 && ! ctype_digit($term)) {
            wp_send_json_success([]);
        }

        wp_send_json_success($source === 'terms' ? $this->search_terms($term) : $this->search_posts($term));
    }

    private function search_posts(string $term): array
    {
        $types = array_keys(APC_Popup_Settings::targetable_post_types());
        $results = [];

        if (ctype_digit($term)) {
            $post = get_post((int) $term);
            if ($post && in_array($post->post_type, $types, true) && self::is_selectable($post)) {
                $results[$post->ID] = ['id' => $post->ID, 'label' => self::post_label($post->ID)];
            }
        }

        $query = new WP_Query([
            'post_type' => $types,
            'post_status' => ['publish', 'future', 'private'],
            's' => $term,
            'posts_per_page' => 50,
            'no_found_rows' => true,
            'orderby' => 'relevance',
        ]);

        foreach ($query->posts as $post) {
            if (count($results) >= 20) {
                break;
            }
            if (self::is_selectable($post)) {
                $results[$post->ID] = ['id' => $post->ID, 'label' => self::post_label($post->ID)];
            }
        }

        return array_values($results);
    }

    /**
     * Contenu proposable : en ligne, programmé ou privé, lisible par
     * l'utilisateur (pas de fuite de titres de brouillons ou de contenus
     * réservés à d'autres rôles), et pas un produit technique masqué.
     */
    private static function is_selectable(WP_Post $post): bool
    {
        return in_array($post->post_status, ['publish', 'future', 'private'], true)
            && current_user_can('read_post', $post->ID)
            && ! self::is_hidden_product($post);
    }

    /**
     * Produits WooCommerce masqués du catalogue et de la recherche (produits
     * techniques : cartes cadeaux, réservations) : ils n'ont pas de page à cibler.
     */
    private static function is_hidden_product(WP_Post $post): bool
    {
        if ($post->post_type !== 'product' || ! function_exists('wc_get_product')) {
            return false;
        }

        $product = wc_get_product($post);

        return $product && $product->get_catalog_visibility() === 'hidden';
    }

    private function search_terms(string $term): array
    {
        $terms = get_terms([
            'taxonomy' => array_keys(APC_Popup_Settings::targetable_taxonomies()),
            'search' => $term,
            'hide_empty' => false,
            'number' => 20,
        ]);

        if (is_wp_error($terms)) {
            return [];
        }

        return array_map(function (WP_Term $item) {
            return ['id' => $item->term_id, 'label' => self::term_label($item->term_id)];
        }, $terms);
    }

    public static function post_label(int $id): string
    {
        $post = get_post($id);
        if (! $post) {
            return '';
        }

        $type = get_post_type_object($post->post_type);
        $title = get_the_title($post);
        if ($title === '') {
            $title = sprintf(__('(sans titre, #%d)', 'apc-popup'), $id);
        }

        $status = $post->post_status !== 'publish' ? get_post_status_object($post->post_status) : null;

        return html_entity_decode(sprintf(
            '%s (%s%s, #%d)',
            $title,
            $type ? $type->labels->singular_name : $post->post_type,
            $status ? ', '.mb_strtolower($status->label) : '',
            $id
        ), ENT_QUOTES, 'UTF-8');
    }

    public static function term_label(int $id): string
    {
        $term = get_term($id);
        if (! $term instanceof WP_Term) {
            return '';
        }

        $taxonomy = get_taxonomy($term->taxonomy);
        $context = $term->taxonomy;
        if ($taxonomy) {
            $context = $taxonomy->labels->singular_name;
            foreach ((array) $taxonomy->object_type as $type) {
                $object = get_post_type_object($type);
                if ($object && is_post_type_viewable($object)) {
                    $context .= ', '.$object->labels->name;
                    break;
                }
            }
        }

        return html_entity_decode(sprintf('%s (%s)', $term->name, $context), ENT_QUOTES, 'UTF-8');
    }

    /* ------------------------------------------------------------------ */
    /* Remise à zéro des compteurs                                         */
    /* ------------------------------------------------------------------ */

    public static function reset_url(int $post_id): string
    {
        return wp_nonce_url(
            add_query_arg(['action' => self::ACTION_RESET, 'post' => $post_id], admin_url('admin-post.php')),
            self::ACTION_RESET.'_'.$post_id
        );
    }

    public static function preview_url(int $post_id): string
    {
        return add_query_arg(APC_Popup_Frontend::PREVIEW_PARAM, $post_id, home_url('/'));
    }

    public function handle_reset(): void
    {
        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;

        check_admin_referer(self::ACTION_RESET.'_'.$post_id);

        if (! $post_id || get_post_type($post_id) !== APC_Popup_Settings::POST_TYPE || ! current_user_can('edit_post', $post_id)) {
            wp_die(esc_html__("Vous n'avez pas l'autorisation de réinitialiser cette popup.", 'apc-popup'), 403);
        }

        APC_Popup_Settings::bump_version($post_id);

        // Seules les popups visibles (publiées, dans leur période) sont dans les pages en cache.
        if (APC_Popup_Cache::is_live($post_id)) {
            APC_Popup_Cache::schedule_purge();
        }

        $back = wp_get_referer() ?: admin_url('edit.php?post_type='.APC_Popup_Settings::POST_TYPE);
        wp_safe_redirect(add_query_arg(self::NOTICE_PARAM, 1, $back));
        exit;
    }

    public function bulk_actions(array $actions): array
    {
        $actions[self::ACTION_RESET] = __('Réinitialiser les compteurs', 'apc-popup');

        return $actions;
    }

    public function handle_bulk_action(string $redirect, string $action, array $post_ids): string
    {
        if ($action !== self::ACTION_RESET) {
            return $redirect;
        }

        $count = 0;
        $purge = false;
        foreach ($post_ids as $post_id) {
            if (current_user_can('edit_post', $post_id)) {
                APC_Popup_Settings::bump_version((int) $post_id);
                $count++;
                $purge = $purge || APC_Popup_Cache::is_live((int) $post_id);
            }
        }

        if ($purge) {
            APC_Popup_Cache::schedule_purge();
        }

        return add_query_arg(self::NOTICE_PARAM, $count, $redirect);
    }

    public function notices(): void
    {
        $screen = get_current_screen();
        if (! $screen || $screen->post_type !== APC_Popup_Settings::POST_TYPE || empty($_GET[self::NOTICE_PARAM])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        $count = absint($_GET[self::NOTICE_PARAM]); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $message = $count > 1
            /* translators: %d: nombre de popups */
            ? sprintf(__('Compteurs réinitialisés pour %d popups : elles seront réaffichées à tous les visiteurs (le cache des pages est vidé pour les popups publiées).', 'apc-popup'), $count)
            : __('Compteurs réinitialisés : la popup sera réaffichée à tous les visiteurs (le cache des pages est vidé si elle est publiée).', 'apc-popup');

        printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html($message));
    }

    public function removable_query_args(array $args): array
    {
        $args[] = self::NOTICE_PARAM;

        return $args;
    }

    /**
     * Retire le paramètre du message de l'adresse de référence de la liste :
     * sinon le message réapparaîtrait après une action groupée suivante.
     */
    public function strip_notice_param(): void
    {
        if (isset($_GET[self::NOTICE_PARAM], $_SERVER['REQUEST_URI'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $_SERVER['REQUEST_URI'] = remove_query_arg(self::NOTICE_PARAM, wp_unslash($_SERVER['REQUEST_URI'])); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        }
    }

    /* ------------------------------------------------------------------ */
    /* Liste des popups                                                    */
    /* ------------------------------------------------------------------ */

    public function columns(array $columns): array
    {
        $date = $columns['date'] ?? null;
        unset($columns['date']);

        $columns['apc_status'] = __('Statut', 'apc-popup');
        $columns['apc_location'] = __('Où', 'apc-popup');
        $columns['apc_timing'] = __('Quand', 'apc-popup');
        $columns['apc_priority'] = __('Priorité', 'apc-popup');

        if ($date) {
            $columns['date'] = $date;
        }

        return $columns;
    }

    public function column_content(string $column, int $post_id): void
    {
        $post = get_post($post_id);
        if (! $post) {
            return;
        }

        $s = APC_Popup_Settings::get($post_id);

        switch ($column) {
            case 'apc_status':
                echo esc_html(self::status_label($post, $s));
                break;
            case 'apc_location':
                echo esc_html(self::location_summary($s));
                break;
            case 'apc_timing':
                echo esc_html(self::trigger_summary($s));
                echo '<br><span class="description">'.esc_html(self::frequency_summary($s)).'</span>';
                break;
            case 'apc_priority':
                echo esc_html((string) $s['priority']);
                break;
        }
    }

    public function row_actions(array $actions, WP_Post $post): array
    {
        if ($post->post_type !== APC_Popup_Settings::POST_TYPE || ! current_user_can('edit_post', $post->ID) || $post->post_status === 'trash') {
            return $actions;
        }

        $actions['apc_preview'] = sprintf(
            '<a href="%s" target="_blank" rel="noopener">%s</a>',
            esc_url(self::preview_url($post->ID)),
            esc_html__('Prévisualiser', 'apc-popup')
        );

        $actions['apc_reset'] = sprintf(
            '<a href="%s" data-apc-confirm="%s">%s</a>',
            esc_url(self::reset_url($post->ID)),
            esc_attr__('Réafficher cette popup à tous les visiteurs, y compris ceux qui l’ont déjà vue ?', 'apc-popup'),
            esc_html__('Réinitialiser les compteurs', 'apc-popup')
        );

        return $actions;
    }

    /* ------------------------------------------------------------------ */
    /* Résumés lisibles                                                    */
    /* ------------------------------------------------------------------ */

    public static function status_label(WP_Post $post, array $s): string
    {
        if ($post->post_status !== 'publish') {
            $object = get_post_status_object($post->post_status);

            return $object ? $object->label : $post->post_status;
        }

        $format = get_option('date_format').' '.get_option('time_format');
        $start = APC_Popup_Settings::timestamp($s['start']);
        $end = APC_Popup_Settings::timestamp($s['end']);
        $now = time();

        if ($end !== null && $end <= $now) {
            /* translators: %s: date de fin */
            return sprintf(__('Terminée (le %s)', 'apc-popup'), wp_date($format, $end));
        }

        if ($start !== null && $start > $now) {
            /* translators: %s: date de début */
            return sprintf(__('Programmée (à partir du %s)', 'apc-popup'), wp_date($format, $start));
        }

        if ($end !== null) {
            /* translators: %s: date de fin */
            return sprintf(__("Active (jusqu'au %s)", 'apc-popup'), wp_date($format, $end));
        }

        return __('Active', 'apc-popup');
    }

    public static function location_summary(array $s): string
    {
        $excluded = count($s['exclude_ids']);

        if ($s['display_mode'] === 'all') {
            return $excluded
                /* translators: %d: nombre de contenus exclus */
                ? sprintf(_n('Partout (sauf %d contenu)', 'Partout (sauf %d contenus)', $excluded, 'apc-popup'), $excluded)
                : __('Partout', 'apc-popup');
        }

        $parts = [];
        if ($s['include_front']) {
            $parts[] = __('accueil', 'apc-popup');
        }
        if ($s['include_blog'] && APC_Popup_Settings::has_posts_page()) {
            $parts[] = __('page des articles', 'apc-popup');
        }
        if ($s['include_ids']) {
            /* translators: %d: nombre de contenus */
            $parts[] = sprintf(_n('%d contenu', '%d contenus', count($s['include_ids']), 'apc-popup'), count($s['include_ids']));
        }
        foreach ($s['include_post_types'] as $type) {
            $object = get_post_type_object($type);
            /* translators: %s: nom d'un type de contenu */
            $parts[] = sprintf(__('tous : %s', 'apc-popup'), $object ? $object->labels->name : $type);
        }
        foreach ($s['include_archives'] as $type) {
            $object = get_post_type_object($type);
            /* translators: %s: nom d'un type de contenu */
            $parts[] = sprintf(__('liste : %s', 'apc-popup'), $object ? $object->labels->name : $type);
        }
        foreach ($s['include_taxonomies'] as $taxonomy) {
            $object = get_taxonomy($taxonomy);
            /* translators: %s: nom d'une taxonomie */
            $parts[] = sprintf(__('archives : %s', 'apc-popup'), $object ? $object->labels->name : $taxonomy);
        }
        if ($s['include_terms']) {
            /* translators: %d: nombre de termes */
            $parts[] = sprintf(_n('%d terme', '%d termes', count($s['include_terms']), 'apc-popup'), count($s['include_terms']));
        }

        if (! $parts) {
            return __('Nulle part (aucun emplacement choisi)', 'apc-popup');
        }

        $summary = ucfirst(implode(', ', $parts));
        if ($excluded) {
            /* translators: %d: nombre de contenus exclus */
            $summary .= ' '.sprintf(_n('(sauf %d contenu)', '(sauf %d contenus)', $excluded, 'apc-popup'), $excluded);
        }

        return $summary;
    }

    public static function trigger_summary(array $s): string
    {
        switch ($s['trigger']) {
            case 'delay':
                /* translators: %d: secondes */
                return sprintf(__('Après %d s', 'apc-popup'), $s['delay']);
            case 'scroll':
                /* translators: %d: pourcentage */
                return sprintf(__('Après %d %% de défilement', 'apc-popup'), $s['scroll']);
            case 'exit':
                return __('Intention de sortie', 'apc-popup');
            default:
                return __("Dès l'arrivée", 'apc-popup');
        }
    }

    public static function frequency_summary(array $s): string
    {
        switch ($s['frequency']) {
            case 'always':
                return __('À chaque chargement', 'apc-popup');
            case 'session':
                return __('Une fois par visite', 'apc-popup');
            case 'once':
                return __('Une seule fois', 'apc-popup');
            default:
                return $s['frequency_days'] === 7
                    ? __('Une fois par semaine', 'apc-popup')
                    /* translators: %d: nombre de jours */
                    : sprintf(_n('Une fois par jour', 'Une fois tous les %d jours', $s['frequency_days'], 'apc-popup'), $s['frequency_days']);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Ressources                                                          */
    /* ------------------------------------------------------------------ */

    public function enqueue(string $hook): void
    {
        $screen = get_current_screen();
        if (! $screen || $screen->post_type !== APC_Popup_Settings::POST_TYPE) {
            return;
        }

        if (! in_array($hook, ['post.php', 'post-new.php', 'edit.php'], true)) {
            return;
        }

        wp_enqueue_style('apc-popup-admin', APC_POPUP_URL.'assets/css/admin.css', [], APC_POPUP_VERSION);
        wp_enqueue_script('apc-popup-admin', APC_POPUP_URL.'assets/js/admin.js', [], APC_POPUP_VERSION, true);
        wp_localize_script('apc-popup-admin', 'apcPopupAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_SEARCH),
            'i18n' => [
                'noResult' => __('Aucun résultat', 'apc-popup'),
                'searching' => __('Recherche…', 'apc-popup'),
                'remove' => __('Retirer %s', 'apc-popup'),
                'error' => __('La recherche a échoué. Réessayez.', 'apc-popup'),
                /* translators: %d: nombre de résultats */
                'results' => __('%d résultat(s), flèches haut et bas pour choisir', 'apc-popup'),
                /* translators: %s: nom du contenu */
                'added' => __('%s ajouté', 'apc-popup'),
                /* translators: %s: nom du contenu */
                'removed' => __('%s retiré', 'apc-popup'),
            ],
        ]);
    }
}
