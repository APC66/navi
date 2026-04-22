{{--
  Template Name: Liste des Croisières
--}}

@extends('layouts.app')

@section('content')
  @php
    \Roots\bundle('resources/js/components/cruise-filters.js')->enqueue();

    $headerGroup = get_field('cruise_header', 'option');
    $categories = get_terms(['taxonomy' => 'cruise_type', 'hide_empty' => false]);
    $tags = get_terms(['taxonomy' => 'cruise_tag', 'hide_empty' => false]);

    $paged = get_query_var('paged') ? get_query_var('paged') : (get_query_var('page') ? get_query_var('page') : 1);
    $searchQuery = get_query_var('s') ?: null;
    $initialQueryArgs = [
      'post_type' => 'cruise',
      'post_status' => 'publish',
      'posts_per_page' => 12,
      'paged' => $paged,
      'orderby' => 'date',
      'order' => 'DESC',
    ];
    if ($searchQuery) {
      $initialQueryArgs['s'] = $searchQuery;
    }
    $initialQuery = new WP_Query($initialQueryArgs);
  @endphp

  <x-partials.page-header :group="$headerGroup" />

  <div
    class="via-primary-1000 to-primary-1000 relative bg-gradient-to-b from-[#182646] from-20% via-20% pb-32"
    x-data="cruiseFilter(
              {{ $initialQuery->max_num_pages }},
              {{ $initialQuery->found_posts }},
            )"
    data-component="cruise-filters"
  >
    <div id="results-anchor"></div>
    <div class="absolute top-1/5 left-0 w-full -translate-y-1/2">
      <img
        src="@asset('resources/images/waves.svg')"
        alt=""
        class="-z-10 h-auto max-h-[360px] w-full"
      />
    </div>

    <div
      class="relative container mx-auto flex items-center justify-between px-2 py-4"
      data-aos="fade-down"
      data-aos-duration="600"
    >
      <span class="text-sm font-medium tracking-wider text-white">
        <span class="text-primary-100">Résultats :</span>
        <span x-text="totalCount"></span>
        <span x-text="totalCount > 1 ? 'croisières' : 'croisières'"></span>
      </span>
      <button
        @click="filterOpen = true"
        data-aos="fade-left"
        data-aos-delay="200"
        data-aos-duration="600"
        class="hover:text-secondary inline-flex cursor-pointer items-center font-bold tracking-wide transition-colors"
      >
        Filtrer par
        <svg class="ml-2 h-5 w-5" fill="none" stroke="#FFD21F" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
          ></path>
        </svg>
      </button>
    </div>

    {{-- Drawer filtres — animations gérées par Alpine x-transition --}}
    <div
      class="fixed inset-0 z-[60] flex justify-end"
      role="dialog"
      aria-modal="true"
      x-show="filterOpen"
      style="display: none"
    >
      <div
        class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
        x-show="filterOpen"
        x-transition:enter="duration-300 ease-out"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="duration-200 ease-in"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="filterOpen = false"
      ></div>

      <div
        class="from-primary-900 to-primary-1000 relative ml-auto flex h-full w-full max-w-[340px] flex-col overflow-y-auto bg-gradient-to-b p-8 text-white shadow-2xl"
        x-show="filterOpen"
        x-transition:enter="transform transition duration-300 ease-in-out"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition duration-300 ease-in-out"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
      >
        <div class="flex items-center justify-between py-6">
          <h2 class="font-heading text-2xl font-bold tracking-wider uppercase">FILTRES</h2>
          <button
            @click="filterOpen = false"
            class="bg-secondary text-primary-900 rounded-full p-2 transition-colors hover:bg-white"
          >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              ></path>
            </svg>
          </button>
        </div>

        <div class="flex-1 space-y-8 py-4">
          <div>
            <h3 class="mb-4 font-bold tracking-wide uppercase">TRIER PAR</h3>
            <div class="space-y-4">
              <label class="group flex cursor-pointer items-center">
                <input
                  type="radio"
                  name="sort"
                  value="price_asc"
                  x-model="filters.sort"
                  @change="applyFilters(false)"
                  class="peer sr-only"
                />
                <span
                  class="peer-checked:bg-secondary mr-3 flex h-5 w-5 items-center justify-center rounded-sm border-2 border-white bg-white transition-colors peer-checked:border-white"
                ></span>
                <span :class="filters.sort === 'price_asc' ? 'text-white' : 'text-primary-100'">
                  Tarif ordre croissant
                </span>
              </label>
              <label class="group flex cursor-pointer items-center">
                <input
                  type="radio"
                  name="sort"
                  value="price_desc"
                  x-model="filters.sort"
                  @change="applyFilters(false)"
                  class="peer sr-only"
                />
                <span
                  class="peer-checked:bg-secondary mr-3 flex h-5 w-5 items-center justify-center rounded-sm border-2 border-white bg-white transition-colors peer-checked:border-white"
                ></span>
                <span :class="filters.sort === 'price_desc' ? 'text-white' : 'text-primary-100'">
                  Tarif ordre décroissant
                </span>
              </label>
              <label class="group flex cursor-pointer items-center">
                <input
                  type="radio"
                  name="sort"
                  value="title_asc"
                  x-model="filters.sort"
                  @change="applyFilters(false)"
                  class="peer sr-only"
                />
                <span
                  class="peer-checked:bg-secondary mr-3 flex h-5 w-5 items-center justify-center rounded-sm border-2 border-white bg-white transition-colors peer-checked:border-white"
                ></span>
                <span :class="filters.sort === 'title_asc' ? 'text-white' : 'text-primary-100'">
                  Ordre Alphabétique
                </span>
              </label>
            </div>
          </div>

          <div>
            <h3 class="mb-4 font-bold tracking-wide uppercase">FILTRES</h3>
            <div class="flex flex-wrap gap-3">
              @foreach ($tags as $tag)
                <button
                  @click="toggleFilter('tags', {{ $tag->term_id }})"
                  class="rounded-full border-2 px-3 py-1.5 text-sm font-medium transition-all"
                  :class="filters.tags.includes({{ $tag->term_id }}) ? 'bg-secondary text-primary-1000 border-secondary' : 'border-white hover:border-secondary hover:bg-secondary hover:text-primary-1000'"
                >
                  {{ htmlspecialchars_decode($tag->name) }}
                </button>
              @endforeach
            </div>
          </div>

          <div>
            <h3 class="mb-4 font-bold tracking-wide uppercase">CATÉGORIES</h3>
            <div class="divide-primary-600 space-y-4 divide-y">
              <div
                class="flex cursor-pointer items-center justify-between pb-4"
                @click="filters.categories = []; applyFilters(false)"
              >
                <span
                  class="w-[180px]"
                  :class="filters.categories.length === 0 ? 'text-white' : 'text-primary-100'"
                >
                  Toutes les croisières
                </span>
                <div
                  class="relative h-6 w-12 rounded-full transition-colors duration-300"
                  :class="filters.categories.length === 0 ? 'bg-secondary' : 'bg-white'"
                >
                  <span
                    class="bg-primary-1000 absolute top-0.5 left-0.5 h-5 w-5 rounded-full transition-transform duration-300"
                    :class="filters.categories.length === 0 ? 'translate-x-6' : 'translate-x-0'"
                  ></span>
                </div>
              </div>
              @foreach ($categories as $cat)
                <div
                  class="flex cursor-pointer items-center justify-between pb-4"
                  @click="toggleFilter('categories', {{ $cat->term_id }})"
                >
                  <span
                    class="w-[180px]"
                    :class="filters.categories.includes({{ $cat->term_id }}) ? 'text-white' : 'text-primary-100'"
                  >
                    {{htmlspecialchars_decode($cat->name )}}
                  </span>
                  <div
                    class="relative h-6 w-12 rounded-full transition-colors duration-300"
                    :class="filters.categories.includes({{ $cat->term_id }}) ? 'bg-secondary' : 'bg-white'"
                  >
                    <span
                      class="bg-primary-1000 absolute top-0.5 left-0.5 h-5 w-5 rounded-full transition-transform duration-300"
                      :class="filters.categories.includes({{ $cat->term_id }}) ? 'translate-x-6' : 'translate-x-0'"
                    ></span>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="relative container mx-auto min-h-[400px] px-4 py-6">
      {{-- Loader --}}
      <div
        x-show="loading"
        class="absolute inset-0 z-20 flex items-start justify-center bg-white/80 pt-20 backdrop-blur-sm transition-opacity"
      >
        <svg
          class="text-secondary h-10 w-10 animate-spin"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
      </div>

      <div id="cruise-results-container">
        {{-- Grille PHP initiale : x-show remplacé par :style pour que AOS puisse observer l'élément --}}
        <div
          x-ref="phpGrid"
          :style="gridHtml !== '' ? 'display:none' : ''"
          data-aos="fade-up"
          data-aos-duration="700"
          data-aos-delay="100"
        >
          @include('partials.cruise-grid', ['query' => $initialQuery])
        </div>

        {{-- Grille dynamique JS — animée via cruise-filters.js --}}
        <div x-html="gridHtml" x-show="gridHtml !== ''" x-ref="jsGrid"></div>
      </div>

      <div
        class="mt-16 flex justify-center"
        x-show="currentPage < maxPages"
        data-aos="fade-up"
        data-aos-duration="600"
        data-aos-offset="50"
      >
        <button
          @click="loadMore()"
          :disabled="loadingMore"
          class="shadow-button bg-secondary !text-primary-900 inline-flex items-center justify-center rounded-full px-8 py-3 font-bold !no-underline transition-all duration-300 hover:bg-white"
        >
          <span x-show="!loadingMore">Voir plus de croisières</span>
          <span x-show="loadingMore" class="flex items-center">
            <svg
              class="text-primary-900 mr-3 -ml-1 h-5 w-5 animate-spin"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              ></circle>
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              ></path>
            </svg>
            Chargement...
          </span>
        </button>
      </div>
    </div>
  </div>
@endsection
