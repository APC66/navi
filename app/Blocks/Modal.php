<?php

namespace App\Blocks;

class Modal
{
    public function render(string $blockContent, array $block): string
    {
        if ($block['blockName'] !== 'radicle/modal') {
            return $blockContent;
        }

        return view('blocks.modal', [
            'block' => $block,
            'blockContent' => $blockContent,
            'buttonText' => $block['attrs']['buttonText'] ?? null,
            'heading' => $block['attrs']['heading'] ?? null,
        ]);
    }
}
