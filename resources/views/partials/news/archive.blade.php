{{-- Liste des actualités : filtre des catégories à gauche, cartes à droite --}}
<x-partials.page-header
  :group="$header['group']"
  :title="$header['title']"
  :subtitle="$header['subtitle']"
  :show-title="$header['showTitle']"
  :parent-title="$header['parentTitle']"
  :parent-url="$header['parentUrl']"
/>

@unless ($header['showTitle'])
  <h1 class="sr-only">{!! $header['title'] !!}</h1>
@endunless

<div
  class="to-primary-1000 relative bg-gradient-to-b from-[#182646] pb-24 md:pb-32"
>
  <div class="relative container mx-auto pt-4 md:pt-8">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-[260px_minmax(0,1fr)] lg:gap-12">
      @include('partials.news.categories')

      <div>
        <p class="mb-6 text-sm font-medium tracking-wider text-white" data-aos="fade-down" data-aos-duration="600">
          <span class="text-primary-100">Résultats :</span>
          {{ $resultsCount }} {{ $resultsCount > 1 ? 'actualités' : 'actualité' }}
        </p>

        @if (have_posts())
          <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 xl:grid-cols-3">
            @php($index = 0)
            @while (have_posts())
              @php(the_post())
              @include('partials.news.card', ['index' => $index++])
            @endwhile
          </div>

          @include('partials.news.pagination')
        @else
          <div class="rounded-[20px] border border-white/10 bg-white/5 px-6 py-20 text-center">
            <p class="font-merriweather mb-4 text-xl font-bold text-white">
              Aucune actualité pour le moment.
            </p>
            @unless ($allActive)
              <a href="{{ $allUrl }}" class="text-secondary font-bold underline hover:text-white">
                Voir toutes les actualités
              </a>
            @endunless
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
