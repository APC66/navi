@extends('layouts.app')

@section('content')
  @while(have_posts())
    @php the_post(); @endphp

  @php
    $headerGroup = get_field('page_header');
    $hasCustomHeader = $headerGroup && (!empty($headerGroup['header_image']) || !empty($headerGroup['header_title']));
  @endphp

  @if($hasCustomHeader)
    <x-partials.page-header :group="$headerGroup" />
  @else
    <div class="bg-primary-900 py-16 md:py-24 text-center relative overflow-hidden">
      <div class="container mx-auto px-4 relative z-10">
        <h1 class="text-4xl md:text-5xl font-bold text-white font-heading">
          {!! get_the_title() !!}
        </h1>
      </div>
    </div>
  @endif

  <div class="container mx-auto px-4 py-12 md:py-20">
    <div class="max-w-6xl mx-auto">
      @include('partials.content-page')
    </div>
  </div>

  @endwhile
@endsection
