<?php

namespace App\Providers;

use App\Blocks\Core\Button;
use App\Blocks\Modal;
use Illuminate\Support\ServiceProvider;

class BlocksServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        /**
         * Render `core/button` block with Blade template
         */
        add_filter('render_block', [new Button, 'render'], 10, 2);

        /**
         * Render `radicle/modal` block with Blade template
         */
        add_filter('render_block', [new Modal, 'render'], 10, 2);

    }
}
