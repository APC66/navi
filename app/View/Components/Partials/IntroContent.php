<?php

namespace App\View\Components\Partials;

use Roots\Acorn\View\Component;

class IntroContent extends Component
{
    public $icon;
    public $title;
    public $content;

    /**
     * Create a new component instance.
     *
     * @param string|null $icon
     * @param string $title
     * @param string $content
     * @param array $group
     */
    public function __construct($icon = null, $title = '', $content = '', $group = [])
    {
        if (!empty($group)) {
            $icon = $icon ?: ($group['intro_icon'] ?? null);
            $title = $title ?: ($group['intro_title'] ?? '');
            $content = $content ?: ($group['intro_content'] ?? '');
        }

        $this->icon = $icon;
        $this->title = $title;
        $this->content = $content;
    }

    public function render()
    {
        return $this->view('components.partials.intro-content');
    }
}
