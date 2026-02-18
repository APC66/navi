<?php

namespace App\View\Components\Partials;

use Roots\Acorn\View\Component;

class ImageTextOverlap extends Component
{
    public $imageFront;
    public $imageBack;
    public $title;
    public $content;
    public $invert; // Pour mettre l'image à droite si besoin

    /**
     * Create a new component instance.
     *
     * @param string|int $imageFront ID ou URL de l'image principale
     * @param string|int $imageBack ID ou URL de l'image de fond (décalée)
     * @param string $title Titre
     * @param string $content Contenu WYSIWYG
     * @param bool $invert Inverser la disposition (Image à droite)
     * @param array $group Données ACF (pour usage futur en bloc)
     * @return void
     */
    public function __construct(
        $imageFront = null,
        $imageBack = null,
        $title = '',
        $content = '',
        $invert = false,
        $group = []
    ) {
        // Hydratation via le groupe ACF si présent (pour le futur bloc)
        if (!empty($group)) {
            $imageFront = $imageFront ?: ($group['image_front'] ?? null);
            $imageBack = $imageBack ?: ($group['image_back'] ?? null);
            $title = $title ?: ($group['title'] ?? '');
            $content = $content ?: ($group['content'] ?? '');
            $invert = $invert ?: ($group['invert'] ?? false);
        }

        $this->imageFront = is_numeric($imageFront) ? wp_get_attachment_image_url($imageFront, 'large') : $imageFront;
        $this->imageBack = is_numeric($imageBack) ? wp_get_attachment_image_url($imageBack, 'large') : $imageBack;

        $this->imageBack = $this->imageBack ?: $this->imageFront;

        $this->title = $title;
        $this->content = $content;
        $this->invert = $invert;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return $this->view('components.partials.image-text-overlap');
    }
}
