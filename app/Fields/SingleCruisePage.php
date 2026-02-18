<?php

namespace App\Fields;

use App\Fields\Partials\ImageTextOverlapField;
use Log1x\AcfComposer\Builder;
use Log1x\AcfComposer\Field;

class SingleCruisePage extends Field
{
    /**
     * The field group.
     */
    public function fields(): array
    {
        $fields = Builder::make('single_cruise_page');

        $fields
            ->setLocation('post_type', '==', 'cruise');

        $fields
            ->addGroup('image_text_overlap')
            ->addPartial(ImageTextOverlapField::class)
            ->endGroup()
            ->addWysiwyg('content', [
                'label' => 'Contenu de la page',
                'instructions' => 'Ajouter le contenu de la page ici.',
                'required' => true,
                'media_upload' => true,
            ])
            ->addGallery('gallery', [
                'label' => 'Galerie d\'images',
                'instructions' => 'Ajouter des images pour la galerie de la page.',
                'required' => false,
                'preview_size' => 'medium',
                'library' => 'all',
            ])
            ->addRepeater('videos', [
                'label' => 'Vidéos',
                'instructions' => 'Ajouter des vidéos pour la page.',
                'required' => false,
                'collapsed' => 'video_url',
            ])
            ->addUrl('video_url', [
                'label' => 'URL de la vidéo',
                'instructions' => 'Ajouter l\'URL de la vidéo (YouTube, Vimeo, etc.).',
                'required' => true,
            ])
            ->addText('video_title', [
                'label' => 'Titre de la vidéo',
                'instructions' => 'Ajouter un titre pour la vidéo.',
                'required' => false,
            ])
            ->addTextarea('video_description', [
                'label' => 'Description de la vidéo',
                'instructions' => 'Ajouter une description pour la vidéo.',
                'required' => false,
            ])
            ->endRepeater()
            ->addImage('map_image', [
                'label' => 'Image de la carte',
                'instructions' => 'Ajouter une image pour la section de la carte.',
                'required' => false,
                'preview_size' => 'medium',
                'library' => 'all',
            ]);

        return $fields->build();
    }
}
