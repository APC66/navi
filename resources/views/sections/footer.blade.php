<footer class="bg-primary-800 relative">
  <div
    class="absolute top-0 left-1/2 z-40 h-[101px] w-[200px] -translate-x-1/2 -translate-y-full transform"
  >
    <a class="relative h-full w-full" href="{{ home_url('/') }}">
      <svg
        class="rotate-180"
        width="200"
        height="101"
        viewBox="0 0 354 159"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <g clip-path="url(#clip0_657_2105)">
          <circle cx="182.042" cy="-11.0281" r="161.293" fill="url(#paint0_linear_657_2105)" />
          <circle
            cx="164.587"
            cy="164.587"
            r="164.587"
            transform="matrix(-1 0 0 1 349.923 -172.321)"
            stroke="url(#paint1_radial_657_2105)"
            stroke-width="4.33727"
          />
          <circle
            cx="167.987"
            cy="-7.73422"
            r="164.587"
            stroke="url(#paint2_radial_657_2105)"
            stroke-width="4.33727"
          />
        </g>
        <defs>
          <linearGradient
            id="paint0_linear_657_2105"
            x1="182.042"
            y1="38.6021"
            x2="182.042"
            y2="150.265"
            gradientUnits="userSpaceOnUse"
          >
            <stop offset="0" stop-color="#101F4D" />
            <stop offset="1" stop-color="#2548B3" />
          </linearGradient>
          <radialGradient
            class="transition duration-1000 group-hover:rotate-25"
            id="paint1_radial_657_2105"
            cx="0"
            cy="0"
            r="1"
            gradientUnits="userSpaceOnUse"
            gradientTransform="translate(-2.80234e-06 180.528) rotate(42.4335) scale(233.739 111.506)"
          >
            <stop offset="0" stop-color="#101F4D" />
            <stop offset="0.38944" stop-color="#FFD21F" />
            <stop offset="1" stop-color="#101F4D" />
          </radialGradient>
          <radialGradient
            class="transition duration-1000 group-hover:rotate-35"
            id="paint2_radial_657_2105"
            cx="0"
            cy="0"
            r="1"
            gradientUnits="userSpaceOnUse"
            gradientTransform="translate(14.3643 11.2544) rotate(52.5011) scale(152.692 319.921)"
          >
            <stop offset="0" stop-color="#101F4D" />
            <stop offset="0.473148" stop-color="#FFD21F" />
            <stop offset="1" stop-color="#101F4D" />
          </radialGradient>
        </defs>
      </svg>
      <img
        src="@asset('resources/images/logo-blanc.svg')"
        alt="Logo Blanc"
        class="absolute bottom-0 left-1/2 z-50 h-auto w-[70px] -translate-x-1/2 object-cover"
      />
    </a>
  </div>
  <div class="pt-12 pb-8">
    @if (has_nav_menu('footer_navigation'))
      <ul class="flex flex-col items-center justify-center lg:flex-row">
        @foreach (App\View\Navi::getMenu('footer_navigation') as $item)
          <li class="px-4 py-2">
            <a
              href="{{ $item->url }}"
              class="{{ $item->active ? 'text-secondary' : 'hover:text-secondary text-white' }} tracking-wide text-white transition-colors"
            >
              {{ $item->label }}
            </a>
          </li>
        @endforeach
      </ul>
    @endif

    @php
      $partners = get_field('footer_partners', 'option');
    @endphp

    @if ($partners)
      <div class="container mx-auto pt-8">
        <div
          class="mx-auto flex w-full max-w-max flex-wrap items-center justify-center gap-4 rounded-xl bg-white p-2"
        >
          @foreach ($partners as $partner)
            @if (! empty($partner['logo']))
              <div
                class="flex w-auto items-center justify-center transition-opacity hover:opacity-100"
              >
                @if (! empty($partner['url']))
                  <a href="{{ $partner['url'] }}" target="_blank" rel="noopener noreferrer">
                    <img
                      src="{{ $partner['logo'] }}"
                      alt="Partenaire"
                      class="h-full w-auto max-w-[120px] object-contain"
                    />
                  </a>
                @else
                  <img
                    src="{{ $partner['logo'] }}"
                    alt="Partenaire"
                    class="h-22 w-auto object-contain"
                  />
                @endif
              </div>
            @endif
          @endforeach
        </div>
      </div>
    @endif
  </div>
  <div class="bg-primary-900 text-primary-200 flex flex-wrap items-center justify-center gap-x-20">
    <p class="mt-4">&copy; {{ date('Y') }} {{ get_bloginfo('name') }}. Tous droits réservés.</p>
    <p class="mt-4">
      Site réalisé avec ♡ par
      <a
        href="https://agencepoint.com"
        target="_blank"
        class="text-primary-300 hover:text-primary-100 transition"
      >
        Agence Point Com
      </a>
    </p>
  </div>
</footer>
