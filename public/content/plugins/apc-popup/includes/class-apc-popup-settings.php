<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Schéma des réglages d'une popup : valeurs par défaut, lecture et nettoyage.
 *
 * Tous les réglages sont stockés dans une seule meta (tableau). Le numéro de
 * version des compteurs est stocké à part : l'incrémenter réaffiche la popup
 * à tous les visiteurs, quel que soit ce qu'ils ont déjà vu.
 */
final class APC_Popup_Settings
{
    const POST_TYPE = 'apc_popup';

    const META_SETTINGS = '_apc_popup_settings';

    const META_VERSION = '_apc_popup_version';

    const TRIGGERS = ['load', 'delay', 'scroll', 'exit'];

    const FREQUENCIES = ['always', 'session', 'days', 'once'];

    const POSITIONS = ['center', 'bottom-right', 'bottom-left'];

    const SIZES = ['small', 'medium', 'large', 'xlarge'];

    const AUDIENCES = ['all', 'logged_in', 'logged_out'];

    const DEVICES = ['desktop', 'mobile'];

    const DATE_FORMAT = 'Y-m-d\TH:i';

    public static function defaults(): array
    {
        return [
            'show_title' => true,
            'priority' => 10,

            // Ciblage
            'display_mode' => 'all',
            'include_front' => false,
            'include_blog' => false,
            'include_ids' => [],
            'include_post_types' => [],
            'include_archives' => [],
            'include_taxonomies' => [],
            'include_terms' => [],
            'exclude_ids' => [],

            // Visiteurs
            'devices' => self::DEVICES,
            'audience' => 'all',

            // Déclenchement
            'trigger' => 'load',
            'delay' => 5,
            'scroll' => 50,
            'exit_fallback' => 15,

            // Fréquence
            'frequency' => 'days',
            'frequency_days' => 7,

            // Planification (heure du site, format datetime-local)
            'start' => '',
            'end' => '',

            // Bouton d'action
            'cta_label' => '',
            'cta_url' => '',
            'cta_new_tab' => false,

            // Apparence
            'position' => 'center',
            'size' => 'medium',
            'bg_color' => '#ffffff',
            'text_color' => '#1f2937',
            'cta_bg_color' => '#1d4ed8',
            'cta_text_color' => '#ffffff',
            'cta_hover_bg_color' => '#1e3a8a',
            'cta_hover_text_color' => '#ffffff',
            'cta_border_width' => 0,
            'cta_border_color' => '#1d4ed8',
            'cta_hover_border_color' => '#1e3a8a',
            'cta_radius' => 999,
            'overlay_opacity' => 60,
            'close_on_overlay' => true,
        ];
    }

    public static function get(int $post_id): array
    {
        $stored = get_post_meta($post_id, self::META_SETTINGS, true);

        return array_merge(self::defaults(), is_array($stored) ? $stored : []);
    }

    public static function version(int $post_id): int
    {
        $version = (int) get_post_meta($post_id, self::META_VERSION, true);

        return $version > 0 ? $version : 1;
    }

    /**
     * Incrémente la version des compteurs : tous les visiteurs reverront la popup.
     */
    public static function bump_version(int $post_id): int
    {
        $version = self::version($post_id) + 1;
        update_post_meta($post_id, self::META_VERSION, $version);

        return $version;
    }

    /**
     * Types de contenu proposés au ciblage : ceux qui ont une page visible
     * sur le site (hors médias et popups).
     *
     * @return array<string, WP_Post_Type>
     */
    public static function targetable_post_types(): array
    {
        $types = array_filter(get_post_types(['public' => true], 'objects'), 'is_post_type_viewable');
        unset($types['attachment'], $types[self::POST_TYPE]);

        return $types;
    }

    /**
     * Types de contenu disposant d'une page d'archive.
     *
     * @return array<string, WP_Post_Type>
     */
    public static function archivable_post_types(): array
    {
        return array_filter(self::targetable_post_types(), function ($type) {
            return ! empty($type->has_archive);
        });
    }

    /**
     * Taxonomies proposées au ciblage de leurs pages d'archive : visibles sur
     * le site, gérables dans l'administration et rattachées à un type de
     * contenu ciblable (exclut formats d'articles, classes de livraison, etc.).
     *
     * @return array<string, WP_Taxonomy>
     */
    public static function targetable_taxonomies(): array
    {
        $post_types = array_keys(self::targetable_post_types());

        return array_filter(get_taxonomies(['public' => true], 'objects'), function ($taxonomy) use ($post_types) {
            return $taxonomy->name !== 'post_format'
                && $taxonomy->show_ui
                && is_taxonomy_viewable($taxonomy)
                && ! empty($taxonomy->rewrite)
                && array_intersect((array) $taxonomy->object_type, $post_types);
        });
    }

    /**
     * Libellé d'une taxonomie suivi des types de contenu concernés, pour
     * distinguer deux taxonomies homonymes (ex. « Catégories (Articles) »).
     */
    public static function taxonomy_label(WP_Taxonomy $taxonomy): string
    {
        $types = [];
        foreach ((array) $taxonomy->object_type as $type) {
            $object = get_post_type_object($type);
            if ($object && is_post_type_viewable($object)) {
                $types[] = $object->labels->name;
            }
        }

        return $types ? sprintf('%s (%s)', $taxonomy->labels->name, implode(', ', $types)) : $taxonomy->labels->name;
    }

