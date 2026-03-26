blade<section
  @if($ref_id ?? null) id="{{ $ref_id }}" @endif
class="{{ $background ?? 'bg-primary-900' }} py-16 px-8 text-center"
>
  @if($title_group ?? null)
    @include('partials.section-header', ['data' => $title_group])
  @endif

  @if($content ?? null)
    <div class="cta-content mb-8">
      {!! $content !!}
    </div>
  @endif

  @if(($button_1['label'] ?? null) || ($button_2['label'] ?? null))
    <div class="flex gap-4 justify-center flex-wrap">
      @if($button_1['label'] ?? null)
        <a href="{{ $button_1['url'] ?? '#' }}" class="btn btn-primary">
          {{ $button_1['label'] }}
        </a>
      @endif
      @if($button_2['label'] ?? null)
        <a href="{{ $button_2['url'] ?? '#' }}" class="btn btn-secondary">
          {{ $button_2['label'] }}
        </a>
      @endif
    </div>
  @endif
</section>
