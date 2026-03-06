<div
  class="bg-primary-1000 via-primary-1000 to-primary-1000 relative min-h-screen bg-gradient-to-b from-[#182646] from-20% via-20% font-sans"
>
  <img
    src="@asset('resources/images/waves.svg')"
    class="absolute top-1/5 z-0 h-auto w-full -translate-y-1/2"
    alt=""
  />
  <div class="container mx-auto max-w-7xl px-4">
    <div
      class="relative rounded-[30px] bg-[#EBF0F5] p-6 shadow-2xl md:p-10"
      x-data="globalPlanning('{{ wp_create_nonce('wp_rest') }}')"
    >
      {{-- LOADER OVERLAY --}}
      <div
        x-show="loading"
        class="absolute inset-0 z-50 flex items-center justify-center rounded-[30px] bg-white/50 backdrop-blur-sm"
        style="display: none"
      >
        <svg
          class="text-primary-600 h-12 w-12 animate-spin"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
      </div>

      {{-- 1. EN-TÊTE : Navigation Semaine --}}
      <div class="mb-8 flex items-center justify-center">
        <button
          @click="prevWeek()"
          class="text-primary-900 flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white shadow-sm transition-transform hover:scale-105 hover:bg-gray-50"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 19l-7-7 7-7"
            ></path>
          </svg>
        </button>

        <h1
          class="text-primary-900 font-heading mx-6 text-xl font-bold md:text-2xl"
          x-text="weekRangeLabel"
        ></h1>

        <button
          @click="nextWeek()"
          class="text-primary-900 flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white shadow-sm transition-transform hover:scale-105 hover:bg-gray-50"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            ></path>
          </svg>
        </button>
      </div>

      {{-- 3. BARRE DE FILTRES UNIFIÉE (Nouveau Design) --}}
      <div class="relative mb-10 flex justify-center md:justify-end">
        <button
          @click="filterMenuOpen = !filterMenuOpen"
          @click.away="filterMenuOpen = false"
          class="text-primary-900 z-20 flex items-center justify-center rounded-2xl border border-gray-200 bg-white px-6 py-3.5 font-bold shadow-sm transition-all hover:shadow-md"
        >
          <svg
            class="text-secondary mr-3 h-5 w-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2.5"
              d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
            ></path>
          </svg>
          Filtrer les départs

          {{-- Pastille de compteur de filtres actifs --}}
          <span
            x-show="activeFiltersCount > 0"
            class="bg-secondary text-primary-900 ml-3 rounded-full px-2 py-0.5 text-xs font-black"
            x-text="activeFiltersCount"
            style="display: none"
          ></span>
        </button>

        {{-- Panneau des filtres unifiés --}}
        <div
          x-show="filterMenuOpen"
          style="display: none"
          x-transition:enter="transition duration-200 ease-out"
          x-transition:enter-start="translate-y-4 opacity-0"
          x-transition:enter-end="translate-y-0 opacity-100"
          x-transition:leave="transition duration-150 ease-in"
          x-transition:leave-start="translate-y-0 opacity-100"
          x-transition:leave-end="translate-y-4 opacity-0"
          class="absolute top-full right-0 z-30 mt-4 w-[calc(100vw-3rem)] cursor-default rounded-3xl border border-gray-100 bg-white p-6 shadow-2xl md:right-auto md:w-[480px] md:p-8"
          @click.stop
        >
          <div class="mb-6 flex items-center justify-between border-b border-gray-100 pb-4">
            <h3 class="text-primary-900 text-lg font-bold">Vos filtres</h3>
            <button
              @click="resetFilters()"
              x-show="activeFiltersCount > 0"
              class="text-primary-400 hover:text-secondary text-sm font-bold transition-colors"
            >
              Tout effacer
            </button>
          </div>

          {{-- Filtre Date Rapide --}}
          <div class="mb-6">
            <p class="text-primary-400 mb-3 text-xs font-bold tracking-widest uppercase">
              Aller à la date
            </p>
            <div class="flex gap-2">
              <input
                type="date"
                x-model="datePickerValue"
                class="text-primary-900 focus:ring-secondary flex-1 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-bold focus:ring-2 focus:outline-none"
              />
              <button
                @click="goToToday(); filterMenuOpen = false;"
                class="bg-primary-50 text-primary-600 hover:bg-primary-100 rounded-xl px-4 py-2.5 text-sm font-bold transition-colors"
              >
                Aujourd'hui
              </button>
            </div>
          </div>

          {{-- Filtre Ports --}}
          <div class="mb-6">
            <p class="text-primary-400 mb-3 text-xs font-bold tracking-widest uppercase">
              Ports de départ
            </p>
            <div class="flex flex-wrap gap-2">
              @php
                $ports = get_terms(['taxonomy' => 'harbor', 'hide_empty' => false]);
              @endphp

              @foreach ($ports as $port)
                <button
                  @click="toggleFilter('ports', {{ $port->term_id }})"
                  class="rounded-full border px-4 py-2 text-sm font-bold transition-all duration-200"
                  :class="filters.ports.includes({{ $port->term_id }}) ? 'bg-primary-900 border-primary-900 text-white shadow-md' : 'bg-white border-gray-200 text-primary-600 hover:border-primary-400'"
                >
                  {{ $port->name }}
                </button>
              @endforeach
            </div>
          </div>

          {{-- Filtre Types --}}
          <div class="mb-6">
            <p class="text-primary-400 mb-3 text-xs font-bold tracking-widest uppercase">
              Types de croisière
            </p>
            <div class="flex flex-wrap gap-2">
              @php
                $types = get_terms(['taxonomy' => 'cruise_type', 'hide_empty' => false]);
              @endphp

              @foreach ($types as $type)
                <button
                  @click="toggleFilter('types', {{ $type->term_id }})"
                  class="rounded-full border px-4 py-2 text-sm font-bold transition-all duration-200"
                  :class="filters.types.includes({{ $type->term_id }}) ? 'bg-primary-900 border-primary-900 text-white shadow-md' : 'bg-white border-gray-200 text-primary-600 hover:border-primary-400'"
                >
                  {{ $type->name }}
                </button>
              @endforeach
            </div>
          </div>

          {{-- Filtre Tags (Nouveau) --}}
          <div>
            <p class="text-primary-400 mb-3 text-xs font-bold tracking-widest uppercase">
              Thématiques
            </p>
            <div class="flex flex-wrap gap-2">
              @php
                $tags = get_terms(['taxonomy' => 'cruise_tag', 'hide_empty' => false]);
              @endphp

              @foreach ($tags as $tag)
                <button
                  @click="toggleFilter('tags', {{ $tag->term_id }})"
                  class="rounded-full border px-4 py-2 text-sm font-bold transition-all duration-200"
                  :class="filters.tags.includes({{ $tag->term_id }}) ? 'bg-secondary border-secondary text-primary-900 shadow-md' : 'bg-white border-gray-200 text-primary-600 hover:border-secondary'"
                >
                  {{ $tag->name }}
                </button>
              @endforeach
            </div>
          </div>

          <div class="mt-8 flex justify-end border-t border-gray-100 pt-4">
            <button
              @click="filterMenuOpen = false"
              class="bg-secondary text-primary-900 hover:bg-secondary-hover rounded-xl px-8 py-3 font-bold shadow-md transition-colors"
            >
              Appliquer les filtres
            </button>
          </div>
        </div>
      </div>

      {{-- 4. GRILLE DU CALENDRIER (Scrollable sur mobile) --}}
      <div class="scrollbar-hide -mx-6 overflow-x-auto px-6 pb-6 md:mx-0 md:px-0">
        <div class="grid min-w-[900px] grid-cols-7 gap-2">
          {{-- Boucle sur les 7 jours --}}
          <template x-for="(day, index) in weekDays" :key="index">
            <div class="flex h-full flex-col">
              {{-- En-tête de la colonne (Jour) --}}
              <div
                class="rounded-t-2xl pt-2 pb-4 text-center text-sm font-bold tracking-wider uppercase transition-colors"
                :class="isToday(day) ? 'bg-secondary text-primary-900' : 'bg-transparent text-primary-900'"
                x-text="formatDayHeader(day)"
              ></div>

              {{-- Liste des croisières pour ce jour --}}
              <div
                class="flex flex-1 flex-col overflow-hidden gap-3 p-3"
                :class="isToday(day) ? 'bg-[#FFF5CA] rounded-b-2xl' : 'bg-white rounded-2xl'"
              >
                <template x-for="sailing in getFilteredSailingsForDay(day)" :key="sailing.id">
                  {{-- CARTE CROISIÈRE --}}
                  <div class="relative rounded-xl overflow-hidden"
                       :class="getCardStyle(sailing).bg + ' ' + getCardStyle(sailing).text + (getCardStyle(sailing).isPast ? ' opacity-60 pointer-events-none' : '')"
                  >
                    <div
                      class="relative flex flex-col p-3 text-center transition-all"
                    >
                      {{-- Effet Rayé si Annulé --}}
                      <template x-if="sailing.status === 'Annulé'">
                        <svg
                          class="pointer-events-none absolute inset-0 h-full w-full text-red-500 opacity-60"
                          preserveAspectRatio="none"
                          viewBox="0 0 100 100"
                        >
                          <line
                            x1="0"
                            y1="100"
                            x2="100"
                            y2="0"
                            stroke="currentColor"
                            stroke-width="4"
                          />
                        </svg>
                      </template>

                      {{-- Heure --}}
                      <span
                        class="mb-1 text-xs font-bold opacity-90"
                        x-text="formatTime(sailing.datetime)"
                      ></span>

                      {{-- Titre --}}
                      <h4
                        class="font-heading mb-1 text-[13px] leading-tight font-extrabold"
                        x-text="sailing.cruise_title"
                      ></h4>

                      {{-- Port --}}
                      <span class="mb-3 text-[11px] opacity-90" x-text="sailing.port"></span>

                      {{-- Bouton Réserver --}}
                      <a
                        :href="sailing.cruise_url"
                        class="z-10 mb-2 w-full rounded bg-white py-2 text-xs font-bold shadow-sm transition-shadow hover:shadow"
                        :class="getCardStyle(sailing).btnText + (!getCardStyle(sailing).isSelectable ? ' opacity-50 pointer-events-none' : '')"
                        x-text="getCardStyle(sailing).buttonLabel"
                      ></a>

                      {{-- Zone cachée : Détails des places et heures (S'insère ENTRE le bouton et la flèche) --}}
                      <div
                        x-show="expandedSailingId === sailing.id"
                        x-transition:enter="transition duration-200 ease-out"
                        x-transition:enter-start="-translate-y-2 opacity-0"
                        x-transition:enter-end="translate-y-0 opacity-100"
                        style="display: none"
                        class="z-10 w-full px-1 pb-3 text-left"
                      >
                        <div class="mb-1 flex items-center justify-between text-[11px]">
                          <span class="opacity-70">Départ :</span>
                          <span
                            class="font-bold opacity-90"
                            x-text="formatTime(sailing.datetime)"
                          ></span>
                        </div>
                        <div class="mb-1 flex items-center justify-between text-[11px]">
                          <span class="opacity-70">Retour :</span>
                          {{-- Fallback '--:--' si return_time n'est pas encore présent dans l'API --}}
                          <span
                            class="font-bold opacity-90"
                            x-text="sailing.return_time ? formatTime(sailing.return_time) : '--:--'"
                          ></span>
                        </div>
                        <div
                          class="mt-2 flex items-center justify-between border-t border-black/10 pt-2 text-[11px]"
                        >
                          <span class="opacity-70">Places libres :</span>
                          <span class="font-bold opacity-90" x-text="sailing.available"></span>
                        </div>
                      </div>

                      {{-- Statut Bottom Bar (Flèche toujours en bas) --}}
                      <span
                        class="text-[9px] font-black tracking-widest uppercase"
                        x-text="getCardStyle(sailing).label"
                      ></span>
                    </div>

                    {{-- Bouton Triangle --}}
                    <button
                      class="group/btn z-10 mt-auto flex w-full cursor-pointer flex-col bg-secondary text-black/50 hover:text-black items-center  py-2 transition-colors"
                      @click="toggleSailing(sailing.id)"
                    >
                      <svg
                        class="h-6 w-6"
                        :class="expandedSailingId === sailing.id ? 'rotate-180 opacity-100 scale-125' : ''"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                      >
                        {{-- Icône Triangle --}}
                        <path d="M7 10l5 5 5-5z"></path>
                      </svg>
                    </button>

                  </div>
                </template>

                {{-- Message si vide --}}
                <template x-if="getFilteredSailingsForDay(day).length === 0">
                  <div
                    class="flex h-24 flex-1 items-center justify-center rounded-2xl border-2 border-dashed opacity-50"
                    :class="isToday(day) ? 'border-secondary text-secondary' : 'text-primary-400 border-gray-300'"
                  >
                    <span class="text-xs font-bold">Aucun départ</span>
                  </div>
                </template>
              </div>
            </div>
          </template>
        </div>
      </div>

      <div
        class="text-primary-900 mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 rounded-full bg-white px-6 py-3 text-xs font-bold tracking-wider uppercase shadow-sm"
      >
        @foreach (config('sailing.statuses') as $key => $status)
          @if ($status['showInLegend'])
            <div class="flex items-center">
              <span
                class="{{ $status['bg'] }} {{ $key === 'Annulé' ? 'relative overflow-hidden' : '' }} mr-2 h-5 w-5 rounded shadow-inner"
              >
                @if ($key === 'Annulé')
                  <svg
                    class="absolute inset-0 h-full w-full text-red-500 opacity-80"
                    preserveAspectRatio="none"
                    viewBox="0 0 100 100"
                  >
                    <line x1="0" y1="100" x2="100" y2="0" stroke="currentColor" stroke-width="8" />
                  </svg>
                @endif
              </span>
              {{ $status['label'] }}
            </div>
          @endif
        @endforeach
      </div>
    </div>
  </div>
</div>
