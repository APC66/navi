<?php

namespace App\Blocks;

use App\Fields\Partials\ButtonFields;
use App\Fields\Partials\IntroContentFields;
use App\Fields\Partials\SectionHeaderFields;
use Log1x\AcfComposer\Block;
use StoutLogic\AcfBuilder\FieldsBuilder;

class TextImagesCards extends Block
{
    public $name = 'Text Images Cards';

    public $description = 'Bloc présentation avec 2 images inclinées et contenu texte.';

    public $category = 'marketing';

    public $icon = 'format-gallery';

    public function with()
    {
        return [
            'title_group' => get_field('title_group'),
            'intro_group' => get_field('intro_group'),
            'image_1' => get_field('image_1'),
            'image_2' => get_field('image_2'),
            'bg_image' => get_field('bg_image'),
            'cta' => get_field('cta_group'),
        ];
    }

    public function fields()
    {
        $cards = new FieldsBuilder('text_images_cards');

        $cards
            ->addTab('contenu', ['label' => 'Contenu'])

            ->addGroup('title_group', ['label' => 'Titre'])
            ->addFields(app(SectionHeaderFields::class)->fields())
            ->endGroup()

            ->addGroup('intro_group', ['label' => 'Intro / Badge'])
            ->addFields(app(IntroContentFields::class)->fields())
            ->endGroup()
            ->addGroup('cta_group', ['label' => 'Bouton d\'action'])
            ->addFields(app(ButtonFields::class)->fields())
            ->endGroup()

            ->addTab('visuel', ['label' => 'Visuels'])
            ->addImage('image_1', [
                'label' => 'Image Avant (Focus)',
                'return_format' => 'url',
                'wrapper' => ['width' => '50'],
            ])
            ->addImage('image_2', [
                'label' => 'Image Arrière (Fond)',
                'return_format' => 'url',
                'wrapper' => ['width' => '50'],
            ])
            ->addImage('bg_image', [
                'label' => 'Image de fond',
                'instructions' => 'Image de fond du bloc',
                'return_format' => 'url',
            ]);

        return $cards->build();
    }

    public function enqueue()
    {
        //
    }
}
