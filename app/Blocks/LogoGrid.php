<?php

namespace App\Blocks;

use Log1x\AcfComposer\Block;
use StoutLogic\AcfBuilder\FieldsBuilder;

class LogoGrid extends Block
{
    public $name = 'Logo Grid';

    public $description = 'Grille de logos avec lien optionnel, 4 colonnes sur desktop.';

    public $category = 'marketing';

    public $icon = 'images-alt2';

    public function with()
    {
        return [
            'title_group' => get_field('title_group'),
            'logos' => get_field('logos'),
        ];
    }

    public function fields()
    {
        $logoGrid = new FieldsBuilder('logo_grid');

        $logoGrid
            ->addTab('contenu', ['label' => 'Contenu'])
            ->addRepeater('logos', [
                'label' => 'Logos',
                'layout' => 'block',
                'button_label' => 'Ajouter un logo',
            ])
            ->addImage('logo', [
                'label' => 'Logo',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'wrapper' => ['width' => '60'],
            ])
            ->addUrl('link', [
                'label' => 'Lien (optionnel)',
                'wrapper' => ['width' => '40'],
            ])
            ->endRepeater();

        return $logoGrid->build();
    }
}
