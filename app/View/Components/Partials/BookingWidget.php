<?php

namespace App\View\Components\Partials;

use Roots\Acorn\View\Component;
use function Roots\bundle;

class BookingWidget extends Component
{
    /**
     * L'ID de la croisière parente
     */
    public int $cruiseId;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($cruiseId = null)
    {
        $this->cruiseId = $cruiseId ?: get_the_ID();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        asset('resources/js/components/booking-widget.js')->uri();
        wp_enqueue_script('booking-widget', asset('resources/js/components/booking-widget.js')->uri(), [], null, true);

        return $this->view('components.partials.booking-widget');
    }
}
