<?php

namespace App\Blocks;

use App\Fields\Partials\ButtonFields;
use App\Fields\Partials\IntroContentFields;
use App\Fields\Partials\SectionHeaderFields;
use Log1x\AcfComposer\Block;
use StoutLogic\AcfBuilder\FieldsBuilder;

class TextImage extends Block
{
    /**
     * The block name.
     *
     * @var string
     */
    public $name = 'Text Image';

    /**
     * The block description.
     *
     * @var string
     */
    public $description = 'Bloc classique Texte + Image avec inversion de position.';

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
    public $icon = 'align-pull-left';

    /**
     * Data to be passed to the block before rendering.
     *
     * @return array
     */
    public function with()
    {
        $margins = get_field('margin_top') . ' ' . get_field('margin_bottom');

        return [
            'title_group' => get_field('title_group'),
            'intro_group' => get_field('intro_group'),
            'image' => get_field('image'),
            'cta' => get_field('cta_group'),
            'invert' => get_field('invert_layout'),
            'background' => get_field('background_style'),
            'margins' => $margins,
        ];
    }

    /**
     * The block field group.
     *
     * @return array
     */
    public function fields()
    {
        $textImage = new FieldsBuilder('text_image');

        $textImage
            ->addTab('contenu', ['label' => 'Contenu'])
            // Titre
            ->addGroup('title_group', ['label' => 'Titre'])
            ->addFields(app(SectionHeaderFields::class)->fields())
            ->endGroup()

            ->addGroup('intro_group', ['label' => 'Intro / Badge'])
            ->addFields(app(IntroContentFields::class)->fields())
            ->endGroup()

            ->addGroup('cta_group', ['label' => 'Bouton d\'action'])
            ->addFields(app(ButtonFields::class)->fields())
            ->endGroup()

            ->addTab('visuel', ['label' => 'Visuel'])
            ->addImage('image', ['label' => 'Image Principale', 'return_format' => 'url'])

            ->addTab('reglages', ['label' => 'Réglages'])
            ->addTrueFalse('invert_layout', [
                'label' => 'Image à gauche ?',
                'instructions' => 'Par défaut l\'image est à droite. Cocher pour inverser.',
                'ui' => 1,
            ])
            ->addSelect('background_style', [
                'label' => 'Couleur de fond',
                'choices' => [
                    'bg-primary-1000' => 'Bleu Nuit',
                    'bg-primary-900' => 'Bleu Foncé',
                ],
                'default_value' => 'bg-white',
            ])
            ->addSelect('margin_bottom', [
                'label' => 'Marge inférieure',
                'choices' => [
                    'pb-0' => 'Aucune',
                    'pb-4 lg:pb-8' => 'Petite',
                    'pb-10 lg:pb-20' => 'Moyenne',
                    'pb-20 lg:pb-40' => 'Grande',
                ],
                'default_value' => 'pb-8',
            ])
            ->addSelect('margin_top', [
                'label' => 'Marge supérieure',
                'choices' => [
                    'pt-0' => 'Aucune',
                    'pt-4 lg:pt-8' => 'Petite',
                    'pt-10 lg:pt-20' => 'Moyenne',
                    'pt-20 lg:pt-40' => 'Grande',
                ],
                'default_value' => 'pt-8',
            ]);

        return $textImage->build();
    }

    public function enqueue()
    {
        //
    }
}
