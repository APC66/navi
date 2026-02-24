<section class="{{ $block->classes }} bg-primary-900 group/section relative overflow-visible">
  @if ($bg_image)
    <div class="absolute inset-0 z-0">
      <img src="{{ $bg_image }}" alt="" class="h-full w-full object-cover" />
    </div>
  @endif

  <div class="absolute right-0 bottom-0 left-0 translate-y-1/2">
    <img src="@asset('resources/images/waves.svg')" alt="" class="z-10 h-auto w-full" />
  </div>

  <div class="relative z-10 container mx-auto px-4 py-12">
    <div class="grid grid-cols-1 items-center gap-16 lg:grid-cols-2 lg:gap-24">
      <div
        class="relative flex min-h-[400px] justify-center md:min-h-[500px] lg:ml-10 lg:justify-start"
      >
        @if ($image_2)
          <div
            class="shadow-image-card rounded-card absolute top-1/2 left-1/2 z-20 h-[660px] w-[420px] -translate-1/2 -rotate-6 transform overflow-hidden"
          >
            <img src="{{ $image_2 }}" alt="Image décor" class="h-full w-full object-cover" />
          </div>
        @endif

        @if ($image_1)
          <div
            class="shadow-image-card rounded-card absolute top-1/2 left-[calc(50%-5rem)] z-10 h-[660px] w-[420px] -translate-1/2 -rotate-20 transform overflow-hidden"
          >
            <img src="{{ $image_1 }}" alt="Image principale" class="h-full w-full object-cover" />
          </div>
        @endif
      </div>

      <div class="text-left">
        @if ($title_group)
          <x-partials.section-header :group="$title_group" />
        @endif

        @if ($intro_group)
          <x-partials.intro-content :group="$intro_group" />
        @endif

        @if ($cta)
          <div class="mt-8">
            <x-partials.button :group="$cta" />
          </div>
        @endif
      </div>
    </div>
  </div>
</section>
