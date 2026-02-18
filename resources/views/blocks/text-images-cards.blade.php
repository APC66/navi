<section class="{{ $block->classes }} relative bg-primary-900 overflow-visible group/section">
    @if($bg_image)
        <div class="absolute inset-0 z-0">
            <img src="{{ $bg_image }}" alt="" class="w-full h-full object-cover">
        </div>
    @endif
    <div class="absolute left-0 right-0 bottom-0 translate-y-1/2">
        <img src="@asset('resources/images/waves.svg')" alt="" class="w-full h-auto z-10">
    </div>

    <div class="container mx-auto px-4 relative py-12 z-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 lg:gap-24 items-center">

            <div class="relative min-h-[400px] md:min-h-[500px] flex justify-center lg:justify-start lg:ml-10">

                @if($image_2)
                    <div class="absolute top-1/2 left-1/2 -translate-1/2 w-[420px] h-[660px] transform -rotate-6 z-20 shadow-image-card rounded-card overflow-hidden">
                        <img src="{{ $image_2 }}" alt="Image décor" class="w-full h-full object-cover">
                    </div>
                @endif

                @if($image_1)
                    <div class="absolute top-1/2 left-[calc(50%-5rem)]  w-[420px] h-[660px] -translate-1/2 transform -rotate-20 z-10 shadow-image-card rounded-card overflow-hidden">
                        <img src="{{ $image_1 }}" alt="Image principale" class="w-full h-full object-cover">
                    </div>
                @endif
            </div>

            <div class="text-left">

                @if($title_group)
                    <x-partials.section-header :group="$title_group" />
                @endif

                @if($intro_group)
                    <x-partials.intro-content :group="$intro_group" />
                @endif



                @if($cta)
                    <div class="mt-8">
                        <x-partials.button :group="$cta" />
                    </div>
                @endif

            </div>

        </div>
    </div>
</section>
