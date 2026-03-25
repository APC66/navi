{{--
  Template Name: Carte Cadeau
--}}

@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php(the_post())
    <x-partials.page-header :group="get_field('page_header')" />
    <x-gift-card />
  @endwhile
@endsection
