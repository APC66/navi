<article @php(post_class())>
    <header>
        <x-heading level="h2" size="2xl" class="entry-title mb-3">
            <x-link href="{{ get_permalink() }}" variant="unstyled">
                {!! $title !!}
            </x-link>
        </x-heading>

        @include('partials.entry-meta')
    </header>

    <div class="entry-summary">
        @php(the_excerpt())
    </div>
</article>
