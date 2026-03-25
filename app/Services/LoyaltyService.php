<?php

namespace App\Services;

/**
 * Service de gestion du programme de fidélité.
 *
 * Règles métier :
 * - 1€ dépensé = 1 point de fidélité
 * - Points attribués uniquement sur commandes "Terminées" (wc-completed)
 * - Points attribués au client final (get_customer_id), jamais à l'agent
 * - Au palier défini (ex: 500 points), génération automatique d'un coupon WooCommerce
 * - Coupon à usage unique, lié à l'email du client
 * - Déduction des points après génération du coupon
 */
class LoyaltyService
{
    /**
     * Initialise les hooks WooCommerce pour le système de fidélité.
     */
    public function init(): void
    {
        // Attribution des points lors du passage au statut "Terminée"
        add_action('woocommerce_order_status_completed', [$this, 'awardPointsOnOrderComplete'], 10, 1);

        // Affichage du solde de points dans le compte client (optionnel)
        add_action('woocommerce_account_dashboard', [$this, 'displayPointsBalance']);
    }

    /**
     * Attribue les points de fidélité au client final lorsque la commande est terminée.
     */
    public function awardPointsOnOrderComplete(int $order_id): void
    {
        $order = wc_get_order($order_id);

        if (! $order) {
            return;
        }

        // Vérifier si les points ont déjà été attribués pour cette commande
        if ($order->get_meta('_loyalty_points_awarded')) {
            return;
        }

        // Récupérer le client final (jamais l'agent)
        $customer_id = $order->get_customer_id();

        if (! $customer_id || $customer_id <= 0) {
            return; // Commande invité, pas de points
        }

        // Calcul des points : 1€ = 1 point
        $order_total = (float) $order->get_total();
        $points_earned = (int) floor($order_total);

        if ($points_earned <= 0) {
            return;
        }

        // Ajouter les points au solde du client
        $this->addPoints($customer_id, $points_earned, $order_id);

        // Marquer la commande comme traitée pour les points
        $order->update_meta_data('_loyalty_points_awarded', '1');
        $order->update_meta_data('_loyalty_points_amount', $points_earned);
        $order->save();

        // Vérifier si le client atteint le palier pour générer un coupon
        $this->checkAndGenerateReward($customer_id);
    }

    /**
     * Récupère le solde de points d'un client.
     */
    protected function getCustomerPoints(int $customer_id): int
    {
        $points = get_user_meta($customer_id, '_loyalty_points', true);

        return $points ? (int) $points : 0;
    }

    /**
     * Ajoute des points au solde d'un client.
     */
    protected function addPoints(int $customer_id, int $points, int $order_id): void
    {
        $current_points = $this->getCustomerPoints($customer_id);
        $new_total = $current_points + $points;

        update_user_meta($customer_id, '_loyalty_points', $new_total);

        // Log de l'historique (optionnel, pour traçabilité)
        $this->logPointsHistory($customer_id, $points, 'earned', $order_id);
    }

    /**
     * Déduit des points du solde d'un client.
     */
    protected function deductPoints(int $customer_id, int $points): void
    {
        $current_points = $this->getCustomerPoints($customer_id);
        $new_total = max(0, $current_points - $points);

        update_user_meta($customer_id, '_loyalty_points', $new_total);

        // Log de l'historique
        $this->logPointsHistory($customer_id, -$points, 'redeemed', 0);
    }

    /**
     * Enregistre l'historique des points (optionnel).
     */
    protected function logPointsHistory(int $customer_id, int $points, string $type, int $order_id): void
    {
        $history = get_user_meta($customer_id, '_loyalty_points_history', true);

        if (! is_array($history)) {
            $history = [];
        }

        $history[] = [
            'date' => current_time('mysql'),
            'points' => $points,
            'type' => $type, // 'earned' ou 'redeemed'
            'order_id' => $order_id,
        ];

        update_user_meta($customer_id, '_loyalty_points_history', $history);
    }

