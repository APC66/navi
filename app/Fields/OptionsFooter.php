<?php

namespace App\Fields;

use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

class OptionsFooter extends Field
{
    /**
     * The field group.
     *
     * @return array
     */
    public function fields()
    {
        $options = new FieldsBuilder('options_footer', [
            'title' => 'Pied de page (Partenaires)',
            'style' => 'seamless',
        ]);

        $options
            ->setLocation('options_page', '==', 'theme-options');

        $options
            ->addTab('partenaires', ['label' => 'Partenaires'])
            ->addRepeater('footer_partners', [
                'label' => 'Logos Partenaires',
                'layout' => 'block',
                'button_label' => 'Ajouter un partenaire',
            ])
            ->addImage('logo', [
                'label' => 'Logo',
                'return_format' => 'url',
                'preview_size' => 'medium',
                'wrapper' => ['width' => '50'],
            ])
            ->addUrl('url', [
                'label' => 'Lien (Optionnel)',
                'wrapper' => ['width' => '50'],
            ])
            ->endRepeater();

        return $options->build();
    }
}
