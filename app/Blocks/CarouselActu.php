<?php

namespace App\Blocks;

use App\Fields\Partials\ButtonFields;
use App\Fields\Partials\SectionHeaderFields;
use Log1x\AcfComposer\Block;
use StoutLogic\AcfBuilder\FieldsBuilder;

class CarouselActu extends Block
{
    public $name = 'Carousel Actu';

    public $description = 'Carrousel des actualités (articles de blog), dans le même esprit que le carrousel de croisières.';

    public $category = 'widgets';

    public $icon = 'megaphone';

    public function with()
    {
        $type = get_field('selection_type');
        $count = get_field('count') ?: 6;

        $args = [
            'post_type' => 'post',
            'posts_per_page' => $count,
            'post_status' => 'publish',
        ];

        if ($type === 'manual') {
            $ids = get_field('manual_selection');
            if (! empty($ids)) {
                $args['post__in'] = $ids;
                $args['orderby'] = 'post__in';
            }
        } else {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        }

        $query = new \WP_Query($args);

        $posts = collect($query->posts)->map(function ($p) {
            return [
                // Texte brut : les entités (&#8217;...) sont échappées par Blade dans la vue.
                'title' => html_entity_decode(get_the_title($p), ENT_QUOTES, 'UTF-8'),
                'permalink' => get_permalink($p),
                'thumbnail_url' => get_the_post_thumbnail_url($p, 'large') ?: '',
                'date' => get_the_date('', $p),
            ];
        });

        // Le bloc ne s'affiche en front que s'il y a au moins 3 articles publiés.
        $publishedCount = (int) (wp_count_posts('post')->publish ?? 0);

        return [
            'title_group' => get_field('title_group'),
            'posts' => $posts,
            'background' => get_field('background_style'),
            'cta' => $this->cta(),
            'bg_image' => get_field('bg_image'),
            'block_id' => 'carousel-actu-'.$this->block->id,
            'has_enough' => $publishedCount >= 3,
            'is_preview' => ! empty($this->block->is_preview),
        ];
    }

    /**
     * Bouton sous le carousel : celui saisi dans le bloc s'il y en a un, sinon
     * « Voir toutes nos actus » vers la page des actualités (Réglages > Lecture),
     * sauf si la case « Bouton vers toutes les actualités » est décochée.
     */
    protected function cta(): array
    {
        $cta = get_field('cta_group');
        if (is_array($cta) && ! empty($cta['title']) && ! empty($cta['url'])) {
            return $cta;
        }

        // Champ jamais enregistré (blocs déjà en place) : bouton affiché par défaut.
        $showAllLink = get_field('show_all_link');
        $postsPageId = (int) get_option('page_for_posts');

        if ($showAllLink === false || $showAllLink === 0 || $showAllLink === '0' || ! $postsPageId) {
            return [];
        }

        return [
            'title' => 'Voir toutes nos actus',
            'url' => get_permalink($postsPageId),
            'target' => '_self',
            'variant' => 'secondary',
            'align' => 'center',
        ];
    }

    public function fields()
    {
        $carousel = new FieldsBuilder('carousel_actu');

        $carousel
            ->addTab('contenu', ['label' => 'Contenu'])

            ->addGroup('title_group', ['label' => 'Titre de la section'])
            ->addFields(app(SectionHeaderFields::class)->fields())
            ->endGroup()

            ->addSelect('selection_type', [
                'label' => 'Source',
                'choices' => ['latest' => 'Dernières', 'manual' => 'Sélection'],
                'default_value' => 'latest',
            ])

            ->addPostObject('manual_selection', [
                'label' => 'Articles',
                'post_type' => ['post'],
                'multiple' => 1,
                'return_format' => 'id',
                'conditional_logic' => [[['field' => 'selection_type', 'operator' => '==', 'value' => 'manual']]],
            ])

            ->addNumber('count', ['label' => 'Nombre', 'default_value' => 6])

            ->addTrueFalse('show_all_link', [
                'label' => 'Bouton vers toutes les actualités',
                'instructions' => 'Affiche « Voir toutes nos actus » sous le carousel, vers la page des articles (Réglages > Lecture). Ignoré si un bouton d\'action est renseigné ci-dessous.',
                'default_value' => 1,
                'ui' => 1,
            ])

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
        \Roots\bundle('resources/js/blocks/carousel-actu.js')->enqueue();
    }
}
