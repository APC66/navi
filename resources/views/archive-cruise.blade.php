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

        $paged = (get_query_var('paged')) ? get_query_var('paged') : ((get_query_var('page')) ? get_query_var('page') : 1);
        $initialQuery = new WP_Query([
            'post_type' => 'cruise',
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
    @endphp


    <x-partials.page-header :group="$headerGroup" />

    <div
        class="relative from-[#182646] from-20%  via-20% via-primary-1000 to-primary-1000 bg-gradient-to-b pb-32"
        x-data="cruiseFilter({{ $initialQuery->max_num_pages }}, {{ $initialQuery->found_posts }})"
        data-component="cruise-filters"
    >
        <div id="results-anchor"></div>
        <div class="absolute top-1/5 -translate-y-1/2 left-0 w-full">
            <img src="@asset('resources/images/waves.svg')" alt="" class="w-full h-auto max-h-[360px] -z-10">
        </div>
        <div class="relative container mx-auto px-2 py-4 flex justify-between items-center">
            <span class="text-white text-sm font-medium tracking-wider">
                <span class="text-primary-100"> Résultats : </span>
                <span x-text="totalCount"></span>
                <span x-text="totalCount > 1 ? 'croisières' : 'croisières'"></span>
            </span>
            <button @click="filterOpen = true" class="inline-flex items-center font-bold tracking-wide cursor-pointer hover:text-secondary transition-colors">
                Filtrer par
                <svg class="w-5 h-5 ml-2" fill="none" stroke="#FFD21F" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
            </button>
        </div>

        <div class="fixed inset-0 z-[60] flex justify-end" role="dialog" aria-modal="true" x-show="filterOpen" style="display: none;">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" x-show="filterOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="filterOpen = false">
            </div>

            <div class="relative ml-auto flex h-full w-full max-w-[340px] p-8 flex-col overflow-y-auto from-primary-900 to-primary-1000 bg-gradient-to-b shadow-2xl text-white"
                 x-show="filterOpen"
                 x-transition:enter="transform transition ease-in-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full">

                <div class="py-6 flex items-center justify-between">
                    <h2 class="text-2xl font-bold uppercase tracking-wider font-heading">FILTRES</h2>
                    <button @click="filterOpen = false" class="bg-secondary text-primary-900 rounded-full p-2 hover:bg-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="py-4 space-y-8 flex-1">
                    <div>
                        <h3 class="font-bold uppercase mb-4 tracking-wide">TRIER PAR</h3>
                        <div class="space-y-4">
                            <label class="flex items-center cursor-pointer group">
                                <input type="radio"
                                       name="sort"
                                       value="price_asc"
                                       x-model="filters.sort"
                                       @change="applyFilters(false)"
                                       class="sr-only peer"
                                >
                                <span class="w-5 h-5 border-2 border-white bg-white rounded-sm flex items-center justify-center mr-3 peer-checked:bg-secondary peer-checked:border-white transition-colors"></span>
                                <span :class="filters.sort === 'price_asc' ? 'text-white' : 'text-primary-100'"
                                >Tarif ordre croissant</span>
                            </label>

                            <label class="flex items-center cursor-pointer group">
                                <input type="radio"
                                       name="sort"
                                       value="price_desc"
                                       x-model="filters.sort"
                                       @change="applyFilters(false)"
                                       class="sr-only peer"

                                >
                                <span class="w-5 h-5 border-2 border-white bg-white rounded-sm flex items-center justify-center mr-3 peer-checked:bg-secondary peer-checked:border-white transition-colors"></span>
                                <span :class="filters.sort === 'price_desc' ? 'text-white' : 'text-primary-100'"
                                >Tarif ordre décroissant</span>
                            </label>

                            <label class="flex items-center cursor-pointer group">
                                <input
                                    type="radio"
                                    name="sort"
                                    value="title_asc"
                                    x-model="filters.sort"
                                    @change="applyFilters(false)"
                                    class="sr-only peer"
                                >
                                <span class="w-5 h-5 border-2 border-white bg-white rounded-sm flex items-center justify-center mr-3 peer-checked:bg-secondary peer-checked:border-white transition-colors"></span>
                                <span :class="filters.sort === 'title_asc' ? 'text-white' : 'text-primary-100'"
                                >Ordre Alphabétique</span>
                            </label>
                        </div>
                    </div>

                    {{-- 2. TAGS (Filtres) --}}
                    <div>
                        <h3 class="font-bold uppercase mb-4 tracking-wide">FILTRES</h3>
                        <div class="flex flex-wrap gap-3">
                            @foreach($tags as $tag)
                                <button
                                    @click="toggleFilter('tags', {{ $tag->term_id }})"
                                    class="px-3 py-1.5 rounded-full border-2 text-sm font-medium transition-all"
                                    :class="filters.tags.includes({{ $tag->term_id }}) ? 'bg-secondary text-primary-1000 border-secondary' : 'border-white hover:border-secondary hover:bg-secondary hover:text-primary-1000'"
                                >
                                    {{ $tag->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- 3. CATÉGORIES --}}
                    <div>
                        <h3 class="font-bold uppercase mb-4 tracking-wide">CATÉGORIES</h3>
                        <div class="space-y-4 divide-y divide-primary-600">
                            <div class="flex items-center justify-between cursor-pointer pb-4" @click="filters.categories = []; applyFilters(false)">
                                <span
                                    class="w-[180px]"
                                    :class="filters.categories.length === 0 ? 'text-white' : 'text-primary-100'"
                                >Toutes les croisières</span>
                                <div class="w-12 h-6 rounded-full relative transition-colors duration-300" :class="filters.categories.length === 0 ? 'bg-secondary' : 'bg-white'">
                                    <span class="absolute top-0.5 left-0.5 bg-primary-1000 w-5 h-5 rounded-full transition-transform duration-300" :class="filters.categories.length === 0 ? 'translate-x-6' : 'translate-x-0'"></span>
                                </div>
                            </div>

                            @foreach($categories as $cat)
                                <div class="flex items-center justify-between cursor-pointer pb-4" @click="toggleFilter('categories', {{ $cat->term_id }})">
                                    <span class="w-[180px]"
                                          :class="filters.categories.includes({{ $cat->term_id }}) ? 'text-white' : 'text-primary-100'"
                                    >{{ $cat->name }}</span>
                                    <div class=" w-12 h-6 rounded-full relative transition-colors duration-300"
                                         :class="filters.categories.includes({{ $cat->term_id }}) ? 'bg-secondary' : 'bg-white'">
                                        <span class="absolute top-0.5 left-0.5 bg-primary-1000 w-5 h-5 rounded-full transition-transform duration-300"
                                              :class="filters.categories.includes({{ $cat->term_id }}) ? 'translate-x-6' : 'translate-x-0'"></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container mx-auto px-4 py-6 min-h-[400px] relative">

            {{-- Loader --}}
            <div x-show="loading" class="absolute inset-0 z-20 bg-white/80 backdrop-blur-sm flex items-start justify-center pt-20 transition-opacity">
                <svg class="animate-spin h-10 w-10 text-secondary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>

            <div id="cruise-results-container">
                <div x-show="gridHtml === ''" x-ref="phpGrid">
                    @include('partials.cruise-grid', ['query' => $initialQuery])
                </div>

                <div x-html="gridHtml" x-show="gridHtml !== ''" x-ref="jsGrid"></div>
            </div>

            <div class="mt-16 flex justify-center" x-show="currentPage < maxPages">
                <button
                    @click="loadMore()"
                    :disabled="loadingMore"
                    class="btn-secondary px-8 py-3 rounded-full flex items-center disabled:opacity-50"
                >
                    <span x-show="!loadingMore">Voir plus de croisières</span>
                    <span x-show="loadingMore" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Chargement...
                    </span>
                </button>
            </div>
        </div>
    </div>

@endsection
