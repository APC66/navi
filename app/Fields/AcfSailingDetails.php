<?php

namespace App\Fields;

use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

class AcfSailingDetails extends Field
{
    /**
     * The field group.
     *
     * @return array
     */
    public function fields()
    {
        $sailing = new FieldsBuilder('sailing_details', [
            'title' => 'Configuration du Départ',
            'position' => 'acf_after_title',
            'style' => 'seamless',
        ]);

        $sailing
            ->setLocation('post_type', '==', 'sailing');

        $sailing
            ->addGroup('sailing_config', [
                'label' => 'Paramètres de la date',
                'layout' => 'block',
            ])
            // Liaison Parent/Enfant
            ->addPostObject('parent_cruise', [
                'label' => 'Croisière associée',
                'post_type' => ['cruise'],
                'return_format' => 'id',
                'required' => 1,
                'wrapper' => ['width' => '50'],
            ])

            // Statut (Nouveau Champ)
            // On utilise un champ Taxonomie pour gérer le statut proprement
            ->addTaxonomy('status', [
                'label' => 'Statut du Départ',
                'taxonomy' => 'sailing_status', // Le slug défini dans config/post-types.php
                'field_type' => 'radio', // Radio pour un choix unique (plus visuel)
                'allow_null' => 0,
                'add_term' => 0, // On empêche la création de nouveaux statuts ici
                'save_terms' => 1, // On sauvegarde la relation WP standard
                'load_terms' => 1,
                'return_format' => 'object',
                'multiple' => 0,
                'wrapper' => ['width' => '50'],
            ])

            // Date précise
            ->addDateTimePicker('departure_date', [
                'label' => 'Date et Heure de DÉPART',
                'required' => 1,
                'display_format' => 'd/m/Y H:i',
                'return_format' => 'Y-m-d H:i:s',
                'wrapper' => ['width' => '50'],
            ])

            // Arrivée
            ->addDateTimePicker('arrival_date', [
                'label' => 'Date et Heure d\'ARRIVÉE (Retour)',
                'instructions' => 'Optionnel. Si vide, la durée par défaut de la croisière sera utilisée.',
                'display_format' => 'd/m/Y H:i',
                'return_format' => 'Y-m-d H:i:s',
                'wrapper' => ['width' => '50'],
            ])

            ->addNumber('quota', [
                'label' => 'Quota Global (Passagers)',
                'default_value' => 50,
                'min' => 0,
            ])

            // --- 1. TARIFS DE BASE (PASSAGERS) ---
            ->addTab('tab_passengers', ['label' => 'Tarifs Passagers'])
            ->addRepeater('passenger_fares', [
                'label' => 'Grille Tarifaire Passagers',
                'layout' => 'table',
            ])
            ->addTaxonomy('passenger_type', [
                'label' => 'Type de passager',
                'taxonomy' => 'passenger_type',
                'field_type' => 'select',
                'allow_null' => 0,
                'add_term' => 1,
                'save_terms' => 0,
                'return_format' => 'id',
            ])
            ->addNumber('price', ['label' => 'Prix', 'append' => '€', 'required' => 1])
            ->endRepeater()

            // --- 2. SUPPLÉMENTS / OPTIONS ---
            ->addTab('tab_options', ['label' => 'Options & Suppléments'])
            ->addRepeater('extra_options', [
                'label' => 'Options Payantes',
                'layout' => 'row',
            ])
            ->addTaxonomy('option_type', [
                'label' => 'Type d\'option',
                'taxonomy' => 'extra_option_type',
                'field_type' => 'select',
                'allow_null' => 0,
                'add_term' => 1,
                'save_terms' => 0,
                'return_format' => 'id',
                'wrapper' => ['width' => '33'],
            ])
            ->addNumber('price', ['label' => 'Prix', 'append' => '€', 'wrapper' => ['width' => '33']])
            ->addTrueFalse('has_quota', ['label' => 'Stock ?', 'ui' => 1, 'wrapper' => ['width' => '33']])
            ->addNumber('quota', [
                'label' => 'Stock',
                'conditional_logic' => [[['field' => 'has_quota', 'operator' => '==', 'value' => '1']]]
            ])
            ->endRepeater()

            ->endGroup();

        return $sailing->build();
    }
}
