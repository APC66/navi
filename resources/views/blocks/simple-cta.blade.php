<section class="{{ $block->classes }} {{ $background }} py-12 md:py-20">
  <div class="container mx-auto px-4 text-center">
    @if ($title_group)
      <div class="mx-auto mb-6 max-w-3xl">
        <x-partials.section-header :group="$title_group" />
      </div>
    @endif

    {{-- Boutons alignés --}}
    @if ($button_1 || $button_2)
      <div class="mt-8 flex flex-col items-center justify-center gap-4 md:flex-row">
        @if ($button_1 && ! empty($button_1['url']))
          <x-partials.button :group="$button_1" />
        @endif

        @if ($button_2 && ! empty($button_2['url']))
          <x-partials.button :group="$button_2" />
        @endif
      </div>
    @endif
  </div>
</section>
