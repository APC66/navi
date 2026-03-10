{{-- Chargement du script JS spécifique à ce bloc --}}
@php
  \Roots\bundle('resources/js/blocks/image-carousel.js')->enqueue();
@endphp

<section class="{{ $block->classes }} py-16 md:py-24 overflow-hidden relative bg-primary-1000">

  {{-- Décoration de fond (Vagues) pour correspondre à la maquette --}}
  <div class="absolute inset-0 z-0 pointer-events-none opacity-20 mix-blend-screen">
    <img src="@asset('resources/images/waves.svg')" alt="" class="w-full h-full object-cover">
  </div>

  <div class="relative z-10 w-full container">
    <div class="swiper image-carousel-swiper h-[500px] !overflow-visible" id="{{ $block_id }}">
      <div class="swiper-wrapper flex items-center">
        @if(empty($gallery))
          {{-- Placeholder pour l'éditeur s'il n'y a pas d'image --}}
          <div class="swiper-slide">
            <div class="swiper-material-wrapper transition-filter filter-none">
              <div class="swiper-material-content aspect-video bg-primary-900 border-2 border-dashed border-primary-600 flex items-center justify-center">
                <span class="text-primary-400 font-bold">Sélectionnez des images via ACF</span>
              </div>
            </div>
          </div>
        @else
          @foreach($gallery as $image)
            <div class="swiper-slide">
              <div class="swiper-material-wrapper shadow-2xl">
                <div class="swiper-material-content">
                  <img src="{{ $image['sizes']['large'] ?? $image['url'] }}"
                       alt="{{ $image['alt'] }}"
                       class="w-full h-full object-cover">
                </div>
              </div>
            </div>
          @endforeach
        @endif
      </div>

      {{-- Pagination Style Maquette (Points évidés) --}}
      <div class="swiper-pagination !relative !bottom-0 mt-12 flex justify-center gap-3"></div>
    </div>
  </div>
</section>

<style>
  /* ========================================================
     STYLE SUR MESURE (D'APRÈS LA MAQUETTE)
     ======================================================== */
  /*
    Note: Le module Material You de UI Initiative gère déjà l'échelle (scale)
    et les bordures arrondies de façon dynamique via Javascript.
    On n'ajoute ici que l'assombrissement des slides inactifs.
  */

  /* Assombrissement des slides inactifs pour faire ressortir le central */
  .image-carousel-swiper .swiper-slide:not(.swiper-slide-active) .swiper-material-wrapper {
    filter: brightness(0.3) contrast(1.1);
    transition: filter 0.6s ease;
  }

  /* Restauration de la luminosité pour le slide actif */
  .image-carousel-swiper .swiper-slide-active .swiper-material-wrapper {
    filter: brightness(1) contrast(1);
    transition: filter 0.6s ease;
  }

  /* Pagination (Points transparents avec bordure blanche) */
  .image-carousel-swiper .swiper-pagination-bullet {
    width: 10px;
    height: 10px;
    background: transparent;
    border: 1px solid #ffffff;
    opacity: 0.7;
    margin: 0 !important; /* On gère l'espacement via le parent gap-3 */
    transition: all 0.3s ease;
  }

  /* Point actif (Plein et légèrement plus grand) */
  .image-carousel-swiper .swiper-pagination-bullet-active {
    background: #ffffff;
    border-color: #ffffff;
    opacity: 1;
    transform: scale(1.2);
  }
</style>
