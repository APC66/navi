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
    @endphp

    <div class="relative">
        @if($bgImage)
            <div class="w-full min-h-[300px] md:h-[400px] flex items-end overflow-hidden bg-primary-900">
                <img src="{{ $bgImage }}" alt="{{ strip_tags($title) }}" class="w-full h-full object-cover">
            </div>
        @endif
        <div class="relative">
            <img class="absolute inset-0 w-full h-full object-cover" alt="" src="@asset('resources/images/bg-single.jpg')"/>
            <div class="container mx-auto px-4 relative z-20 py-12">
                <div class="mb-10 flex items-center">
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
                            <x-partials.button title="Réserver" align="center" url="#cruise-calendar"/>
                        </div>
                    @endif
                </div>
            </div>
            @if($imageTextOverlapGroup)
                <x-partials.image-text-overlap :group="$imageTextOverlapGroup" />
            @endif
        </div>
    </div>

    {{-- WIDGET DE RÉSERVATION (Pleine largeur) --}}
    <div id="booking-area" class="bg-gray-50 border-t border-gray-200 py-16">
        <div class="container mx-auto px-4">
            <x-partials.booking-widget :cruise-id="get_the_ID()" />
        </div>
    </div>

    @endwhile
@endsection
