# Export des Commandes WooCommerce

## Vue d'ensemble

Le système d'export des commandes permet de filtrer et d'exporter les commandes WooCommerce en CSV avec des informations détaillées sur les réservations de croisières.

## Fonctionnalités

### 1. Filtre par Agent Créateur

Sur la page des commandes WooCommerce (`WooCommerce > Commandes`), un nouveau filtre dropdown est disponible :

- **Tous les agents créateurs** : Affiche toutes les commandes
- **Client Direct (sans agent)** : Affiche uniquement les commandes passées directement par les clients
- **[Nom de l'agent]** : Affiche les commandes créées par un agent spécifique

Le filtre fonctionne avec :
- Le système legacy de WooCommerce (CPT `shop_order`)
- Le nouveau système HPOS (High-Performance Order Storage)

### 2. Export CSV en Masse

#### Utilisation

1. Aller sur `WooCommerce > Commandes`
2. Optionnel : Filtrer par agent créateur
3. Sélectionner les commandes à exporter (cases à cocher)
4. Dans le menu déroulant "Actions groupées", choisir **"📊 Exporter en CSV"**
5. Cliquer sur "Appliquer"
6. Le fichier CSV sera téléchargé automatiquement

#### Colonnes du CSV

Le fichier CSV exporté contient les colonnes suivantes :

| Colonne | Description |
|---------|-------------|
| **Statut** | Statut de la commande (En attente, Terminée, etc.) |
| **Nom Client** | Nom complet du client (prénom + nom) |
| **Email** | Adresse email du client |
| **N° Commande** | Numéro de la commande WooCommerce |
| **Téléphone** | Numéro de téléphone du client |
| **Montant** | Montant total de la commande |
| **Nom de Croisière** | Nom du produit/croisière réservé |
| **Jour de Croisière** | Date et heure de départ formatée |
| **Nb de Participants (Détail)** | Détail des passagers (ex: "2x Adulte, 1x Enfant") |
| **Restant à Payer** | Montant restant dû (balance) |
| **Agent Créateur** | Nom de l'agent ayant créé la commande ou "Client Direct" |
| **Commentaire** | Notes du client + notes privées internes |

#### Format du fichier

- **Encodage** : UTF-8 avec BOM (compatible Excel)
- **Séparateur** : Point-virgule (`;`)
- **Nom du fichier** : `export-commandes-YYYY-MM-DD-HHMMSS.csv`

## Architecture Technique

### Classe principale

**`App\Admin\OrdersExportPage`**

Cette classe gère :
- L'affichage du filtre par agent créateur
- Le filtrage des commandes selon l'agent sélectionné
- L'action d'export en masse
- La génération du fichier CSV

### Hooks WordPress/WooCommerce utilisés

#### Filtres
```php
// Legacy (CPT)
add_action('restrict_manage_posts', [$this, 'addAgencyCreatorFilter'], 10, 2);
add_filter('request', [$this, 'filterOrdersByAgencyCreator']);

// HPOS
add_action('woocommerce_order_list_table_restrict_manage_orders', [$this, 'addAgencyCreatorFilterHPOS']);
add_filter('woocommerce_orders_table_query_clauses', [$this, 'filterOrdersByAgencyCreatorHPOS'], 10, 2);
```

#### Actions en masse
```php
add_filter('bulk_actions-edit-shop_order', [$this, 'addBulkExportAction']);
add_filter('bulk_actions-woocommerce_page_wc-orders', [$this, 'addBulkExportAction']);

add_filter('handle_bulk_actions-edit-shop_order', [$this, 'handleBulkExport'], 10, 3);
add_filter('handle_bulk_actions-woocommerce_page_wc-orders', [$this, 'handleBulkExport'], 10, 3);
```

### Initialisation

Le service est initialisé dans `ThemeServiceProvider::boot()` :

```php
(new OrdersExportPage)->init();
```

## Métadonnées utilisées

Le système utilise les métadonnées suivantes sur les commandes :

- `_agency_creator_id` : ID de l'utilisateur agent créateur
- `_balance_due` : Montant restant à payer
- `_private_boarding_note` : Note privée interne
- `_sailing_id` : ID du départ (sailing) réservé
- `_booking_data_raw` : Données JSON de la réservation (passagers, options)

## Compatibilité

- ✅ WooCommerce Legacy (CPT)
- ✅ WooCommerce HPOS (High-Performance Order Storage)
- ✅ Compatible avec le système d'agence (`AgencyOrderService`)
- ✅ Compatible avec le système de réservation de croisières

## Cas d'usage

### Export pour comptabilité
Exporter toutes les commandes d'un mois pour la comptabilité avec les montants et restants à payer.

### Suivi des agents
Filtrer et exporter les commandes d'un agent spécifique pour suivre ses performances.

### Liste d'embarquement
Exporter les commandes d'une période pour préparer les listes d'embarquement avec détails des participants.

### Relances de paiement
Identifier les commandes avec un restant à payer pour effectuer des relances clients.

## Notes techniques

- Le CSV est généré en streaming pour gérer de gros volumes de commandes
- L'encodage UTF-8 avec BOM garantit la compatibilité avec Excel
- Les requêtes SQL sont optimisées pour éviter les N+1 queries
- Le système gère automatiquement les commandes avec ou sans agent créateur
