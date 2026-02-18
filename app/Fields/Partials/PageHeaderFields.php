<?php

namespace App\Fields\Partials;

use Log1x\AcfComposer\Partial;
use StoutLogic\AcfBuilder\FieldsBuilder;

class PageHeaderFields extends Partial
{
    public function fields()
    {
        $header = new FieldsBuilder('page_header_fields');

        $header
            ->addImage('header_image', [
                'label' => 'Image d\'entête',
                'instructions' => 'Image large (ex: 1920x600).',
                'return_format' => 'url',
            ])
            ->addText('header_highlight', [
                'label' => 'Texte mis en avant',
                'instructions' => 'Texte de couleur différente pour mettre en avant une partie du titre.',
            ])
            ->addText('header_title', [
                'label' => 'Titre principal',
                'instructions' => 'Laisser vide pour utiliser le titre de la page.',
            ])

            ->addSelect('header_highlight_color', [
                'label' => 'Couleur de mise en avant',
                'choices' => [
                    'text-secondary' => 'Jaune (Secondary)',
                    'text-tertiary-800' => 'Bleu Canard (Tertiary)',
                    'text-primary-600' => 'Bleu Principal',
                    'text-white' => 'Blanc',
                ],
                'default_value' => 'text-secondary',
                'ui' => 0,
            ])
            // Fin Ajout
            ->addTextarea('header_subtitle', [
                'label' => 'Sous-titre / Introduction',
                'rows' => 2,
                'new_lines' => 'br',
            ]);

        return $header;
    }
}
