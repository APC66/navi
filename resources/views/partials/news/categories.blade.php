{{--
  Filtre des catégories : colonne à gauche sur grand écran, pastilles qui
  défilent horizontalement au-dessus des actualités sur mobile et tablette.
  Chaque catégorie a sa propre adresse (/category/...), partageable et indexable.
--}}
@php
  $plural = fn ($count) => $count > 1 ? 'actualités' : 'actualité';
  $chipClasses = 'inline-flex items-center rounded-full border-2 px-4 py-1.5 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white';
  $chipActive = 'bg-secondary text-primary-1000 border-secondary';
  $chipIdle = 'hover:border-secondary hover:bg-secondary hover:text-primary-1000 border-white text-white';
@endphp

<aside class="lg:sticky lg:top-28 lg:self-start">
  {{-- Grand écran : la colonne suit le défilement (défile elle-même si la liste est très longue) --}}
  <nav
    aria-label="Catégories d'actualités"
    class="from-primary-900 to-primary-1000 hidden rounded-[20px] bg-gradient-to-b p-6 text-white shadow-lg lg:block lg:max-h-[calc(100vh-8rem)] lg:overflow-y-auto"
    data-aos="fade-right"
    data-aos-duration="600"
  >
    <h2 class="font-heading mb-4 text-lg font-bold tracking-wider uppercase">Catégories</h2>

    <ul class="divide-primary-600 divide-y">
      <li>
        <a
          href="{{ $allUrl }}"
          @if ($allActive) aria-current="page" @endif
          class="group flex items-center justify-between gap-3 py-3 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white {{ $allActive ? 'text-secondary font-bold' : 'text-primary-100 hover:text-white' }}"
        >
          <span>
            Toutes les actualités
            <span class="sr-only">, {{ $allCount }} {{ $plural($allCount) }}</span>
          </span>
          <span
            aria-hidden="true"
            class="min-w-8 rounded-full px-2 py-0.5 text-center text-xs font-bold {{ $allActive ? 'bg-secondary text-primary-1000' : 'group-hover:bg-secondary group-hover:text-primary-1000 bg-white/10 text-white' }}"
          >
            {{ $allCount }}
          </span>
        </a>
      </li>

      @foreach ($categories as $category)
        <li>
          <a
            href="{{ $category['url'] }}"
            @if ($category['active']) aria-current="page" @endif
            style="padding-left: {{ $category['depth'] }}rem"
            class="group flex items-center justify-between gap-3 py-3 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white {{ $category['active'] ? 'text-secondary font-bold' : 'text-primary-100 hover:text-white' }}"
          >
            <span>
              {!! $category['name'] !!}
              <span class="sr-only">, {{ $category['count'] }} {{ $plural($category['count']) }}</span>
            </span>
            <span
              aria-hidden="true"
              class="min-w-8 rounded-full px-2 py-0.5 text-center text-xs font-bold {{ $category['active'] ? 'bg-secondary text-primary-1000' : 'group-hover:bg-secondary group-hover:text-primary-1000 bg-white/10 text-white' }}"
            >
              {{ $category['count'] }}
            </span>
          </a>
        </li>
      @endforeach
    </ul>
  </nav>

  {{-- Mobile et tablette : la zone de défilement va jusqu'aux bords de l'écran --}}
  <nav
    aria-label="Catégories d'actualités"
    class="mx-[calc(var(--container-padding)*-1)] overflow-x-auto px-[var(--container-padding)] pt-1 pb-2 lg:hidden"
  >
    <ul class="flex gap-3 whitespace-nowrap">
      <li>
        <a
          href="{{ $allUrl }}"
          @if ($allActive) aria-current="page" @endif
          class="{{ $chipClasses }} {{ $allActive ? $chipActive : $chipIdle }}"
        >
          Toutes
        </a>
      </li>
      @foreach ($categories as $category)
        <li>
          <a
            href="{{ $category['url'] }}"
            @if ($category['active']) aria-current="page" @endif
            class="{{ $chipClasses }} {{ $category['active'] ? $chipActive : $chipIdle }}"
          >
            {!! $category['name'] !!}
          </a>
        </li>
      @endforeach
    </ul>
  </nav>
</aside>
