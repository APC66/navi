<div
  id="booking-widget-{{ $cruiseId }}"
  class="booking-widget text-primary-900 relative"
  x-data="bookingWidget({{ $cruiseId }}, '{{ wp_create_nonce('wp_rest') }}')"
  x-init="init()"
>
  <h3 class="text-center text-3xl text-white uppercase font-medium my-12 ">
    Réserver votre
    <span class="text-secondary">croisière</span>
  </h3>
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-12">
    <div
      class="calendar-section bg-primary-50 flex h-full flex-col rounded-2xl p-6 shadow-sm lg:p-10"
    >
      <div class="flex-1">
        <div class="mb-6 flex items-center justify-between">
          <button
            @click="changeMonth(-1)"
            class="text-primary-900 flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white shadow-sm transition-colors hover:bg-gray-50"
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
          <span
            class="text-primary-900 font-heading text-xl font-bold capitalize md:text-2xl"
            x-text="monthName"
          ></span>
          <button
            @click="changeMonth(1)"
            class="text-primary-900 flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white shadow-sm transition-colors hover:bg-gray-50"
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

        <div class="rounded-2xl bg-[#FBF8F0] p-6">
          <div
            class="text-primary-900 mb-2 grid grid-cols-7 text-center text-xs font-bold tracking-widest uppercase"
          >
            <div>Lun</div>
            <div>Mar</div>
            <div>Mer</div>
            <div>Jeu</div>
            <div>Ven</div>
            <div>Sam</div>
            <div>Dim</div>
          </div>

          <div class="relative grid grid-cols-7 gap-1.5 text-sm md:gap-2">
            <div
              x-show="loading"
              class="absolute inset-0 z-20 flex flex-col items-center justify-center rounded-xl bg-white/80 backdrop-blur-sm"
            >
              <svg
                class="text-primary-600 mb-2 h-8 w-8 animate-spin"
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
              <span class="text-primary-600 text-sm font-bold">Chargement...</span>
            </div>

            <template x-for="(dayObj, index) in calendarGrid" :key="index">
              <div
                class="relative aspect-square w-full overflow-hidden rounded-xl border-4 shadow-sm transition-transform duration-200"
                :class="getDayClasses(dayObj)"
                @click="handleDayClick(dayObj)"
              >
                <button
                  class="flex h-full w-full flex-col items-center justify-center p-1 disabled:cursor-not-allowed"
                  :disabled="dayObj.empty || !dayObj.sailing || dayObj.isPast || !dayObj.isSelectable"
                >
                  <span x-text="dayObj.day" class="text-sm font-bold md:text-base"></span>

                  <template x-if="dayObj.statusLabel">
                    <span
                      class="xs:block mt-1 hidden text-[9px] font-extrabold tracking-tighter uppercase"
                      x-text="dayObj.statusLabel"
                    ></span>
                  </template>

                  <template x-if="dayObj.status === 'Annulé'">
                    <svg
                      class="pointer-events-none absolute inset-0 h-full w-full text-red-500 opacity-90"
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
                </button>
              </div>
            </template>
          </div>
        </div>
      </div>

      <div
        class="bg-text-primary-900 mt-6 grid grid-cols-1 gap-x-4 gap-y-3 rounded-2xl bg-[#FBF8F0] p-5 text-sm font-bold shadow-sm sm:grid-cols-2"
      >
        <div class="flex items-center">
          <span class="mr-3 h-4 w-4 rounded bg-[#C5F8A5] shadow-sm"></span>
          <span class="shrink-1">Disponible</span>
        </div>
        <div class="flex items-center">
          <span class="mr-3 h-4 w-4 rounded bg-[#FFA632] shadow-sm"></span>
          <span class="shrink-1">Dernièr places disponibles</span>
        </div>
        <div class="flex items-center">
          <span class="mr-3 h-4 w-4 rounded bg-[#FBF166] shadow-sm"></span>
          <span class="shrink-1">Reporté</span>
        </div>
        <div class="flex items-center">
          <span class="relative mr-3 h-4 w-4 overflow-hidden rounded bg-[#60386B] shadow-sm">
            <svg
              class="absolute inset-0 h-full w-full text-red-500"
              preserveAspectRatio="none"
              viewBox="0 0 100 100"
            >
              <line x1="0" y1="100" x2="100" y2="0" stroke="currentColor" stroke-width="8" />
            </svg>
          </span>
          <span class="shrink-1">Annulé</span>
        </div>
        <div class="flex items-center">
          <span class="mr-3 h-4 w-4 rounded bg-[#C33149] shadow-sm"></span>
          <span class="shrink-1">Complet</span>
        </div>
      </div>
    </div>

    <div class="form-section bg-primary-50 flex h-full flex-col rounded-2xl p-6 lg:p-10">
      <div
        x-show="!currentSailing"
        class="text-primary-600/50 flex min-h-[400px] flex-1 flex-col items-center justify-center text-center"
      >
        <svg
          class="mb-4 h-16 w-16 opacity-30"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
          ></path>
        </svg>
        <p class="text-lg font-bold">
          Sélectionnez une date
          <br />
          sur le calendrier.
        </p>
      </div>

      <template x-if="currentSailing">
        <div class="animate-fade-in flex h-full flex-col">
          <!-- En-tête Départ -->
          <div class="mb-8">
            <div class="mb-2 flex items-start justify-between">
              <div>
                <h4 class="text-primary-600 mb-1 font-bold tracking-widest uppercase">
                  Votre départ
                </h4>
                <div
                  class="text-primary-900 font-heading text-2xl leading-none font-medium uppercase md:text-3xl"
                  x-text="formatHeaderDate(currentSailing.start)"
                ></div>
              </div>
              <div
                class="ml-4 rounded-full bg-[#C6F6D5] px-4 py-1.5 text-xs font-bold whitespace-nowrap text-[#166534] shadow-sm"
              >
                <span x-text="currentSailing.extendedProps.available"></span>
                places dispo
              </div>
            </div>
            <div class="text-primary-600 mt-3 font-bold tracking-widest uppercase opacity-80">
              DÉPART DEPUIS
              <span x-text="currentSailing.port || 'VOTRE PORT DE CROISIÈRE'"></span>
            </div>
          </div>

          <div class="mb-8">
            <h4 class="text-primary-900 mb-2 text-lg font-bold tracking-widest uppercase">
              Passagers
            </h4>
            <div class="space-y-0">
              <template
                x-for="(fare, index) in currentSailing.extendedProps.fares"
                :key="fare.id"
              >
                <div>
                  <div class="flex items-center justify-between py-2">
                    <div class="flex flex-col">
                      <span class="text-primary-900 block font-bold" x-text="fare.name"></span>
                      <span
                        class="text-primary-600 font-medium"
                        x-text="formatPrice(fare.price)"
                      ></span>
                    </div>
                    <div class="flex items-center space-x-3">
                      <button
                        type="button"
                        @click="decrementPassenger(fare.id)"
                        class="text-primary-900 flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white font-bold shadow-sm transition-colors hover:bg-gray-50 disabled:opacity-50"
                        :disabled="!passengers[fare.id]"
                      >
                        -
                      </button>
                      <span
                        class="text-primary-900 w-4 text-center font-bold"
                        x-text="passengers[fare.id] || 0"
                      ></span>
                      <button
                        type="button"
                        @click="incrementPassenger(fare.id)"
                        class="text-primary-900 flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white font-bold shadow-sm transition-colors hover:bg-gray-50"
                      >
                        +
                      </button>
                    </div>
                  </div>
                  <hr
                    class="border-primary-200/50 my-2"
                    x-show="index < currentSailing.extendedProps.fares.length - 1"
                  />
                </div>
              </template>
            </div>
          </div>

          <!-- OPTIONS -->
          <div
            x-show="
              currentSailing.extendedProps.options &&
                currentSailing.extendedProps.options.length > 0
            "
            class="mb-8"
          >
            <h4 class="text-primary-900 mb-2 text-lg font-bold tracking-widest uppercase">
              Options
            </h4>
            <div class="space-y-0">
              <template
                x-for="(option, index) in currentSailing.extendedProps.options"
                :key="option.id"
              >
                <div>
                  <div class="flex items-center justify-between py-2">
                    <div class="flex flex-col">
                      <span class="text-primary-900 block font-bold" x-text="option.name"></span>
                      <span
                        class="text-primary-600 font-medium"
                        x-text="formatPrice(option.price)"
                      ></span>
                    </div>
                    <div class="flex items-center space-x-3">
                      <button
                        type="button"
                        @click="decrementOption(option.id)"
                        class="text-primary-900 flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white font-bold shadow-sm transition-colors hover:bg-gray-50 disabled:opacity-50"
                        :disabled="!selectedOptions[option.id]"
                      >
                        -
                      </button>
                      <span
                        class="text-primary-900 w-4 text-center font-bold"
                        x-text="selectedOptions[option.id] || 0"
                      ></span>
                      <button
                        type="button"
                        @click="incrementOption(option.id, option.has_quota ? option.quota : 999)"
                        class="text-primary-900 flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white font-bold shadow-sm transition-colors hover:bg-gray-50"
                      >
                        +
                      </button>
                    </div>
                  </div>
                  <hr
                    class="border-primary-200/50 my-2"
                    x-show="index < currentSailing.extendedProps.options.length - 1"
                  />
                </div>
              </template>
            </div>
          </div>

          <!-- TOTAL & ACTION -->
          <div class="border-primary-200 mt-auto border-t pt-6">
            <div class="mb-8 flex items-end justify-between">
              <span class="text-primary-900 text-xl font-bold tracking-widest uppercase">
                Total
              </span>
              <span
                class="text-primary-900 text-3xl leading-none font-extrabold lg:text-4xl"
                x-text="formatPrice(totalPrice)"
              ></span>
            </div>

            <button
              @click="addToCart"
              :disabled="totalPrice <= 0 || adding"
              class="bg-secondary text-primary-900 hover:bg-secondary-hover flex w-full transform items-center justify-center rounded-full px-6 py-4 text-xl font-bold shadow-lg transition-all hover:-translate-y-1 hover:shadow-xl active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
            >
              <span x-show="!adding">Réserver maintenant</span>
              <span x-show="adding" class="flex items-center">
                <svg
                  class="text-primary-900 mr-3 -ml-1 h-5 w-5 animate-spin"
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
                Redirection...
              </span>
            </button>

            @php
              $giftCardPages = get_posts([
                'post_type'      => 'page',
                'posts_per_page' => 1,
                'meta_query'     => [[
                  'key'     => '_wp_page_template',
                  'value'   => 'gift-card',
                  'compare' => 'LIKE',
                ]],
              ]);
              $giftCardUrl = !empty($giftCardPages) ? get_permalink($giftCardPages[0]->ID) : '';
            @endphp

            @if($giftCardUrl)
              <a
                href="{{ $giftCardUrl }}?cruise_id={{ $cruiseId }}"
                class="mt-3 flex w-full items-center justify-center rounded-full border-2 border-secondary px-6 py-4 text-xl font-bold text-secondary transition-all hover:-translate-y-1 hover:bg-secondary/10"
              >
                Offrir cette croisière
              </a>
            @endif

            <div
              x-show="message"
              class="mt-4 text-center text-sm font-bold"
              :class="messageType === 'error' ? 'text-red-600' : 'text-green-600'"
              x-text="message"
              x-transition
            ></div>
          </div>
        </div>
      </template>
    </div>
  </div>
</div>
