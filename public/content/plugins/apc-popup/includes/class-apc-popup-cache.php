<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Purge des caches de page.
 *
 * Les popups sont rendues dans le HTML des pages : si une page est en cache,
 * elle garde l'ancienne version de la popup (et l'ancienne version des
 * compteurs) tant que le cache n'est pas vidé. On purge donc à chaque
 * modification d'une popup visible sur le site, à chaque remise à zéro de
 * ses compteurs, et à ses dates de début et de fin.
 *
 * La purge est regroupée en une seule fois, en fin de requête.
 */
final class APC_Popup_Cache
{
    /** Tâche cron : purge à la date de début ou de fin d'une popup. */
    const CRON_HOOK = 'apc_popup_date_purge';

    const EDGES = ['start', 'end'];

    private static $scheduled = false;

    /** @var array<int, bool> Popup visible sur le site avant l'enregistrement en cours. */
    private static $was_live = [];

    public static function hooks(): void
    {
        add_action(self::CRON_HOOK, [__CLASS__, 'date_purge']);

        add_action('pre_post_update', [__CLASS__, 'remember_state']);

        // Après l'enregistrement complet (réglages compris), y compris lors d'une
        // publication programmée, d'une mise à la corbeille ou d'une restauration.
        add_action('wp_after_insert_post', [__CLASS__, 'on_after_insert'], 10, 2);
        add_action('before_delete_post', [__CLASS__, 'on_delete']);
    }

    /**
     * La popup est-elle actuellement écrite dans les pages du site ?
     * (publiée, sans mot de passe, dans sa période de diffusion)
     */
    public static function is_live(int $post_id): bool
    {
        $post = get_post($post_id);

        return $post
            && $post->post_type === APC_Popup_Settings::POST_TYPE
            && $post->post_status === 'publish'
            && $post->post_password === ''
            && APC_Popup_Settings::in_period(APC_Popup_Settings::get($post_id));
    }

    public static function remember_state(int $post_id): void
    {
        if (get_post_type($post_id) === APC_Popup_Settings::POST_TYPE) {
            self::$was_live[$post_id] = self::is_live($post_id);
        }
    }

    /**
     * Le cache n'est vidé que si la popup était ou est maintenant visible :
     * un brouillon, une popup programmée ou terminée n'est dans aucune page.
     */
    public static function on_after_insert(int $post_id, WP_Post $post): void
    {
        if ($post->post_type !== APC_Popup_Settings::POST_TYPE) {
            return;
        }

        $was_live = self::$was_live[$post_id] ?? false;
        unset(self::$was_live[$post_id]);

        if ($was_live || self::is_live($post_id)) {
            self::schedule_purge();
        }

        self::schedule_dates($post_id);
    }

    public static function on_delete(int $post_id): void
    {
        if (get_post_type($post_id) !== APC_Popup_Settings::POST_TYPE) {
            return;
        }

        if (self::is_live($post_id)) {
            self::schedule_purge();
        }

        self::clear_dates($post_id);
    }

    /**
     * Les popups programmées ne sont pas écrites dans les pages avant leur
     * date de début (leur contenu resterait lisible dans le code source).
     * Une purge est donc programmée au début, et une autre à la fin pour
     * retirer la popup des pages en cache.
     */
    public static function schedule_dates(int $post_id): void
    {
        self::clear_dates($post_id);

        if (get_post_status($post_id) !== 'publish') {
            return;
        }

        $settings = APC_Popup_Settings::get($post_id);
        foreach (self::EDGES as $edge) {
            $timestamp = APC_Popup_Settings::timestamp($settings[$edge]);
            if ($timestamp !== null && $timestamp > time()) {
                wp_schedule_single_event($timestamp, self::CRON_HOOK, [$post_id, $edge]);
            }
        }
    }

    public static function clear_dates(int $post_id): void
    {
        foreach (self::EDGES as $edge) {
            wp_clear_scheduled_hook(self::CRON_HOOK, [$post_id, $edge]);
        }
    }

    public static function date_purge($post_id = 0, $edge = ''): void
    {
        if (get_post_type((int) $post_id) === APC_Popup_Settings::POST_TYPE && get_post_status((int) $post_id) === 'publish') {
            self::purge();
        }
    }

    /**
     * Activation (ou réactivation) : reprogramme les purges aux dates de
     * début et de fin, et vide les pages mises en cache sans les popups.
     */
    public static function activate(): void
    {
        $ids = get_posts([
            'post_type' => APC_Popup_Settings::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        foreach ($ids as $id) {
            self::schedule_dates((int) $id);
        }

        if ($ids) {
            self::purge();
        }
    }

    /**
     * Désactivation de l'extension : les pages en cache contiennent encore les
     * popups, on les vide et on supprime les tâches programmées.
     */
    public static function deactivate(): void
    {
        $ids = get_posts([
            'post_type' => APC_Popup_Settings::POST_TYPE,
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        foreach ($ids as $id) {
            self::clear_dates((int) $id);
        }

        self::purge();
    }

    public static function schedule_purge(): void
    {
        if (self::$scheduled) {
            return;
        }

        self::$scheduled = true;
        add_action('shutdown', [__CLASS__, 'purge']);
    }

    public static function purge(): void
    {
        // WP Rocket
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }

        // W3 Total Cache
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }

        // WP Super Cache
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }

        // SiteGround Optimizer
        if (function_exists('sg_cachepress_purge_cache')) {
            sg_cachepress_purge_cache();
        }

        // LiteSpeed Cache
        do_action('litespeed_purge_all');

        /**
         * Permet de brancher la purge d'un autre système de cache.
         */
        do_action('apc_popup_purge_cache');
    }
}
