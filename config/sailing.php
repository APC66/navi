<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration centralisée des statuts de croisière
    |--------------------------------------------------------------------------
    | Cette configuration est la source de vérité pour les couleurs,
    | labels et comportements des différents statuts dans toute l'application.
    | Elle est automatiquement partagée avec le JS via wp_localize_script.
    */
    'statuses' => [
        'Dispo' => [
            'label' => 'DISPONIBLE',
            'bg' => 'bg-status-available',
            'border' => 'border-status-available',
            'text' => 'text-status-available-text',
            'btnText' => 'text-status-available-text',
            'isSelectable' => true,
            'showInLegend' => true,
        ],
        'Limité' => [
            'label' => 'DERNIÈRES PLACES !',
            'bg' => 'bg-status-limited',
            'border' => 'border-status-limited',
            'text' => 'text-status-limited-text',
            'btnText' => 'text-status-limited-text',
            'isSelectable' => true,
            'showInLegend' => true,
        ],
        'Reporté' => [
            'label' => 'REPORTÉ',
            'bg' => 'bg-status-postponed',
            'border' => 'border-status-postponed',
            'text' => 'text-status-postponed-text',
            'btnText' => 'text-status-postponed-text',
            'isSelectable' => false,
            'showInLegend' => true,
        ],
        'Annulé' => [
            'label' => 'ANNULÉ',
            'bg' => 'bg-status-cancelled',
            'border' => 'border-status-cancelled',
            'text' => 'text-status-cancelled-text',
            'btnText' => 'text-status-cancelled',
            'isSelectable' => false,
            'showInLegend' => true,
        ],
        'Complet' => [
            'label' => 'COMPLET',
            'bg' => 'bg-status-full',
            'border' => 'border-status-full',
            'text' => 'text-status-full-text',
            'btnText' => 'text-status-full',
            'isSelectable' => false,
            'showInLegend' => true,
        ],
        'default' => [
            'label' => 'NON DISPONIBLE',
            'bg' => 'bg-gray-100',
            'border' => 'border-gray-200',
            'text' => 'text-gray-600',
            'btnText' => 'text-gray-900',
            'isSelectable' => false,
            'showInLegend' => false,
        ],
    ],
];
