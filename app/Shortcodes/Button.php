<?php

namespace App\Shortcodes;

class Button
{
    public function register(): void
    {
        add_shortcode('button', [$this, 'render']);
    }

    public function render($atts): string
    {
        $atts = shortcode_atts([
            'label' => '',
            'url' => '#',
            'style' => 'primary',
            'target' => '_self',
        ], $atts, 'button');

        return view('partials.custom-button', $atts)->render();
    }
}
