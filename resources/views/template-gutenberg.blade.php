{{--
  Template Name: Template gutenberg
--}}

@extends('layouts.app')
@php
  $headerGroup = get_field('page_header');
@endphp
@section('content')
  <div class="bg-primary-1000 relative">
    <x-partials.page-header :group="$headerGroup" />

    @if (! have_posts())
      <x-alert type="warning">
        {!! __('Sorry, no results were found.', 'radicle') !!}
      </x-alert>

      {!! get_search_form(false) !!}
    @endif
    @while (have_posts())
      @php(the_post())
      @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
    @endwhile
  </div>
  {!! get_the_posts_navigation() !!}
@endsection
