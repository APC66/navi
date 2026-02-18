<?php

namespace App\Fields;

use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

class AcfCruiseInfos extends Field
{
    public function fields()
    {
        $cruise = new FieldsBuilder('cruise_infos', [
            'title' => 'Informations de la Croisière',
            'position' => 'acf_after_title',
            'style' => 'default',
        ]);

        $cruise
            ->setLocation('post_type', '==', 'cruise');

        $cruise
            ->addTab('infos_general', ['label' => 'Général'])
            ->addText('duration', ['label' => 'Durée affichée', 'placeholder' => 'ex: 2h30', 'wrapper' => ['width' => '50']])
            ->addNumber('base_price', ['label' => 'Prix de base (À partir de)', 'append' => '€', 'wrapper' => ['width' => '50']])

            ->addTab('infos_booking', ['label' => 'Réservation'])
            ->addNumber('booking_cutoff', ['label' => 'Blocage avant départ', 'default_value' => 30, 'append' => 'minutes'])

            ->addTab('infos_planning', ['label' => 'Planning des Départs'])

            ->addGroup('recurrence_generator', ['label' => '⚡ Ajouter des dates', 'layout' => 'block'])
            ->addDatePicker('start_date', ['label' => 'Du', 'display_format' => 'd/m/Y', 'return_format' => 'Y-m-d', 'wrapper' => ['width' => '25']])
            ->addDatePicker('end_date', ['label' => 'Au', 'display_format' => 'd/m/Y', 'return_format' => 'Y-m-d', 'wrapper' => ['width' => '25']])

            // HEURE DE DÉPART (Format 24h)
            ->addTimePicker('time', [
                'label' => 'Heure Départ',
                'display_format' => 'H:i', // H = 24h avec zéro, i = minutes
                'return_format' => 'H:i:s',
                'wrapper' => ['width' => '25']
            ])

            // HEURE DE RETOUR (Format 24h)
            ->addTimePicker('return_time', [
                'label' => 'Heure Retour',
                'display_format' => 'H:i', // H = 24h avec zéro
                'return_format' => 'H:i:s',
                'wrapper' => ['width' => '25'],
                'instructions' => 'Si < à l\'heure de départ, sera considéré le lendemain.'
            ])

            ->addNumber('batch_quota', ['label' => 'Quota Global', 'default_value' => 50])

            ->addCheckbox('days', [
                'label' => 'Jours actifs',
                'choices' => ['1'=>'Lundi', '2'=>'Mardi', '3'=>'Mercredi', '4'=>'Jeudi', '5'=>'Vendredi', '6'=>'Samedi', '0'=>'Dimanche'],
                'layout' => 'horizontal',
                'return_format' => 'value',
            ])

            ->addAccordion('batch_pricing_accordion', ['label' => 'Configuration des Prix & Quotas', 'open' => 0])
            ->addRepeater('batch_passenger_fares', ['label' => 'Tarifs Passagers (Modèle)', 'button_label' => 'Ajouter un tarif', 'layout' => 'table'])
            ->addTaxonomy('passenger_type', [
                'label' => 'Type de passager', 'taxonomy' => 'passenger_type', 'field_type' => 'select',
                'allow_null' => 0, 'add_term' => 1, 'save_terms' => 0, 'return_format' => 'id',
            ])
            ->addNumber('price', ['label' => 'Prix (€)'])
            ->endRepeater()

            ->addRepeater('batch_extra_options', ['label' => 'Options (Modèle)', 'button_label' => 'Ajouter une option', 'layout' => 'row'])
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
            ->addNumber('price', ['label' => 'Prix Supp.', 'wrapper' => ['width' => '33']])
            ->addTrueFalse('has_quota', ['label' => 'Stock ?', 'ui' => 1, 'wrapper' => ['width' => '33']])
            ->addNumber('quota', [
                'label' => 'Stock',
                'conditional_logic' => [[['field' => 'has_quota', 'operator' => '==', 'value' => '1']]]
            ])
            ->endRepeater()
            ->addAccordion('batch_pricing_accordion_end')

            ->addTrueFalse('trigger_generation', ['label' => 'Générer ces dates à la sauvegarde ?', 'message' => 'Oui, lancer la création.', 'default_value' => 0])
            ->endGroup()

            ->addMessage('existing_sailings_table', '📅 Départs programmés', [
                'key' => 'field_cruise_existing_sailings',
                'message' => '<em>La liste des départs s\'affichera ici...</em>',
                'new_lines' => 'wpautop',
            ]);

        return $cruise->build();
    }
}
