<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Nettoyage des comptes avant l'activation des champs pro du plugin apc-billing
 * (onglet « Champs pro »), décidé avec le client le 23/10/2026.
 *
 * 1. Comptes des agents (capacité place_agency_orders, ou auteurs de commandes d'agence qui
 *    l'ont perdue depuis, par exemple des saisonniers repassés en client) :
 *    - suppression du SIRET du thème (billing_siret) et des données pro du plugin
 *      (apc_customer_type, apc_siret, apc_vat, apc_no_siret) : un agent n'a pas de statut
 *      propre, c'est le client final qui en a un. Pour un ancien agent, seulement quand son
 *      compte porte les coordonnées d'un client, et jamais ses données apc_* ;
 *    - suppression des coordonnées de facturation (billing_*) quand ce sont celles d'un
 *      client : jusqu'ici, WooCommerce recopiait sur le compte de l'agent les coordonnées
 *      du client de sa dernière commande d'agence. Détection : e-mail ou nom de
 *      facturation identique à celui d'une commande passée par cet agent pour un client.
 *      La méta last_update est alors renouvelée, pour que la session WooCommerce de l'agent
 *      ne remette pas ces coordonnées au paiement.
 * 2. Comptes clients listés dans --clients (validés par le client), dont le SIRET saisi dans
 *    le champ du thème (billing_siret) est un SIRET valide pour le plugin : copie vers le
 *    champ du plugin (apc_siret) et type « pro ». Un compte qui a déjà un type, ou un autre
 *    SIRET dans apc_siret, n'est pas modifié.
 *
 * Sans --appliquer, rien n'est écrit : la commande affiche ce qu'elle ferait. Avec --appliquer,
 * les anciennes valeurs sont d'abord enregistrées dans un NOUVEAU fichier JSON (dossier privé
 * des archives du plugin, ou --sauvegarde=dossier ou fichier), puis les métas sont modifiées
 * et relues. Les valeurs personnelles ne sont jamais affichées.
 *
 * Utilisation : wp acorn navi:champs-pro-nettoyage [--clients=1,2,3] [--appliquer] [--sauvegarde=chemin]
 */
class ProFieldsCleanupCommand extends Command
{
    protected $signature = 'navi:champs-pro-nettoyage
        {--clients= : Comptes clients validés pour la reprise du SIRET, séparés par des virgules}
        {--appliquer : Enregistre les anciennes valeurs dans un fichier JSON puis modifie les comptes}
        {--sauvegarde= : Dossier ou nouveau fichier JSON des anciennes valeurs (par défaut : dossier privé des archives du plugin)}';

    protected $description = 'Nettoie les comptes des agents et reprend les SIRET des clients avant l\'activation des champs pro';

    /** Coordonnées de facturation recopiées par WooCommerce sur le compte connecté. */
    protected const BILLING_KEYS = [
        'billing_first_name', 'billing_last_name', 'billing_company', 'billing_address_1',
        'billing_address_2', 'billing_city', 'billing_postcode', 'billing_country',
        'billing_state', 'billing_phone', 'billing_email',
    ];

    /** Données pro qu'un compte d'agent ne doit pas porter (Customer_Type::ACCOUNT_FIELDS compris). */
    protected const AGENT_PRO_KEYS = ['billing_siret', 'apc_customer_type', 'apc_siret', 'apc_vat', 'apc_no_siret'];

