<article @php(post_class('h-entry'))>
  <header>
    <x-heading level="h1" size="3xl" class="p-name mb-6">
      {!! $title !!}
    </x-heading>

    @include('partials.entry-meta')
  </header>

  <div class="e-content mb-8 space-y-4">
    @php(the_content())
  </div>

  @if ($pagination())
    <footer>
      <nav class="page-nav" aria-label="Page">
        {!! $pagination !!}
      </nav>
    </footer>
  @endif

  @php(comments_template())
</article>
