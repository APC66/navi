<?php

namespace App\Blocks;

use App\Fields\Partials\ButtonFields;
use App\Fields\Partials\SectionHeaderFields;
use App\Models\Cruise;
use Log1x\AcfComposer\Block;
use StoutLogic\AcfBuilder\FieldsBuilder;

class CruiseCarousel extends Block
{
    public $name = 'Cruise Carousel';

    public $description = 'Carrousel de croisières (Swiper) avec filtres.';

    public $category = 'widgets';

    public $icon = 'images-alt2';

    public function with()
    {
        $type = get_field('selection_type');
        $count = get_field('count') ?: 6;
        $args = ['post_type' => 'cruise', 'posts_per_page' => $count, 'post_status' => 'publish'];

        if ($type === 'manual') {
            $ids = get_field('manual_selection');
            if (! empty($ids)) {
                $args['post__in'] = $ids;
                $args['orderby'] = 'post__in';
            }
        } elseif ($type === 'taxonomy') {
            $termId = get_field('filter_port');
            if ($termId) {
                $args['tax_query'] = [['taxonomy' => 'harbor', 'field' => 'term_id', 'terms' => $termId]];
            }
        } else {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        }

        $cruises = method_exists(Cruise::class, 'fetch') ? Cruise::fetch($args) : collect([]);

        return [
            'title_group' => get_field('title_group'),
            'cruises' => $cruises,
            'background' => get_field('background_style'),
            'cta' => get_field('cta_group'),
            'bg_image' => get_field('bg_image'),
            'block_id' => 'cruise-carousel-'.$this->block->id,
        ];
    }

    public function fields()
    {
        $carousel = new FieldsBuilder('cruise_carousel');

        $carousel
            ->addTab('contenu', ['label' => 'Contenu'])

            ->addGroup('title_group', ['label' => 'Titre de la section'])
            ->addFields(app(SectionHeaderFields::class)->fields())
            ->endGroup()

            ->addSelect('selection_type', [
                'label' => 'Source',
                'choices' => ['latest' => 'Dernières', 'manual' => 'Sélection', 'taxonomy' => 'Par Port'],
                'default_value' => 'latest',
            ])

            ->addTaxonomy('filter_port', [
                'label' => 'Port',
                'taxonomy' => 'harbor',
                'field_type' => 'select',
                'allow_null' => 1,
                'conditional_logic' => [[['field' => 'selection_type', 'operator' => '==', 'value' => 'taxonomy']]],
            ])

            ->addPostObject('manual_selection', [
                'label' => 'Croisières',
                'post_type' => ['cruise'],
                'multiple' => 1,
                'return_format' => 'id',
                'conditional_logic' => [[['field' => 'selection_type', 'operator' => '==', 'value' => 'manual']]],
            ])

            ->addNumber('count', ['label' => 'Nombre', 'default_value' => 6])
            ->addGroup('cta_group', ['label' => 'Bouton d\'action'])
            ->addFields(app(ButtonFields::class)->fields())
            ->endGroup()

            ->addTab('style', ['label' => 'Apparence'])
            ->addImage('bg_image', [
                'label' => 'Image de fond',
                'instructions' => '',
                'return_format' => 'url',
                'preview_size' => 'medium',
            ])
            ->addSelect('background_style', [
                'label' => 'Fond',
                'choices' => [
                    'bg-white' => 'Blanc',
                    'bg-off-white' => 'Blanc Cassé',
                    'bg-primary-50' => 'Bleu',
                    'bg-primary-900 text-white' => 'Bleu Nuit (Texte blanc)',
                ],
                'default_value' => 'bg-off-white',

            ]);

        return $carousel->build();
    }

    public function enqueue()
    {
        \Roots\bundle('resources/js/blocks/cruise-carousel.js')->enqueue();
    }
}
