# Programme de Fidélité

## Vue d'ensemble

Le système de fidélité permet de récompenser les clients pour leurs achats en leur attribuant des points qui peuvent être convertis en coupons de réduction.

## Règles métier

### Attribution des points
- **1€ dépensé = 1 point de fidélité**
- Les points sont calculés sur le total de la commande (`$order->get_total()`)
- Les points sont attribués **uniquement** lorsque la commande passe au statut **"Terminée"** (`wc-completed`)
- Les points sont **toujours attribués au client final** (`$order->get_customer_id()`), jamais à l'agent qui a passé la commande

### Compatibilité avec le système Agence
Le système de fidélité est **100% compatible** avec le système de commandes pour compte tiers :
- Les points sont attribués au `customer_id` de la commande (client final)
- L'agent créateur (stocké dans `_agency_creator_id`) ne reçoit jamais de points
- Cela garantit que les points bénéficient toujours au client final

### Génération des récompenses
- Au palier défini (par défaut 500 points), un coupon WooCommerce est généré automatiquement
- Le coupon est :
  - **À usage unique** (`usage_limit = 1`)
  - **Lié exclusivement à l'email du client** (restriction email)
  - **Généré avec un code unique** (format : `FIDELITE_XXXXXXXX`)
- Une fois le coupon généré, les points correspondants sont **déduits** du solde du client
- Un **email de notification** est envoyé automatiquement au client avec son code promo

## Architecture technique

### Fichiers créés

```
app/Services/LoyaltyService.php
app/Admin/LoyaltySettingsPage.php
```

### Intégration

Le système est initialisé dans `ThemeServiceProvider.php` :

```php
(new LoyaltyService)->init();
(new LoyaltySettingsPage)->init();
```

## Configuration

### Accès aux réglages

**WooCommerce → Réglages → Programme de Fidélité**

### Options disponibles

| Option | Description | Valeur par défaut |
|--------|-------------|-------------------|
| **Activer le programme** | Active/désactive le système de fidélité | Oui |
| **Palier de points** | Nombre de points nécessaires pour générer un coupon | 500 |
| **Type de réduction** | Montant fixe (€) ou Pourcentage (%) | Montant fixe |
| **Valeur de la réduction** | Montant en euros ou pourcentage | 10 |
| **Afficher le solde** | Afficher le solde de points dans le compte client | Oui |

### Statistiques

La page de réglages affiche également :
- Nombre de clients avec des points
- Total de points en circulation
- Nombre de coupons générés

## Fonctionnalités

### 1. Attribution automatique des points

Lorsqu'une commande passe au statut "Terminée" :

```php
// Hook déclenché
add_action('woocommerce_order_status_completed', [$this, 'awardPointsOnOrderComplete']);

// Calcul
$order_total = $order->get_total(); // Ex: 150.00€
$points_earned = floor($order_total); // 150 points
```

### 2. Stockage des points

Les points sont stockés dans les **user meta** :

```php
// Solde actuel
get_user_meta($customer_id, '_loyalty_points', true);

// Historique (optionnel)
get_user_meta($customer_id, '_loyalty_points_history', true);
```

### 3. Génération automatique du coupon

Lorsque le palier est atteint :

```php
$coupon = new WC_Coupon();
$coupon->set_code('FIDELITE_XY8K3M2P');
$coupon->set_discount_type('fixed_cart'); // ou 'percent'
$coupon->set_amount(10);
$coupon->set_usage_limit(1);
$coupon->set_email_restrictions([$customer_email]);
$coupon->save();
```

### 4. Email de notification

Le client reçoit automatiquement un email contenant :
- Son code promo
- Le type et la valeur de la réduction
- Les instructions d'utilisation

### 5. Affichage dans le compte client

Si activé, un widget s'affiche sur le tableau de bord du compte client :

```
┌─────────────────────────────────────┐
│ Votre Programme de Fidélité         │
│                                     │
│ Solde actuel : 520 points          │
│                                     │
│ Plus que 0 points pour obtenir     │
│ votre prochaine récompense !       │
└─────────────────────────────────────┘
```

### 6. Colonne admin

Une colonne "Points de Fidélité" est ajoutée dans la liste des utilisateurs WordPress pour visualiser rapidement les soldes.

## Flux complet

### Exemple de scénario

1. **Client passe une commande de 150€**
   - Commande #1234 créée

2. **Commande passe au statut "Terminée"**
   - Hook `woocommerce_order_status_completed` déclenché
   - 150 points attribués au client
   - Solde actuel : 150 points

3. **Client passe une 2ème commande de 200€**
   - 200 points attribués
   - Solde actuel : 350 points

