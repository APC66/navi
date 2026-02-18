<?php

namespace App\Blocks;

use App\Fields\Partials\SectionHeaderFields;
use App\Fields\Partials\SubHeaderFields;
use Log1x\AcfComposer\Block;
use StoutLogic\AcfBuilder\FieldsBuilder;

class Reassurance extends Block
{
    public $name = 'Reassurance';

    public $description = 'Bloc de réassurance avec grille d\'icônes sur fond sombre.';

    public $category = 'marketing';

    public $icon = 'shield';

    public function with()
    {
        return [
            'title_group' => get_field('title_group'),
            'subheader' => get_field('subheader_group'),
            'items' => get_field('items'),
            'bg_image' => get_field('bg_image'),
        ];
    }

    public function fields()
    {
        $reassurance = new FieldsBuilder('reassurance');

        $reassurance
            ->addTab('contenu', ['label' => 'Contenu'])

            // TITRE (Réutilisation du Partial)
            ->addGroup('title_group', ['label' => 'Titre Principal'])
            ->addFields(app(SectionHeaderFields::class)->fields())
            ->endGroup()

            // SOUS-TITRE (Spécifique)
            ->addGroup('subheader_group', ['label' => 'Sous-titre & Intro'])
            ->addFields(app(SubHeaderFields::class)->fields())
            ->endGroup()

            // GRILLE D'ICÔNES
            ->addRepeater('items', ['label' => 'Éléments de réassurance', 'layout' => 'block', 'button_label' => 'Ajouter un élément'])
            ->addImage('icon', ['label' => 'Icône', 'return_format' => 'url', 'preview_size' => 'thumbnail', 'wrapper' => ['width' => '20']])
            ->addText('title', ['label' => 'Titre (Jaune)', 'wrapper' => ['width' => '40']])
            ->addTextarea('text', ['label' => 'Description (Blanc)', 'rows' => 2, 'wrapper' => ['width' => '40']])
            ->endRepeater()

            ->addTab('style', ['label' => 'Apparence'])
            ->addImage('bg_image', ['label' => 'Image de fond', 'return_format' => 'url']);

        return $reassurance->build();
    }

    public function enqueue()
    {
        // Pas de JS spécifique pour l'instant
    }
}
