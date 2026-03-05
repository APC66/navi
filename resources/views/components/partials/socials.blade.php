<div class="{{$containerClass}}">
@foreach($socials as $link => $icon )
    @if($icon['type'] === 'icon')
      <div class="{{$linkClass}}">
        <a href="{{ $icon['link'] }}" target="_blank">
          @svg( $icon['icon'], 'w-6 h-6' )
        </a>
      </div>
    @elseif($icon['type'] === 'blade')
      <div class="{{$linkClass}}">
        <a href="{{ $icon['link'] }}" target="_blank">
          {{svg($icon['icon'])}}
        </a>
      </div>
    @endif
  @endforeach
</div>
