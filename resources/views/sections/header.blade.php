<header class="bg-transparent pointer-events-none fixed top-0 w-full z-50" x-data="{ mobileMenuOpen: false }">
    <div class="mx-auto flex justify-between items-center h-20 px-4 md:px-0 pointer-events-auto">

        <div class="fixed top-6 left-0 bg-primary-1000 rounded-r-full w-[100px] h-[80px] flex items-center justify-end transition-all ease-in-out duration-300 z-[70]"
             :class="mobileMenuOpen ? 'translate-x-[370px]' : 'translate-x-0'"
        >
            <button
                class="mr-4 p-2 flex flex-col w-[55px] h-[55px] justify-center items-center bg-secondary text-black rounded-full shadow-lg hover:bg-white focus:outline-none transition-all"
                @click="mobileMenuOpen = true"
                aria-label="Ouvrir le menu"
            >
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <span class="text-xs font-bold">
                    MENU
                </span>
            </button>
        </div>

        <div class="group relative mx-auto">
            <div class="fixed top-0 left-1/2 w-[354px] h-[159px] transform -translate-x-1/2 z-40">
                <a class="relative w-full h-full" href="{{ home_url('/') }}">
                    <svg width="354" height="159" viewBox="0 0 354 159" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#clip0_657_2105)">
                            <circle cx="182.042" cy="-11.0281" r="161.293" fill="url(#paint0_linear_657_2105)"/>
                            <circle cx="164.587" cy="164.587" r="164.587" transform="matrix(-1 0 0 1 349.923 -172.321)" stroke="url(#paint1_radial_657_2105)" stroke-width="4.33727"/>
                            <circle cx="167.987" cy="-7.73422" r="164.587" stroke="url(#paint2_radial_657_2105)" stroke-width="4.33727"/>
                        </g>
                        <defs>
                            <linearGradient id="paint0_linear_657_2105" x1="182.042" y1="38.6021" x2="182.042" y2="150.265" gradientUnits="userSpaceOnUse">
                                <stop offset="0" stop-color="#101F4D"/>
                                <stop offset="1" stop-color="#2548B3"/>
                            </linearGradient>
                            <radialGradient class="transition duration-1000 group-hover:rotate-25" id="paint1_radial_657_2105" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(-2.80234e-06 180.528) rotate(42.4335) scale(233.739 111.506)">
                                <stop offset="0" stop-color="#101F4D"/>
                                <stop offset="0.38944" stop-color="#FFD21F"/>
                                <stop offset="1" stop-color="#101F4D"/>
                            </radialGradient>
                            <radialGradient class="transition duration-1000 group-hover:rotate-35" id="paint2_radial_657_2105" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(14.3643 11.2544) rotate(52.5011) scale(152.692 319.921)">
                                <stop offset="0" stop-color="#101F4D"/>
                                <stop offset="0.473148" stop-color="#FFD21F"/>
                                <stop offset="1" stop-color="#101F4D"/>
                            </radialGradient>
                        </defs>
                    </svg>
                    <img src="@asset('resources/images/logo-blanc.svg')" alt="Logo Blanc" class="absolute top-4 left-1/2 -translate-x-1/2 w-[108px] object-cover h-auto z-50">
                </a>
            </div>
        </div>

        <div class="fixed top-6 right-0 z-50 bg-primary-1000 rounded-l-full h-[60px] w-[190px] flex items-center justify-end gap-4 pr-4 ">
            <a href="{{ get_permalink( get_option('woocommerce_myaccount_page_id') ) }}" class="inline-flex justify-center items-center p-2 rounded-full bg-secondary hover:bg-white">
                @svg('user', 'w-6 h-6')
            </a>
            <a href="{{ get_permalink( get_option('woocommerce_cart_page_id') ) }}" class="relative inline-flex justify-center items-center p-2 mr-2 rounded-full bg-secondary hover:bg-white">
                @svg('cart', 'w-6 h-6')
                <div class="absolute flex justify-center items-center -right-2 top-0 text-md font-medium w-6 h-6 bg-white rounded-full">
                    {{ WC()->cart->get_cart_contents_count() }}
                </div>
            </a>
            <button class="inline-flex justify-center items-center p-2 rounded-full bg-secondary hover:bg-white">
                @svg('glass', 'w-6 h-6')
            </button>
        </div>
    </div>

    {{-- Mobile Menu (Off-Canvas) --}}
    <div
        class="fixed inset-0 z-[60] flex justify-start pointer-events-auto"
        role="dialog"
        aria-modal="true"
        x-show="mobileMenuOpen"
        style="display: none;"
    >
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 bg-primary-1000/50 transition-opacity backdrop-blur-sm"
            aria-hidden="true"
            x-show="mobileMenuOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileMenuOpen = false"
        ></div>

        {{-- Panel (Glisse depuis la gauche) --}}
        <div
            class="relative mr-auto flex h-full w-full max-w-sm flex-col overflow-y-auto bg-white py-6 pb-12 shadow-2xl"
            x-show="mobileMenuOpen"
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
        >
            <div class="flex items-center justify-between px-6 mb-8">
                {{-- Logo dans le menu --}}
                <a class="brand font-bold text-2xl text-primary-900 flex items-center" href="{{ home_url('/') }}">
                    <span class="mr-2 text-3xl">⚓</span>
                    {{ $siteName }}
                </a>

                <button
                    type="button"
                    class="-mr-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-50 text-gray-500 hover:bg-gray-100 focus:outline-none transition-colors"
                    @click="mobileMenuOpen = false"
                >
                    <span class="sr-only">Fermer le menu</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="px-2">
                @if (has_nav_menu('primary_navigation'))
                    <ul class="flex flex-col space-y-1">
                        @foreach ( \App\View\Navi::getMenu('primary_navigation') as $item)
                            <li>
                                <a href="{{ $item->url }}"
                                   class="block px-4 py-3 text-lg font-medium rounded-lg transition-colors {{ $item->active ? 'bg-primary-50 text-primary-900' : 'text-gray-700 hover:bg-gray-50 hover:text-primary-600' }}">
                                    {{ $item->label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="mt-auto px-6 pt-8 border-t border-gray-100">
                <p class="text-sm text-gray-400 text-center">
                    &copy; {{ date('Y') }} {{ $siteName }}
                </p>
            </div>
        </div>
    </div>
</header>
