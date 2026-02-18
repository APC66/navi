<section class="{{ $block->classes }} {{ $background }} {{ $margins }}">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-24 items-center">

            <div class="relative {{ $invert ? 'lg:order-first' : 'lg:order-last' }}">
                @if($image)
                    <div class="overflow-hidden relative group">
                        <img src="{{ $image }}" alt="" class="w-full h-auto object-cover">
                    </div>
                @endif
            </div>

            <div class="{{ $invert ? 'lg:pl-10' : 'lg:pr-10' }}">
                @if($title_group)
                    <x-partials.section-header
                        :group="$title_group"
                        align="text-left"
                    />
                @endif

                <x-partials.intro-content
                    :group="$intro_group"
                />

                @if($cta)
                    <div class="mt-8">
                        <x-partials.button :group="$cta" />
                    </div>
                @endif
            </div>

        </div>
    </div>
</section>
