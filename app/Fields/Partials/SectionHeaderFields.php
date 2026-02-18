<?php

namespace App\Fields\Partials;

use Log1x\AcfComposer\Partial;
use StoutLogic\AcfBuilder\FieldsBuilder;

class SectionHeaderFields extends Partial
{
    public function fields()
    {
        $header = new FieldsBuilder('section_header_fields');

        $header
            ->addText('highlight', ['label' => 'Texte Mis en avant', 'placeholder' => 'Croisières'])
            ->addText('suffix', ['label' => 'Texte Introduction', 'placeholder' => 'Nos'])

            ->addSelect('highlight_color', [
                'label' => 'Couleur Mise en avant',
                'choices' => [
                    'text-secondary' => 'Jaune (Secondary)',
                    'text-white' => 'Blanc',
                    'text-primary-600' => 'Bleu Principal',
                ],
                'default_value' => 'text-secondary',
                'ui' => 0,
            ])

            ->addSelect('tag', [
                'label' => 'Niveau de titre',
                'choices' => ['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3'],
                'default_value' => 'h2',
                'wrapper' => ['width' => '50'],
            ])

            ->addSelect('size', [
                'label' => 'Taille du titre',
                'choices' => ['XL' => 'Large', 'M' => 'Moyen', 'S' => 'Petit'],
                'default_value' => 'XL',
                'wrapper' => ['width' => '50'],
            ])
            ->addTrueFalse('highlight_break', [
                'label' => 'Saut de ligne après le texte mis en avant',
                'default_value' => false,
                'wrapper' => ['width' => '50'],
            ])
            ->addSelect('align', [
                'label' => 'Alignement',
                'choices' => ['text-left' => 'Gauche', 'text-center' => 'Centré', 'text-right' => 'Droite'],
                'default_value' => 'text-center',
                'wrapper' => ['width' => '50'],
            ]);

        return $header;
    }
}
