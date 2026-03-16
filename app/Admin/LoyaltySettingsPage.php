<?php

namespace App\Admin;

/**
 * Page de réglages du programme de fidélité dans l'admin WooCommerce.
 *
 * Permet de configurer :
 * - Le nombre de points nécessaires pour déclencher la récompense
 * - Le type de réduction (Montant fixe ou Pourcentage)
 * - La valeur de la réduction
 */
class LoyaltySettingsPage
{
    /**
     * Initialise les hooks pour la page de réglages.
     */
    public function init(): void
    {
        // Ajouter un onglet dans les réglages WooCommerce
        add_filter('woocommerce_settings_tabs_array', [$this, 'addSettingsTab'], 50);

        // Afficher les champs de réglages
        add_action('woocommerce_settings_tabs_loyalty', [$this, 'renderSettings']);

        // Enregistrer les réglages
        add_action('woocommerce_update_options_loyalty', [$this, 'saveSettings']);

        // Ajouter une colonne "Points de fidélité" dans la liste des utilisateurs
        add_filter('manage_users_columns', [$this, 'addLoyaltyPointsColumn']);
        add_filter('manage_users_custom_column', [$this, 'displayLoyaltyPointsColumn'], 10, 3);
    }

    /**
     * Ajoute un onglet "Fidélité" dans les réglages WooCommerce.
     */
    public function addSettingsTab(array $settings_tabs): array
    {
        $settings_tabs['loyalty'] = 'Programme de Fidélité';

        return $settings_tabs;
    }

    /**
     * Affiche les champs de réglages du programme de fidélité.
     */
    public function renderSettings(): void
    {
        woocommerce_admin_fields($this->getSettings());
    }

    /**
     * Enregistre les réglages du programme de fidélité.
     */
    public function saveSettings(): void
    {
        woocommerce_update_options($this->getSettings());
    }

    /**
     * Définit les champs de réglages du programme de fidélité.
     */
    protected function getSettings(): array
    {
        return [
            [
                'title' => 'Réglages du Programme de Fidélité',
                'type' => 'title',
                'desc' => 'Configurez les paramètres du système de fidélité. Les clients gagnent 1 point par euro dépensé sur les commandes terminées.',
                'id' => 'loyalty_settings',
            ],
            [
                'title' => 'Activer le programme de fidélité',
                'desc' => 'Cochez pour activer le système de fidélité',
                'id' => 'loyalty_enabled',
                'default' => 'yes',
                'type' => 'checkbox',
            ],
            [
                'title' => 'Palier de points',
                'desc' => 'Nombre de points nécessaires pour générer un coupon de récompense',
                'id' => 'loyalty_points_threshold',
                'type' => 'number',
                'default' => '500',
                'custom_attributes' => [
                    'min' => '1',
                    'step' => '1',
                ],
                'desc_tip' => true,
            ],
            [
                'title' => 'Type de réduction',
                'desc' => 'Choisissez le type de réduction pour le coupon généré',
                'id' => 'loyalty_reward_type',
                'type' => 'select',
                'default' => 'fixed_cart',
                'options' => [
                    'fixed_cart' => 'Montant fixe (€)',
                    'percent' => 'Pourcentage (%)',
                ],
                'desc_tip' => true,
            ],
            [
                'title' => 'Valeur de la réduction',
                'desc' => 'Montant en euros ou pourcentage de réduction',
                'id' => 'loyalty_reward_value',
                'type' => 'number',
                'default' => '10',
                'custom_attributes' => [
                    'min' => '0',
                    'step' => '0.01',
                ],
                'desc_tip' => true,
            ],
            [
                'title' => 'Afficher le solde dans le compte client',
                'desc' => 'Afficher le solde de points sur le tableau de bord du compte client',
                'id' => 'loyalty_display_balance',
                'default' => 'yes',
                'type' => 'checkbox',
            ],
            [
                'type' => 'sectionend',
                'id' => 'loyalty_settings',
            ],
            [
                'title' => 'Statistiques du Programme',
                'type' => 'title',
                'desc' => $this->getLoyaltyStats(),
                'id' => 'loyalty_stats',
            ],
            [
                'type' => 'sectionend',
                'id' => 'loyalty_stats',
            ],
        ];
    }

    /**
     * Génère les statistiques du programme de fidélité.
     */
    protected function getLoyaltyStats(): string
    {
        global $wpdb;

        // Nombre total de clients avec des points
        $customers_with_points = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id)
            FROM {$wpdb->usermeta}
            WHERE meta_key = '_loyalty_points'
            AND meta_value > 0"
        );

        // Total de points en circulation
        $total_points = $wpdb->get_var(
            "SELECT SUM(CAST(meta_value AS UNSIGNED))
            FROM {$wpdb->usermeta}
            WHERE meta_key = '_loyalty_points'"
        );

        // Nombre de coupons générés
        $coupons_generated = $wpdb->get_var(
            "SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_loyalty_customer_id'"
        );

        $stats = '<div style="background: #f0f0f1; padding: 15px; border-radius: 5px; margin-top: 10px;">';
        $stats .= '<p><strong>Clients avec des points :</strong> '.($customers_with_points ?: 0).'</p>';
        $stats .= '<p><strong>Total de points en circulation :</strong> '.number_format($total_points ?: 0, 0, ',', ' ').' points</p>';
        $stats .= '<p><strong>Coupons générés :</strong> '.($coupons_generated ?: 0).'</p>';
        $stats .= '</div>';

        return $stats;
    }

    /**
     * Ajoute une colonne "Points de fidélité" dans la liste des utilisateurs.
     */
    public function addLoyaltyPointsColumn(array $columns): array
    {
        $columns['loyalty_points'] = 'Points de Fidélité';

        return $columns;
    }

    /**
     * Affiche le solde de points dans la colonne personnalisée.
     */
    public function displayLoyaltyPointsColumn(string $value, string $column_name, int $user_id): string
    {
        if ($column_name !== 'loyalty_points') {
            return $value;
        }

        $points = get_user_meta($user_id, '_loyalty_points', true);
        $points = $points ? (int) $points : 0;

        if ($points > 0) {
            return '<strong>'.$points.' pts</strong>';
        }

        return '<span style="color: #999;">0 pts</span>';
    }
}
