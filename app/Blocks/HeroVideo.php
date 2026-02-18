<?php

namespace App\Blocks;

use Log1x\AcfComposer\Block;
use StoutLogic\AcfBuilder\FieldsBuilder;

class HeroVideo extends Block
{
    /**
     * The block name.
     *
     * @var string
     */
    public $name = 'Hero Video';

    /**
     * The block description.
     *
     * @var string
     */
    public $description = 'Bannière plein écran avec vidéo différente sur mobile et desktop.';

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
    public $icon = 'video-alt3';

    /**
     * Data to be passed to the block before rendering.
     *
     * @return array
     */
    public function with()
    {
        return [
            'video_desktop' => get_field('video_desktop'),
            'video_mobile'  => get_field('video_mobile'),
            'fallback_image' => get_field('fallback_image'),
        ];
    }

    /**
     * The block field group.
     *
     * @return array
     */
    public function fields()
    {
        $heroVideo = new FieldsBuilder('hero_video');

        $heroVideo
            ->addTab('medias', ['label' => 'Médias'])
            ->addFile('video_desktop', [
                'label' => 'Vidéo Desktop',
                'instructions' => 'Format 16:9 recommandé. MP4 ou WebM.',
                'return_format' => 'array',
                'library' => 'all',
                'mime_types' => 'mp4,webm',
                'required' => 0, // Mieux vaut 0 pour éviter les erreurs de validation bloquantes en admin
            ])
            ->addFile('video_mobile', [
                'label' => 'Vidéo Mobile',
                'instructions' => 'Format vertical (9:16) recommandé.',
                'return_format' => 'array',
                'library' => 'all',
                'mime_types' => 'mp4,webm',
                'required' => 0,
            ])
            ->addImage('fallback_image', [
                'label' => 'Image de remplacement',
                'instructions' => 'S\'affiche si la vidéo ne charge pas ou pour le poster.',
                'return_format' => 'url',
            ]);

        return $heroVideo->build();
    }

    /**
     * Assets to be enqueued when rendering the block.
     *
     * @return void
     */
    public function enqueue()
    {
        //
    }
}
