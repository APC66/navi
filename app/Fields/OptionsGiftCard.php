<?php

namespace App\Fields;

use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

class OptionsGiftCard extends Field
{
    public function fields()
    {
        $options = new FieldsBuilder('options_gift_card', [
            'title' => 'Carte Cadeau (PDF)',
            'style' => 'seamless',
        ]);

        $options->setLocation('options_page', '==', 'theme-options');

        $options
            ->addTab('gift_card', ['label' => 'Carte Cadeau PDF'])
            ->addImage('gift_card_bg_image', [
                'label' => 'Photo de fond (carte cadeau PDF)',
                'instructions' => 'Image affichée en bannière en haut du PDF. Format paysage recommandé (ex: 1200×400px).',
                'return_format' => 'url',
                'preview_size' => 'medium',
            ]);

        return $options->build();
    }
}
