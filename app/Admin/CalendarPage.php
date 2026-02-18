<?php

namespace App\Admin;

use function Roots\asset;
use function Roots\view;

class CalendarPage
{
    protected $pageSlug = 'navi-planning';


    public function __invoke(): void
    {
        add_menu_page(
            'Planning des Croisières',
            'Planning',
            'edit_posts',
            $this->pageSlug,
            [$this, 'render'],
            'dashicons-calendar-alt',
            25
        );

        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function render(): void
    {
        echo view('admin.calendar')->render();
    }

    public function enqueueAssets(): void
    {
        $screen = get_current_screen();

        if (! $screen || strpos($screen->id, $this->pageSlug) === false) {
            return;
        }

        $manifestPath = public_path('build/manifest.json');
        $manifest = json_decode(file_get_contents($manifestPath), true);
        $entry = 'resources/js/admin/calendar.js';
        if (isset($manifest[$entry])) {
            $file = $manifest[$entry]['file'];
            wp_enqueue_script('calendar.js', asset($file)->uri(), null, null, true);
        }

        $config = [
            'apiUrl' => rest_url('radicle/v1/calendar/events'),
            'nonce' => wp_create_nonce('wp_rest'),
        ];

        wp_add_inline_script(
            'calendar',
            'window.NaviCalendarConfig = '.wp_json_encode($config).';',
            'before'
        );

        wp_add_inline_script('common', 'window.NaviCalendarConfig = '.json_encode($config).';', 'after');

    }
}