    /**
     * Nettoie et valide les données envoyées par le formulaire d'administration.
     */
    public static function sanitize(array $raw): array
    {
        $defaults = self::defaults();
        $clean = [];

        foreach (['show_title', 'include_front', 'include_blog', 'cta_new_tab', 'close_on_overlay'] as $key) {
            $clean[$key] = ! empty($raw[$key]);
        }

        $clean['priority'] = self::int_between($raw['priority'] ?? $defaults['priority'], -999, 999);

        $clean['display_mode'] = self::one_of($raw['display_mode'] ?? '', ['all', 'include'], $defaults['display_mode']);

        foreach (['include_ids', 'include_terms', 'exclude_ids'] as $key) {
            $clean[$key] = self::ids($raw[$key] ?? []);
        }

        $clean['include_post_types'] = self::keys_in($raw['include_post_types'] ?? [], array_keys(self::targetable_post_types()));
        $clean['include_archives'] = self::keys_in($raw['include_archives'] ?? [], array_keys(self::archivable_post_types()));
        $clean['include_taxonomies'] = self::keys_in($raw['include_taxonomies'] ?? [], array_keys(self::targetable_taxonomies()));

        $clean['devices'] = self::keys_in($raw['devices'] ?? [], self::DEVICES);
        $clean['audience'] = self::one_of($raw['audience'] ?? '', self::AUDIENCES, $defaults['audience']);

        $clean['trigger'] = self::one_of($raw['trigger'] ?? '', self::TRIGGERS, $defaults['trigger']);
        $clean['delay'] = self::int_between($raw['delay'] ?? $defaults['delay'], 0, 3600);
        $clean['scroll'] = self::int_between($raw['scroll'] ?? $defaults['scroll'], 1, 100);
        $clean['exit_fallback'] = self::int_between($raw['exit_fallback'] ?? $defaults['exit_fallback'], 0, 3600);

        $clean['frequency'] = self::one_of($raw['frequency'] ?? '', self::FREQUENCIES, $defaults['frequency']);
        $clean['frequency_days'] = self::int_between($raw['frequency_days'] ?? $defaults['frequency_days'], 1, 365);

        $clean['start'] = self::local_date($raw['start'] ?? '');
        $clean['end'] = self::local_date($raw['end'] ?? '');

        $clean['cta_label'] = sanitize_text_field(wp_unslash($raw['cta_label'] ?? ''));
        $clean['cta_url'] = esc_url_raw(wp_unslash($raw['cta_url'] ?? ''));

        $clean['position'] = self::one_of($raw['position'] ?? '', self::POSITIONS, $defaults['position']);
        $clean['size'] = self::one_of($raw['size'] ?? '', self::SIZES, $defaults['size']);

        foreach (['bg_color', 'text_color', 'cta_bg_color', 'cta_text_color', 'cta_hover_bg_color', 'cta_hover_text_color', 'cta_border_color', 'cta_hover_border_color'] as $key) {
            $color = sanitize_hex_color(wp_unslash($raw[$key] ?? ''));
            $clean[$key] = $color ?: $defaults[$key];
        }

        $clean['overlay_opacity'] = self::int_between($raw['overlay_opacity'] ?? $defaults['overlay_opacity'], 0, 95);
        $clean['cta_border_width'] = self::int_between($raw['cta_border_width'] ?? $defaults['cta_border_width'], 0, 10);
        $clean['cta_radius'] = self::int_between($raw['cta_radius'] ?? $defaults['cta_radius'], 0, 999);

        return $clean;
    }

    /**
     * Le site a-t-il une page qui liste les articles (Réglages > Lecture) ?
     * Sinon is_home() n'est vrai sur aucune page réellement visitée.
     */
    public static function has_posts_page(): bool
    {
        return get_option('show_on_front') !== 'page' || (int) get_option('page_for_posts') > 0;
    }

    /**
     * La date du jour est-elle dans la période de diffusion (début et fin) ?
     */
    public static function in_period(array $settings, ?int $now = null): bool
    {
        $now = $now ?? time();
        $start = self::timestamp($settings['start']);
        $end = self::timestamp($settings['end']);

        return ($start === null || $start <= $now) && ($end === null || $end > $now);
    }

    /**
     * Convertit une date saisie à l'heure du site en timestamp Unix, ou null.
     */
    public static function timestamp(string $local): ?int
    {
        if ($local === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $local, wp_timezone());

        return $date ? $date->getTimestamp() : null;
    }

    private static function local_date($value): string
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            return '';
        }

        $date = DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $value, wp_timezone());

        return $date ? $date->format(self::DATE_FORMAT) : '';
    }

    private static function int_between($value, int $min, int $max): int
    {
        return max($min, min($max, (int) $value));
    }

    private static function one_of($value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private static function ids($values): array
    {
        $ids = array_map('absint', (array) $values);

        return array_values(array_unique(array_filter($ids)));
    }

    private static function keys_in($values, array $allowed): array
    {
        $keys = array_map('sanitize_key', (array) $values);

        return array_values(array_intersect($allowed, $keys));
    }
}
