# Système de Commandes pour Compte Tiers (B2B/Agences)

## Vue d'ensemble

Le système de commandes pour compte tiers permet aux utilisateurs autorisés (agences, revendeurs, etc.) de passer des commandes WooCommerce pour le compte de clients finaux sans avoir à effectuer de paiement immédiat au moment du checkout.

## Architecture

### Fichiers principaux

- **Service** : `app/Services/AgencyOrderService.php`
- **Enregistrement** : `app/Providers/ThemeServiceProvider.php`

### Capability requise

Le système repose sur une capability personnalisée : **`place_agency_orders`**

> ⚠️ **Important** : Cette capability doit être attribuée manuellement aux utilisateurs via un plugin de gestion des rôles (ex: User Role Editor, Members, etc.). Le système ne crée pas de rôle automatiquement.

## Fonctionnalités

### 1. Bypass du paiement

Les utilisateurs possédant la capability `place_agency_orders` peuvent passer des commandes sans paiement immédiat :

- Le filtre `woocommerce_cart_needs_payment` retourne `false`
- Aucune passerelle de paiement n'est requise au checkout
- La commande est créée directement avec le statut par défaut de WooCommerce

### 2. Sélection du client au checkout

Une section dédiée apparaît au-dessus du formulaire de facturation pour les agents :

#### Option 1 : Client existant
- **Recherche AJAX optimisée** : Champ de recherche avec autocomplétion
- Recherche par nom, email ou username
- Limite de 20 résultats par recherche pour des performances optimales
- Fonctionne avec des bases de données de 10 000+ utilisateurs
- Debounce de 300ms pour éviter les requêtes excessives
- **Préremplissage automatique** : Les champs de facturation sont automatiquement remplis avec les données du client sélectionné

#### Option 2 : Nouveau client
- Champ email obligatoire
- Un compte WordPress est créé automatiquement avec :
  - Rôle : `customer`
  - Username généré à partir de l'email
  - Mot de passe aléatoire sécurisé
  - Informations de facturation pré-remplies
  - Email de notification envoyé au nouveau client

### 3. Attribution de la commande

La commande est automatiquement attribuée au client sélectionné ou créé via `$order->set_customer_id()`.

### 4. Tracking de l'agent créateur

L'ID de l'utilisateur qui a réellement passé la commande est enregistré dans une meta-donnée :

```php
$order->get_meta('_agency_creator_id');
```

### 5. Colonne admin "Agent Créateur"

Une colonne supplémentaire est ajoutée dans la liste des commandes WooCommerce (admin) :

- **Affichage** : Nom et email de l'agent créateur
- **Fallback** : "Client Direct" si la commande n'a pas été passée par un agent
- **Support HPOS** : Compatible avec le nouveau système de stockage des commandes

## Utilisation

### Étape 1 : Attribution de la capability

Utilisez un plugin de gestion des rôles pour attribuer la capability `place_agency_orders` aux utilisateurs concernés :

```php
// Exemple avec User Role Editor ou code personnalisé
$user = get_user_by('id', $user_id);
$user->add_cap('place_agency_orders');
```

### Étape 2 : Passer une commande

1. L'agent se connecte à WordPress
2. Il ajoute des produits au panier
3. Au checkout, il voit la section "Commande pour le compte d'un client"
4. Il sélectionne un client existant ou crée un nouveau client
5. Il remplit les informations de facturation (si nouveau client)
6. Il valide la commande sans paiement

### Étape 3 : Suivi des commandes

Dans l'admin WooCommerce :
- La colonne "🏢 Agent Créateur" affiche qui a passé la commande
- Les commandes peuvent être filtrées par agent créateur (via meta-données)

## Validation et sécurité

### Vérifications effectuées

1. **Affichage des champs** : Vérification de la capability avant d'afficher la section
2. **Validation du formulaire** :
   - Type de client obligatoire (existant ou nouveau)
   - Client existant : vérification de l'ID et du rôle `customer`
   - Nouveau client : validation de l'email et vérification qu'il n'existe pas déjà
3. **Création de commande** : Vérification de la capability avant attribution

### Sanitization

