<?php

namespace App\Blocks\Core;

use Masterminds\HTML5;

class Button
{
    public function __construct()
    {
        add_action('init', [$this, 'modifyButtonBlock'], 20);
    }

    public function modifyButtonBlock()
    {
        $registry = \WP_Block_Type_Registry::get_instance();
        $buttonBlock = $registry->get_registered('core/button');

        if ($buttonBlock) {
            $buttonBlock->attributes['backgroundColor'] = array_merge(
                $buttonBlock->attributes['backgroundColor'] ?? [],
                ['default' => 'black']
            );
            $buttonBlock->attributes['textColor'] = array_merge(
                $buttonBlock->attributes['textColor'] ?? [],
                ['default' => 'white']
            );
        }
    }

    public function render(string $blockContent, array $block): string
    {
        if ($block['blockName'] !== 'core/button') {
            return $blockContent;
        }

        $html5 = new HTML5();
        $dom = $html5->loadHTML($blockContent);
        $anchor = $dom->getElementsByTagName('a')->item(0);

        if (!$anchor) {
            return $blockContent;
        }

        $classes = $anchor->getAttribute('class') ?? '';
        $classes .= ' ' . ($block['attrs']['className'] ?? '');

        $href = $anchor->getAttribute('href') ?? null;
        $rel = $anchor->getAttribute('rel') ?? null;
        $text = $anchor->textContent ?? null;

        $div = $dom->getElementsByTagName('div')->item(0);
        $id = $div ? $div->getAttribute('id') : null;

        $variant = 'primary';
        if (
            isset($block['attrs']['className']) &&
            strpos($block['attrs']['className'], 'is-style-outline') !== false
        ) {
            $variant = 'outline';
        }

        return view('blocks.button', [
            'variant' => $variant,
            'classes' => $classes,
            'href' => $href,
            'id' => $id,
            'rel' => $rel,
            'text' => $text,
        ]);
    }
}
