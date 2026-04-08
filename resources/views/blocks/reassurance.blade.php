<section
  class="{{ $block->classes }} bg-primary-1000 group dark-section relative overflow-hidden pt-30 pb-40"
>
  @if ($bg_image)
    <div class="absolute inset-0 z-0">
      <img
        src="{{ $bg_image }}"
        alt=""
        class="h-full w-full object-cover opacity-30 mix-blend-overlay"
      />
      <div
        class="from-primary-1000 to-primary-1000 absolute inset-0 bg-gradient-to-b via-transparent opacity-80"
      ></div>
    </div>
  @endif

  <div class="relative z-10 container mx-auto px-4">
    <div class="mb-16 text-center">
      @if ($title_group)
        <x-partials.section-header :group="$title_group" :is-dark="true" />
      @endif

      @if ($subheader)
        <x-partials.sub-header
          :subtitle="$subheader['subtitle']"
          :content="$subheader['content']"
        />
      @endif
    </div>

    @if ($items)
      @php
        $cols = min(count($items), 6);
        $gridClass = match ($cols) {
          1 => 'lg:grid-cols-1',
          2 => 'lg:grid-cols-2',
          3 => 'lg:grid-cols-3',
          4 => 'lg:grid-cols-4',
          5 => 'lg:grid-cols-5',
          default => 'grid-cols-6',
        };
      @endphp

      <div class="{{ $gridClass }} grid grid-cols-2 gap-x-8 gap-y-12 md:grid-cols-3">
        @foreach ($items as $item)
          <div
            class="group/item flex flex-col items-center text-center transition-transform duration-300 hover:-translate-y-1"
          >
            @if ($item['icon'])
              <div class="mb-4 flex h-24 w-24 items-center justify-center">
                <img
                  src="{{ $item['icon'] }}"
                  alt=""
                  class="max-h-full max-w-full object-contain brightness-0 invert filter"
                />
              </div>
            @endif

            <h4
              class="text-secondary font-heading mb-2 max-w-[14ch] text-center text-sm font-bold tracking-wider text-balance uppercase md:text-base"
            >
              {{ $item['title'] }}
            </h4>

            <p class="text-sm leading-relaxed text-white">
              {!! nl2br(e($item['text'])) !!}
            </p>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</section>
