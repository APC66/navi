<section class="{{ $block->classes }} {{ $background }} {{ $margins }} text-white">
  <div class="mx-auto max-w-4xl px-4">
    <div class="mb-8 text-center lg:mb-12" data-aos="fade-up" data-aos-duration="600">
      @if ($main_title || $main_subtitle)
        <h2 class="mb-2 text-xl leading-loose font-bold tracking-wide uppercase lg:text-3xl">
          <span class="text-secondary">
            @if ($main_title)
              {{ $main_title }}
            @endif
          </span>
          @if ($main_subtitle)
            <br />
            <p class="mt-2">{{ $main_subtitle }}</p>
          @endif
        </h2>
      @endif
    </div>

    @if ($icon && $text_icon)
      <div
        class="mb-8 flex flex-col items-center justify-center sm:flex-row"
        data-aos="fade-up"
        data-aos-duration="600"
        data-aos-delay="150"
      >
        <img src="{{ $icon }}" alt="Icon" class="h-32 w-32" />
        <div class="contentText ml-0 lg:ml-4">
          {!! $text_icon !!}
        </div>
      </div>
    @endif

    @if ($content)
      <div
        class="contentText mx-auto leading-relaxed lg:text-base"
        data-aos="fade-up"
        data-aos-duration="600"
        data-aos-delay="200"
      >
        {!! $content !!}
      </div>
    @endif

    @if ($cta && $cta['title'])
      <div
        class="mt-8 text-center lg:mt-12"
        data-aos="fade-up"
        data-aos-duration="600"
        data-aos-delay="300"
      >
        <x-partials.button :group="$cta" />
      </div>
    @endif
  </div>
</section>
