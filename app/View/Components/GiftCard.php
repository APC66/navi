<?php

namespace App\View\Components;

use Roots\Acorn\View\Component;

class GiftCard extends Component
{
    /**
     * Liste des croisières publiées
     */
    public array $cruises;

    /**
     * Email de l'acheteur connecté (si disponible)
     */
    public string $buyerEmail;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $query = new \WP_Query([
            'post_type' => 'cruise',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $this->cruises = array_map(function ($post) {
            $basePrice = get_field('base_price', $post->ID);

            return [
                'id' => $post->ID,
                'title' => get_the_title($post->ID),
                'base_price' => $basePrice ? floatval($basePrice) : null,
            ];
        }, $query->posts);

        // Email de l'acheteur connecté pour la checkbox "M'envoyer à moi-même"
        $currentUser = wp_get_current_user();
        $this->buyerEmail = ($currentUser && $currentUser->ID > 0)
            ? $currentUser->user_email
            : '';
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return $this->view('components.gift-card');
    }
}
