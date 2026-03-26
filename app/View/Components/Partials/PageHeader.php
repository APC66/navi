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

    public $showTitle;

    public function __construct(
        $image = null,
        $title = '',
        $highlight = '',
        $highlightColor = 'text-secondary',
        $subtitle = '',
        $group = [],
        $showTitle = false,
    ) {
        $group = is_array($group) ? $group : [];

        if (! empty($group)) {
            $image = $image ?: ($group['header_image'] ?? null);
            $title = $title ?: ($group['header_title'] ?? '');
            $highlight = $highlight ?: ($group['header_highlight'] ?? '');
            $highlightColor = $highlightColor !== 'text-secondary'
                ? $highlightColor
                : ($group['header_highlight_color'] ?? 'text-secondary');
            $subtitle = $subtitle ?: ($group['header_subtitle'] ?? '');
            $showTitle = $showTitle ?: ($group['show_title'] ?? false);
        }

        // Fallback titre
        if (empty($title)) {
            $title = get_the_title() ?: __('Page sans titre');
        }

        // Fallback highlightColor
        $highlightColor = $highlightColor ?: 'text-secondary';

        // Fallback image
        if (empty($image)) {
            $image = \Roots\asset('resources/images/bg-default.jpg');
        }

        $this->image = $image;
        $this->title = $title;
        $this->highlight = $highlight;
        $this->highlightColor = $highlightColor;
        $this->subtitle = $subtitle;
        $this->showTitle = $showTitle;
    }

    public function render()
    {
        return $this->view('components.partials.page-header');
    }
}
