<section class="{{ $block->classes }} py-12 md:py-20 {{ $background }}">
    <div class="container mx-auto px-4 text-center">
        @if($title_group)
            <div class="max-w-3xl mx-auto mb-6">
                <x-partials.section-header
                    :group="$title_group"
                />
            </div>
        @endif

        {{-- Boutons alignés --}}
        @if($button_1 || $button_2)
            <div class="flex flex-col md:flex-row gap-4 justify-center items-center mt-8">
                @if($button_1 && !empty($button_1['url']))
                    <x-partials.button :group="$button_1" />
                @endif

                @if($button_2 && !empty($button_2['url']))
                    <x-partials.button :group="$button_2" />
                @endif
            </div>
        @endif

    </div>
</section>
