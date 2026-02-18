<?php

namespace App\View\Components\Partials;

use Roots\Acorn\View\Component;

class PageHeader extends Component
{
    public $image;
    public $title;
    public $highlight;
    public $highlightColor;
    public $subtitle;

    /**
     * Create a new component instance.
     *
     * @param string|null $image
     * @param string $title
     * @param string $highlight
     * @param string $highlightColor
     * @param string $subtitle
     * @param array $group (Optionnel) Groupe ACF contenant les champs
     * @return void
     */
    public function __construct(
        $image = null,
        $title = '',
        $highlight = '',
        $highlightColor = 'text-secondary',
        $subtitle = '',
        $group = []
    ) {
        if (!empty($group)) {
            $image = $image ?: ($group['header_image'] ?? null);
            $title = $title ?: ($group['header_title'] ?: get_the_title());
            $highlight = $highlight ?: ($group['header_highlight'] ?? '');
            $highlightColor = $highlightColor !== 'text-secondary' ? $highlightColor : ($group['header_highlight_color'] ?? 'text-secondary');
            $subtitle = $subtitle ?: ($group['header_subtitle'] ?? '');
        }

        // Fallback ultime pour le titre si aucun groupe n'est passé
        if (empty($title) && empty($group)) {
            $title = get_the_title();
        }

        $this->image = $image;
        $this->title = $title;
        $this->highlight = $highlight;
        $this->highlightColor = $highlightColor;
        $this->subtitle = $subtitle;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return $this->view('components.partials.page-header');
    }
}
