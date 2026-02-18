<?php

namespace App\View\Components\Partials;

use Roots\Acorn\View\Component;

class SectionHeader extends Component
{
    public string $suffix;
    public string $highlight;
    public bool $highlightBreak; // Nouveau paramètre
    public string $highlightColor;
    public string $align;
    public string $tag;
    public string $sizeClass;

    public function __construct(
        $suffix = '',
        $highlight = '',
        $highlightBreak = false,
        $highlightColor = 'text-secondary',
        $align = 'text-center md:text-left',
        $tag = 'h2',
        $size = 'L',
        $group = []
    ) {
        if (!empty($group)) {
            $suffix = $suffix ?: ($group['suffix'] ?? '');
            $highlight = $highlight ?: ($group['highlight'] ?? '');
            $highlightBreak = $highlightBreak ?: ($group['highlight_break'] ?? false);
            $highlightColor = $highlightColor !== 'text-secondary' ? $highlightColor : ($group['highlight_color'] ?? 'text-secondary');
            $align = $align !== 'text-center md:text-left' ? $align : ($group['align'] ?? 'text-center md:text-left');
            $tag = $tag !== 'h2' ? $tag : ($group['tag'] ?? 'h2');
            $size = $size !== 'L' ? $size : ($group['size'] ?? 'L');
        }

        $this->tag = $tag;
        $this->suffix = $suffix;
        $this->highlight = $highlight;
        $this->highlightBreak = $highlightBreak;
        $this->highlightColor = $highlightColor;
        $this->align = $align;

        $this->sizeClass = match($size) {
            'S' => 'text-2xl md:text-3xl',
            'M' => 'text-3xl md:text-4xl',
            'XL' => 'text-5xl md:text-6xl',
            default => 'text-4xl md:text-5xl',
        };
    }

    public function render()
    {
        return $this->view('components.partials.section-header');
    }
}
