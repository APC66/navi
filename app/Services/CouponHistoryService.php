<?php

namespace App\Services;

class CouponHistoryService
{
    /**
     * Meta où est stocké le journal des actions du coupon.
     */
    const LOG_META = '_navi_coupon_log';

    /**
     * État des coupons capturé avant sauvegarde, pour détecter les changements.
     */
    private array $snapshots = [];

    public function init(): void
    {
        add_action('add_meta_boxes', [$this, 'registerMetabox']);
        add_action('woocommerce_process_shop_coupon_meta', [$this, 'snapshotBeforeSave'], 1, 1);
        add_action('woocommerce_coupon_options_save', [$this, 'logManualSave'], 20, 2);
    }

    /**
     * Ajoute une entrée horodatée au journal d'un coupon.
     *
     * @param  bool  $auto  true si l'action est automatique (générée par le code)
     */
    public static function log(int $couponId, string $message, bool $auto = false): void
    {
        if (! $couponId) {
            return;
        }

        $log = get_post_meta($couponId, self::LOG_META, true);
        if (! is_array($log)) {
            $log = [];
        }

        if ($auto) {
            $actor = 'Automatique';
        } else {
            $user = wp_get_current_user();
            $actor = ($user && $user->ID) ? $user->display_name : 'Système';
        }

        $log[] = [
            'time' => current_time('mysql'),
            'actor' => $actor,
            'message' => $message,
        ];

        update_post_meta($couponId, self::LOG_META, $log);
    }

    /**
     * Capture l'état du coupon AVANT sauvegarde (priorité 1, avant l'écriture WC).
     */
    public function snapshotBeforeSave($postId): void
    {
        $this->snapshots[(int) $postId] = $this->captureState(new \WC_Coupon($postId));
    }

    /**
     * Journalise une sauvegarde manuelle depuis l'écran d'édition du coupon,
     * en détaillant précisément les champs modifiés.
     */
    public function logManualSave($postId, $coupon): void
    {
        $postId = (int) $postId;

        $existing = get_post_meta($postId, self::LOG_META, true);
        $hasHistory = is_array($existing) && ! empty($existing);

        // Premier enregistrement : création manuelle (pas de diff à faire).
        if (! $hasHistory) {
            self::log($postId, 'Coupon créé manuellement', false);

            return;
        }

        $before = $this->snapshots[$postId] ?? null;
        if ($before === null) {
            self::log($postId, 'Coupon enregistré manuellement', false);

            return;
        }

        $after = $this->captureState($coupon);

        $changes = [];
        foreach ($this->fieldLabels() as $key => $label) {
            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;
            if ($old != $new) {
                $changes[] = sprintf(
                    '%s : de « %s » à « %s »',
                    $label,
                    $this->fmt($old, $key),
                    $this->fmt($new, $key)
                );
            }
        }

        // Aucun champ significatif modifié : on ne pollue pas le journal.
        if (empty($changes)) {
            return;
        }

        foreach ($changes as $change) {
            self::log($postId, $change, false);
        }
    }

    /**
     * Capture les champs du coupon susceptibles d'être modifiés manuellement.
     */
    private function captureState(\WC_Coupon $coupon): array
    {
        $expires = $coupon->get_date_expires();

        return [
            'discount_type' => (string) $coupon->get_discount_type(),
            'amount' => (float) $coupon->get_amount(),
            'usage_limit' => (int) $coupon->get_usage_limit(),
            'usage_limit_per_user' => (int) $coupon->get_usage_limit_per_user(),
            'individual_use' => (bool) $coupon->get_individual_use(),
            'free_shipping' => (bool) $coupon->get_free_shipping(),
            'date_expires' => $expires ? $expires->date('Y-m-d') : '',
            'email_restrictions' => implode(', ', (array) $coupon->get_email_restrictions()),
            'description' => (string) $coupon->get_description(),
        ];
    }

    /**
     * Libellés lisibles des champs suivis.
     */
    private function fieldLabels(): array
    {
        return [
            'discount_type' => 'Type de remise',
            'amount' => 'Montant',
            'usage_limit' => "Limite d'utilisation",
            'usage_limit_per_user' => 'Limite par utilisateur',
            'individual_use' => 'Usage individuel uniquement',
            'free_shipping' => 'Livraison gratuite',
            'date_expires' => "Date d'expiration",
            'email_restrictions' => 'Emails autorisés',
            'description' => 'Description',
        ];
    }

    /**
     * Met en forme une valeur de champ pour l'affichage dans le journal.
     */
    private function fmt($value, string $key = ''): string
    {
        if (in_array($key, ['usage_limit', 'usage_limit_per_user'], true) && (int) $value === 0) {
            return 'illimité';
        }
        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }
        if ($value === '' || $value === null) {
            return 'vide';
        }
        if (is_string($value)) {
            $types = [
                'smart_coupon' => 'Store credit',
                'fixed_cart' => 'Montant fixe (panier)',
                'fixed_product' => 'Montant fixe (produit)',
                'percent' => 'Pourcentage',
            ];
            if (isset($types[$value])) {
                return $types[$value];
            }
        }

