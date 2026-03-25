<header
  class="pointer-events-none fixed top-0 z-50 w-full bg-transparent"
  x-data="{ mobileMenuOpen: false }"
>
    @include('partials.navigation')

    <div class="group relative mx-auto">
      <div class="fixed top-0 left-1/2 z-40 h-[159px] w-[354px] -translate-x-1/2 transform ">
        <a class="relative h-full w-full" href="{{ home_url('/') }}">
          <svg
            width="354"
            height="159"
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
            class="absolute top-4 left-1/2 z-50 h-auto w-[108px] -translate-x-1/2 object-cover"
          />
        </a>
      </div>
    </div>

    <div
      class="w-[70px] bg-primary-1000 pointer-events-auto fixed top-6 right-0 z-50 flex h-[60px] md:w-[190px] items-center justify-end gap-4 rounded-l-full pr-4"
    >
      <a
        href="{{ get_permalink(get_option('woocommerce_myaccount_page_id')) }}"
        class="hidden bg-secondary text-primary-1000 md:inline-flex items-center justify-center rounded-full p-2 hover:bg-white"
      >
        @svg('user', 'h-6 w-6')
      </a>
      <a
        href="{{ get_permalink(get_option('woocommerce_cart_page_id')) }}"
        class="hidden bg-secondary text-primary-1000 relative mr-2 md:inline-flex items-center justify-center rounded-full p-2 hover:bg-white"
      >
        @svg('cart', 'h-6 w-6')
        <div
          class="text-md text-primary-1000 absolute top-0 -right-2 flex h-6 w-6 items-center justify-center rounded-full bg-white font-medium"
        >
          {{ WC()->cart->get_cart_contents_count() }}
        </div>
      </a>
      <button
        class="bg-secondary text-primary-1000 inline-flex items-center justify-center rounded-full p-2 hover:bg-white"
        @click="$dispatch('open-search')"
        aria-label="Ouvrir la recherche"
      >
        @svg('glass', 'h-6 w-6')
      </button>
    </div>
  </div>

  {{-- Mobile Menu (Off-Canvas) --}}
  <div
    class="pointer-events-auto fixed inset-0 z-[60] flex justify-start"
    role="dialog"
    aria-modal="true"
    x-show="mobileMenuOpen"
    style="display: none"
  >
    {{-- Backdrop --}}
    <div
      class="bg-primary-1000/50 fixed inset-0 backdrop-blur-sm transition-opacity"
      aria-hidden="true"
      x-show="mobileMenuOpen"
      x-transition:enter="duration-300 ease-out"
      x-transition:enter-start="opacity-0"
      x-transition:enter-end="opacity-100"
      x-transition:leave="duration-200 ease-in"
      x-transition:leave-start="opacity-100"
      x-transition:leave-end="opacity-0"
      @click="mobileMenuOpen = false"
    ></div>

    {{-- Panel (Glisse depuis la gauche) --}}
    <div
      class="relative mr-auto flex h-full w-full max-w-sm flex-col overflow-y-auto bg-white py-6 pb-12 shadow-2xl"
      x-show="mobileMenuOpen"
      x-transition:enter="transform transition duration-300 ease-in-out"
      x-transition:enter-start="-translate-x-full"
      x-transition:enter-end="translate-x-0"
      x-transition:leave="transform transition duration-300 ease-in-out"
      x-transition:leave-start="translate-x-0"
      x-transition:leave-end="-translate-x-full"
    >
      <div class="mb-8 flex items-center justify-between px-6">
        {{-- Logo dans le menu --}}
        <a
          class="brand text-primary-900 flex items-center text-2xl font-bold"
          href="{{ home_url('/') }}"
        >
          <span class="mr-2 text-3xl">⚓</span>
          {{ $siteName }}
        </a>

        <button
          type="button"
          class="-mr-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-50 text-gray-500 transition-colors hover:bg-gray-100 focus:outline-none"
          @click="mobileMenuOpen = false"
        >
          <span class="sr-only">Fermer le menu</span>
          <svg
            class="h-6 w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            aria-hidden="true"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="px-2">
        @if (has_nav_menu('primary_navigation'))
          <ul class="flex flex-col space-y-1">
            @foreach (\App\View\Navi::getMenu('primary_navigation') as $item)
              <li>
                <a
                  href="{{ $item->url }}"
                  class="{{ $item->active ? 'bg-primary-50 text-primary-900' : 'hover:text-primary-600 text-gray-700 hover:bg-gray-50' }} block rounded-lg px-4 py-3 text-lg font-medium transition-colors"
                >
                  {{ $item->label }}
                </a>
              </li>
            @endforeach
          </ul>
        @endif
      </div>

      <div class="mt-auto border-t border-gray-100 px-6 pt-8">
        <p class="text-center text-sm text-gray-400">&copy; {{ date('Y') }} {{ $siteName }}</p>
      </div>
    </div>
  </div>
</header>
