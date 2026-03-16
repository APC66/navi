<?php

namespace App\Blocks;

use App\Fields\Partials\ButtonFields;
use Log1x\AcfComposer\Block;
use StoutLogic\AcfBuilder\FieldsBuilder;

class TextWithIcon extends Block
{
    /**
     * The block name.
     *
     * @var string
     */
    public $name = 'Text With Icon';

    /**
     * The block description.
     *
     * @var string
     */
    public $description = 'Bloc générique avec icône/logo, titres et contenu riche.';

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
    public $icon = 'groups';

    /**
     * Data to be passed to the block before rendering.
     *
     * @return array
     */
    public function with()
    {
        $margins = get_field('margin_top').' '.get_field('margin_bottom');

        return [
            'main_title' => get_field('main_title'),
            'main_subtitle' => get_field('main_subtitle'),
            'icon' => get_field('icon'),
            'text_icon' => get_field('icon_text'),
            'content' => get_field('content'),
            'cta' => get_field('cta_group'),
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
        $aboutUs = new FieldsBuilder('about_us');

        $aboutUs
            ->addTab('contenu', ['label' => 'Contenu'])
            ->addText('main_title', ['label' => 'Titre principal'])
            ->addText('main_subtitle', ['label' => 'Sous-titre'])
            ->addImage('icon', ['label' => 'Icône / Logo', 'return_format' => 'url'])
            ->addWysiwyg('icon_text', ['label' => 'Texte a droite de l\'icone '])
            ->addWysiwyg('content', [
                'label' => 'Contenu',
                'media_upload' => 0,
                'toolbar' => 'full',
            ])
            ->addGroup('cta_group', ['label' => 'Bouton d\'action'])
            ->addFields(app(ButtonFields::class)->fields())
            ->endGroup()

            ->addTab('reglages', ['label' => 'Réglages'])
            ->addSelect('background_style', [
                'label' => 'Couleur de fond',
                'choices' => [
                    'bg-primary-1000' => 'Bleu Nuit',
                    'bg-primary-900' => 'Bleu Foncé',
                    'bg-white' => 'Blanc',
                ],
                'default_value' => 'bg-primary-1000',
            ])
            ->addSelect('margin_bottom', [
                'label' => 'Marge inférieure',
                'choices' => [
                    'pb-0' => 'Aucune',
                    'pb-4 lg:pb-8' => 'Petite',
                    'pb-10 lg:pb-20' => 'Moyenne',
                    'pb-20 lg:pb-40' => 'Grande',
                ],
                'default_value' => 'pb-10 lg:pb-20',
            ])
            ->addSelect('margin_top', [
                'label' => 'Marge supérieure',
                'choices' => [
                    'pt-0' => 'Aucune',
                    'pt-4 lg:pt-8' => 'Petite',
                    'pt-10 lg:pt-20' => 'Moyenne',
                    'pt-20 lg:pt-40' => 'Grande',
                ],
                'default_value' => 'pt-10 lg:pt-20',
            ]);

        return $aboutUs->build();
    }

    public function enqueue()
    {
        //
    }
}