        return (string) $value;
    }

    public function registerMetabox(): void
    {
        add_meta_box(
            'navi_coupon_history',
            'Historique & suivi',
            [$this, 'renderMetabox'],
            'shop_coupon',
            'normal',
            'low'
        );
    }

    public function renderMetabox($post): void
    {
        $coupon = new \WC_Coupon($post->ID);

        $usages = $this->getCouponUsages($coupon->get_code());
        $totalUsed = array_sum(array_column($usages, 'amount'));
        $remaining = (float) $coupon->get_amount();
        $initial = $remaining + $totalUsed;

        $emails = $coupon->get_email_restrictions();
        $expires = $coupon->get_date_expires();
        $created = get_post_datetime($post) ?: null;

        echo '<div style="font-size:13px;line-height:1.6;">';

        // ============================= RÉSUMÉ =============================
        echo '<h4 style="margin:0 0 8px;">Résumé</h4>';
        echo '<table class="widefat striped" style="margin-bottom:18px;"><tbody>';
        $this->summaryRow('Origine', $this->detectOrigin($coupon));
        $this->summaryRow('Type de remise', $coupon->get_discount_type() === 'smart_coupon' ? 'Store credit (solde dégressif)' : $coupon->get_discount_type());
        $this->summaryRow('Créé le', $created ? wp_date('d/m/Y à H:i', $created->getTimestamp()) : 'Inconnu');
        $this->summaryRow('Email(s) lié(s)', ! empty($emails) ? esc_html(implode(', ', $emails)) : 'Aucun');
        $this->summaryRow('Montant initial', wc_price($initial));
        $this->summaryRow('Déjà utilisé', wc_price($totalUsed));
        $this->summaryRow('Solde restant', '<strong>'.wc_price($remaining).'</strong>');
        $this->summaryRow('Expiration', $expires ? wp_date('d/m/Y', $expires->getTimestamp()) : 'Aucune');
        echo '</tbody></table>';

        // ============================ UTILISATIONS ========================
        echo '<h4 style="margin:0 0 8px;">Utilisations</h4>';
        if (empty($usages)) {
            echo '<p style="margin:0 0 18px;color:#666;">Ce coupon n\'a encore été utilisé sur aucune commande.</p>';
        } else {
            echo '<table class="widefat striped" style="margin-bottom:18px;"><thead><tr>';
            echo '<th>Date</th><th>Commande</th><th>Client</th><th>Montant utilisé</th>';
            echo '</tr></thead><tbody>';
            foreach ($usages as $u) {
                echo '<tr>';
                echo '<td>'.esc_html($u['date']).'</td>';
                echo '<td><a href="'.esc_url($u['order_url']).'">#'.esc_html($u['order_id']).'</a> <span style="color:#888;">('.esc_html($u['status']).')</span></td>';
                echo '<td>'.esc_html($u['customer']).'</td>';
                echo '<td>'.wc_price($u['amount']).'</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        // ========================= JOURNAL DES ACTIONS ====================
        echo '<h4 style="margin:0 0 8px;">Journal des actions</h4>';
        $log = get_post_meta($post->ID, self::LOG_META, true);
        if (! is_array($log) || empty($log)) {
            echo '<p style="margin:0;color:#666;">Aucune action enregistrée (le journal démarre à partir de la mise en place de cette fonctionnalité).</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>Date</th><th>Par</th><th>Action</th>';
            echo '</tr></thead><tbody>';
            foreach (array_reverse($log) as $entry) {
                $time = ! empty($entry['time']) ? mysql2date('d/m/Y à H:i', $entry['time']) : '';
                echo '<tr>';
                echo '<td>'.esc_html($time).'</td>';
                echo '<td>'.esc_html($entry['actor'] ?? '').'</td>';
                echo '<td>'.esc_html($entry['message'] ?? '').'</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '</div>';
    }

    private function summaryRow(string $label, string $value): void
    {
        echo '<tr><td style="width:180px;font-weight:600;">'.esc_html($label).'</td><td>'.$value.'</td></tr>';
    }

    private function detectOrigin(\WC_Coupon $coupon): string
    {
        $desc = strtolower($coupon->get_description());
        if (str_contains($desc, 'carte cadeau')) {
            return 'Carte cadeau (automatique)';
        }
        if (str_contains($desc, 'avoir')) {
            return 'Avoir (automatique)';
        }

        return 'Manuel / autre';
    }

    /**
     * Reconstruit la liste des commandes ayant consommé ce coupon,
     * à partir des lignes de commande WooCommerce (rétroactif).
     *
     * @return array<int,array{order_id:int,order_url:string,date:string,customer:string,status:string,amount:float}>
     */
    private function getCouponUsages(string $code): array
    {
        global $wpdb;

        $code = strtolower($code);

        $rows = $wpdb->get_results($wpdb->prepare("
            SELECT oi.order_id, oim.meta_value AS discount
            FROM {$wpdb->prefix}woocommerce_order_items oi
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim
                ON oi.order_item_id = oim.order_item_id
                AND oim.meta_key = 'discount_amount'
            WHERE oi.order_item_type = 'coupon'
                AND LOWER(oi.order_item_name) = %s
            ORDER BY oi.order_id ASC
        ", $code));

        $usages = [];
        foreach ($rows as $row) {
            $order = wc_get_order($row->order_id);
            if (! $order) {
                continue;
            }

            $date = $order->get_date_created();

            $usages[] = [
                'order_id' => (int) $row->order_id,
                'order_url' => $order->get_edit_order_url(),
                'date' => $date ? wp_date('d/m/Y à H:i', $date->getTimestamp()) : '',
                'customer' => trim($order->get_formatted_billing_full_name()) ?: 'Client',
                'status' => wc_get_order_status_name($order->get_status()),
                'amount' => (float) $row->discount,
            ];
        }

        return $usages;
    }
}
