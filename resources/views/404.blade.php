@extends('layouts.app')

@section('content')
  <!-- Hero Section 404 -->
  <div class="relative text-white overflow-hidden">
    <!-- Background Image -->
    <x-partials.page-header  title="404 Non trouvé"/>

    <!-- Content Section -->
    <div class="relative bg-[#182646]">
      <div class="relative z-20 container mx-auto px-4 py-12 md:py-20">
        <!-- Main Content -->
        <div class="max-w-3xl">
          <h1 class="font-heading text-6xl md:text-7xl font-bold uppercase mb-6">
            <span class="text-secondary block md:inline">404</span>
          </h1>

          <h2 class="font-heading text-3xl md:text-4xl font-bold mb-6 leading-tight">
            Oups ! La page que vous cherchez n'existe pas.
          </h2>

          <p class="text-primary-200 text-lg md:text-xl mb-8 leading-relaxed max-w-2xl">
            Désolé, la page que vous avez demandée n'a pas pu être trouvée.
            Elle a peut-être été supprimée, déplacée ou l'URL est incorrecte.
          </p>

          <!-- CTA Buttons -->
          <div class="flex flex-col sm:flex-row gap-4 mt-10">
            <x-partials.button
              title="Retour à l'accueil"
              url="{{ home_url('/') }}"
              class="bg-secondary hover:bg-secondary/90 text-primary-1000"
            />
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Suggestions Section -->
  <div class="bg-primary-1000 py-16 md:py-24">
    <div class="container mx-auto px-4">
      <div class="max-w-3xl mx-auto text-center">
        <h3 class="font-heading text-2xl md:text-3xl font-bold text-white mb-8">
          Vous pourriez aussi consulter :
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Link 1 -->
          <a
            href="{{ get_post_type_archive_link('cruise') }}"
            class="group bg-primary-900 hover:bg-secondary transition-all duration-300 rounded-lg p-6 text-left"
          >
            <h4 class="font-heading text-xl font-bold text-white group-hover:text-primary-1000 mb-2">
              Découvrir nos croisières
            </h4>
            <p class="text-primary-200 group-hover:text-primary-1000 text-sm">
              Explorez notre catalogue complet de croisières et trouvez votre prochaine aventure.
            </p>
          </a>

          <!-- Link 2 -->
          <a
            href="{{ home_url('/contact') }}"
            class="group bg-primary-900 hover:bg-secondary transition-all duration-300 rounded-lg p-6 text-left"
          >
            <h4 class="font-heading text-xl font-bold text-white group-hover:text-primary-1000 mb-2">
              Nous contacter
            </h4>
            <p class="text-primary-200 group-hover:text-primary-1000 text-sm">
              Une question ? Notre équipe est là pour vous aider et répondre à vos demandes.
            </p>
          </a>
        </div>
      </div>
    </div>
  </div>
@endsection