    /**
     * Vérifie si le client atteint le palier et génère un coupon si nécessaire.
     */
    protected function checkAndGenerateReward(int $customer_id): void
    {
        $current_points = $this->getCustomerPoints($customer_id);

        // Récupérer les réglages du programme de fidélité
        $threshold = (int) get_option('loyalty_points_threshold', 500);
        $reward_type = get_option('loyalty_reward_type', 'fixed_cart');
        $reward_value = (float) get_option('loyalty_reward_value', 10);

        // Vérifier si le palier est atteint
        if ($current_points < $threshold) {
            return;
        }

        // Récupérer l'email du client
        $user = get_user_by('id', $customer_id);

        if (! $user || ! is_email($user->user_email)) {
            return;
        }

        // Générer le coupon
        $coupon_code = $this->generateCoupon($customer_id, $user->user_email, $reward_type, $reward_value);

        if (! $coupon_code) {
            return;
        }

        // Déduire les points utilisés
        $this->deductPoints($customer_id, $threshold);

        // Envoyer l'email de notification
        $this->sendRewardEmail($user, $coupon_code, $reward_type, $reward_value);
    }

    /**
     * Génère un coupon WooCommerce pour le client.
     */
    protected function generateCoupon(int $customer_id, string $email, string $reward_type, float $reward_value): ?string
    {
        // Générer un code unique
        $coupon_code = 'FIDELITE_'.strtoupper(wp_generate_password(8, false, false));

        // Créer le coupon
        $coupon = new \WC_Coupon;
        $coupon->set_code($coupon_code);
        $coupon->set_discount_type($reward_type); // 'fixed_cart' ou 'percent'
        $coupon->set_amount($reward_value);
        $coupon->set_usage_limit(1);
        $coupon->set_usage_limit_per_user(1);
        $coupon->set_email_restrictions([$email]);
        $coupon->set_description('Coupon de fidélité généré automatiquement pour le client #'.$customer_id);

        // Enregistrer le coupon
        $coupon_id = $coupon->save();

        if (! $coupon_id) {
            return null;
        }

        // Lier le coupon au client dans les meta
        update_post_meta($coupon_id, '_loyalty_customer_id', $customer_id);
        update_post_meta($coupon_id, '_loyalty_generated_date', current_time('mysql'));

        return $coupon_code;
    }

    /**
     * Envoie l'email de notification au client avec son code promo.
     */
    protected function sendRewardEmail(\WP_User $user, string $coupon_code, string $reward_type, float $reward_value): void
    {
        $to = $user->user_email;
        $subject = sprintf('[%s] Félicitations ! Votre récompense fidélité est prête', get_bloginfo('name'));

        // Formater la valeur de la réduction
        if ($reward_type === 'percent') {
            $reward_display = $reward_value.'%';
        } else {
            $reward_display = wc_price($reward_value);
        }

        // Corps de l'email
        $message = sprintf(
            "Bonjour %s,\n\n".
            "Félicitations ! Vous avez atteint un palier de fidélité.\n\n".
            "Votre code promo : %s\n".
            "Réduction : %s\n\n".
            "Ce code est à usage unique et réservé à votre compte.\n".
            "Utilisez-le lors de votre prochaine commande !\n\n".
            "Merci de votre fidélité,\n".
            "L'équipe %s\n\n".
            '%s',
            $user->display_name ?: $user->user_login,
            $coupon_code,
            $reward_display,
            get_bloginfo('name'),
            home_url()
        );

        // Headers
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        // Envoi de l'email
        wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Affiche le solde de points dans le tableau de bord du compte client.
     */
    public function displayPointsBalance(): void
    {
        $customer_id = get_current_user_id();

        if (! $customer_id) {
            return;
        }

        $points = $this->getCustomerPoints($customer_id);
        $threshold = (int) get_option('loyalty_points_threshold', 500);

        echo '<div class="loyalty-points-balance bg-primary-50 border border-primary-100 rounded-lg p-6 mb-8">';
        echo '<h3 style="margin-top: 0;">Votre Programme de Fidélité</h3>';
        echo '<p style="font-size: 18px; margin: 10px 0;"><strong>Solde actuel :</strong> '.$points.' points</p>';

        if ($points >= $threshold) {
            echo '<p style="color: #2e7d32; font-weight: bold;">Vous avez atteint le palier ! Un coupon vous sera envoyé prochainement.</p>';
        } else {
            $remaining = $threshold - $points;
            echo '<p style="color: #666;">Plus que '.$remaining.' points pour obtenir votre prochaine récompense !</p>';
        }

        echo '</div>';
    }
}
