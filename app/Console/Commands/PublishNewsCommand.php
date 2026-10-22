<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use WP_Post;

/**
 * Publie les actualités rédigées dans le dépôt (resources/content/actualites) :
 * crée les catégories manquantes, puis chaque article absent du site, avec
 * son image à la une (retrouvée par nom de fichier dans la médiathèque) et
 * ses réglages Yoast SEO.
 *
 * Sans risque à relancer : un article déjà présent (même adresse) n'est pas
 * modifié, sauf avec l'option --maj.
 *
 * Utilisation : wp acorn navi:actus-publier [--maj] [--brouillon]
 */
class PublishNewsCommand extends Command
{
    protected $signature = 'navi:actus-publier
        {--maj : Met à jour les articles déjà présents avec le contenu du dépôt (garde leur date)}
        {--brouillon : Crée les nouveaux articles en brouillon au lieu de les publier}';

    protected $description = 'Publie les actualités rédigées dans resources/content/actualites';

    public function handle(): int
    {
        $dir = resource_path('content/actualites');
        $manifest = $dir.'/articles.php';

        if (! is_file($manifest)) {
            $this->error("Fichier introuvable : {$manifest}");

            return self::FAILURE;
        }

        $data = require $manifest;
        $author = $this->authorId($data['author_email'] ?? '');
        wp_set_current_user($author);

        $categories = $this->ensureCategories($data['categories'] ?? []);

        $rows = [];
        // Le premier article de la liste est le plus récent.
        foreach (array_values($data['articles'] ?? []) as $index => $article) {
            $rows[] = $this->publish($article, $dir, $categories, $author, $index);
        }

        $this->table(['Article', 'Action', 'Image à la une', 'Adresse'], $rows);

        return self::SUCCESS;
    }

    /**
     * Auteur des articles : le compte dont l'e-mail est indiqué, sinon le
     * premier administrateur.
     */
    protected function authorId(string $email): int
    {
        $user = $email ? get_user_by('email', $email) : false;
        if ($user) {
            return (int) $user->ID;
        }

        $admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID', 'orderby' => 'ID']);

        return (int) ($admins[0] ?? 1);
    }

    /**
     * @return array<string, int> identifiant de chaque catégorie par slug
     */
    protected function ensureCategories(array $definitions): array
    {
        $ids = [];

        foreach ($definitions as $slug => $category) {
            $term = get_term_by('slug', $slug, 'category');

            if ($term) {
                $ids[$slug] = (int) $term->term_id;
                $this->line("Catégorie déjà présente : {$category['name']}");

                continue;
            }

            $created = wp_insert_term($category['name'], 'category', [
                'slug' => $slug,
                'description' => $category['description'] ?? '',
            ]);

            if (is_wp_error($created)) {
                $this->error("Catégorie « {$category['name']} » non créée : ".$created->get_error_message());

                continue;
            }

            $ids[$slug] = (int) $created['term_id'];
            $this->info("Catégorie créée : {$category['name']}");
        }

        return $ids;
    }

    protected function publish(array $article, string $dir, array $categories, int $author, int $index): array
    {
        $file = $dir.'/'.$article['file'];
        if (! is_file($file)) {
            return [$article['title'], 'ERREUR : fichier '.$article['file'].' introuvable', '-', '-'];
        }

        $existing = get_page_by_path($article['slug'], OBJECT, 'post');
        if ($existing instanceof WP_Post && ! $this->option('maj')) {
            return [$article['title'], 'déjà présent, non modifié', '-', get_permalink($existing)];
        }

        $postarr = [
            'post_type' => 'post',
            'post_title' => $article['title'],
            'post_name' => $article['slug'],
            'post_content' => trim((string) file_get_contents($file)),
            'post_excerpt' => $article['excerpt'] ?? '',
            'post_author' => $author,
            'post_category' => array_values(array_filter([$categories[$article['category']] ?? null])),
            'comment_status' => 'closed',
        ];

        if ($existing instanceof WP_Post) {
            $postarr['ID'] = $existing->ID;
            $postId = wp_update_post(wp_slash($postarr), true);
            $action = 'mis à jour';
        } else {
            $postarr['post_status'] = $this->option('brouillon') ? 'draft' : 'publish';
            // Un écart d'une minute par article fixe l'ordre d'affichage.
            $postarr['post_date'] = wp_date('Y-m-d H:i:s', time() - $index * MINUTE_IN_SECONDS);
            $postId = wp_insert_post(wp_slash($postarr), true);
            $action = $postarr['post_status'] === 'draft' ? 'créé en brouillon' : 'publié';
        }

        if (is_wp_error($postId)) {
            return [$article['title'], 'ERREUR : '.$postId->get_error_message(), '-', '-'];
        }

        $image = $this->attachmentByFilename($article['image'] ?? '');
        if ($image) {
            set_post_thumbnail($postId, $image);
        }

        foreach (['seo_title' => '_yoast_wpseo_title', 'meta_description' => '_yoast_wpseo_metadesc', 'focus_keyword' => '_yoast_wpseo_focuskw'] as $key => $meta) {
            if (! empty($article[$key])) {
                update_post_meta($postId, $meta, $article[$key]);
            }
        }

        return [
            $article['title'],
            $action,
            $image ? '#'.$image : 'INTROUVABLE : '.($article['image'] ?? '-'),
            get_permalink($postId),
        ];
    }

    /**
     * Image de la médiathèque dont le fichier porte ce nom (identifiants
     * différents entre le site local et le site en ligne).
     */
    protected function attachmentByFilename(string $filename): int
    {
        if ($filename === '') {
            return 0;
        }

        global $wpdb;

        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND (meta_value = %s OR meta_value LIKE %s) ORDER BY post_id ASC LIMIT 1",
            $filename,
            '%/'.$wpdb->esc_like($filename)
        ));

        return (int) $id;
    }
}
