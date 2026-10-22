{{-- Carte d'une actualité (dans la boucle WordPress). Toute la carte est cliquable. --}}
@php
  $category = collect(get_the_category())
    ->first(fn ($term) => ! in_array($term->slug, \App\View\Composers\NewsArchive::HIDDEN_CATEGORY_SLUGS, true));
  // Texte brut : les entités (&#8217;...) sont décodées puis échappées par Blade.
  $excerpt = html_entity_decode(wp_trim_words(get_the_excerpt(), 24, '…'), ENT_QUOTES, 'UTF-8');
  $delay = (($index ?? 0) % 3) * 100;
@endphp

<article
  @php(post_class('group relative flex h-full flex-col overflow-hidden rounded-[20px] shadow-lg transition-shadow duration-300 hover:shadow-xl'))
  data-aos="fade-up"
  data-aos-duration="600"
  data-aos-delay="{{ $delay }}"
>
  <div class="bg-primary-800 relative aspect-[4/3] overflow-hidden">
    @if (has_post_thumbnail())
      {!! get_the_post_thumbnail(null, 'medium_large', [
        'class' => 'h-full w-full object-cover transition-transform duration-700 group-hover:scale-105',
        'alt' => '',
        'loading' => 'lazy',
        // Largeur réelle de la carte selon la grille (sidebar 260 px, 2 puis 3 colonnes)
        'sizes' => '(min-width: 1440px) 340px, (min-width: 1280px) calc((100vw - 420px) / 3), (min-width: 1024px) calc((100vw - 388px) / 2), (min-width: 640px) calc((100vw - 80px) / 2), calc(100vw - 48px)',
      ]) !!}
    @else
      <div class="text-primary-400 flex h-full w-full items-center justify-center">
        <svg class="h-12 w-12 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
          ></path>
        </svg>
      </div>
    @endif

    @if ($category)
      <span
        class="text-primary-900 pointer-events-none absolute top-4 left-4 z-20 rounded-full bg-white/90 px-3 py-1 text-xs font-bold shadow-xl backdrop-blur"
      >
        {!! $category->name !!}
      </span>
    @endif
  </div>

  <div class="from-primary-1000 to-primary-900 flex flex-1 flex-col bg-gradient-to-b p-6 text-white">
    <time class="font-elms text-secondary mb-2 text-sm" datetime="{{ get_the_date('c') }}">
      {{ get_the_date() }}
    </time>

    <h2 class="font-heading mb-3 text-xl leading-snug font-bold uppercase">
      {{-- Lien étendu à toute la carte --}}
      <a href="{{ get_permalink() }}" class="group-hover:text-secondary transition-colors after:absolute after:inset-0 after:z-10">
        {!! get_the_title() !!}
      </a>
    </h2>

    @if ($excerpt)
      <p class="text-primary-100 mb-6 text-sm leading-relaxed font-light">{{ $excerpt }}</p>
    @endif

    <span
      class="text-secondary mt-auto inline-flex items-center gap-2 text-sm font-bold"
      aria-hidden="true"
    >
      Lire l'article
      <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
      </svg>
    </span>
  </div>
</article>
