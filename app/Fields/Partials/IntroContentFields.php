<?php

namespace App\Fields\Partials;

use Log1x\AcfComposer\Partial;
use StoutLogic\AcfBuilder\FieldsBuilder;

class IntroContentFields extends Partial
{
    /**
     * The partial field group.
     *
     * @return \StoutLogic\AcfBuilder\FieldsBuilder
     */
    public function fields()
    {
        $intro = new FieldsBuilder('intro_content_fields');

        $intro
            ->addImage('intro_icon', [
                'label' => 'Icône / Logo',
                'instructions' => 'Optionnel',
                'return_format' => 'url',
                'preview_size' => 'thumbnail',
                'wrapper' => ['width' => '30'],
            ])
            ->addText('intro_title', [
                'label' => 'Titre Intro',
                'default_value' => 'Entreprise familiale depuis 1992.',
                'wrapper' => ['width' => '70'],
            ])
            ->addWysiwyg('intro_content', [
                'label' => 'Contenu principal',
                'media_upload' => 0,
                'toolbar' => 'basic',
                'rows' => 4,
            ]);

        return $intro;
    }
}
