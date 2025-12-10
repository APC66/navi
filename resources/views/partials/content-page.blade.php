<div class="e-content space-y-4">
    @php(the_content())
</div>

@if ($pagination())
    <nav class="page-nav" aria-label="Page">
        {!! $pagination !!}
    </nav>
@endif
