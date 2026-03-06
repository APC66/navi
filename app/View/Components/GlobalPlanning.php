<?php

namespace App\View\Components;

use Roots\Acorn\View\Component;

class GlobalPlanning extends Component
{
    public $types;

    public $ports;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        // On récupère les taxonomies ici pour alléger la vue
        $this->types = get_terms(['taxonomy' => 'cruise_type', 'hide_empty' => false]);
        $this->ports = get_terms(['taxonomy' => 'harbor', 'hide_empty' => false]);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        $handle = 'global-planning-js';

        // 1. On charge le script JS via l'URL générée par Vite/Acorn
        wp_enqueue_script(
            $handle,
            asset('resources/js/components/global-planning.js')->uri(),
            [],
            null,
            true
        );

        // 2. On encode la configuration PHP en JSON
        $config = json_encode(config('sailing.statuses'));

        // 3. On injecte le script INLINE juste "avant" le chargement du fichier JS
        wp_add_inline_script($handle, "window.SailingConfig = {$config};", 'before');

        return $this->view('components.global-planning');
    }
}
