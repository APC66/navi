<?php

namespace App\Fields\Partials;

use Log1x\AcfComposer\Partial;
use StoutLogic\AcfBuilder\FieldsBuilder;

class ButtonFields extends Partial
{
    /**
     * The partial field group.
     *
     * @return \StoutLogic\AcfBuilder\FieldsBuilder
     */
    public function fields()
    {
        $button = new FieldsBuilder('button_fields');

        $button
            ->addText('title', ['label' => 'Texte du bouton'])
            ->addUrl('url', ['label' => 'Lien (URL)'])
            ->addSelect('target', [
                'label' => 'Cible',
                'choices' => [
                    '_self' => 'Même fenêtre',
                    '_blank' => 'Nouvel onglet',
                ],
                'default_value' => '_self',
                'wrapper' => ['width' => '33'],
            ])
            ->addSelect('variant', [
                'label' => 'Style',
                'choices' => [
                    'secondary' => 'Jaune Plein',
                    'outline' => 'Bordure Jaune',
                ],
                'default_value' => 'secondary',
                'wrapper' => ['width' => '33'],
            ])
            ->addSelect('align', [
                'label' => 'Alignement',
                'instructions' => 'Fonctionne si le bouton n\'est pas en pleine largeur.',
                'choices' => [
                    'left' => 'Gauche (Défaut)',
                    'center' => 'Centré',
                    'right' => 'Droite',
                ],
                'default_value' => 'left',
                'wrapper' => ['width' => '33'],
            ]);

        return $button;
    }
}
