[Voici le **Project Context Prompt** final, prêt à être copié-collé en tête de n'importe quelle session :

---

## 📋 PROJECT CONTEXT PROMPT — NAVI (Radicle / Sage 10)

**Stack :** PHP 8.3, Laravel/Acorn, Blade, Alpine.js, Tailwind CSS, ACF Pro, WooCommerce, Vite.

---

### 🏗️ ARCHITECTURE BRANCHÉE

**`app/`**
- `Providers/` → `ThemeServiceProvider` (boot global, menus, whitelist blocs Gutenberg), `BlocksServiceProvider` (render `core/button` + `radicle/modal`), `ApiServiceProvider` (REST namespace `radicle/v1`)
- `Blocks/` → 9 blocs ACF Composer : `HeroVideo`, `CruiseCarousel`, `Reassurance`, `TextImage`, `TextImagesCards`, `SimpleCta`, `ImageCarousel`, `LatestSeeds`, `Modal` + `Core/Button`
- `Fields/` → `AcfCruiseInfos`, `AcfSailingDetails`, `OptionsFooter`, `OptionsArchiveCruise`, `PageSettings`, `SingleCruisePage` + Partials réutilisables (`ButtonFields`, `SectionHeaderFields`, `PageHeaderFields`, `IntroContentFields`, `ImageTextOverlapField`)
- `Models/` → `Cruise`, `Sailing`, `Post`, `PostMeta`, `Seed` (Eloquent + WP_Query hybride)
- `Services/` → `CruiseManagement` (générateur batch de départs, sync WC), `WoocommerceBridge` (panier, quotas, remboursements, avoirs), `ReservationService` (dispo + calcul prix)
- `Admin/` → `BoardingListPage` (CRUD réservations, export CSV, reprogrammation, avoirs coupon), `CalendarPage`
- `View/Composers/` → `App`, `FrontPage`, `Post`, `Comments` | `View/Components/` → `GlobalPlanning`
- `Http/Controllers/Api/` → `PlanningController`, `CalendarController`, `SearchController`, `CancellationController`, `SeedController`, `CartController`

**`resources/views/`**
- Templates : `front-page`, `archive-cruise`, `single-cruise`, `template-gutenberg`, `template-planning`
- `blocks/` : 1 vue Blade par bloc ACF
- `components/partials/` : `booking-widget`, `page-header`, `image-text-overlap`, `intro-content`, `section-header`
- `components/global-planning.blade.php` : planning hebdomadaire Alpine.js (filtres port/type/tag, datepicker, légende statuts)
- `admin/` : `boarding-list`, `boarding-edit`, `calendar`

**`config/`**
- `post-types.php` : CPT `cruise` + `sailing`, taxonomies `harbor`, `cruise_type`, `cruise_tag`, `passenger_type`, `extra_option_type`, `sailing_status`
- `sailing.php` : **source de vérité unique** des statuts (Dispo, Limité, Reporté, Annulé, Complet) — partagée PHP→JS via `wp_add_inline_script` → `window.SailingConfig`

---

### 📐 CONVENTIONS FIXÉES

| Élément | Convention |
|---|---|
| **Blocs ACF Composer** | `app/Blocks/PascalCase.php` → vue `resources/views/blocks/kebab-case.blade.php` |
| **Fields ACF Composer** | `app/Fields/AcfPascalCase.php` (CPT) ou `app/Fields/Partials/PascalCaseFields.php` (réutilisables) |
| **Blade Components** | `app/View/Components/PascalCase.php` → `<x-kebab-case />` |
| **Blade Partials** | `resources/views/components/partials/` → `<x-partials.kebab-case />` |
| **API REST** | Namespace `radicle/v1`, controllers dans `app/Http/Controllers/Api/` |
| **Blocs Gutenberg** | Whitelist stricte dans `ThemeServiceProvider` (uniquement `acf/*`), Gutenberg actif uniquement sur home + template `template-gutenberg.blade.php` |
| **Statuts sailing** | Taxonomie `sailing_status` + `config/sailing.php` comme source unique (ne jamais hardcoder les couleurs/labels ailleurs) |

---

### ✅ FONCTIONNALITÉS OPÉRATIONNELLES

1. CPT `cruise` + `sailing` avec taxonomies complètes
2. Générateur de départs batch (ACF → `CruiseManagement::generateSailingsFromBatch`)
3. Widget de réservation (`booking-widget` Alpine.js ↔ API `radicle/v1/planning/week`)
4. Sync WooCommerce : produit virtuel créé/mis à jour à la sauvegarde d'une croisière
5. Gestion des quotas passagers + options (check dispo, compteurs `booked_count`)
6. Planning global (`<x-global-planning>` ↔ API `planning/week`)
7. Liste d'embarquement admin (CRUD, reprogrammation, remboursement, avoir coupon, export CSV)
8. API Annulation (`analyze` + `confirm` + `reschedule`, protégée `edit_posts`)
9. Archive croisières : filtres Alpine.js (catégorie, tag, tri) + pagination "load more"
10. Single croisière : onglets (description, galerie, vidéos, carte, tabs custom), croisières similaires

---

### 🚧 RESTE À CODER AVANT LIVRAISON

- [ ] **Page de confirmation post-checkout** : vue récap réservation après paiement WooCommerce
- [ ] **Logique `booking_cutoff`** : le champ ACF existe (minutes avant départ) mais `ReservationService::checkAvailability` ne le vérifie pas encore
- 
]
