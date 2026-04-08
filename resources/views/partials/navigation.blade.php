@php
  // Récupération de l'arbre du menu
  $menuItems = \App\View\Navi::getMenu('primary_navigation');
@endphp

{{-- On englobe avec pointer-events-none pour ne pas bloquer les clics sur la page quand le menu est fermé --}}
<div x-data="flyoutMenu()" @keydown.escape.window="closeMenu()" class="pointer-events-none">

  {{-- Overlay sombre (Backdrop) --}}
  <div
    class="fixed inset-0 z-[70] bg-primary-1000/80 backdrop-blur-md transition-opacity pointer-events-auto"
    x-show="isOpen"
    x-transition.opacity.duration.400ms
    @click="closeMenu()"
    style="display: none;"
  ></div>

  <div
    class="fixed inset-y-0 left-0 z-[80] w-[calc(100vw-100px)] md:w-[400px] transition-transform duration-500 ease-in-out flex flex-col -translate-x-full"
    :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
  >

    {{-- Bouton Burger --}}
    <div
      class="absolute top-6 -right-[100px] w-[100px] h-[80px] bg-primary-800 rounded-r-full flex items-center justify-end shadow-lg pointer-events-auto transition-colors">
      <button
        class="mr-4 p-2 flex flex-col text-primary-1000 w-[55px] h-[55px] justify-center items-center bg-secondary rounded-full shadow-lg hover: focus:outline-none transition-all transform hover:scale-105"
        @click="toggleMenu()"
        :aria-label="isOpen ? 'Fermer le menu' : 'Ouvrir le menu'"
      >
        <svg x-show="!isOpen" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
        </svg>

        <svg x-show="isOpen" style="display: none;" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
             stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
        </svg>

        <span class="text-[9px] font-extrabold mt-1 uppercase tracking-widest"
              x-text="isOpen ? 'Fermer' : 'Menu'"></span>
      </button>
    </div>

    {{-- Contenu du menu --}}
    <div
      class="w-full h-full bg-primary-1000 flex flex-col overflow-hidden relative pointer-events-auto transition-shadow duration-500"
      :class="isOpen ? 'shadow-[20px_0_50px_rgba(0,0,0,0.5)]' : 'shadow-none'"
    >

      {{-- En-tête du Menu --}}
      <div class="bg-primary-800 flex items-center justify-center p-6 md:p-8 relative">
        <a class="brand font-bold text-3xl flex items-center font-heading" href="{{ home_url('/') }}">
          <img
            src="@asset('resources/images/logo-blanc.svg')"
            alt="Logo Blanc"
            class="h-auto w-[108px] object-cover"
          />
        </a>
      </div>
      {{--  Drapeaux pour plus tard --}}

      <div class="py-10 mx-auto">
        <hr class="w-24 border-b border-primary-200 mx-auto ">
      </div>
      <div class="flex items-center justify-center gap-4 pb-6">
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
      </div>


      {{-- Zone des panneaux dynamiques --}}
      <div class="relative flex-1 overflow-hidden ">
        {{-- PANNEAU PRINCIPAL (Niveau 0) --}}
        <div
          class="absolute inset-0 overflow-y-auto  transition-all duration-500 ease-in-out"
          :class="{'translate-x-0 opacity-100 z-20 pointer-events-auto': activePanel === 'main','-translate-x-1/4 opacity-0 pointer-events-none z-10': panelStack.includes('main') && activePanel !== 'main', 'translate-x-full opacity-0 pointer-events-none z-20': !panelStack.includes('main') }" >
          <ul class="flex flex-col space-y-8 px-4 lg:px-10">
            @if (!empty($menuItems))
              @foreach ($menuItems as $item)
                <li class="{{$item->classes ?? ''}} px-4 lg:px-8 py-4">
                  @if (!empty($item->children))
                    <button
                      @click="openSubPanel('panel-{{ $item->id }}')"
                      class="w-full flex items-center justify-between font-medium tracking-wider hover:text-secondary transition-colors group"
                    >
                      <span>{!! $item->label  !!}</span>
                      <span class="w-5 h-5 flex items-center justify-center ">
                          <svg
                            class="group-hover:text-secondary text-white transform group-hover:translate-x-1 transition-transform"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round"
                                                                                        stroke-linejoin="round"
                                                                                        stroke-width="2.5"
                                                                                        d="M9 5l7 7-7 7"></path></svg>
                      </span>
                    </button>
                  @else
                    <a href="{{ $item->url }}"
                       class="block font-medium tracking-wider hover:text-secondary transition-colors">
                      {!! $item->label !!}
                    </a>
                  @endif
                </li>
              @endforeach
            @else
              <li class="px-8 py-4 text-gray-500 italic">Menu non configuré.</li>
            @endif
          </ul>
        </div>

        {{-- SOUS-PANNEAUX (Niveau 1) --}}
        @if (!empty($menuItems))
          @foreach ($menuItems as $item)
            @if (!empty($item->children))
              <div
                id="panel-{{ $item->id }}"
                class="absolute inset-0 overflow-y-auto transition-all duration-500 ease-in-out flex flex-col shadow-[-20px_0_30px_-10px_rgba(0,0,0,0.1)] translate-x-full opacity-0 pointer-events-none"
                :class="{ 'translate-x-0 opacity-100 z-20 pointer-events-auto': activePanel === 'panel-{{ $item->id }}', '-translate-x-1/4 opacity-0 pointer-events-none z-10': panelStack.includes('panel-{{ $item->id }}') && activePanel !== 'panel-{{ $item->id }}', 'translate-x-full opacity-0 pointer-events-none z-20': !panelStack.includes('panel-{{ $item->id }}') }"
              >
                {{-- Bouton Retour --}}
                <div class="p-4">
                  <button
                    @click="goBack()"
                    class="inline-flex cursor-pointer items-center font-medium text-secondary transition-colors uppercase tracking-widest px-4 py-2"
                  >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Retour
                  </button>
                </div>


                <ul class="flex flex-col py-4 space-y-4 flex-1">
                  @foreach ($item->children as $child)
                    <li class="{{$child->classes ?? ''}} px-4 lg:px-8 py-4">
                      {{-- S'il y a un Niveau 2 (Sous-sous-menu) --}}
                      @if (!empty($child->children))
                        <button
                          @click="openSubPanel('panel-{{ $child->id }}')"
                          class="w-full flex items-center justify-between text-left font-medium  hover:text-secondary transition-colors group"
                        >
                          {!! $child->label !!}
                          <svg class="w-5 h-5 group-hover:text-secondary" fill="none"
                               stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5l7 7-7 7"></path>
                          </svg>
                        </button>
                      @else
                        <a href="{{ $child->url }}"
                           class="block font-medium hover:text-secondary transition-colors">
                          {!! $child->label !!}
                        </a>
                      @endif
                    </li>
                  @endforeach
                </ul>
              </div>

              {{-- SOUS-PANNEAUX (Niveau 2) --}}
              @foreach ($item->children as $child)
                @if (!empty($child->children))
                  <div
                    id="panel-{{ $child->id }}"
                    class="absolute inset-0 overflow-y-auto  transition-all duration-500 ease-in-out flex flex-col shadow-[-20px_0_30px_-10px_rgba(0,0,0,0.1)] translate-x-full opacity-0 pointer-events-none"
                    :class="{'translate-x-0 opacity-100 z-20 pointer-events-auto': activePanel === 'panel-{{ $child->id }}','-translate-x-1/4 opacity-0 pointer-events-none z-10': panelStack.includes('panel-{{ $child->id }}') && activePanel !== 'panel-{{ $child->id }}','translate-x-full opacity-0 pointer-events-none z-20': !panelStack.includes('panel-{{ $child->id }}')}"
                  >
                    <div class="shrink-0 p-4">
                      <button
                        @click="goBack()"
                        class="inline-flex cursor-pointer items-center font-medium text-secondary transition-colors uppercase tracking-widest px-4 py-2"
                      >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Retour
                      </button>
                    </div>

                    <ul class="flex flex-col py-4 space-y-4 flex-1">
                      @foreach ($child->children as $grandChild)
                        <li class="{{$grandChild->classes ?? ''}} px-4 lg:px-8 py-4">
                          <a href="{{ $grandChild->url }}"
                             class="block font-medium hover:text-secondary transition-colors">
                            {!! $grandChild->label !!}
                          </a>
                        </li>
                      @endforeach
                    </ul>
                  </div>
                @endif
              @endforeach
            @endif
          @endforeach
        @endif

      </div>

      {{-- Pied du Menu --}}
      <div class="px-4 lg:px-8 py-12 bg-primary-1000">
        <hr class="w-24 border-b border-b-primary-200 mx-auto my-12">
        <x-partials.socials container-class="flex justify-center items-center gap-4 " />
      </div>

    </div>
  </div>
</div>

<script>
  const flyoutMenuData = () => ({
    isOpen: false,
    activePanel: 'main',
    panelStack: ['main'],

    toggleMenu() {
      if (this.isOpen) {
        this.closeMenu()
      } else {
        this.openMenu()
      }
    },

    openMenu() {
      this.isOpen = true
      document.body.style.overflow = 'hidden'
    },

    closeMenu() {
      this.isOpen = false
      document.body.style.overflow = ''

      // On réinitialise l'état une fois le panneau rangé hors écran
      setTimeout(() => {
        this.activePanel = 'main'
        this.panelStack = ['main']
      }, 500)
    },

    openSubPanel(panelId) {
      this.panelStack.push(panelId)
      this.activePanel = panelId
    },

    goBack() {
      if (this.panelStack.length > 1) {
        this.panelStack.pop()
        this.activePanel = this.panelStack[this.panelStack.length - 1]
      }
    },
  })

  // Enregistrement sécurisé pour éviter le problème de timing avec Vite
  if (window.Alpine) {
    window.Alpine.data('flyoutMenu', flyoutMenuData)
  } else {
    document.addEventListener('alpine:init', () => {
      window.Alpine.data('flyoutMenu', flyoutMenuData)
    })
  }
</script>
