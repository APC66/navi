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
        asset('resources/js/components/global-planning.js')->uri();
        wp_enqueue_script('booking-widget', asset('resources/js/components/global-planning.js')->uri(), [], null, true);

        return $this->view('components.global-planning');
    }
}
