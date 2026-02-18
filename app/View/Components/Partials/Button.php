<?php

namespace App\View\Components\Partials;

use Roots\Acorn\View\Component;

class Button extends Component
{
    public string $title;

    public string $url;

    public string $target;

    public string $variant;

    public string $align;

    public string $variantClasses;

    public string $alignClasses;

    /**
     * Create a new component instance.
     */
    public function __construct(
        $title = '',
        $url = '',
        $target = '_self',
        $variant = 'secondary',
        $align = 'left',
        $group = []
    ) {
        if (! empty($group)) {
            $title = $title ?: ($group['title'] ?? '');
            $url = $url ?: ($group['url'] ?? '');
            $target = $target !== '_self' ? $target : ($group['target'] ?? '_self');
            $variant = $variant !== 'secondary' ? $variant : ($group['variant'] ?? 'secondary');
            $align = $align !== 'left' ? $align : ($group['align'] ?? 'left');
        }

        $this->title = $title;
        $this->url = $url;
        $this->target = $target;
        $this->variant = $variant;
        $this->align = $align;

        // Vos classes spécifiques (conservées)
        $this->variantClasses = match ($variant) {
            'outline' => 'border-2 border-secondary text-secondary hover:bg-white hover:text-primary-900',
            default => 'bg-secondary text-primary-900 hover:bg-white',
        };

        $this->alignClasses = match ($align) {
            'center' => 'w-max mr-auto ml-0 md:mx-auto',
            'right' => 'w-max ml-auto',
            'full' => 'w-full',
            default => 'mr-auto', // Left
        };
    }

    public function render()
    {
        return $this->view('components.partials.button');
    }
}
