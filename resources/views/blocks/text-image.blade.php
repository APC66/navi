<section class="{{ $block->classes }} {{ $background }} {{ $margins }}">
  <div class="container mx-auto px-4">
    <div class="grid grid-cols-1 items-center gap-4 lg:grid-cols-2 lg:gap-24">
      {{-- Colonne image --}}
      <div
        class="{{ $invert ? 'lg:order-first' : 'lg:order-last' }} relative"
        data-aos="{{ $invert ? 'fade-right' : 'fade-left' }}"
        data-aos-duration="700"
      >
        @if ($image)
          <div class="group relative overflow-hidden">
            <img src="{{ $image }}" alt="" class="h-auto w-full object-cover" />
          </div>
        @endif
      </div>

      {{-- Colonne texte --}}
      <div
        class="{{ $invert ? 'lg:pl-10' : 'lg:pr-10' }}"
        data-aos="{{ $invert ? 'fade-left' : 'fade-right' }}"
        data-aos-duration="700"
        data-aos-delay="150"
      >
        @if ($title_group)
          <x-partials.section-header :group="$title_group" align="text-left" />
        @endif

        <x-partials.intro-content :group="$intro_group" />

        @if ($cta)
          <div class="mt-8">
            <x-partials.button :group="$cta" />
          </div>
        @endif
      </div>
    </div>
  </div>
</section>
