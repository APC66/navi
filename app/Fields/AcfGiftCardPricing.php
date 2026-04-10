<?php

namespace App\Fields;

use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

class AcfGiftCardPricing extends Field
{
    public function fields()
    {
        $giftCard = new FieldsBuilder('gift_card_pricing', [
            'title' => 'Tarifs Carte Cadeau',
            'position' => 'normal',
            'style' => 'default',
        ]);

        $giftCard
            ->setLocation('post_type', '==', 'cruise');

        $giftCard
            ->addTab('gc_pricing_tab', ['label' => 'Tarifs Carte Cadeau'])

            ->addWysiwyg('gift_card_description', [
                'label' => 'Description de la croisière (carte cadeau)',
                'instructions' => 'Texte affiché dans le formulaire de carte cadeau lors de la sélection de cette croisière. Supporte le HTML basique.',
                'wrapper' => ['width' => '100'],
            ])

            ->addTrueFalse('gc_no_seasonality', [
                'label' => 'Tarifs sans saisonnalité',
                'ui' => 1,
                'ui_on_text' => 'Oui',
                'ui_off_text' => 'Non',
                'instructions' => 'Activez cette option si les tarifs sont identiques toute l\'année. Le sélecteur de saison sera masqué sur le formulaire. Seule la colonne "Prix unique" sera utilisée.',
                'wrapper' => ['width' => '100'],
                'default_value' => 0,
            ])

            ->addText('low_season_label', [
                'label' => 'Période Basse Saison',
                'instructions' => 'Ex : Octobre → Avril',
                'placeholder' => 'Ex : Octobre → Avril',
                'wrapper' => ['width' => '50'],
                'conditional_logic' => [[['field' => 'gc_no_seasonality', 'operator' => '!=', 'value' => '1']]],
            ])
            ->addText('high_season_label', [
                'label' => 'Période Haute Saison',
                'instructions' => 'Ex : Mai → Septembre',
                'placeholder' => 'Ex : Mai → Septembre',
                'wrapper' => ['width' => '50'],
                'conditional_logic' => [[['field' => 'gc_no_seasonality', 'operator' => '!=', 'value' => '1']]],
            ])

            ->addRepeater('pricing_rows', [
                'label' => 'Tarifs par type de passager',
                'button_label' => 'Ajouter un tarif',
                'layout' => 'table',
            ])
            ->addTaxonomy('passenger_type', [
                'label' => 'Type de passager',
                'taxonomy' => 'passenger_type',
                'field_type' => 'select',
                'allow_null' => 0,
                'add_term' => 0,
                'save_terms' => 0,
                'return_format' => 'id',
                'wrapper' => ['width' => '34'],
            ])
            ->addNumber('price_low_season', [
                'label' => 'Prix unique / Basse Saison (€)',
                'instructions' => 'Sert de prix unique si la saisonnalité est désactivée.',
                'min' => 0,
                'step' => 0.01,
                'wrapper' => ['width' => '33'],
            ])
            ->addNumber('price_high_season', [
                'label' => 'Prix Haute Saison (€)',
                'min' => 0,
                'step' => 0.01,
                'wrapper' => ['width' => '33'],
            ])
            ->endRepeater()

            ->addRepeater('options_pricing', [
                'label' => 'Tarifs des options extras',
                'button_label' => 'Ajouter une option',
                'layout' => 'table',
            ])
            ->addTaxonomy('option_type', [
                'label' => 'Type d\'option',
                'taxonomy' => 'extra_option_type',
                'field_type' => 'select',
                'allow_null' => 0,
                'add_term' => 0,
                'save_terms' => 0,
                'return_format' => 'id',
                'wrapper' => ['width' => '34'],
            ])
            ->addNumber('option_price_low', [
                'label' => 'Prix unique / Basse Saison (€)',
                'instructions' => 'Sert de prix unique si la saisonnalité est désactivée.',
                'min' => 0,
                'step' => 0.01,
                'wrapper' => ['width' => '33'],
            ])
            ->addNumber('option_price_high', [
                'label' => 'Prix Haute Saison (€)',
                'min' => 0,
                'step' => 0.01,
                'wrapper' => ['width' => '33'],
            ])
            ->endRepeater();

        return $giftCard->build();
    }
}
