@extends('layouts.app')

@section('content')
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
  @if (! have_posts())
    <x-alert type="warning">
      {!! __('Sorry, no results were found.', 'radicle') !!}
    </x-alert>

  @endif

  @while (have_posts())
    @php(the_post())
    @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
  @endwhile

@endsection
