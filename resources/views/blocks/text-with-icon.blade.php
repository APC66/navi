<section class="{{ $block->classes }} {{ $background }} {{ $margins }} text-white">
  <div class="max-w-4xl mx-auto px-4">
    <div class="mb-8 text-center lg:mb-12">
      @if ($main_title || $main_subtitle)
        <h2 class="mb-2 text-xl font-bold uppercase leading-loose tracking-wide lg:text-3xl">
          <span class="text-secondary">
            @if ($main_title)
                {{ $main_title }}
            @endif
          </span>
          @if ($main_subtitle)
            <br>
            <p class="mt-2">
              {{ $main_subtitle }}
            </p>
          @endif
        </h2>
      @endif
    </div>
    @if ($icon && $text_icon)
      <div class="mb-8 flex flex-col sm:flex-row items-center justify-center">
        <img src="{{ $icon }}" alt="Icon" class="h-32 w-32" />
        <div class="contentText ml-0 lg:ml-4">
          {!! $text_icon !!}
        </div>
      </div>
    @endif

    {{-- Contenu principal --}}
    @if ($content)
      <div class="contentText mx-auto leading-relaxed lg:text-base">
        {!! $content !!}
      </div>
    @endif

    {{-- CTA --}}
    @if ($cta && $cta['title'])
      <div class="mt-8 text-center lg:mt-12">
        <x-partials.button :group="$cta" />
      </div>
    @endif
  </div>
</section>
