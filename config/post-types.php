<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Post Types
    |--------------------------------------------------------------------------
    |
    | Post types to be registered with Extended CPTs
    | <https://github.com/johnbillion/extended-cpts>
    |
    */

    'post_types' => [
        'seed' => [
            'menu_icon' => 'dashicons-star-filled',
            'supports' => ['title', 'editor', 'author', 'revisions', 'thumbnail'],
            'show_in_rest' => true,
            'names' => [
                'singular' => 'Seed',
                'plural' => 'Seeds',
                'slug' => 'seeds',
            ]
        ],

        'cruise' => [
            'menu_icon' => 'dashicons-ship',
            'supports' => ['title', 'thumbnail', 'excerpt'],
            'show_in_rest' => false,
            'menu_position' => 20,
            'names' => [
                'singular' => 'Croisière',
                'plural' => 'Croisières',
                'slug' => 'croisieres',
            ],
            'admin_cols' => [
                'cruise_duration' => [
                    'title' => 'Durée',
                    'meta_key' => 'duration',
                ],
            ],
        ],

        // 2. CPT Enfant : Le Départ (Date technique & Quotas)
        'sailing' => [
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => ['title'],
            'show_in_rest' => false,
            'menu_position' => 21,
            'names' => [
                'singular' => 'Départ',
                'plural' => 'Départs',
                'slug' => 'depart',
            ],
            'admin_cols' => [
                'sailing_date' => [
                    'title' => 'Date de départ',
                    'meta_key' => 'departure_date',
                    'function' => function() {
                        $date = get_field('departure_date');
                        echo $date ? date_i18n('d/m/Y H:i', strtotime($date)) : '-';
                    }
                ],
                'sailing_quota' => [
                    'title' => 'Quota',
                    'meta_key' => 'quota',
                ],
                // Ajout de la colonne statut pour voir l'état rapidement
                'sailing_status' => [
                    'title' => 'Statut',
                    'taxonomy' => 'sailing_status',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Taxonomies
    |--------------------------------------------------------------------------
    |
    | Taxonomies to be registered with Extended CPTs library
    | <https://github.com/johnbillion/extended-cpts>
    |
    */

    'taxonomies' => [
        'seed_category' => [
            'post_types' => ['seed'],
            'meta_box' => 'radio',
            'names' => [
                'singular' => 'Category',
                'plural' => 'Categories',
            ],
        ],
        'harbor' => [
            'post_types' => ['cruise'],
            'meta_box' => 'radio',
            'names' => [
                'singular' => 'Port de départ',
                'plural' => 'Ports',
                'slug' => 'port',
            ],
        ],
        'cruise_type' => [
            'post_types' => ['cruise'],
            'names' => [
                'singular' => 'Type de Croisière',
                'plural' => 'Types de Croisières',
                'slug' => 'type-croisiere',
            ],
        ],
        'cruise_tag' => [
            'post_types' => ['cruise'],
            'names' => [
                'singular' => 'Tag de Croisière',
                'plural' => 'Tags de Croisières',
                'slug' => 'tag-croisiere',
            ],
        ],
        'passenger_type' => [
            'post_types' => ['cruise', 'sailing'],
            'meta_box' => false,
            'show_in_rest' => true,
            'names' => [
                'singular' => 'Type de Passager',
                'plural' => 'Types de Passagers',
                'slug' => 'type-passager',
            ],
        ],
        // Taxonomie : Types d'Options
        'extra_option_type' => [
            'post_types' => ['cruise', 'sailing'],
            'meta_box' => false, // Masquer la métabox standard
            'show_in_rest' => true,
            'names' => [
                'singular' => 'Type d\'Option',
                'plural' => 'Types d\'Options',
                'slug' => 'type-option',
            ],
        ],
        // NOUVELLE TAXONOMIE : Statut du Départ (Annulé, Confirmé, etc.)
        'sailing_status' => [
            'post_types' => ['sailing'],
            'meta_box' => false, // Masqué pour gestion via ACF
            'show_ui' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'names' => [
                'singular' => 'Statut du Départ',
                'plural' => 'Statuts des Départs',
                'slug' => 'statut-depart',
            ],
        ],
    ],
];
