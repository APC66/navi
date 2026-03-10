<?php

namespace App\Blocks;

use Log1x\AcfComposer\Block;
use StoutLogic\AcfBuilder\FieldsBuilder;

class ImageCarousel extends Block
{
    /**
     * The block name.
     *
     * @var string
     */
    public $name = 'Image Carousel';

    /**
     * The block description.
     *
     * @var string
     */
    public $description = 'Carrousel d\'images';

    /**
     * The block category.
     *
     * @var string
     */
    public $category = 'media';

    /**
     * The block icon.
     *
     * @var string|array
     */
    public $icon = 'images-alt2';

    /**
     * Data to be passed to the block before rendering.
     *
     * @return array
     */
    public function with()
    {
        return [
            'gallery' => get_field('gallery') ?: [],
            'block_id' => 'image-carousel-'.$this->block->id,
        ];
    }

    /**
     * The block field group.
     *
     * @return array
     */
    public function fields()
    {
        $carousel = new FieldsBuilder('image_carousel');

        $carousel
            ->addGallery('gallery', [
                'label' => 'Galerie d\'images',
                'instructions' => 'Ajoutez les images à faire défiler dans le carrousel.',
                'return_format' => 'array',
                'preview_size' => 'medium',
            ]);

        return $carousel->build();
    }

    /**
     * Assets to be enqueued when rendering the block.
     *
     * @return void
     */
    public function enqueue()
    {
        \Roots\bundle('resources/js/blocks/image-carousel.js')->enqueue();
    }
}
