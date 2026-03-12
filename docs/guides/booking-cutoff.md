# Gestion du Booking Cutoff

## Vue d'ensemble

Le **booking cutoff** est un délai de sécurité (en minutes) avant le départ d'une croisière, au-delà duquel les réservations ne sont plus acceptées. Cette fonctionnalité permet d'éviter les réservations de dernière minute qui pourraient poser des problèmes logistiques.

## Configuration

### Champ ACF

Le délai de cutoff est configuré au niveau de chaque **croisière** (CPT `cruise`) via le champ ACF :

- **Nom du champ** : `booking_cutoff`
- **Type** : Number
- **Unité** : Minutes
- **Valeur par défaut** : 30 minutes
- **Emplacement** : Onglet "Réservation" dans l'éditeur de croisière

**Exemple** : Si `booking_cutoff = 30`, les réservations seront bloquées 30 minutes avant l'heure de départ.

### Désactivation

Pour désactiver le cutoff sur une croisière spécifique, définir la valeur à `0`.

## Fonctionnement technique

### 1. Vérification dans ReservationService

La logique principale se trouve dans `app/Services/ReservationService.php`, méthode `checkAvailability()` :

```php
// 0. Vérification du Booking Cutoff (délai avant départ)
$departureDate = $sailing->start;
if ($departureDate) {
    try {
        $departureTime = new \DateTime($departureDate);
        $now = new \DateTime('now', new \DateTimeZone(wp_timezone_string()));
        
        // Récupérer le booking_cutoff depuis la croisière parente
        $cruiseId = $sailing->parentCruiseId;
        if ($cruiseId) {
            $bookingCutoff = (int) get_field('booking_cutoff', $cruiseId);
            
            if ($bookingCutoff > 0) {
                // Calculer le temps limite de réservation
                $cutoffTime = clone $departureTime;
                $cutoffTime->modify("-{$bookingCutoff} minutes");
                
                // Si on est après le cutoff, la réservation n'est plus possible
                if ($now >= $cutoffTime) {
                    throw new Exception('Le délai de réservation pour ce départ est dépassé.');
                }
            }
        }
    } catch (\Exception $e) {
        // Si c'est notre exception de cutoff, on la relance
        if (strpos($e->getMessage(), 'délai de réservation') !== false) {
            throw $e;
        }
        // Sinon on log et on continue (problème de parsing de date)
        error_log('Erreur parsing date départ pour cutoff: ' . $e->getMessage());
    }
}
```

**Points clés** :
- Le cutoff est récupéré depuis la **croisière parente** du départ
- Le calcul utilise le fuseau horaire WordPress (`wp_timezone_string()`)
- Si le cutoff est dépassé, une exception est levée avec un message explicite
- Les erreurs de parsing de date sont loggées mais n'empêchent pas le processus

### 2. Affichage dans le Planning

Le `PlanningController` (`app/Http/Controllers/Api/PlanningController.php`) vérifie également le cutoff pour afficher le bon statut :

```php
// Vérification du booking cutoff
$isCutoffPassed = false;
$departureDate = $sailing->start;
if ($departureDate) {
    try {
        $departureTime = new \DateTime($departureDate);
        $now = new \DateTime('now', new \DateTimeZone(wp_timezone_string()));
        $bookingCutoff = (int) get_field('booking_cutoff', $cruiseId);
        
        if ($bookingCutoff > 0) {
            $cutoffTime = clone $departureTime;
            $cutoffTime->modify("-{$bookingCutoff} minutes");
            $isCutoffPassed = ($now >= $cutoffTime);
        }
    } catch (\Exception $e) {
        error_log('Erreur calcul cutoff dans PlanningController: ' . $e->getMessage());
    }
}

// Gestion du statut
$status = 'Dispo';
if ($apiStatus === 'Annulé') {
    $status = 'Annulé';
} elseif ($apiStatus === 'Reporté') {
    $status = 'Reporté';
} elseif ($isCutoffPassed) {
    $status = 'Complet'; // On affiche "Complet" si le délai est dépassé
} elseif ($apiStatus === 'Complet' || $available <= 0) {
    $status = 'Complet';
} elseif ($available > 0 && $available <= 5) {
    $status = 'Limité';
}
```

**Comportement** :
- Si le cutoff est dépassé, le départ est affiché avec le statut **"Complet"**
- Cela empêche visuellement les utilisateurs de sélectionner ce départ
- Le widget de réservation ne permettra pas de cliquer sur ces dates

### 3. Points d'entrée API

