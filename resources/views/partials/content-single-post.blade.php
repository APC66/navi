{{--
  Page d'une actualité, sur le modèle des pages croisières : bannière avec
  l'image à la une, bandeau titre texturé, contenu sur fond bleu nuit, puis
  carousel « Nos autres actualités ».
--}}
<article @php(post_class('h-entry text-white'))>
  {{-- Bannière : image à la une, avec le texte alternatif de la médiathèque --}}
  <div class="bg-primary-900 relative h-[260px] w-full overflow-hidden md:h-[420px]">
    @if (has_post_thumbnail())
      {!! get_the_post_thumbnail(null, 'full', [
        'class' => 'h-full w-full object-cover',
        'loading' => 'eager',
        'fetchpriority' => 'high',
        'sizes' => '100vw',
      ]) !!}
    @else
      <img src="@asset('resources/images/bg-default.jpg')" alt="" fetchpriority="high" class="h-full w-full object-cover" />
    @endif
  </div>

  {{-- Bandeau titre --}}
  <header class="relative">
    <img class="absolute inset-0 h-full w-full object-cover" alt="" src="@asset('resources/images/bg-single.jpg')" />

    <div class="relative z-20 container mx-auto py-10 md:py-12">
      <nav aria-label="Fil d'Ariane" class="text-primary-200 mb-8 md:mb-10">
        <ol class="flex items-center">
          <li><a href="{{ home_url('/') }}" class="hover:text-secondary transition-colors">Accueil</a></li>
          <li class="px-4" aria-hidden="true">/</li>
          <li><a href="{{ $postsPageUrl }}" class="hover:text-secondary transition-colors">{!! $postsPageTitle !!}</a></li>
          <li class="px-4" aria-hidden="true">/</li>
          <li class="text-secondary max-w-[160px] truncate sm:max-w-[320px] md:max-w-none" aria-current="page">{!! $title !!}</li>
        </ol>
      </nav>

      <div class="mb-6 flex flex-wrap items-center gap-x-4 gap-y-3 text-sm">
        @foreach ($categories as $category)
          <a
            href="{{ $category['url'] }}"
            class="text-primary-900 hover:bg-secondary rounded-full bg-white/90 px-3 py-1 text-xs font-bold transition-colors"
          >
            {!! $category['name'] !!}
          </a>
        @endforeach

        <time class="dt-published font-elms text-secondary" datetime="{{ get_post_time('c', true) }}">
          {{ get_the_date() }}
        </time>

        @if ($readingTime)
          <span class="text-primary-200 inline-flex items-center gap-1.5">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="12" cy="12" r="9" stroke-width="2"></circle>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7v5l3 2"></path>
            </svg>
            {{ $readingTime }} min de lecture
          </span>
        @endif
      </div>

      <h1 class="p-name font-heading max-w-5xl text-3xl leading-tight font-bold uppercase md:text-4xl lg:text-5xl">
        {!! $title !!}
      </h1>

      @if (has_excerpt() && ! post_password_required())
        <p class="p-summary border-secondary mt-8 max-w-3xl border-l-4 pl-6 text-lg leading-relaxed font-light md:text-xl">
          {{ html_entity_decode(get_the_excerpt(), ENT_QUOTES, 'UTF-8') }}
        </p>
      @endif
    </div>
  </header>

  {{-- Contenu, aligné sous le titre, et encart de réservation à droite --}}
  <div class="bg-primary-1000 relative">
    <div class="container mx-auto grid grid-cols-1 gap-12 py-12 md:py-20 lg:grid-cols-[minmax(0,1fr)_300px] xl:gap-16">
      <div class="min-w-0 max-w-3xl">
        <div class="news-content e-content">
          @php(the_content())
        </div>

        {{-- Article découpé en plusieurs pages (saut de page de l'éditeur) --}}
        @if ($pagination())
          <nav class="news-content mt-10" aria-label="Pages de l'article">
            {!! $pagination !!}
          </nav>
        @endif

        {{-- Retour à la liste et partage --}}
        <footer
          class="border-primary-600 mt-16 flex flex-col gap-6 border-t pt-8 md:flex-row md:items-center md:justify-between"
        >
          <a
            href="{{ $postsPageUrl }}"
            class="text-secondary inline-flex items-center gap-2 font-bold transition-colors hover:text-white"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Toutes les actualités
          </a>

          <div class="flex flex-wrap items-center gap-3" x-data="{ copied: false, failed: false }">
            <span class="text-primary-200 basis-full text-sm font-medium sm:basis-auto">Partager&nbsp;:</span>

            @foreach ($share['links'] as $link)
              <a
                href="{{ $link['href'] }}"
                @if ($link['new_tab']) target="_blank" rel="noopener noreferrer" @endif
                class="bg-primary-900 hover:bg-secondary hover:text-primary-1000 flex h-10 w-10 items-center justify-center rounded-full text-white transition-colors"
              >
                <span class="sr-only">{{ $link['new_tab'] ? 'Partager sur '.$link['label'].' (nouvel onglet)' : 'Partager par '.$link['label'] }}</span>
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="{{ $link['icon'] }}"></path>
                </svg>
              </a>
            @endforeach

            <button
              type="button"
              class="bg-primary-900 hover:bg-secondary hover:text-primary-1000 flex h-10 w-10 cursor-pointer items-center justify-center rounded-full text-white transition-colors"
              @click="(navigator.clipboard ? navigator.clipboard.writeText(@js($share['url'])) : Promise.reject()).then(() => { failed = false; copied = true; setTimeout(() => copied = false, 2000) }).catch(() => { failed = true; setTimeout(() => failed = false, 6000) })"
            >
              <span class="sr-only">Copier le lien de l'article</span>
              <svg x-show="! copied" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14a4 4 0 005.7 0l3-3a4 4 0 00-5.7-5.7l-1 1M14 10a4 4 0 00-5.7 0l-3 3a4 4 0 005.7 5.7l1-1"></path>
              </svg>
              <svg x-show="copied" style="display: none" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
            </button>
            <span class="sr-only" role="status" x-text="copied ? 'Lien copié' : ''"></span>
            <p x-show="failed" style="display: none" class="text-primary-200 basis-full text-xs" role="status">
              Copie impossible : sélectionnez l'adresse de la page dans la barre du navigateur.
            </p>
          </div>
        </footer>
      </div>

      {{-- Sur ordinateur, l'encart suit le défilement de la page --}}
      <aside aria-label="Réserver une croisière" class="lg:sticky lg:top-28 lg:self-start">
        <div class="from-primary-900 to-primary-1000 rounded-[20px] bg-gradient-to-b p-6 shadow-lg" data-aos="fade-left" data-aos-duration="600">
          <h2 class="mb-3 text-lg font-bold tracking-wider uppercase">Envie de prendre la mer&nbsp;?</h2>
          <p class="text-primary-100 mb-6 text-sm leading-relaxed font-light">
            Croisières en catamaran à voile au départ de Canet-en-Roussillon et de Port-Vendres&nbsp;: coucher de soleil, dauphins, baignade, escale à Collioure.
          </p>
          <x-partials.button title="Réserver une croisière" :url="$bookingUrl" align="full" />
          <x-partials.button title="Offrir une carte cadeau" :url="$giftCardUrl" variant="outline" align="full" class="mt-3" />
        </div>
      </aside>
    </div>
  </div>

  {{-- Autres actualités : même bloc que sur l'accueil --}}
  @if ($related->count() >= 3)
    @include('blocks.carousel-actu', [
      'block' => (object) ['classes' => ''],
      'is_preview' => false,
      'has_enough' => true,
      'background' => 'bg-primary-900 text-white',
      'bg_image' => asset('resources/images/bg-carousel.jpg'),
      // Carousel en bas de page : son image de fond ne doit pas passer avant la bannière.
      'bg_lazy' => true,
      'title_group' => [
        'highlight' => 'Nos',
        'suffix' => 'autres actualités',
        'highlight_break' => false,
        'highlight_color' => 'text-secondary',
        'tag' => 'h2',
        'align' => 'text-center',
        'size' => 'M',
      ],
      'posts' => $related,
      'cta' => [
        'title' => 'Voir toutes nos actus',
        'url' => $postsPageUrl,
        'target' => '_self',
        'variant' => 'secondary',
        'align' => 'center',
      ],
      'block_id' => 'carousel-actu-related-'.get_the_ID(),
    ])
  @endif
</article>
