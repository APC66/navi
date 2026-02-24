<div class="bg-primary-900 relative w-full overflow-hidden py-16 md:py-24">
  <div class="relative z-10 container mx-auto px-4">
    <div
      class="{{ $invert ? 'lg:flex-row-reverse' : '' }} flex flex-col items-center gap-12 lg:flex-row lg:gap-20"
    >
      <div class="group perspective-1000 relative w-full lg:w-1/4">
        <div class="relative mx-auto aspect-[4/5] w-full">
          @if ($imageFront)
            <div
              class="rounded-card absolute inset-0 z-10 max-w-[280px] translate-x-16 translate-y-4 rotate-8 transform overflow-hidden shadow-2xl transition-transform duration-700 group-hover:rotate-6"
            >
              <img
                src="{{ $imageFront }}"
                alt="{{ strip_tags($title) }}"
                class="h-full w-full object-cover"
              />
            </div>
          @endif

          @if ($imageBack)
            <div
              class="rounded-card absolute inset-0 z-0 max-w-[280px] -rotate-8 transform overflow-hidden shadow-2xl transition-transform duration-700 group-hover:-rotate-6"
            >
              <img src="{{ $imageBack }}" alt="" class="h-full w-full object-cover" />
            </div>
          @else
            <div
              class="bg-primary-800 rounded-card absolute inset-0 flex items-center justify-center"
            >
              <svg
                class="text-primary-600 h-24 w-24 opacity-50"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                ></path>
              </svg>
            </div>
          @endif
        </div>
      </div>

      <div class="{{ $invert ? 'lg:text-right' : 'lg:text-left' }} w-full text-center lg:w-3/4">
        @if ($title)
          <h2
            class="font-heading mb-6 text-3xl leading-relaxed font-bold text-white md:text-xl lg:text-2xl"
          >
            {!! $title !!}
          </h2>
        @endif

        @if ($content)
          <div class="max-w-none leading-relaxed font-light">
            {!! $content !!}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
