<?php

use App\Services\ReservationService;

beforeEach(function () {
    $this->reservationService = new ReservationService;
});

afterEach(function () {
    // Nettoyage des posts de test
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type IN ('cruise', 'sailing') AND post_title LIKE 'Test%'");
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})");
});

it('blocks booking when cutoff is passed', function () {
    // Créer une croisière avec un cutoff de 30 minutes
    $cruiseId = wp_insert_post([
        'post_type' => 'cruise',
        'post_title' => 'Test Cruise Cutoff',
        'post_status' => 'publish',
    ]);

    update_field('booking_cutoff', 30, $cruiseId);

    // Créer un départ dans 20 minutes (donc après le cutoff de 30 min)
    $departureDate = new \DateTime('now', new \DateTimeZone(wp_timezone_string()));
    $departureDate->modify('+20 minutes');

    $sailingId = wp_insert_post([
        'post_type' => 'sailing',
        'post_title' => 'Test Sailing',
        'post_status' => 'publish',
    ]);

    update_field('sailing_config', [
        'parent_cruise' => $cruiseId,
        'departure_date' => $departureDate->format('Y-m-d H:i:s'),
        'quota' => 50,
    ], $sailingId);

    // Tenter de réserver - devrait lever une exception
    expect(fn () => $this->reservationService->checkAvailability($sailingId, 2))
        ->toThrow(\Exception::class, 'délai de réservation');
});

it('allows booking when cutoff is not passed', function () {
    // Créer une croisière avec un cutoff de 30 minutes
    $cruiseId = wp_insert_post([
        'post_type' => 'cruise',
        'post_title' => 'Test Cruise Cutoff OK',
        'post_status' => 'publish',
    ]);

    update_field('booking_cutoff', 30, $cruiseId);

    // Créer un départ dans 60 minutes (donc avant le cutoff de 30 min)
    $departureDate = new \DateTime('now', new \DateTimeZone(wp_timezone_string()));
    $departureDate->modify('+60 minutes');

    $sailingId = wp_insert_post([
        'post_type' => 'sailing',
        'post_title' => 'Test Sailing OK',
        'post_status' => 'publish',
    ]);

    update_field('sailing_config', [
        'parent_cruise' => $cruiseId,
        'departure_date' => $departureDate->format('Y-m-d H:i:s'),
        'quota' => 50,
    ], $sailingId);

    // Tenter de réserver - devrait réussir
    $result = $this->reservationService->checkAvailability($sailingId, 2);

    expect($result)->toBeTrue();
});

it('allows booking when cutoff is disabled', function () {
    // Créer une croisière avec cutoff désactivé
    $cruiseId = wp_insert_post([
        'post_type' => 'cruise',
        'post_title' => 'Test Cruise No Cutoff',
        'post_status' => 'publish',
    ]);

    update_field('booking_cutoff', 0, $cruiseId);

    // Créer un départ dans 5 minutes
    $departureDate = new \DateTime('now', new \DateTimeZone(wp_timezone_string()));
    $departureDate->modify('+5 minutes');

    $sailingId = wp_insert_post([
        'post_type' => 'sailing',
        'post_title' => 'Test Sailing No Cutoff',
        'post_status' => 'publish',
    ]);

    update_field('sailing_config', [
        'parent_cruise' => $cruiseId,
        'departure_date' => $departureDate->format('Y-m-d H:i:s'),
        'quota' => 50,
    ], $sailingId);

    // Tenter de réserver - devrait réussir car cutoff désactivé
    $result = $this->reservationService->checkAvailability($sailingId, 2);

    expect($result)->toBeTrue();
});
