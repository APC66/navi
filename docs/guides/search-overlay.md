# Fenêtre de recherche croisières (Search Overlay)

> Ajoutée le 20/03/2026

## Fonctionnement

Un overlay de recherche live déclenché depuis le bouton loupe du header. Il interroge uniquement le CPT `cruise` via l'API REST et affiche les résultats en temps réel avec debounce.

### Flux utilisateur

1. Clic sur le bouton **loupe** (header haut-droite) → dispatch de l'event Alpine `open-search`
2. L'overlay s'ouvre (animation slide-down) avec focus automatique sur l'input
3. Frappe ≥ 2 caractères → debounce 300ms → requête `GET /wp-json/radicle/v1/search?q=xxx`
4. Résultats affichés : **thumbnail** + **titre** + **port de départ** (taxonomy `harbor`)
5. Clic sur un résultat → redirection vers la fiche croisière
6. Clic sur **"Voir tous les résultats"** → redirection vers l'archive croisières avec `?s=xxx`
7. Fermeture : touche `Escape` ou clic sur le backdrop

---

## Architecture

### Endpoint API

**Route :** `GET /wp-json/radicle/v1/search?q={terme}`

**Contrôleur :** `app/Http/Controllers/Api/SearchController.php` → méthode `liveSearch()`

**Réponse JSON :**
```json
[
  {
    "id": 42,
    "title": "Croisière Méditerranée",
    "url": "https://site.com/croisieres/mediterranee/",
    "thumbnail": "https://site.com/.../image-150x150.jpg",
    "harbor": "Marseille"
  }
]
```

**Paramètres :**
- `q` (string) — terme de recherche, sanitisé via `sanitize_text_field()`, minimum 2 caractères
- Max 6 résultats, triés par pertinence (`orderby=relevance`)
- Endpoint public (pas de nonce requis)

---

### Composant Blade / Alpine

**Fichier :** `resources/views/components/partials/search-overlay.blade.php`

**Usage :** `<x-partials.search-overlay />`

**Composant Alpine :** `x-data="searchOverlay()"` (enregistré via `Alpine.data()` inline)

**State Alpine :**
| Propriété | Type | Description |
|---|---|---|
| `isOpen` | bool | Visibilité de l'overlay |
| `query` | string | Valeur de l'input |
| `results` | array | Résultats de l'API |
| `loading` | bool | État de chargement |
| `archiveUrl` | string | URL archive cruise (rendu PHP) |

**Méthodes Alpine :**
| Méthode | Description |
|---|---|
| `open()` | Ouvre l'overlay, reset le state, focus l'input |
| `close()` | Ferme l'overlay, restaure le scroll |
| `fetchResults()` | Appel fetch vers l'API (déclenché par debounce) |
| `goToArchive()` | Redirige vers l'archive avec `?s=query` via `URL` API |

---

## Fichiers modifiés

| Fichier | Modification |
|---|---|
| `app/Http/Controllers/Api/SearchController.php` | Ajout méthode `liveSearch()` |
| `app/Providers/ApiServiceProvider.php` | Ajout route `GET radicle/v1/search` |
| `app/Providers/ThemeServiceProvider.php` | Hook `template_redirect` : redirige `/search?s=xxx` → archive cruise |
| `resources/views/components/partials/search-overlay.blade.php` | **Nouveau fichier** — composant complet |
| `resources/views/sections/header.blade.php` | Bouton loupe : `@click="$dispatch('open-search')"` + `pointer-events-auto` sur le conteneur |
| `resources/views/layouts/app.blade.php` | Inclusion `<x-partials.search-overlay />` après le header |
| `resources/views/archive-cruise.blade.php` | Lecture de `get_query_var('s')` dans la WP_Query initiale |

---

## Détails techniques

### Pourquoi `template_redirect` ?

WordPress intercepte toutes les URLs avec `?s=` et les redirige vers sa page de recherche native (`/search/...`). Le hook `template_redirect` dans `ThemeServiceProvider` détecte `is_search()` et redirige en **301** vers l'archive cruise :

```php
add_action('template_redirect', function () {
    if (is_search() && ! empty(get_query_var('s'))) {
        $archiveUrl = get_post_type_archive_link('cruise');
        if ($archiveUrl) {
            wp_redirect(add_query_arg('s', get_query_var('s'), $archiveUrl), 301);
            exit;
        }
    }
});
```

### Pourquoi `pointer-events-auto` sur le header ?

Le `<header>` a `pointer-events-none` pour ne pas bloquer les clics sur le contenu de la page en dessous. Le conteneur des boutons (user, panier, loupe) nécessite `pointer-events-auto` pour être cliquable. Le bouton burger fonctionnait déjà car il est dans `navigation.blade.php` qui gère ses propres `pointer-events`.

### Communication Alpine inter-composants

Le bouton loupe et l'overlay sont dans des scopes Alpine différents. La communication se fait via un **custom event** :
- Bouton : `@click="$dispatch('open-search')"`
- Overlay : `@open-search.window="open()"` (écoute sur `window`)

Cela évite tout conflit avec le scope `flyoutMenu` du menu de navigation.
