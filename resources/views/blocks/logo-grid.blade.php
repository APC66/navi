<section class="{{ $block->classes }} py-16">
  <div class="container mx-auto px-4">

    @if ($title_group)
      <div
        class="mb-12 text-center"
        data-aos="fade-up"
        data-aos-duration="600"
      >
        <x-partials.section-header :group="$title_group" />
      </div>
    @endif

    @if ($logos)
      <div class="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:grid-cols-4 items-center">
        @foreach ($logos as $index => $item)
          @php $hasLink = !empty($item['link']); @endphp

          @if ($hasLink)
          <a
            href="{{ $item['link'] }}"
            target="_blank"
            rel="noopener noreferrer"
            class="flex items-center justify-center transition-opacity hover:opacity-70"
            data-aos="fade-up"
            data-aos-duration="500"
            data-aos-delay="{{ ($index % 4) * 100 }}"
            >
          @else
            <div
              class="flex items-center justify-center"
              data-aos="fade-up"
              data-aos-duration="500"
              data-aos-delay="{{ ($index % 4) * 100 }}"
            >
              @endif

              @if (!empty($item['logo']))
                <img
                  src="{{ $item['logo']['url'] }}"
                  alt="{{ $item['logo']['alt'] ?: $item['logo']['title'] }}"
                  width="{{ $item['logo']['width'] }}"
                  height="{{ $item['logo']['height'] }}"
                  class="max-h-48 w-full object-contain"
                />
                @endif

                @if ($hasLink)
                  </a>
              @else
            </div>
          @endif
        @endforeach
      </div>
    @endif

  </div>
</section>