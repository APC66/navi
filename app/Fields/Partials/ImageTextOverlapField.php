<?php

namespace App\Fields\Partials;

use Log1x\AcfComposer\Partial;
use StoutLogic\AcfBuilder\FieldsBuilder;

class ImageTextOverlapField extends Partial
{
    /**
     * The field group.
     */
    public function fields()
    {
        $fields = new FieldsBuilder('image_text_overlap');

        $fields
            ->addTab('contenu', ['label' => 'Contenu'])
            ->addText('title', [
                'label' => 'Titre',
                'default_value' => 'Prenez le large...',
            ])
            ->addWysiwyg('content', [
                'label' => 'Contenu',
                'media_upload' => 0,
                'toolbar' => 'basic',
                'rows' => 6,
            ])

            ->addTab('visuel', ['label' => 'Visuels'])
            ->addImage('image_front', [
                'label' => 'Image Avant (Principale)',
                'instructions' => 'Image au premier plan.',
                'return_format' => 'id',
                'preview_size' => 'medium',
            ])
            ->addImage('image_back', [
                'label' => 'Image Arrière (Décor)',
                'instructions' => 'Image en arrière plan décalé.',
                'return_format' => 'id',
                'preview_size' => 'medium',
            ])

            ->addTab('reglages', ['label' => 'Réglages'])
            ->addTrueFalse('invert', [
                'label' => 'Inverser la disposition',
                'instructions' => 'Mettre les images à droite et le texte à gauche.',
                'ui' => 1,
            ]);

        return $fields;
    }
}
