{{-- Pagination numérotée (liens générés par WordPress, styles dans resources/css/app.css) --}}
@if (! empty($pagination))
  <nav class="news-pagination mt-16" aria-label="Pagination des actualités">
    <ul class="flex flex-wrap items-center justify-center gap-2">
      @foreach ($pagination as $link)
        <li>{!! $link !!}</li>
      @endforeach
    </ul>
  </nav>
@endif
