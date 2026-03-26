@php
  $colors = [
    'text-secondary' => '[&>span]:text-secondary',
    'text-white' => '[&>span]:text-white',
    'danger' => '[&>span]:text-danger',
  ];
@endphp

<div class="bg-primary-900 relative h-[300px] w-full overflow-hidden md:h-[400px]">
  @if ($image)
    <img src="{{ $image }}" alt="{{ strip_tags($title) }}" class="h-full w-full object-cover object-center" />
    <div class="bg-primary-900/20 absolute inset-0"></div>
  @else
    <div class="from-primary-900 to-primary-800 absolute inset-0 bg-gradient-to-r"></div>
  @endif
</div>
<div class="w-full bg-[#182646]">
  <div class="relative z-10 container mx-auto px-4 pt-8 pb-12">
    <div class="mb-6 flex items-center">
      <a href="{{ home_url('/') }}" class="hover:text-secondary transition-colors">Accueil</a>

      <span class="px-4">/</span>

      <span class="text-secondary max-w-[200px] truncate md:max-w-none">
        {!! $title ?? get_the_title()  !!}
      </span>
    </div>
    @if($showTitle)
      <h1
        class="font-heading mb-6 mt-12 text-center text-4xl leading-tight font-bold uppercase md:text-6xl {{$colors[$highlightColor]}} [&>span]:font-light"
      >
        @if ($highlight)
          <span>{!! $highlight !!}</span>
        @endif

        {!! $title !!}
      </h1>

      @if ($subtitle)
        <div
          class="border-secondary max-w-4xl border-l-4 pl-6 text-lg leading-relaxed font-light text-gray-600 md:text-xl"
        >
          {!! $subtitle !!}
        </div>
      @endif
    @endif

  </div>
</div>
