@extends('layouts.app')

@section('content')
    @while(have_posts()) @php the_post() @endphp
    @php
        $cruise = \App\Models\Cruise::find(get_the_ID());
        $headerGroup = get_field('page_header');


        $bgImage = $headerGroup['header_image'] ?? get_the_post_thumbnail_url(null, 'full');

        $title = $headerGroup['header_title'] ?: get_the_title();

        $highlight = $headerGroup['header_highlight'] ?? '';
        $highlightColor = $headerGroup['header_highlight_color'] ?? 'text-secondary';
        $subtitle = $headerGroup['header_subtitle'] ?? '';

        $price = $cruise->base_price;

        $imageTextOverlapGroup = get_field('image_text_overlap');

        $description = get_field('desc_content');
        $gallery = get_field('gallery');
        $videos = get_field('videos');
        $mapImage = get_field('map_image');
        $additionalTabs = get_field('additional_tabs');
    @endphp

    <div class="relative text-white">
        @if($bgImage)
            <div class="w-full min-h-[300px] md:h-[400px] flex items-end overflow-hidden bg-primary-900">
                <img src="{{ $bgImage }}" alt="{{ strip_tags($title) }}" class="w-full h-full object-cover">
            </div>
        @endif
        <div class="relative">
            <img class="absolute inset-0 w-full h-full object-cover" alt="" src="@asset('resources/images/bg-single.jpg')"/>
            <div class="container mx-auto px-4 relative z-20 py-12">
                <div class="mb-10 flex items-center text-primary-200">
                    <a href="{{ home_url('/') }}" class="hover:text-secondary transition-colors">Accueil</a>
                    <span class="px-4">/</span>
                    <a href="{{get_post_type_archive_link('cruise')}}" class="hover:text-secondary transition-colors">Nos Croisières</a>
                    <span class="px-4">/</span>
                    <span class="text-secondary truncate max-w-[200px] md:max-w-none">{!! get_the_title() !!}</span>
                </div>

                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold uppercase font-heading leading-tight">
                        @if($highlight)
                            <span class="{{ $highlightColor }} block md:inline">{!! $highlight !!}</span>
                        @endif
                        {{ $title }}
                        @if($subtitle)
                            <span class="block font-thin text-xl lg:text-2xl lowercase mt-2">{!! $subtitle !!}</span>
                        @endif
                    </h1>

                    @if($price)
                        <div class="flex flex-col flex-shrink-0 md:items-center">
                            <span class="uppercase font-light mb-1">À partir de</span>
                            <div class="text-4xl md:text-5xl font-bold text-secondary leading-none font-merriweather mt-2 mb-4">
                                {{ number_format($price, 2, ',') }}€
                            </div>
                            <x-partials.button title="Réserver" align="center" url="#booking-area"/>
                        </div>
                    @endif
                </div>
            </div>
            @if($imageTextOverlapGroup)
                <x-partials.image-text-overlap :group="$imageTextOverlapGroup" />
            @endif
        </div>
    </div>

    <div class="relative bg-primary-1000">
        <div id="booking-area" class="bg-gray-50 border-t border-gray-200 py-16">
            <div class="container mx-auto px-4 max-w-5xl">
                <x-partials.booking-widget :cruise-id="get_the_ID()" />
            </div>
        </div>

        <div class="container mx-auto px-4 py-12 md:py-20" x-data="{ activeTab: 'description' }">
            <div class="flex flex-wrap overflow-x-auto gap-6 justify-center space-x-2 border-b border-primary-400 pb-4 mb-16" style="scrollbar-width: none; -ms-overflow-style: none;">
                <button @click="activeTab = 'description'"
                        :class="activeTab === 'description' ? 'bg-secondary text-primary-1000' : 'bg-primary-900 text-primary-100'"
                        class="py-2 px-4 transition cursor-pointer rounded-full hover:bg-secondary hover:text-primary-1000 font-bold text-sm tracking-wider">
                    Description
                </button>

                @if(!empty($gallery))
                    <button @click="activeTab = 'gallery'"
                            :class="activeTab === 'gallery' ? 'bg-secondary text-primary-1000' : 'bg-primary-900 text-primary-100'"
                            class="py-2 px-4 transition cursor-pointer rounded-full hover:bg-secondary hover:text-primary-1000 font-bold text-sm tracking-wider">
                        Galerie
                    </button>
                @endif

                @if(!empty($videos))
                    <button @click="activeTab = 'videos'"
                            :class="activeTab === 'videos' ? 'bg-secondary text-primary-1000' : 'bg-primary-900 text-primary-100'"
                            class="py-2 px-5 transition cursor-pointer rounded-full hover:bg-secondary hover:text-primary-1000 font-bold text-sm tracking-wider">
                        Galerie vidéo
                    </button>
                @endif

                @if(!empty($mapImage))
                    <button @click="activeTab = 'map'"
                            :class="activeTab === 'map' ? 'bg-secondary text-primary-1000' : 'bg-primary-900 text-primary-100'"
                            class="py-2 px-4 transition cursor-pointer rounded-full hover:bg-secondary hover:text-primary-1000 font-bold text-sm tracking-wider">
                        Carte
                    </button>
                @endif

                @if(!empty($additionalTabs))
                    @foreach($additionalTabs as $index => $tab)
                        <button @click="activeTab = 'custom-{{ $index }}'"
                                :class="activeTab === 'custom-{{ $index }}' ? 'bg-secondary text-primary-1000' : 'bg-primary-900 text-primary-100'"
                                class="py-2 px-4 transition cursor-pointer rounded-full hover:bg-secondary hover:text-primary-1000 font-bold text-sm tracking-wider">
                            {{ $tab['tab_title'] }}
                        </button>
                    @endforeach
                @endif
            </div>

            <div class="tab-content relative min-h-[300px]">

                <div x-show="activeTab === 'description'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                    @include('partials.single-cruise.tab-description', ['description' => $description])
                </div>

                @if(!empty($gallery))
                    <div x-show="activeTab === 'gallery'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                        @include('partials.single-cruise.tab-gallery', ['gallery' => $gallery])
                    </div>
                @endif

                @if(!empty($videos))
                    <div x-show="activeTab === 'videos'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                        @include('partials.single-cruise.tab-videos', ['videos' => $videos])
                    </div>
                @endif

                @if(!empty($mapImage))
                    <div x-show="activeTab === 'map'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                        @include('partials.single-cruise.tab-map', ['mapImage' => $mapImage])
                    </div>
                @endif

                @if(!empty($additionalTabs))
                    @foreach($additionalTabs as $index => $tab)
                        <div x-show="activeTab === 'custom-{{ $index }}'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                            @include('partials.single-cruise.tab-description', ['description' => $tab['tab_content']])
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
    @endwhile
@endsection
