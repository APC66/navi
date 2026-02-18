<?php

namespace App\Fields\Partials;

use Log1x\AcfComposer\Partial;
use StoutLogic\AcfBuilder\FieldsBuilder;

class SubHeaderFields extends Partial
{
    /**
     * The partial field group.
     *
     * @return \StoutLogic\AcfBuilder\FieldsBuilder
     */
    public function fields()
    {
        $subheader = new FieldsBuilder('sub_header_fields');

        $subheader
            ->addText('subtitle', [
                'label' => 'Sous-titre (Majuscules)',
                'placeholder' => '',
                'instructions' => 'S\'affiche en gras et en majuscules au-dessus du texte.'
            ])

            ->addWysiwyg('content', [
                'label' => 'Contenu Riche',
                'media_upload' => 0,
                'toolbar' => 'basic',
                'rows' => 4,
                'instructions' => 'Le paragraphe descriptif.'
            ])

            ->addSelect('align', [
                'label' => 'Alignement',
                'choices' => [
                    'text-left' => 'Gauche',
                    'text-center' => 'Centré',
                    'text-right' => 'Droite',
                ],
                'default_value' => 'text-center',
                'ui' => 0,
                'wrapper' => ['width' => '50']
            ]);

        return $subheader;
    }
}