Le cutoff est vérifié lors de l'ajout au panier via :

**Endpoint** : `POST /wp-json/radicle/v1/booking/add-to-cart`  
**Controller** : `app/Http/Controllers/CartController.php`

```php
$isAvailable = $this->reservationService->checkAvailability($sailingId, $totalHumans, $options);

if (!$isAvailable) {
    return ['success' => false, 'message' => 'Désolé, il n\'y a plus assez de places ou d\'options disponibles.'];
}
```

Si le cutoff est dépassé, l'exception est catchée et un message d'erreur est retourné à l'utilisateur.

## Affichage Frontend

### Widget de réservation

Le widget (`resources/views/components/partials/booking-widget.blade.php` + `resources/js/components/booking-widget.js`) :

1. **Récupère les départs** via l'API `/wp-json/radicle/v1/calendar/events`
2. **Affiche le statut** retourné par le `PlanningController`
3. **Bloque la sélection** des dates avec statut "Complet" (incluant celles dont le cutoff est dépassé)
4. **Affiche l'erreur** si l'utilisateur tente quand même de réserver (via message d'erreur de l'API)

### Planning global

Le composant `<x-global-planning>` utilise également l'API `planning/week` qui intègre la vérification du cutoff.

## Tests

Des tests unitaires sont disponibles dans `tests/Feature/BookingCutoffTest.php` :

### Exécution des tests

```bash
./vendor/bin/pest tests/Feature/BookingCutoffTest.php
```

### Scénarios testés

1. **test_booking_blocked_when_cutoff_passed** : Vérifie qu'une réservation est bloquée si le départ est dans 20 minutes avec un cutoff de 30 minutes
2. **test_booking_allowed_when_cutoff_not_passed** : Vérifie qu'une réservation est autorisée si le départ est dans 60 minutes avec un cutoff de 30 minutes
3. **test_booking_allowed_when_cutoff_disabled** : Vérifie qu'une réservation est autorisée même 5 minutes avant le départ si le cutoff est à 0

## Cas d'usage

### Exemple 1 : Croisière avec cutoff de 30 minutes

- **Heure de départ** : 14h00
- **Cutoff** : 30 minutes
- **Limite de réservation** : 13h30
- **Comportement** :
  - À 13h25 → Réservation possible ✅
  - À 13h35 → Réservation bloquée ❌ (message : "Le délai de réservation pour ce départ est dépassé.")

### Exemple 2 : Croisière sans cutoff

- **Heure de départ** : 14h00
- **Cutoff** : 0 (désactivé)
- **Comportement** :
  - À 13h59 → Réservation possible ✅
  - Aucune limite temporelle (sauf quota)

## Bonnes pratiques

1. **Définir un cutoff adapté** : 30 minutes est une valeur raisonnable pour la plupart des croisières
2. **Communiquer clairement** : Informer les clients du délai de réservation dans les conditions générales
3. **Tester régulièrement** : Vérifier que le fuseau horaire WordPress est correctement configuré
4. **Monitoring** : Surveiller les logs pour détecter d'éventuelles erreurs de parsing de dates

## Dépannage

### Problème : Les réservations sont bloquées trop tôt

**Cause possible** : Fuseau horaire WordPress mal configuré

**Solution** :
```php
// Vérifier dans wp-admin > Réglages > Général
// Ou dans wp-config.php :
define('WP_TIMEZONE', 'Europe/Paris');
```

### Problème : Le cutoff ne fonctionne pas

**Vérifications** :
1. Le champ `booking_cutoff` est bien renseigné sur la croisière
2. Le départ a bien une `parent_cruise` définie
3. La date de départ est au bon format (`Y-m-d H:i:s`)
4. Consulter les logs WordPress pour les erreurs

### Problème : Message d'erreur générique au lieu du message de cutoff

**Cause** : L'exception est catchée trop tôt dans le `CartController`

**Solution** : Vérifier que le try/catch dans `ReservationService::checkAvailability()` relance bien l'exception de cutoff.

## Évolutions futures possibles

- [ ] Ajouter un champ `booking_cutoff` au niveau du **départ** pour surcharger celui de la croisière
- [ ] Afficher un compte à rebours dans le widget ("Plus que 25 minutes pour réserver")
- [ ] Envoyer une notification admin quand un départ atteint son cutoff
- [ ] Permettre un cutoff différent selon le type de passager (ex: groupes vs individuels)

---

**Dernière mise à jour** : 12 mars 2026  
**Auteur** : Équipe Navi
