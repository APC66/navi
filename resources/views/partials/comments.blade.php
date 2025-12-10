@if (! post_password_required())
    <section id="comments" class="comments">
        @if ($responses())
            <x-heading level="h2" size="xl">
                {!! $title !!}
            </x-heading>

            <ol class="comment-list">
                {!! $responses !!}
            </ol>

            @if ($paginated())
                <nav aria-label="Comment">
                    <ul class="pager">
                        @if ($previous())
                            <li class="previous">
                                {!! $previous !!}
                            </li>
                        @endif

                        @if ($next())
                            <li class="next">
                                {!! $next !!}
                            </li>
                        @endif
                    </ul>
                </nav>
            @endif
        @endif

        @if ($closed())
            <x-alert type="warning">
                {!! __('Comments are closed.', 'radicle') !!}
            </x-alert>
        @endif

        @php(comment_form())
    </section>
@endif
