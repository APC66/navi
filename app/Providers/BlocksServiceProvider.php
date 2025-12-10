<?php

namespace App\Providers;

use App\Blocks\Core\Button;
use App\Blocks\Modal;
use App\Blocks\LatestSeeds;
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
        add_filter('render_block', [new Button(), 'render'], 10, 2);

        /**
         * Render `radicle/modal` block with Blade template
         */
        add_filter('render_block', [new Modal(), 'render'], 10, 2);

        /**
         * Register and render `radicle/latest-seeds` block
         */
        add_action('init', function () {
            register_block_type('radicle/latest-seeds', [
                'api_version' => 3,
                'attributes' => [
                    'posts' => [
                        'type' => 'number',
                        'default' => 5,
                    ],
                    'displayPostContent' => [
                        'type' => 'string',
                        'default' => 'none',
                    ],
                    'postLayout' => [
                        'type' => 'string',
                        'default' => 'list',
                    ],
                    'displayFeaturedImage' => [
                        'type' => 'boolean',
                        'default' => false,
                    ],
                ],
                'render_callback' => [new LatestSeeds(), 'render'],
            ]);
        });
    }
}
