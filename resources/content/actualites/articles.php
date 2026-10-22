<?php

/**
 * Actualités rédigées dans le dépôt, publiées par la commande
 * « wp acorn navi:actus-publier » (voir app/Console/Commands/PublishNewsCommand.php).
 *
 * - image : nom du fichier dans la médiathèque (identique en local et en ligne) ;
 * - le premier article de la liste est le plus récent ;
 * - les réglages SEO alimentent Yoast SEO (titre, méta-description, mot-clé).
 */

return [
    // Auteur des articles (sinon : premier administrateur)
    'author_email' => 'contact@navivoile.com',

    'categories' => [
        'idees-cadeaux-bons-plans' => [
            'name' => 'Idées cadeaux & bons plans',
            'description' => 'Cartes cadeaux, programme de fidélité et bons plans pour profiter d\'une sortie en mer en catamaran à voile dans les Pyrénées-Orientales.',
        ],
        'faune-marine-observations' => [
            'name' => 'Faune marine & observations',
            'description' => 'Dauphins, baleines et faune de Méditerranée : conseils et découvertes au départ de Canet-en-Roussillon et de Port-Vendres.',
        ],
        'groupes-sorties-scolaires' => [
            'name' => 'Groupes & sorties scolaires',
            'description' => 'Sorties scolaires, classes de mer et sorties de groupe à bord du catamaran NAVIVOILE.',
        ],
    ],

    'articles' => [
        [
            'file' => 'carte-cadeau-croisiere.html',
            'slug' => 'carte-cadeau-croisiere-catamaran-pyrenees-orientales',
            'title' => 'Carte cadeau croisière : offrez une sortie en mer en catamaran dans les Pyrénées-Orientales',
            'category' => 'idees-cadeaux-bons-plans',
            'image' => 'carte-cadeau-croisiere-pyrenees-orientales-coucher-de-soleil-en-mer-canet-en-roussillon-navivoile-scaled.jpg',
            'excerpt' => 'Offrez une sortie en mer en catamaran à voile : les deux formules, quelle croisière choisir selon la personne, la commande pas à pas et l\'utilisation de la carte cadeau croisière NAVIVOILE.',
            'seo_title' => 'Carte cadeau croisière en catamaran à voile | NAVIVOILE',
            'meta_description' => 'Offrez une carte cadeau croisière NAVIVOILE : une sortie en mer en catamaran à voile au départ de Canet-en-Roussillon ou Port-Vendres, à commander en ligne.',
            'focus_keyword' => 'carte cadeau croisière',
        ],
        [
            'file' => 'observation-dauphins-baleines.html',
            'slug' => 'observation-dauphins-baleines-mediterranee-pyrenees-orientales',
            'title' => 'Observation des dauphins et des baleines en Méditerranée depuis les Pyrénées-Orientales',
            'category' => 'faune-marine-observations',
            'image' => 'catamaran-a-voile-a-canet-en-roussillon-et-port-vendres-navivoile-dauphins-logo-oniria-voile-barcares-scaled.jpg',
            'excerpt' => 'Grand dauphin près des côtes, rorqual commun ou cachalot au large : découvrez les sorties d\'observation des dauphins et des baleines en Méditerranée au départ de Canet-en-Roussillon et Port-Vendres.',
            'seo_title' => 'Observation des dauphins en Méditerranée | NAVIVOILE',
            'meta_description' => 'Observation des dauphins et des baleines en Méditerranée depuis Canet-en-Roussillon et Port-Vendres : espèces, sorties en catamaran à voile et conseils.',
            'focus_keyword' => 'observation des dauphins',
        ],
        [
            'file' => 'sortie-scolaire-bateau.html',
            'slug' => 'sortie-scolaire-bateau-pyrenees-orientales',
            'title' => 'Sortie scolaire en bateau : une journée pédagogique en mer dans les Pyrénées-Orientales',
            'category' => 'groupes-sorties-scolaires',
            'image' => 'manoeuvre-voile-gennaker-eleves-marin-collioure-port-vendres-catamaran-navivoile-scaled.jpg',
            'excerpt' => 'Une sortie scolaire en bateau à bord du catamaran NAVIVOILE : apprentissages adaptés à chaque niveau, formules à la carte, sécurité, documents fournis et étapes pour préparer votre journée en mer.',
            'seo_title' => 'Sortie scolaire en bateau dans les Pyrénées-Orientales',
            'meta_description' => 'Organisez une sortie scolaire en bateau sur un catamaran à voile, de la maternelle au lycée, au départ de Canet-en-Roussillon ou de Port-Vendres.',
            'focus_keyword' => 'sortie scolaire en bateau',
        ],
        [
            'file' => 'programme-fidelite.html',
            'slug' => 'programme-fidelite-croisiere-navivoile',
            'title' => 'Programme de fidélité NAVIVOILE : cumulez des points et naviguez moins cher',
            'category' => 'idees-cadeaux-bons-plans',
            'image' => 'catamaran-a-voile-a-canet-en-roussillon-et-port-vendres-navivoile-navigation-port-la-nouvelle-aout-scaled.jpg',
            'excerpt' => '1 € dépensé = 1 point : comment adhérer au programme de fidélité NAVIVOILE, les paliers de réduction, les conditions à connaître et nos idées pour profiter de vos points.',
            'seo_title' => 'Programme de fidélité et réductions croisière | NAVIVOILE',
            'meta_description' => 'Programme de fidélité NAVIVOILE : 1 € dépensé = 1 point et des réductions dès 350 points sur vos croisières en catamaran au départ de Canet et Port-Vendres.',
            'focus_keyword' => 'programme de fidélité',
        ],
    ],
];
