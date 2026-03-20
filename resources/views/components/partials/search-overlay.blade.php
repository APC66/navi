@php
  $archiveUrl = rtrim(get_post_type_archive_link('cruise') ?: home_url('/croisières'), '/');
@endphp

<div
  x-data="searchOverlay()"
  @open-search.window="open()"
  @keydown.escape.window="close()"
  class="relative z-[90]"
>
  {{-- Backdrop --}}
  <div
    class="fixed inset-0 bg-primary-1000/70 backdrop-blur-sm transition-opacity"
    x-show="isOpen"
    x-transition:enter="duration-300 ease-out"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="duration-200 ease-in"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="close()"
    style="display: none;"
    aria-hidden="true"
  ></div>

  {{-- Panel de recherche --}}
  <div
    class="fixed inset-x-0 top-0 z-[91] flex flex-col"
    x-show="isOpen"
    x-transition:enter="transform transition duration-300 ease-out"
    x-transition:enter-start="-translate-y-full opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transform transition duration-200 ease-in"
    x-transition:leave-start="translate-y-0 opacity-100"
    x-transition:leave-end="-translate-y-full opacity-0"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-label="Recherche de croisières"
  >
    <div class="bg-primary-1000 shadow-2xl">
      {{-- Barre de recherche --}}
      <div class="container mx-auto px-4 py-6">
        <div class="flex items-center gap-4">
          {{-- Icône loupe --}}
          <svg class="h-6 w-6 shrink-0 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
          </svg>

          {{-- Input --}}
          <input
            type="search"
            x-ref="searchInput"
            x-model="query"
            @input.debounce.300ms="fetchResults()"
            placeholder="Rechercher une croisière…"
            class="w-full bg-transparent text-xl font-light text-white placeholder-primary-300 outline-none"
            autocomplete="off"
            spellcheck="false"
          />

          {{-- Bouton fermer --}}
          <button
            @click="close()"
            class="shrink-0 rounded-full p-2 text-primary-300 transition-colors hover:bg-primary-800 hover:text-white"
            aria-label="Fermer la recherche"
          >
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      {{-- Résultats --}}
      <div
        x-show="query.length >= 2"
        class="border-t border-primary-800"
        style="display: none;"
      >
        <div class="container mx-auto px-4 py-4">

          {{-- Loader --}}
          <div x-show="loading" class="flex items-center justify-center py-8">
            <svg class="h-8 w-8 animate-spin text-secondary" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
          </div>

          {{-- Aucun résultat --}}
          <div
            x-show="!loading && results.length === 0 && query.length >= 2"
            class="py-8 text-center text-primary-300"
            style="display: none;"
          >
            Aucune croisière trouvée pour « <span x-text="query" class="text-white"></span> »
          </div>

          {{-- Liste des résultats --}}
          <ul
            x-show="!loading && results.length > 0"
            class="divide-y divide-primary-800"
            style="display: none;"
          >
            <template x-for="result in results" :key="result.id">
              <li>
                <a
                  :href="result.url"
                  class="group flex items-center gap-4 py-3 transition-colors hover:bg-primary-900 -mx-4 px-4 rounded-lg"
                >
                  {{-- Thumbnail --}}
                  <div class="h-14 w-20 shrink-0 overflow-hidden rounded-md bg-primary-800">
                    <img
                      x-show="result.thumbnail"
                      :src="result.thumbnail"
                      :alt="result.title"
                      class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    <div
                      x-show="!result.thumbnail"
                      class="flex h-full w-full items-center justify-center text-primary-500"
                    >
                      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                    </div>
                  </div>

                  {{-- Infos --}}
                  <div class="min-w-0 flex-1">
                    <p class="truncate font-medium text-white group-hover:text-secondary transition-colors" x-text="result.title"></p>
                    <p
                      x-show="result.harbor"
                      class="mt-0.5 flex items-center gap-1 text-sm text-primary-300"
                      style="display: none;"
                    >
                      <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      <span x-text="result.harbor"></span>
                    </p>
                  </div>

                  {{-- Flèche --}}
                  <svg class="h-4 w-4 shrink-0 text-primary-500 transition-transform group-hover:translate-x-1 group-hover:text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                  </svg>
                </a>
              </li>
            </template>
          </ul>

          {{-- Voir tous les résultats --}}
          <div
            x-show="!loading && results.length > 0"
            class="mt-4 border-t border-primary-800 pt-4"
            style="display: none;"
          >
            <button
              @click="goToArchive()"
              class="group cursor-pointer flex w-full items-center justify-center gap-2 rounded-full bg-secondary px-6 py-3 text-sm font-semibold text-primary-1000 transition-colors hover:bg-white"
            >
              Voir tous les résultats pour « <span x-text="query"></span> »
              <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const searchOverlayData = () => ({
    isOpen: false,
    query: '',
    results: [],
    loading: false,
    archiveUrl: '{{ $archiveUrl }}',

    open() {
      this.isOpen = true
      this.query = ''
      this.results = []
      document.body.style.overflow = 'hidden'
      this.$nextTick(() => {
        this.$refs.searchInput?.focus()
      })
    },

    close() {
      this.isOpen = false
      document.body.style.overflow = ''
    },

    async fetchResults() {
      if (this.query.length < 2) {
        this.results = []
        return
      }

      this.loading = true

      try {
        const url = new URL('/wp-json/radicle/v1/search', window.location.origin)
        url.searchParams.set('q', this.query)

        const response = await fetch(url.toString(), {
          headers: { 'Accept': 'application/json' },
        })

        if (response.ok) {
          this.results = await response.json()
        } else {
          this.results = []
        }
      } catch (e) {
        this.results = []
      } finally {
        this.loading = false
      }
    },

    goToArchive() {
      const url = new URL(this.archiveUrl, window.location.origin)
      url.searchParams.set('cruise_search', this.query)
      window.location.href = url.toString()
    },
  })

  if (window.Alpine) {
    window.Alpine.data('searchOverlay', searchOverlayData)
  } else {
    document.addEventListener('alpine:init', () => {
      window.Alpine.data('searchOverlay', searchOverlayData)
    })
  }
</script>