- Tous les champs sont sanitizés (`sanitize_email`, `sanitize_text_field`)
- Les IDs utilisateurs sont castés en `int`
- Les erreurs sont gérées via `WP_Error`

## Personnalisation

### Modifier le nombre de résultats de recherche AJAX

Dans `AgencyOrderService::ajaxSearchCustomers()`, modifier la clause `LIMIT` :

```php
$query = "
    SELECT DISTINCT u.ID, u.user_email, u.display_name, u.user_login
    FROM {$wpdb->users} u
    INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
    WHERE um.meta_key = '{$wpdb->prefix}capabilities'
    AND um.meta_value LIKE '%customer%'
    AND (
        u.display_name LIKE %s
        OR u.user_email LIKE %s
        OR u.user_login LIKE %s
    )
    ORDER BY u.display_name ASC
    LIMIT 20  // Modifier cette valeur (max recommandé : 50)
";
```

### Modifier le délai de debounce de la recherche

Dans `AgencyOrderService::enqueueCheckoutScripts()`, modifier le timeout :

```javascript
searchTimeout = setTimeout(function() {
    searchCustomers(term);
}, 300); // Modifier cette valeur en millisecondes
```

### Désactiver l'email de notification au nouveau client

Dans `AgencyOrderService::createNewCustomer()`, commenter la ligne :

```php
// wp_new_user_notification($user_id, null, 'user');
```

### Personnaliser les styles Tailwind

Les classes Tailwind CSS sont utilisées pour le styling :

```php
// Dans addClientSelectionFields()
echo '<div class="agency-client-selection bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">';
```

Modifiez ces classes selon votre charte graphique.

## Hooks disponibles

Le service utilise les hooks WooCommerce suivants :

| Hook | Type | Priorité | Usage |
|------|------|----------|-------|
| `woocommerce_cart_needs_payment` | Filter | 10 | Bypass du paiement |
| `woocommerce_before_checkout_billing_form` | Action | 10 | Affichage des champs |
| `woocommerce_after_checkout_validation` | Action | 10 | Validation |
| `woocommerce_checkout_create_order` | Action | 10 | Attribution du client |
| `woocommerce_checkout_order_created` | Action | 10 | Tracking de l'agent |
| `manage_edit-shop_order_columns` | Filter | 20 | Colonne admin (legacy) |
| `manage_woocommerce_page_wc-orders_columns` | Filter | 20 | Colonne admin (HPOS) |

## Compatibilité

- **PHP** : 8.3+ (typage strict)
- **WordPress** : 6.8+
- **WooCommerce** : 10.4+
- **HPOS** : Compatible avec High-Performance Order Storage
- **Radicle/Sage** : Intégré via Service Provider

## Dépannage

### Les champs ne s'affichent pas au checkout

1. Vérifier que l'utilisateur possède la capability `place_agency_orders`
2. Vérifier que le service est bien initialisé dans `ThemeServiceProvider`
3. Vider le cache si nécessaire

### La commande n'est pas attribuée au bon client

1. Vérifier les logs d'erreurs WooCommerce
2. S'assurer que le client sélectionné existe et a le rôle `customer`
3. Vérifier que l'email du nouveau client est valide et unique

### La colonne admin ne s'affiche pas

1. Vérifier que HPOS est activé ou désactivé selon votre configuration
2. Les deux hooks (legacy et HPOS) sont implémentés pour assurer la compatibilité

## Exemple de code pour attribuer la capability

```php
// Dans functions.php ou un plugin personnalisé
add_action('init', function() {
    // Attribuer la capability à un rôle spécifique
    $role = get_role('shop_manager');
    if ($role) {
        $role->add_cap('place_agency_orders');
    }
    
    // Ou à un utilisateur spécifique
    $user = get_user_by('email', 'agent@example.com');
    if ($user) {
        $user->add_cap('place_agency_orders');
    }
});
```

## Support

Pour toute question ou problème, consultez :
- Les logs Laravel : `storage/logs/laravel.log`
- Les logs WooCommerce : WooCommerce > Statut > Logs
- Le code source : `app/Services/AgencyOrderService.php`
