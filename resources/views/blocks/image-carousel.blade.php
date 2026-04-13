{{-- Chargement du script JS spécifique à ce bloc --}}
@php
  \Roots\bundle('resources/js/blocks/image-carousel.js')->enqueue();
@endphp

<section class="{{ $block->classes }} bg-primary-1000 relative overflow-hidden py-16 md:py-24">
  <div class="pointer-events-none absolute inset-0 z-0 opacity-20 mix-blend-screen">
    <img src="@asset('resources/images/waves.svg')" alt="" class="h-full w-full object-cover" />
  </div>

  <div class="relative z-10 container w-full" data-aos="fade-up" data-aos-duration="700">
    <div class="swiper image-carousel-swiper h-[500px] !overflow-visible" id="{{ $block_id }}">
      <div class="swiper-wrapper flex items-center">
        @if (empty($gallery))
          <div class="swiper-slide">
            <div class="swiper-material-wrapper transition-filter filter-none">
              <div
                class="swiper-material-content bg-primary-900 border-primary-600 flex aspect-video items-center justify-center border-2 border-dashed"
              >
                <span class="text-primary-400 font-bold">Sélectionnez des images via ACF</span>
              </div>
            </div>
          </div>
        @else
          @foreach ($gallery as $image)
            <div class="swiper-slide">
              <div class="swiper-material-wrapper shadow-2xl">
                <div class="swiper-material-content">
                  <img
                    src="{{ $image['sizes']['large'] ?? $image['url'] }}"
                    alt="{{ $image['alt'] }}"
                    class="h-full w-full object-cover"
                  />
                </div>
              </div>
            </div>
          @endforeach
        @endif
      </div>

      <div class="swiper-pagination !relative !bottom-0 mt-12 flex justify-center gap-3"></div>
    </div>
  </div>
</section>

<style>
  .image-carousel-swiper .swiper-slide:not(.swiper-slide-active) .swiper-material-wrapper {
    filter: brightness(0.3) contrast(1.1);
    transition: filter 0.6s ease;
  }

  .image-carousel-swiper .swiper-slide-active .swiper-material-wrapper {
    filter: brightness(1) contrast(1);
    transition: filter 0.6s ease;
  }

  .image-carousel-swiper .swiper-pagination-bullet {
    width: 10px;
    height: 10px;
    background: transparent;
    border: 1px solid #ffffff;
    opacity: 0.7;
    margin: 0 !important;
    transition: all 0.3s ease;
  }

  .image-carousel-swiper .swiper-pagination-bullet-active {
    background: #ffffff;
    border-color: #ffffff;
    opacity: 1;
    transform: scale(1.2);
  }
</style>