4. **Client passe une 3ème commande de 180€**
   - 180 points attribués
   - Solde actuel : 530 points
   - **Palier atteint !** (530 ≥ 500)

5. **Génération automatique**
   - Coupon `FIDELITE_AB7CD9EF` créé (10€ de réduction)
   - 500 points déduits
   - Solde restant : 30 points
   - Email envoyé au client

6. **Client utilise son coupon**
   - Réduction de 10€ appliquée
   - Coupon marqué comme utilisé (usage unique)

## Cas particuliers

### Commandes pour compte tiers (Agence)

```php
// Commande passée par un agent pour un client
$order->get_meta('_agency_creator_id'); // ID de l'agent
$order->get_customer_id(); // ID du client final

// ✅ Les points vont au client final
// ❌ L'agent ne reçoit JAMAIS de points
```

### Commandes invités

Les commandes passées par des invités (sans compte) ne génèrent **pas de points** :

```php
if (!$customer_id || $customer_id <= 0) {
    return; // Pas de points pour les invités
}
```

### Protection contre les doublons

Chaque commande est marquée après attribution des points :

```php
$order->update_meta_data('_loyalty_points_awarded', '1');
$order->update_meta_data('_loyalty_points_amount', $points_earned);
```

## Personnalisation

### Modifier le calcul des points

Éditez `LoyaltyService.php`, méthode `awardPointsOnOrderComplete()` :

```php
// Exemple : 2 points par euro
$points_earned = (int) floor($order_total * 2);

// Exemple : Arrondir au multiple de 10 supérieur
$points_earned = (int) ceil($order_total / 10) * 10;
```

### Modifier le template de l'email

Éditez `LoyaltyService.php`, méthode `sendRewardEmail()` :

```php
$message = sprintf(
    "Votre message personnalisé...\n\n".
    "Code : %s\n",
    $coupon_code
);
```

### Ajouter des paliers multiples

Actuellement, le système gère un seul palier. Pour ajouter des paliers multiples, vous pouvez :

1. Créer plusieurs options de réglages (`loyalty_threshold_1`, `loyalty_threshold_2`, etc.)
2. Modifier la méthode `checkAndGenerateReward()` pour vérifier plusieurs paliers
3. Générer des coupons différents selon le palier atteint

## Maintenance

### Consulter l'historique d'un client

```php
$history = get_user_meta($customer_id, '_loyalty_points_history', true);

// Format :
// [
//   ['date' => '2026-03-13 14:30:00', 'points' => 150, 'type' => 'earned', 'order_id' => 1234],
//   ['date' => '2026-03-15 10:20:00', 'points' => -500, 'type' => 'redeemed', 'order_id' => 0],
// ]
```

### Ajuster manuellement les points d'un client

Via l'admin WordPress ou phpMyAdmin :

```sql
-- Consulter le solde
SELECT meta_value FROM wp_usermeta 
WHERE user_id = 123 AND meta_key = '_loyalty_points';

-- Modifier le solde
UPDATE wp_usermeta 
SET meta_value = 1000 
WHERE user_id = 123 AND meta_key = '_loyalty_points';
```

### Réinitialiser tous les points

```sql
DELETE FROM wp_usermeta WHERE meta_key = '_loyalty_points';
DELETE FROM wp_usermeta WHERE meta_key = '_loyalty_points_history';
```

## Dépannage

### Les points ne sont pas attribués

Vérifiez :
1. Le statut de la commande est bien `wc-completed`
2. La commande a un `customer_id` valide (pas un invité)
3. Les points n'ont pas déjà été attribués (`_loyalty_points_awarded`)

### Le coupon n'est pas généré

Vérifiez :
1. Le solde du client atteint bien le palier
2. Les réglages du programme sont corrects
3. L'email du client est valide

### L'email n'est pas envoyé

Vérifiez :
1. La configuration SMTP de WordPress
2. Les logs d'erreur PHP
3. Testez avec `wp_mail()` manuellement

## Sécurité

- Les points sont stockés en **user meta** (sécurisé)
- Les coupons sont **liés à l'email** du client (pas de partage possible)
- Les coupons sont **à usage unique**
- L'historique permet la **traçabilité** complète

## Performance

- Pas de requêtes supplémentaires sur le front-end
- Calculs effectués uniquement lors du changement de statut de commande
- Statistiques calculées à la demande (page admin uniquement)

## Évolutions futures possibles

- [ ] Paliers multiples avec récompenses progressives
- [ ] Points d'expiration (validité limitée)
- [ ] Bonus de parrainage
- [ ] Interface client dédiée pour consulter l'historique
- [ ] Export CSV des points clients
- [ ] Notifications push/SMS en plus de l'email
- [ ] Intégration avec un système de gamification