    public function handle(): int
    {
        if (! function_exists('wc_get_orders')) {
            $this->error('WooCommerce n\'est pas chargé.');

            return self::FAILURE;
        }

        $agents = $this->agentIds();
        $orders = $this->agencyOrdersByCreator();
        $plan = [];
        $former = 0;

        foreach ($agents as $agentId) {
            $isFormer = ! user_can($agentId, 'place_agency_orders');
            $former += $isFormer ? 1 : 0;
            $reason = $this->clientDataReason($agentId, $orders[$agentId] ?? []);
            $deletes = [];
            if (! $isFormer || '' !== $reason) {
                foreach ($isFormer ? ['billing_siret'] : self::AGENT_PRO_KEYS as $key) {
                    if ('' !== $this->meta($agentId, $key)) {
                        $deletes[] = $key;
                    }
                }
            }
            $set = [];
            if ('' !== $reason) {
                foreach (self::BILLING_KEYS as $key) {
                    if ('' !== $this->meta($agentId, $key)) {
                        $deletes[] = $key;
                    }
                }
                if (array_intersect($deletes, self::BILLING_KEYS)) {
                    // Invalide la copie des coordonnées gardée dans la session WooCommerce de
                    // l'agent : delete_user_meta() ne met pas last_update à jour.
                    $set['last_update'] = gmdate('U');
                }
            }
            if ($deletes) {
                $plan[] = [
                    'user' => $agentId,
                    'kind' => $isFormer ? 'ancien agent' : 'agent',
                    'delete' => array_values(array_unique($deletes)),
                    'set' => $set,
                    'note' => '' !== $reason ? $reason : 'données pro seulement',
                ];
            }
        }

        $allowed = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $this->option('clients'))))));
        $skipped = [];
        $seen = [];
        foreach ($this->clientSirets($agents) as $userId => $raw) {
            $siret = (string) preg_replace('/[\s.\-]+/u', '', $raw);
            if (! $this->isValidSiret($siret)) {
                $skipped[] = [$userId, preg_match('/^(\d{9}|\d{14})$/', $siret) ? 'clé invalide ou SIREN seul' : 'pas un SIRET (texte libre)'];

                continue;
            }
            $seen[] = $userId;
            if (! in_array($userId, $allowed, true)) {
                $skipped[] = [$userId, 'SIRET valide, compte absent de --clients'];

                continue;
            }
            if ('' !== $this->meta($userId, 'apc_customer_type')) {
                $skipped[] = [$userId, 'type déjà choisi sur le compte'];

                continue;
            }
            $current = (string) preg_replace('/[\s.\-]+/u', '', $this->meta($userId, 'apc_siret'));
            if ('' !== $current && $current !== $siret) {
                $skipped[] = [$userId, 'autre SIRET déjà enregistré dans apc_siret'];

                continue;
            }
            $set = ['apc_customer_type' => 'pro'];
            if ('' === $current) {
                $set['apc_siret'] = $siret;
            }
            $plan[] = [
                'user' => $userId,
                'kind' => 'client',
                'delete' => [],
                'set' => $set,
                'note' => 'SIRET du thème repris',
            ];
        }
        foreach (array_diff($allowed, $seen) as $userId) {
            $skipped[] = [$userId, 'listé dans --clients sans SIRET valide du thème'];
        }

        $this->line(sprintf(
            'Agents : %d, dont %d ancien(s) agent(s) sans la capacité place_agency_orders ; commandes d\'agence lues : %d.',
            count($agents),
            $former,
            array_sum(array_map('count', $orders))
        ));
        $this->table(
            ['Compte', 'Type', 'Métas supprimées', 'Métas écrites', 'Motif'],
            array_map(fn ($p) => [
                $p['user'],
                $p['kind'],
                implode(', ', $p['delete']) ?: '-',
                implode(', ', array_map(fn ($k, $v) => 'apc_siret' === $k ? $k : $k.'='.$v, array_keys($p['set']), $p['set'])) ?: '-',
                $p['note'],
            ], $plan)
        );
        if ($skipped) {
            $this->line('SIRET du thème non repris (laissés tels quels) :');
            $this->table(['Compte', 'Raison'], $skipped);
        }

        if (! $this->option('appliquer')) {
            $this->info(sprintf('%d compte(s) à modifier. Rien n\'a été écrit (relancer avec --appliquer).', count($plan)));

            return self::SUCCESS;
        }
        if (! $plan) {
            $this->info('Rien à modifier.');

            return self::SUCCESS;
        }
        if (! has_filter('woocommerce_checkout_update_customer_data')) {
            $this->error('Thème non déployé (filtre woocommerce_checkout_update_customer_data absent) : la prochaine commande d\'agence recopierait les coordonnées du client sur le compte de l\'agent. Rien n\'a été modifié.');

            return self::FAILURE;
        }

        $file = $this->backupPath();
        if ('' === $file) {
            $this->error('Pas de fichier de sauvegarde utilisable (dossier non inscriptible, fichier déjà présent ou lancement en root) : préciser --sauvegarde=dossier ou nouveau fichier.');

            return self::FAILURE;
        }
        $backup = [];
        foreach ($plan as $p) {
            foreach (array_merge($p['delete'], array_keys($p['set'])) as $key) {
                $backup[$p['user']][$key] = get_user_meta($p['user'], $key, true);
            }
        }
        $json = wp_json_encode(['date' => gmdate('c'), 'site' => home_url(), 'old_values' => $backup], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (false === $json || ! $this->writeBackup($file, $json)) {
            $this->error('Sauvegarde impossible : '.$file.' (rien n\'a été modifié).');

            return self::FAILURE;
        }
        $this->line('Anciennes valeurs enregistrées : '.$file);

        $failed = [];
        foreach ($plan as $p) {
            foreach ($p['delete'] as $key) {
                delete_user_meta($p['user'], $key);
            }
            foreach ($p['set'] as $key => $value) {
                update_user_meta($p['user'], $key, $value);
            }
            clean_user_cache($p['user']);
            foreach ($p['delete'] as $key) {
                if ('' !== $this->meta($p['user'], $key)) {
                    $failed[] = [$p['user'], $key];
                }
            }
            foreach ($p['set'] as $key => $value) {
                if ((string) $value !== $this->meta($p['user'], $key)) {
                    $failed[] = [$p['user'], $key];
                }
            }
        }
        if ($failed) {
            $this->error(sprintf('%d méta(s) non modifiée(s) après écriture. Anciennes valeurs : %s', count($failed), $file));
            $this->table(['Compte', 'Méta'], $failed);

            return self::FAILURE;
        }
        $this->info(sprintf('%d compte(s) modifié(s) et relu(s).', count($plan)));

        return self::SUCCESS;
    }

    /**
     * Comptes d'agents : membres des rôles qui ont la capacité (contrôle user_can()), puis
     * auteurs de commandes d'agence, même s'ils l'ont perdue depuis : leur compte porte les
     * coordonnées de leur dernier client.
     *
     * @return int[]
     */
    protected function agentIds(): array
    {
        global $wpdb;

        $roles = [];
        foreach (wp_roles()->roles as $slug => $role) {
            if (! empty($role['capabilities']['place_agency_orders'])) {
                $roles[] = $slug;
            }
        }
        $ids = $roles ? array_map('intval', get_users(['role__in' => $roles, 'fields' => 'ID'])) : [];
        $ids = array_filter($ids, fn ($id) => $id > 0 && user_can($id, 'place_agency_orders'));
        $creators = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT meta_value FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key = %s",
            '_agency_creator_id'
        ));
        foreach (array_map('intval', (array) $creators) as $id) {
            if ($id > 0 && false !== get_userdata($id)) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Coordonnées de facturation des commandes d'agence, par agent créateur.
     *
     * @return array<int, array<int, array{email: string, name: string}>>
     */
    protected function agencyOrdersByCreator(): array
    {
        $ids = wc_get_orders([
            'limit' => -1,
            'return' => 'ids',
            'type' => 'shop_order',
            'meta_query' => [['key' => '_agency_creator_id', 'compare' => 'EXISTS']],
        ]);
        $out = [];
        foreach ($ids as $id) {
            $order = wc_get_order($id);
            if (! $order instanceof \WC_Order) {
                continue;
            }
            $creator = (int) $order->get_meta('_agency_creator_id');
            if ($creator <= 0) {
                continue;
            }
            $out[$creator][] = [
                'email' => strtolower(trim((string) $order->get_billing_email())),
                'name' => $this->normName($order->get_billing_first_name(), $order->get_billing_last_name()),
            ];
        }

        return $out;
    }

    /**
     * Les coordonnées de facturation du compte de l'agent sont-elles celles d'un client ?
     * Renvoie le motif, ou '' si rien ne l'indique.
     *
     * @param array<int, array{email: string, name: string}> $orders Commandes passées par l'agent.
     */
    protected function clientDataReason(int $agentId, array $orders): string
    {
        if (! $orders) {
            return '';
        }
        $user = get_userdata($agentId);
        $ownEmail = $user ? strtolower(trim((string) $user->user_email)) : '';
        $ownName = $this->normName((string) get_user_meta($agentId, 'first_name', true), (string) get_user_meta($agentId, 'last_name', true));
        $email = strtolower(trim($this->meta($agentId, 'billing_email')));
        $name = $this->normName($this->meta($agentId, 'billing_first_name'), $this->meta($agentId, 'billing_last_name'));

        foreach ($orders as $o) {
            if ('' !== $email && $email !== $ownEmail && $email === $o['email']) {
                return 'e-mail de facturation d\'un client';
            }
            if ('' !== $name && $name !== $ownName && $name === $o['name']) {
                return 'nom de facturation d\'un client';
            }
        }

        return '';
    }

    /**
     * SIRET saisis dans le champ du thème sur les comptes qui ne sont pas des agents.
     *
     * @param int[] $agents Comptes d'agents (exclus).
     * @return array<int, string>
     */
    protected function clientSirets(array $agents): array
    {
        global $wpdb;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value <> ''",
            'billing_siret'
        ));
        $out = [];
        foreach ((array) $rows as $row) {
            $id = (int) $row->user_id;
            if ($id > 0 && ! in_array($id, $agents, true) && is_scalar($row->meta_value)) {
                $out[$id] = (string) $row->meta_value;
            }
        }

        return $out;
    }

    /**
     * SIRET valide selon la règle du plugin, qui contrôle apc_siret au paiement et dans Mon
     * compte (Customer_Type::is_valid_siret()) : 14 chiffres et clé de Luhn, sans l'exception
     * de La Poste, que le plugin refuse.
     */
    protected function isValidSiret(string $siret): bool
    {
        if (class_exists(\APC\Billing\Customer_Type::class) && method_exists(\APC\Billing\Customer_Type::class, 'is_valid_siret')) {
            return \APC\Billing\Customer_Type::is_valid_siret($siret);
        }
        if (! preg_match('/^\d{14}$/', $siret)) {
            return false;
        }
        $sum = 0;
        foreach (array_reverse(str_split($siret)) as $i => $digit) {
            $n = (int) $digit * (1 === $i % 2 ? 2 : 1);
            $sum += $n > 9 ? $n - 9 : $n;
        }

        return 0 === $sum % 10;
    }

    protected function meta(int $userId, string $key): string
    {
        $value = get_user_meta($userId, $key, true);

        return is_scalar($value) ? trim((string) $value) : '';
    }

    protected function normName(string $first, string $last): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $first.' '.$last) ?? '');

        return '' !== $name ? mb_strtolower($name) : '';
    }

    /**
     * Fichier de sauvegarde, toujours nouveau : --sauvegarde (dossier ou fichier absent), sinon
     * dossier privé des archives du plugin. Refusé en root sans --sauvegarde : le dossier du
     * plugin serait créé au nom de root et PHP-FPM ne pourrait plus y écrire.
     */
    protected function backupPath(): string
    {
        $name = 'nettoyage-champs-pro-'.gmdate('Ymd-His').'.json';
        $file = (string) $this->option('sauvegarde');
        if ('' !== $file) {
            if (is_dir($file)) {
                $file = untrailingslashit($file).'/'.$name;
            }

            return ! file_exists($file) && is_dir(dirname($file)) && is_writable(dirname($file)) ? $file : '';
        }
        if (function_exists('posix_geteuid') && 0 === posix_geteuid()) {
            return '';
        }
        if (! class_exists(\APC\Billing\Settings::class) || ! method_exists(\APC\Billing\Settings::class, 'private_dir')) {
            return '';
        }
        $dir = \APC\Billing\Settings::private_dir('archives');

        return '' !== $dir ? $dir.'/'.$name : '';
    }

    /**
     * Écrit la sauvegarde dans un NOUVEAU fichier (jamais un fichier existant), lisible par son
     * seul propriétaire dès sa création, puis la relit.
     */
    protected function writeBackup(string $file, string $json): bool
    {
        $mask = umask(0077);
        $handle = @fopen($file, 'x');
        umask($mask);
        if (false === $handle) {
            return false;
        }
        $ok = strlen($json) === fwrite($handle, $json) && fflush($handle);
        $ok = fclose($handle) && $ok;
        clearstatcache(true, $file);

        return $ok && 0 === (fileperms($file) & 0077) && file_get_contents($file) === $json;
    }
}
