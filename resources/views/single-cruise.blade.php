@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php
      the_post();
    @endphp

    @php
      $cruise = \App\Models\Cruise::find(get_the_ID());
      $headerGroup = get_field('page_header');

      $bgImage = $headerGroup['header_image'] ?? get_the_post_thumbnail_url(null, 'full');


      $title = $headerGroup['header_title'] ?? get_the_title();


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

      $relatedPosts = get_posts([
          'post_type'      => 'cruise',
          'posts_per_page' => 8,
          'orderby'        => 'rand',
          'post__not_in'   => [get_the_ID()],
          'post_status'    => 'publish',
      ]);


      $relatedCruises = collect(array_map(function($p) {
          return \App\Models\Cruise::find($p->ID);
      }, $relatedPosts));

    @endphp

    <div class="relative text-white">
      @if ($bgImage)
        <div
          class="bg-primary-900 flex min-h-[300px] w-full items-end overflow-hidden md:h-[400px]"
        >
          <img
            src="{{ $bgImage }}"
            alt="{{ strip_tags($title) }}"
            class="h-full w-full object-cover"
          />
        </div>
      @endif

      <div class="relative">
        <img
          class="absolute inset-0 h-full w-full object-cover"
          alt=""
          src="@asset('resources/images/bg-single.jpg')"
        />
        <div class="relative z-20 container mx-auto px-4 py-12">
          <div class="text-primary-200 mb-10 flex items-center">
            <a href="{{ home_url('/') }}" class="hover:text-secondary transition-colors">
              Accueil
            </a>
            <span class="px-4">/</span>
            <a
              href="{{ get_post_type_archive_link('cruise') }}"
              class="hover:text-secondary transition-colors"
            >
              Nos Croisières
            </a>
            <span class="px-4">/</span>
            <span class="text-secondary max-w-[200px] truncate md:max-w-none">
              {!! get_the_title() !!}
            </span>
          </div>

          <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
            <h1
              class="font-heading text-3xl leading-tight font-bold uppercase md:text-4xl lg:text-5xl"
            >
              @if ($highlight)
                <span class="{{ $highlightColor }} block md:inline">{!! $highlight !!}</span>
              @endif

              {{ $title }}
              @if ($subtitle)
                <span class="mt-2 block text-xl font-thin lowercase lg:text-2xl">
                  {!! $subtitle !!}
                </span>
              @endif
            </h1>

            @if ($price)
              <div class="flex flex-shrink-0 flex-col md:items-center">
                <span class="mb-1 font-light uppercase">À partir de</span>
                <div
                  class="text-secondary font-merriweather mt-2 mb-4 text-4xl leading-none font-bold md:text-5xl"
                >
                  {{ number_format($price, 2, ',') }}€
                </div>
                <x-partials.button title="Réserver" align="center" url="#booking-area" />
              </div>
            @endif
          </div>
        </div>
        @if ($imageTextOverlapGroup)
          <x-partials.image-text-overlap :group="$imageTextOverlapGroup" />
        @endif
      </div>
    </div>

    <div class="bg-primary-1000 relative">
      <img
        src="@asset('resources/images/bg-widget.jpg')"
        class="absolute z-0 h-[690px] w-full object-cover"
      />
      <img
        src="@asset('resources/images/waves.svg')"
        class="absolute top-[690px] z-0 h-auto w-full -translate-y-1/2"
      />
      <div id="booking-area" class="bg-primary-1000 py-16">
        <div class="container mx-auto max-w-5xl px-4">
          <x-partials.booking-widget :cruise-id="get_the_ID()" />
        </div>
      </div>
      <div
        class="relative z-10 container mx-auto px-4 py-12 md:py-20"
        x-data="{ activeTab: 'description' }"
      >
        <div
          class="border-primary-400 mb-16 flex flex-wrap justify-center gap-x-10 space-x-2 gap-y-4 overflow-x-auto border-b pb-4"
          style="scrollbar-width: none; -ms-overflow-style: none"
        >
          <button
            @click="activeTab = 'description'"
            :class="activeTab === 'description' ? 'bg-secondary text-primary-1000' : 'bg-primary-900 text-primary-100'"
            class="hover:bg-secondary hover:text-primary-1000 cursor-pointer rounded-full px-4 py-2 text-sm font-bold tracking-wider transition"
          >
            Description
          </button>

          @if (! empty($gallery))
            <button
              @click="activeTab = 'gallery'"
              :class="activeTab === 'gallery' ? 'bg-secondary text-primary-1000' : 'bg-primary-900 text-primary-100'"
              class="hover:bg-secondary hover:text-primary-1000 cursor-pointer rounded-full px-4 py-2 text-sm font-bold tracking-wider transition"
            >
              Galerie
            </button>
          @endif

          @if (! empty($videos))
            <button
              @click="activeTab = 'videos'"
              :class="activeTab === 'videos' ? 'bg-secondary text-primary-1000' : 'bg-primary-900 text-primary-100'"
              class="hover:bg-secondary hover:text-primary-1000 cursor-pointer rounded-full px-5 py-2 text-sm font-bold tracking-wider transition"
            >
              Galerie vidéo
            </button>
          @endif

          @if (! empty($mapImage))
            <button
              @click="activeTab = 'map'"
              :class="activeTab === 'map' ? 'bg-secondary text-primary-1000' : 'bg-primary-900 text-primary-100'"
              class="hover:bg-secondary hover:text-primary-1000 cursor-pointer rounded-full px-4 py-2 text-sm font-bold tracking-wider transition"
            >
              Carte
            </button>
          @endif

          @if (! empty($additionalTabs))
            @foreach ($additionalTabs as $index => $tab)
              <button
                @click="activeTab = 'custom-{{ $index }}'"
                :class="activeTab === 'custom-{{ $index }}' ? 'bg-secondary text-primary-1000' : 'bg-primary-900 text-primary-100'"
                class="hover:bg-secondary hover:text-primary-1000 cursor-pointer rounded-full px-4 py-2 text-sm font-bold tracking-wider transition"
              >
                {{ $tab['tab_title'] }}
              </button>
            @endforeach
          @endif
        </div>

        <div class="tab-content relative min-h-[300px]">
          <div
            x-show="activeTab === 'description'"
            x-transition:enter="transition duration-300 ease-out"
            x-transition:enter-start="translate-y-2 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
          >
            @include('partials.single-cruise.tab-description', ['description' => $description])
          </div>

          @if (! empty($gallery))
            <div
              x-show="activeTab === 'gallery'"
              style="display: none"
              x-transition:enter="transition duration-300 ease-out"
              x-transition:enter-start="translate-y-2 opacity-0"
              x-transition:enter-end="translate-y-0 opacity-100"
            >
              @include('partials.single-cruise.tab-gallery', ['gallery' => $gallery])
            </div>
          @endif

          @if (! empty($videos))
            <div
              x-show="activeTab === 'videos'"
              style="display: none"
              x-transition:enter="transition duration-300 ease-out"
              x-transition:enter-start="translate-y-2 opacity-0"
              x-transition:enter-end="translate-y-0 opacity-100"
            >
              @include('partials.single-cruise.tab-videos', ['videos' => $videos])
            </div>
          @endif

          @if (! empty($mapImage))
            <div
              x-show="activeTab === 'map'"
              style="display: none"
              x-transition:enter="transition duration-300 ease-out"
              x-transition:enter-start="translate-y-2 opacity-0"
              x-transition:enter-end="translate-y-0 opacity-100"
            >
              @include('partials.single-cruise.tab-map', ['mapImage' => $mapImage])
            </div>
          @endif

          @if (! empty($additionalTabs))
            @foreach ($additionalTabs as $index => $tab)
              <div
                x-show="activeTab === 'custom-{{ $index }}'"
                style="display: none"
                x-transition:enter="transition duration-300 ease-out"
                x-transition:enter-start="translate-y-2 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
              >
                @include('partials.single-cruise.tab-description', ['description' => $tab['tab_content']])
              </div>
            @endforeach
          @endif
        </div>
      </div>

      @if($relatedCruises->isNotEmpty())
        @include('blocks.cruise-carousel', [
            'block' => clone (object) ['classes' => ''],
            'bg_image' => asset('resources/images/bg-carousel.jpg'),
            'title_group' => [
                'prefix' => 'Nos',
                'highlight' => 'Nos',
                'suffix' => 'autres croisières',
                'highlight_break' => false,
                'highlight_color' => 'text-white',
                'tag' => 'h2',
                'align' => 'text-center',
                'size' => 'M'
            ],
            'cruises' => $relatedCruises,
            'cta' => null,
            'block_id' => 'cruise-carousel-related-' . uniqid()
        ])
      @endif
    </div>

  @endwhile
@endsection
