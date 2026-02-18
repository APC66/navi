<?php

namespace App\View\Components\Partials;

use Roots\Acorn\View\Component;

class SubHeader extends Component
{
    /**
     * Le sous-titre en majuscules (ex: "À VOS ENVIES...")
     */
    public string $subtitle;

    /**
     * Le contenu riche (WYSIWYG)
     */
    public string $content;

    /**
     * L'alignement du texte
     */
    public string $align;

    /**
     * Create a new component instance.
     *
     * @param string $subtitle
     * @param string $content
     * @param string $align
     * @return void
     */
    public function __construct($subtitle = '', $content = '', $align = 'text-center')
    {
        $this->subtitle = $subtitle;
        $this->content = $content;
        $this->align = $align;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return $this->view('components.partials.sub-header');
    }
}
