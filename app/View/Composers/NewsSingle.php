<?php

namespace App\View\Composers;

use Illuminate\Support\Collection;
use Roots\Acorn\View\Composer;
use WP_Query;
use WP_Term;

/**
 * Données de la page d'une actualité : catégories, temps de lecture, liens de
 * partage, retour vers la liste et actualités à découvrir en bas de page.
 */
class NewsSingle extends Composer
{
    /**
     * Mots lus par minute pour estimer le temps de lecture.
     */
    const WORDS_PER_MINUTE = 200;

    protected static $views = [
        'partials.content-single-post',
    ];

    public function with(): array
    {
        $postId = (int) get_the_ID();
        $postsPageId = (int) get_option('page_for_posts');
        $categories = $this->categories($postId);

        return [
            'categories' => $categories,
            'readingTime' => $this->readingTime($postId),
            'postsPageTitle' => $postsPageId ? get_the_title($postsPageId) : __('Actualités', 'radicle'),
            'postsPageUrl' => $postsPageId ? (string) get_permalink($postsPageId) : home_url('/'),
            'share' => $this->shareLinks($postId),
            'related' => $this->related($postId, $categories),
            'bookingUrl' => $this->pageUrlByTemplate('template-planning.blade.php', '/reserver-promenade-en-mer/'),
            'giftCardUrl' => $this->pageUrlByTemplate('template-gift-card.blade.php', '/carte-cadeau-croisiere-pyrenees-orientales/'),
        ];
    }

    /**
     * Adresse de la page qui utilise ce modèle (planning, carte cadeau), pour
     * suivre un éventuel changement d'adresse de la page.
     */
    protected function pageUrlByTemplate(string $template, string $fallbackPath): string
    {
        $pages = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => 1,
            'fields' => 'ids',
            'meta_key' => '_wp_page_template',
            'meta_value' => $template,
        ]);

        return $pages ? (string) get_permalink($pages[0]) : home_url($fallbackPath);
    }

    /**
     * Catégories de l'actualité, hors catégorie par défaut (« Non classé »).
     *
     * @return array<int, array{id: int, name: string, url: string}>
     */
    protected function categories(int $postId): array
    {
        return collect(get_the_category($postId))
            ->reject(fn (WP_Term $term) => in_array($term->slug, NewsArchive::HIDDEN_CATEGORY_SLUGS, true))
            ->map(fn (WP_Term $term) => [
                'id' => (int) $term->term_id,
                'name' => $term->name,
                'url' => (string) get_term_link($term),
            ])
            ->values()
            ->all();
    }

    /**
     * Minutes de lecture, ou null tant que le mot de passe d'un article protégé
     * n'a pas été saisi.
     */
    protected function readingTime(int $postId): ?int
    {
        if (post_password_required($postId)) {
            return null;
        }

        $text = wp_strip_all_tags(strip_shortcodes((string) get_post_field('post_content', $postId)));
        $words = count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY));

        return max(1, (int) ceil($words / self::WORDS_PER_MINUTE));
    }

    /**
     * Liens de partage simples (aucun script tiers chargé sur la page).
     */
    protected function shareLinks(int $postId): array
    {
        $url = (string) get_permalink($postId);
        $title = html_entity_decode(get_the_title($postId), ENT_QUOTES, 'UTF-8');

        return [
            'url' => $url,
            'links' => [
                [
                    'label' => 'Facebook',
                    'new_tab' => true,
                    'href' => 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($url),
                    'icon' => 'M14 8h3V4h-3c-2.8 0-4 1.7-4 4v2H8v4h2v8h4v-8h3l1-4h-4V8.5c0-.3.2-.5.5-.5H14z',
                ],
                [
                    'label' => 'LinkedIn',
                    'new_tab' => true,
                    'href' => 'https://www.linkedin.com/sharing/share-offsite/?url='.rawurlencode($url),
                    'icon' => 'M4 9h4v12H4zM6 3a2 2 0 110 4 2 2 0 010-4zm4 6h3.8v1.7h.1c.5-1 1.8-2 3.7-2 4 0 4.4 2.6 4.4 6V21h-4v-5.6c0-1.3 0-3-1.9-3s-2.1 1.4-2.1 2.9V21h-4z',
                ],
                [
                    'label' => 'WhatsApp',
                    'new_tab' => true,
                    'href' => 'https://wa.me/?text='.rawurlencode($title.' '.$url),
                    'icon' => 'M12 3a9 9 0 00-7.8 13.5L3 21l4.6-1.2A9 9 0 1012 3zm0 16.4a7.4 7.4 0 01-3.8-1l-.3-.2-2.7.7.7-2.6-.2-.3A7.4 7.4 0 1112 19.4zm4-5.5c-.2-.1-1.3-.7-1.5-.7-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1a6 6 0 01-3-2.6c-.2-.4.2-.4.6-1.2.1-.2 0-.3 0-.4l-.7-1.6c-.2-.4-.4-.4-.5-.4h-.4a.8.8 0 00-.6.3 2.5 2.5 0 00-.8 1.9 4.4 4.4 0 00.9 2.3 10 10 0 003.8 3.4c1.4.6 2 .7 2.7.6.4-.1 1.3-.5 1.5-1s.2-1 .1-1z',
                ],
                [
                    'label' => 'e-mail',
                    'new_tab' => false,
                    'href' => 'mailto:?subject='.rawurlencode($title).'&body='.rawurlencode($title."\n".$url),
                    'icon' => 'M3 5h18a1 1 0 011 1v12a1 1 0 01-1 1H3a1 1 0 01-1-1V6a1 1 0 011-1zm1 2.2V17h16V7.2l-8 5.3zM5.3 7l6.7 4.4L18.7 7z',
                ],
            ],
        ];
    }

    /**
     * Actualités à découvrir : d'abord celles des mêmes catégories, puis les
     * plus récentes, jusqu'à 6. Format attendu par le bloc « Carousel Actu ».
     */
    protected function related(int $postId, array $categories): Collection
    {
        $limit = 6;
        $ids = [];

        $categoryIds = array_column($categories, 'id');
        if ($categoryIds) {
            $ids = (new WP_Query([
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'post__not_in' => [$postId],
                'category__in' => $categoryIds,
                'ignore_sticky_posts' => true,
                'no_found_rows' => true,
                'fields' => 'ids',
            ]))->posts;
        }

        if (count($ids) < $limit) {
            $ids = array_merge($ids, (new WP_Query([
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => $limit - count($ids),
                'post__not_in' => array_merge([$postId], $ids),
                'ignore_sticky_posts' => true,
                'no_found_rows' => true,
                'fields' => 'ids',
            ]))->posts);
        }

        // Chargement groupé des articles et de leurs images (évite une requête par carte).
        if ($ids) {
            _prime_post_caches($ids, false, true);
            $thumbnailIds = array_filter(array_map('get_post_thumbnail_id', $ids));
            if ($thumbnailIds) {
                _prime_post_caches($thumbnailIds, false, true);
            }
        }

        return collect($ids)->map(fn ($id) => [
            'title' => html_entity_decode(get_the_title($id), ENT_QUOTES, 'UTF-8'),
            'permalink' => get_permalink($id),
            'thumbnail_url' => get_the_post_thumbnail_url($id, 'large') ?: '',
            'date' => get_the_date('', $id),
        ]);
    }
}
