<div class="relative bg-primary-1000 min-h-screen font-sans bg-gradient-to-b from-[#182646] from-20% via-20% via-primary-1000 to-primary-1000">
  <img
    src="@asset('resources/images/waves.svg')"
    class="absolute top-1/5 z-0 h-auto w-full -translate-y-1/2"
    alt=""
  />
  <div class="container mx-auto px-4 max-w-7xl">
    <div
      class="bg-[#EBF0F5] rounded-[30px] p-6 md:p-10 shadow-2xl relative"
      x-data="globalPlanning('{{ wp_create_nonce('wp_rest') }}')"
    >
      {{-- LOADER OVERLAY --}}
      <div x-show="loading" class="absolute inset-0 bg-white/50 backdrop-blur-sm z-50 flex items-center justify-center rounded-[30px]" style="display: none;">
        <svg class="animate-spin h-12 w-12 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
      </div>

      {{-- 1. EN-TÊTE : Navigation Semaine --}}
      <div class="flex justify-center items-center mb-8">
        <button @click="prevWeek()" class="w-10 h-10 flex items-center justify-center bg-white border border-gray-200 rounded-xl shadow-sm text-primary-900 hover:bg-gray-50 transition-transform hover:scale-105">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </button>

        <h1 class="text-xl md:text-2xl font-bold text-primary-900 mx-6 font-heading" x-text="weekRangeLabel"></h1>

        <button @click="nextWeek()" class="w-10 h-10 flex items-center justify-center bg-white border border-gray-200 rounded-xl shadow-sm text-primary-900 hover:bg-gray-50 transition-transform hover:scale-105">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </button>
      </div>

      {{-- 2. LÉGENDE --}}
      <div class="bg-white rounded-full py-3 px-6 shadow-sm mb-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-xs font-bold text-primary-900 uppercase tracking-wider">
        <div class="flex items-center"><span class="w-5 h-5 rounded bg-[#C5F8A5] mr-2 shadow-inner"></span> Disponible</div>
        <div class="flex items-center"><span class="w-5 h-5 rounded bg-[#FFA632] mr-2 shadow-inner"></span> Dernières places disponibles</div>
        <div class="flex items-center"><span class="w-5 h-5 rounded bg-[#FBF166] mr-2 shadow-inner"></span> Reporté</div>
        <div class="flex items-center"><span class="w-5 h-5 rounded bg-[#60386B] mr-2 shadow-inner relative overflow-hidden"><svg class="absolute inset-0 w-full h-full text-red-500 opacity-80" preserveAspectRatio="none" viewBox="0 0 100 100"><line x1="0" y1="100" x2="100" y2="0" stroke="currentColor" stroke-width="8" /></svg></span> Annulé</div>
        <div class="flex items-center"><span class="w-5 h-5 rounded bg-[#C33149] mr-2 shadow-inner"></span> Complet</div>
      </div>

      {{-- 3. BARRE DE FILTRES UNIFIÉE (Nouveau Design) --}}
      <div class="flex justify-center md:justify-end mb-10 relative">
        <button
          @click="filterMenuOpen = !filterMenuOpen"
          @click.away="filterMenuOpen = false"
          class="flex items-center justify-center bg-white px-6 py-3.5 rounded-2xl shadow-sm border border-gray-200 text-primary-900 font-bold hover:shadow-md transition-all z-20"
        >
          <svg class="w-5 h-5 mr-3 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
          Filtrer les départs

          {{-- Pastille de compteur de filtres actifs --}}
          <span x-show="activeFiltersCount > 0" class="ml-3 bg-secondary text-primary-900 text-xs px-2 py-0.5 rounded-full font-black" x-text="activeFiltersCount" style="display: none;"></span>
        </button>

        {{-- Panneau des filtres unifiés --}}
        <div
          x-show="filterMenuOpen"
          style="display: none;"
          x-transition:enter="transition ease-out duration-200"
          x-transition:enter-start="opacity-0 translate-y-4"
          x-transition:enter-end="opacity-100 translate-y-0"
          x-transition:leave="transition ease-in duration-150"
          x-transition:leave-start="opacity-100 translate-y-0"
          x-transition:leave-end="opacity-0 translate-y-4"
          class="absolute top-full mt-4 right-0 md:right-auto w-[calc(100vw-3rem)] md:w-[480px] bg-white rounded-3xl shadow-2xl border border-gray-100 z-30 p-6 md:p-8 cursor-default"
          @click.stop
        >
          <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
            <h3 class="font-bold text-lg text-primary-900">Vos filtres</h3>
            <button @click="resetFilters()" x-show="activeFiltersCount > 0" class="text-sm text-primary-400 hover:text-secondary font-bold transition-colors">Tout effacer</button>
          </div>

          {{-- Filtre Date Rapide --}}
          <div class="mb-6">
            <p class="text-xs font-bold text-primary-400 uppercase tracking-widest mb-3">Aller à la date</p>
            <div class="flex gap-2">
              <input type="date" x-model="datePickerValue" class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-bold text-primary-900 focus:outline-none focus:ring-2 focus:ring-secondary">
              <button @click="goToToday(); filterMenuOpen = false;" class="bg-primary-50 text-primary-600 px-4 py-2.5 rounded-xl font-bold text-sm hover:bg-primary-100 transition-colors">
                Aujourd'hui
              </button>
            </div>
          </div>

          {{-- Filtre Ports --}}
          <div class="mb-6">
            <p class="text-xs font-bold text-primary-400 uppercase tracking-widest mb-3">Ports de départ</p>
            <div class="flex flex-wrap gap-2">
              @php $ports = get_terms(['taxonomy' => 'harbor', 'hide_empty' => false]); @endphp
              @foreach($ports as $port)
                <button
                  @click="toggleFilter('ports', {{ $port->term_id }})"
                  class="px-4 py-2 rounded-full text-sm font-bold transition-all duration-200 border"
                  :class="filters.ports.includes({{ $port->term_id }}) ? 'bg-primary-900 border-primary-900 text-white shadow-md' : 'bg-white border-gray-200 text-primary-600 hover:border-primary-400'"
                >
                  {{ $port->name }}
                </button>
              @endforeach
            </div>
          </div>

          {{-- Filtre Types --}}
          <div class="mb-6">
            <p class="text-xs font-bold text-primary-400 uppercase tracking-widest mb-3">Types de croisière</p>
            <div class="flex flex-wrap gap-2">
              @php $types = get_terms(['taxonomy' => 'cruise_type', 'hide_empty' => false]); @endphp
              @foreach($types as $type)
                <button
                  @click="toggleFilter('types', {{ $type->term_id }})"
                  class="px-4 py-2 rounded-full text-sm font-bold transition-all duration-200 border"
                  :class="filters.types.includes({{ $type->term_id }}) ? 'bg-primary-900 border-primary-900 text-white shadow-md' : 'bg-white border-gray-200 text-primary-600 hover:border-primary-400'"
                >
                  {{ $type->name }}
                </button>
              @endforeach
            </div>
          </div>

          {{-- Filtre Tags (Nouveau) --}}
          <div>
            <p class="text-xs font-bold text-primary-400 uppercase tracking-widest mb-3">Thématiques</p>
            <div class="flex flex-wrap gap-2">
              @php $tags = get_terms(['taxonomy' => 'cruise_tag', 'hide_empty' => false]); @endphp
              @foreach($tags as $tag)
                <button
                  @click="toggleFilter('tags', {{ $tag->term_id }})"
                  class="px-4 py-2 rounded-full text-sm font-bold transition-all duration-200 border"
                  :class="filters.tags.includes({{ $tag->term_id }}) ? 'bg-secondary border-secondary text-primary-900 shadow-md' : 'bg-white border-gray-200 text-primary-600 hover:border-secondary'"
                >
                  {{ $tag->name }}
                </button>
              @endforeach
            </div>
          </div>

          <div class="mt-8 pt-4 border-t border-gray-100 flex justify-end">
            <button @click="filterMenuOpen = false" class="bg-secondary text-primary-900 font-bold px-8 py-3 rounded-xl hover:bg-secondary-hover transition-colors shadow-md">
              Appliquer les filtres
            </button>
          </div>
        </div>
      </div>

      {{-- 4. GRILLE DU CALENDRIER (Scrollable sur mobile) --}}
      <div class="overflow-x-auto pb-6 -mx-6 px-6 md:mx-0 md:px-0 scrollbar-hide">
        <div class="min-w-[900px] grid grid-cols-7 gap-4">

          {{-- Boucle sur les 7 jours --}}
          <template x-for="(day, index) in weekDays" :key="index">
            <div class="flex flex-col h-full">

              {{-- En-tête de la colonne (Jour) --}}
              <div
                class="text-center py-2.5 rounded-t-2xl font-bold uppercase tracking-wider text-sm mb-4 transition-colors"
                :class="isToday(day) ? 'bg-secondary text-primary-900' : 'bg-transparent text-primary-900'"
                x-text="formatDayHeader(day)"
              ></div>

              {{-- Liste des croisières pour ce jour --}}
              <div class="flex-1 flex flex-col gap-3">

                <template x-for="sailing in getFilteredSailingsForDay(day)" :key="sailing.id">
                  {{-- CARTE CROISIÈRE --}}
                  <div
                    class="flex flex-col rounded-2xl p-3 shadow-sm hover:shadow-md transition-all text-center relative overflow-hidden"
                    :class="getCardStyle(sailing.status).bg + ' ' + getCardStyle(sailing.status).text"
                  >
                    {{-- Effet Rayé si Annulé --}}
                    <template x-if="sailing.status === 'Annulé'">
                      <svg class="absolute inset-0 w-full h-full text-red-500 opacity-60 pointer-events-none" preserveAspectRatio="none" viewBox="0 0 100 100">
                        <line x1="0" y1="100" x2="100" y2="0" stroke="currentColor" stroke-width="4" />
                      </svg>
                    </template>

                    {{-- Heure --}}
                    <span class="text-xs font-bold mb-1 opacity-90" x-text="formatTime(sailing.datetime)"></span>

                    {{-- Titre --}}
                    <h4 class="font-extrabold text-[13px] leading-tight mb-1 font-heading" x-text="sailing.cruise_title"></h4>

                    {{-- Port --}}
                    <span class="text-[11px] mb-3 opacity-90" x-text="sailing.port"></span>

                    {{-- Bouton Réserver --}}
                    <a
                      :href="sailing.cruise_url"
                      class="w-full py-2 bg-white font-bold text-xs rounded shadow-sm hover:shadow transition-shadow mb-2 z-10"
                      :class="getCardStyle(sailing.status).btnText + (sailing.status === 'Annulé' || sailing.status === 'Complet' || sailing.status === 'Reporté' ? ' opacity-50 pointer-events-none' : '')"
                    >
                      Réserver
                    </a>

                    {{-- Statut Bottom Bar --}}
                    <div class="w-full border-t border-black/10 pt-2 pb-1 flex flex-col items-center mt-auto z-10">
                      <span class="text-[9px] uppercase font-black tracking-widest" x-text="sailing.status === 'Dispo' ? 'DISPONIBLE' : sailing.status.toUpperCase()"></span>
                      <svg class="w-3 h-3 mt-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                  </div>
                </template>

                {{-- Message si vide --}}
                <template x-if="getFilteredSailingsForDay(day).length === 0">
                  <div class="flex-1 flex items-center justify-center border-2 border-dashed border-gray-300 rounded-2xl bg-white/50 opacity-50 h-24">
                    <span class="text-xs font-bold text-gray-400">Aucun départ</span>
                  </div>
                </template>

              </div>

            </div>
          </template>

        </div>
      </div>
      {{-- Fin Grille --}}

    </div>
  </div>
</div>
