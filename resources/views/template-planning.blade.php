{{--
  Template Name: Planning Global
--}}
@extends('layouts.app')

@section('content')

  @php
    // Chargement du script JS exclusif à cette page
    \Roots\bundle('resources/js/components/global-planning.js')->enqueue();

    // Données pour les filtres
    $types = get_terms(['taxonomy' => 'cruise_type', 'hide_empty' => false]);
    $ports = get_terms(['taxonomy' => 'harbor', 'hide_empty' => false]);

    $headerGroup = get_field('page_header');
  @endphp
    <x-partials.page-header :group="$headerGroup" />

    <x-global-planning />
@endsection
