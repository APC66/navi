<?php

namespace App\Blocks;

use App\Fields\Partials\ButtonFields;
use App\Fields\Partials\SectionHeaderFields;
use Log1x\AcfComposer\Block;
use StoutLogic\AcfBuilder\FieldsBuilder;

class SimpleCta extends Block
{
    /**
     * The block name.
     *
     * @var string
     */
    public $name = 'Simple Cta';

    /**
     * The block description.
     *
     * @var string
     */
    public $description = 'Bloc simple avec titre contenu et boutons.';

    /**
     * The block category.
     *
     * @var string
     */
    public $category = 'formatting';

    /**
     * The block icon.
     *
     * @var string|array
     */
    public $icon = 'megaphone';

    /**
     * Data to be passed to the block before rendering.
     *
     * @return array
     */
    public function with()
    {
        if (get_field('gradient')) {
            $background = get_field('from_gradient_color').' '.get_field('to_gradient_color').' bg-gradient-to-b ';
        } else {
            $background = get_field('background_style');
        }

        return [
            'title_group' => get_field('title_group'),
            'ref_id' => get_field('ref_id'),
            'button_1' => get_field('button_1'),
            'button_2' => get_field('button_2'),
            'background' => $background,
            'content' => get_field('content'),
            'review_bloc' => get_field('review_bloc'),
        ];
    }

    /**
     * The block field group.
     *
     * @return array
     */
    public function fields()
    {
        $cta = new FieldsBuilder('simple_cta');

        $cta
            ->addTab('contenu', ['label' => 'Contenu'])
            ->addGroup('title_group', ['label' => 'Titre'])
            ->addFields(app(SectionHeaderFields::class)->fields())
            ->endGroup()

            ->addWysiwyg('content', [
                'label' => 'Contenu',
                'toolbar' => 'basic',
                'media_upload' => 0,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'review_bloc',
                            'operator' => '==',
                            'value' => 0,
                        ],
                    ],
                ],
            ])
            ->addGroup('button_1', ['label' => 'Bouton Principal'])
            ->addFields(app(ButtonFields::class)->fields())
            ->endGroup()

            ->addGroup('button_2', ['label' => 'Bouton Secondaire'])
            ->addFields(app(ButtonFields::class)->fields())
            ->endGroup()

            ->addTab('style', ['label' => 'Apparence'])
            ->addTrueFalse('gradient', [
                'label' => 'Fond en dégradé ?',
                'instructions' => 'Applique un dégradé sur le fond',
                'ui' => 1,
            ])
            ->addSelect('background_style', [
                'label' => 'Couleur de fond',
                'choices' => [
                    'bg-white' => 'Blanc',
                    'bg-off-white' => 'Blanc Cassé',
                    'bg-primary-50' => 'Bleu très clair',
                    'bg-primary-900' => 'Bleu Nuit (Texte blanc)',
                ],
                'default_value' => 'bg-white',
                'conditional_logic' => [
                    [
                        [
                            'field' => 'gradient',
                            'operator' => '==',
                            'value' => 0,
                        ],
                    ],
                ],
            ])
            ->addSelect('from_gradient_color', [
                'label' => 'Couleur du dégradé',
                'choices' => [
                    'from-primary-1000' => 'Bleu Nuit',
                    'from-primary-900' => 'Bleu Foncé',
                    'from-primary-700' => 'Bleu Moyen',
                    'from-primary-500' => 'Bleu Clair',
                ],
                'default_value' => 'from-primary-1000',
                'conditional_logic' => [
                    [
                        [
                            'field' => 'gradient',
                            'operator' => '==',
                            'value' => 1,
                        ],
                    ],
                ],
            ])
            ->addSelect('to_gradient_color', [
                'label' => 'Couleur du dégradé',
                'choices' => [
                    'to-primary-1000' => 'Bleu Nuit',
                    'to-primary-900' => 'Bleu Foncé',
                    'to-primary-700' => 'Bleu Moyen',
                    'to-primary-500' => 'Bleu Clair',
                ],
                'default_value' => 'to-primary-1000',
                'conditional_logic' => [
                    [
                        [
                            'field' => 'gradient',
                            'operator' => '==',
                            'value' => 1,
                        ],
                    ],
                ],
            ]);

        return $cta->build();
    }

    public function enqueue() {}
}
