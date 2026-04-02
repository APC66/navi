<div
  class="bg-primary-1000 via-primary-1000 to-primary-1000 relative min-h-screen bg-gradient-to-b from-[#182646] from-20% via-20% font-sans pb-16"
  x-data="giftCard('{{ wp_create_nonce('wp_rest') }}', '{{ $buyerEmail }}')"
>
  <img
    src="@asset('resources/images/waves.svg')"
    class="absolute top-1/5 z-0 h-auto w-full -translate-y-1/2 pointer-events-none"
    alt=""
  />

  <div class="container mx-auto max-w-3xl px-4 relative z-10">
    {{-- Titre --}}
    <div class="text-center mb-10">
      <h1 class="text-white text-4xl font-bold uppercase tracking-wide">
        Offrez une <span class="text-secondary">croisière</span>
      </h1>
      <p class="text-primary-200 mt-3 text-lg">Créez une carte cadeau personnalisée pour vos proches.</p>
    </div>

    {{-- Indicateur d'étapes --}}
    <div class="flex items-center justify-center mb-10 gap-2">
      <template x-for="(label, i) in stepLabels" :key="i">
        <div class="flex items-center gap-2">
          <div
            class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold transition-all"
            :class="step === i + 1 ? 'bg-secondary text-primary-900' : (step > i + 1 ? 'bg-green-500 text-white' : 'bg-primary-700 text-primary-300')"
            x-text="step > i + 1 ? '✓' : (i + 1)"
          ></div>
          <span
            class="text-sm font-medium hidden sm:inline"
            :class="step === i + 1 ? 'text-secondary' : 'text-primary-400'"
            x-text="label"
          ></span>
          <div x-show="i < stepLabels.length - 1" class="w-8 h-px bg-primary-700 mx-1"></div>
        </div>
      </template>
    </div>

    {{-- Carte principale --}}
    <div class="bg-primary-50 rounded-3xl shadow-2xl overflow-hidden">

      {{-- ===================== ÉTAPE 1 : Choix du mode ===================== --}}
      <div x-show="step === 1" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="p-8 md:p-12">
          <h2 class="text-primary-900 text-2xl font-bold mb-2">Quel type de carte cadeau ?</h2>
          <p class="text-primary-400 mb-8">Choisissez entre une croisière spécifique ou un montant libre.</p>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            {{-- Option : Croisière --}}
            <button
              @click="selectMode('cruise')"
              class="group relative cursor-pointer flex flex-col items-center justify-center p-8 rounded-2xl border-2 transition-all text-left"
              :class="mode === 'cruise' ? 'border-secondary bg-secondary/10 shadow-md' : 'border-primary-100 hover:border-primary-300 '"
            >
              <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4 transition-colors"
                   :class="mode === 'cruise' ? 'bg-secondary' : 'bg-primary-100'">

                <svg class="w-8 h-8 text-primary-900" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="none" stroke="currentColor"><path d="M461.93,261.05c-2-4.76-6.71-7.83-11.67-9.49L263.08,177.08a23.78,23.78,0,0,0-14.17,0l-187,74.52c-5,1.56-9.83,4.77-11.81,9.53s-2.94,9.37-1,15.08L95.63,395.36A7.46,7.46,0,0,0,103.1,400c26.69-1.68,50.31-15.23,68.38-32.5a7.66,7.66,0,0,1,10.49,0C201.29,386,227,400,256,400s54.56-14,73.88-32.54a7.67,7.67,0,0,1,10.5,0c18.07,17.28,41.69,30.86,68.38,32.54a7.45,7.45,0,0,0,7.46-4.61l46.7-119.16C464.9,271.45,463.91,265.82,461.93,261.05Z" style="fill:none;stroke:currentColor;stroke-miterlimit:10;stroke-width:32px"></path><path d="M416,473.14a6.84,6.84,0,0,0-3.56-6c-27.08-14.55-51.77-36.82-62.63-48a10.05,10.05,0,0,0-12.72-1.51c-50.33,32.42-111.61,32.44-161.95.05a10.09,10.09,0,0,0-12.82,1.56c-10.77,11.28-35.19,33.3-62.43,47.75A7.15,7.15,0,0,0,96,472.72a6.73,6.73,0,0,0,7.92,7.15c20.85-4.18,41-13.68,60.2-23.83a8.71,8.71,0,0,1,8-.06A185.14,185.14,0,0,0,340,456a8.82,8.82,0,0,1,8.09.06c19.1,10,39.22,19.59,60,23.8a6.72,6.72,0,0,0,7.95-6.71Z"></path><path d="M320,96V72a24.07,24.07,0,0,0-24-24H216a24.07,24.07,0,0,0-24,24V96" style="fill:none;stroke:currentColor;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px"></path><path d="M416,233V144a48.14,48.14,0,0,0-48-48H144a48.14,48.14,0,0,0-48,48v92" style="fill:none;stroke:currentColor;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px"></path><line x1="256" y1="183.6" x2="256" y2="396.45" style="fill:none;stroke:currentColor;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px"></line></svg>
              </div>
              <h3 class="text-primary-900 text-lg font-bold mb-1">Choisir une croisière</h3>
              <p class="text-primary-400 text-sm text-center">Offrez une croisière précise avec ses tarifs et options.</p>
              <div x-show="mode === 'cruise'" class="absolute top-4 right-4 w-6 h-6 bg-secondary rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-primary-900" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
              </div>
            </button>

            {{-- Option : Montant libre --}}
            <button
              @click="selectMode('free')"
              class="group relative flex flex-col items-center cursor-pointer justify-center p-8 rounded-2xl border-2 transition-all text-left"
              :class="mode === 'free' ? 'border-secondary bg-secondary/10 shadow-md' : 'border-primary-100 hover:border-primary-300 '"
            >
              <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4 transition-colors"
                   :class="mode === 'free' ? 'bg-secondary' : 'bg-primary-100'">
                <svg class="w-8 h-8 text-primary-900" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" fill="currentColor"><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372zm117.7-588.6c-15.9-3.5-34.4-5.4-55.3-5.4-106.7 0-178.9 55.7-198.6 149.9H344c-4.4 0-8 3.6-8 8v27.2c0 4.4 3.6 8 8 8h26.4c-.3 4.1-.3 8.4-.3 12.8v36.9H344c-4.4 0-8 3.6-8 8V568c0 4.4 3.6 8 8 8h30.2c17.2 99.2 90.4 158 200.2 158 20.9 0 39.4-1.7 55.3-5.1 3.7-.8 6.4-4 6.4-7.8v-42.8c0-5-4.6-8.8-9.5-7.8-14.7 2.8-31.9 4.1-51.8 4.1-68.5 0-114.5-36.6-129.8-98.6h130.6c4.4 0 8-3.6 8-8v-27.2c0-4.4-3.6-8-8-8H439.2v-36c0-4.7 0-9.4.3-13.8h135.9c4.4 0 8-3.6 8-8v-27.2c0-4.4-3.6-8-8-8H447.1c17.2-56.9 62.3-90.4 127.6-90.4 19.9 0 37.1 1.5 51.7 4.4a8 8 0 0 0 9.6-7.8v-42.8c0-3.8-2.6-7-6.3-7.8z"></path></svg>
              </div>
              <h3 class="text-primary-900 text-lg font-bold mb-1">Montant libre</h3>
              <p class="text-primary-400 text-sm text-center">Définissez librement le montant de la carte cadeau.</p>
              <div x-show="mode === 'free'" class="absolute top-4 right-4 w-6 h-6 bg-secondary rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-primary-900" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
              </div>
            </button>
          </div>

          {{-- Sélecteur de croisière (si mode cruise) --}}
          <div x-show="mode === 'cruise'" x-transition class="mb-6">
            <label class="block text-primary-900 font-bold mb-2">Choisissez une croisière</label>
            <select
              x-model="selectedCruiseId"
              @change="loadPricing()"
              class="w-full rounded-xl border border-primary-100 bg-gray-50 px-4 py-3 text-primary-900 font-medium focus:ring-2 focus:ring-secondary focus:outline-none"
            >
              <option value="">— Sélectionner une croisière —</option>
              @foreach ($cruises as $cruise)
                <option value="{{ $cruise['id'] }}">{!! $cruise['title']  !!}@if($cruise['base_price']) — À partir de {{ number_format($cruise['base_price'], 2, ',', ' ') }} €@endif</option>
              @endforeach
            </select>
            <div x-show="loadingPricing" class="mt-2 text-sm text-primary-400 flex items-center gap-2">
              <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
              Chargement des tarifs...
            </div>

            {{-- Description de la croisière (WYSIWYG) --}}
            <div
              x-show="cruiseDescription"
              x-transition
              class="mt-4 p-4 text-primary-900 contentText"
              x-html="cruiseDescription"
            ></div>
          </div>

          {{-- Montant libre --}}
          <div x-show="mode === 'free'" x-transition class="mb-6">
            <label class="block text-primary-900 font-bold mb-2">Montant de la carte cadeau (€)</label>
            <div class="relative">
              <input
                type="number"
                x-model.number="freeAmount"
                min="1"
                step="1"
                placeholder="Ex : 150"
                class="w-full rounded-xl border border-primary-100 bg-gray-50 px-4 py-3 pr-12 text-primary-900 font-medium focus:ring-2 focus:ring-secondary focus:outline-none"
              />
              <span class="absolute right-4 top-1/2 -translate-y-1/2 text-primary-400 font-bold">€</span>
            </div>
          </div>

          {{-- Bouton suivant --}}
          <div class="flex justify-end">
            <button
              @click="goToStep2()"
              :disabled="!canGoToStep2"
              class="bg-secondary text-primary-900 hover:bg-secondary-hover font-bold px-8 py-3 rounded-full shadow-md transition-all hover:-translate-y-0.5 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:translate-y-0"
            >
              Continuer →
            </button>
          </div>
        </div>
      </div>

      {{-- ===================== ÉTAPE 2 : Détails (si croisière) ===================== --}}
      <div x-show="step === 2 && mode === 'cruise'" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="p-8 md:p-12">
          <button @click="step = 1" class="text-primary-400 hover:text-primary-600 text-sm font-bold mb-6 flex items-center gap-1 transition-colors">
            ← Retour
          </button>

          <h2 class="text-primary-900 text-2xl font-bold mb-1" x-text="'Croisière : ' + cruiseTitle"></h2>
          <p class="text-primary-400 mb-8">Configurez votre carte cadeau.</p>

          {{-- Choix de la saison --}}
          <div class="mb-8">
            <h3 class="text-primary-900 font-bold text-lg mb-4 tracking-wide uppercase">Saison</h3>
            <div class="grid grid-cols-2 gap-4">
              <button
                @click="season = 'low'"
                class="flex flex-col items-center cursor-pointer p-5 rounded-2xl border-2 transition-all"
                :class="season === 'low' ? 'border-secondary bg-secondary/10 shadow-md' : 'border-primary-100 hover:border-primary-300'"
              >
                <span class="font-bold text-primary-900">Basse Saison</span>
                <span class="text-primary-400 text-xs mt-1" x-show="lowSeasonLabel" x-text="lowSeasonLabel"></span>
                <div x-show="season === 'low'" class="mt-2 w-5 h-5 bg-secondary rounded-full flex items-center justify-center">
                  <svg class="w-3 h-3 text-primary-900" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </div>
              </button>
              <button
                @click="season = 'high'"
                class="flex flex-col items-center cursor-pointer p-5 rounded-2xl border-2 transition-all"
                :class="season === 'high' ? 'border-secondary bg-secondary/10 shadow-md' : 'border-primary-100 hover:border-primary-300'"
              >
                <span class="font-bold text-primary-900">Haute Saison</span>
                <span class="text-primary-400 text-xs mt-1" x-show="highSeasonLabel" x-text="highSeasonLabel"></span>
                <div x-show="season === 'high'" class="mt-2 w-5 h-5 bg-secondary rounded-full flex items-center justify-center">
                  <svg class="w-3 h-3 text-primary-900" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </div>
              </button>
            </div>
          </div>

          {{-- Passagers --}}
          <div class="mb-8" x-show="pricingRows.length > 0">
            <h3 class="text-primary-900 font-bold text-lg mb-4 tracking-wide uppercase">Passagers</h3>
            <div class="space-y-1">
              <template x-for="(row, index) in pricingRows" :key="row.id">
                <div>
                  <div class="flex items-center justify-between py-3">
                    <div>
                      <span class="text-primary-900 font-bold block" x-text="row.name"></span>
                      <span class="text-primary-400 text-sm" x-text="formatPrice(season === 'high' ? row.price_high : row.price_low) + ' / pers.'"></span>
                    </div>
                    <div class="flex items-center gap-3">
                      <button
                        type="button"
                        @click="decrementPassenger(row.id)"
                        :disabled="!passengers[row.id]"
                        class="w-9 h-9 flex items-center cursor-pointer justify-center text-primary-1000 rounded-xl border border-primary-100 bg-primary-50 font-bold shadow-sm  disabled:opacity-40 transition-colors"
                      >−</button>
                      <span class="w-6 text-center font-bold text-primary-900" x-text="passengers[row.id] || 0"></span>
                      <button
                        type="button"
                        @click="incrementPassenger(row.id)"
                        class="w-9 h-9 flex items-center cursor-pointer justify-center text-primary-1000 rounded-xl border border-primary-100 bg-primary-50 font-bold shadow-sm  transition-colors"
                      >+</button>
                    </div>
                  </div>
                  <hr class="border-gray-100" x-show="index < pricingRows.length - 1" />
                </div>
              </template>
            </div>
          </div>

          {{-- Options extras --}}
          <div class="mb-8" x-show="optionsPricing.length > 0">
            <h3 class="text-primary-900 font-bold text-lg mb-4 tracking-wide uppercase">Options</h3>
            <div class="space-y-1">
              <template x-for="(opt, index) in optionsPricing" :key="opt.id">
                <div>
                  <div class="flex items-center justify-between py-3">
                    <div>
                      <span class="text-primary-900 font-bold block" x-text="opt.name"></span>
                      <span class="text-primary-400 text-sm" x-text="formatPrice(season === 'high' ? opt.price_high : opt.price_low) + ' / unité'"></span>
                    </div>
                    <div class="flex items-center gap-3">
                      <button
                        type="button"
                        @click="decrementOption(opt.id)"
                        :disabled="!options[opt.id]"
                        class="w-9 h-9 flex items-center justify-center rounded-xl text-primary-1000 border border-primary-100 bg-primary-50 font-bold shadow-sm  disabled:opacity-40 transition-colors"
                      >−</button>
                      <span class="w-6 text-center font-bold text-primary-900" x-text="options[opt.id] || 0"></span>
                      <button
                        type="button"
                        @click="incrementOption(opt.id)"
                        class="w-9 h-9 flex items-center justify-center text-primary-1000 rounded-xl border border-primary-100 bg-primary-50 font-bold shadow-sm  transition-colors"
                      >+</button>
                    </div>
                  </div>
                  <hr class="border-gray-100" x-show="index < optionsPricing.length - 1" />
                </div>
              </template>
            </div>
          </div>

          {{-- Total temps réel --}}
          <div class="bg-primary-50 rounded-2xl p-5 mb-8 flex items-center justify-between">
            <span class="text-primary-900 font-bold text-lg">Total estimé</span>
            <span class="text-primary-900 text-3xl font-extrabold" x-text="formatPrice(totalPrice)"></span>
          </div>

          <div class="flex justify-between">
            <button @click="step = 1" class="text-primary-400 hover:text-primary-600 font-bold px-6 py-3 rounded-full border border-primary-100 transition-colors">
              ← Retour
            </button>
            <button
              @click="goToStep3()"
              :disabled="!canGoToStep3"
              class="bg-secondary text-primary-900 hover:bg-secondary-hover font-bold px-8 py-3 rounded-full shadow-md transition-all hover:-translate-y-0.5 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:translate-y-0"
            >
              Continuer →
            </button>
          </div>
        </div>
      </div>

      {{-- ===================== ÉTAPE 3 : Destinataire & Récap ===================== --}}
      <div x-show="step === 3 || (step === 2 && mode === 'free')" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="p-8 md:p-12">
          <button @click="step = mode === 'free' ? 1 : 2" class="text-primary-400 hover:text-primary-600 text-sm font-bold mb-6 flex items-center gap-1 transition-colors">
            ← Retour
          </button>

          <h2 class="text-primary-900 text-2xl font-bold mb-1">Destinataire & Message</h2>
          <p class="text-primary-400 mb-8">Personnalisez votre carte cadeau.</p>

          {{-- Checkbox "M'envoyer à moi-même" --}}
          @auth
            <div class="mb-6 p-4 bg-primary-50 rounded-2xl">
              <label class="flex items-center gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  x-model="sendToSelf"
                  @change="handleSendToSelf()"
                  class="w-5 h-5 rounded accent-secondary"
                />
                <span class="text-primary-900 font-bold">M'envoyer le code à moi-même</span>
              </label>
              <p class="text-primary-400 text-sm mt-1 ml-8" x-show="buyerEmail">
                Le code sera envoyé à <span class="font-bold" x-text="buyerEmail"></span>
              </p>
            </div>
          @endauth

          {{-- Email destinataire --}}
          <div class="mb-6" x-show="!sendToSelf">
            <label class="block text-primary-900 font-bold mb-2">Email du destinataire <span class="text-red-500">*</span></label>
            <input
              type="email"
              x-model="recipientEmail"
              placeholder="prenom.nom@email.com"
              class="w-full rounded-xl border border-primary-100 bg-gray-50 px-4 py-3 text-primary-900 font-medium focus:ring-2 focus:ring-secondary focus:outline-none"
              :class="recipientEmailError ? 'border-red-400' : ''"
            />
            <p x-show="recipientEmailError" class="text-red-500 text-sm mt-1" x-text="recipientEmailError"></p>
          </div>

          {{-- Message personnalisé --}}
          <div class="mb-8">
            <label class="block text-primary-900 font-bold mb-2">Message personnalisé <span class="text-primary-400 font-normal">(optionnel)</span></label>
            <textarea
              x-model="recipientMessage"
              rows="4"
              placeholder="Joyeux anniversaire ! Profite bien de cette belle aventure..."
              class="w-full rounded-xl border border-primary-100 bg-gray-50 px-4 py-3 text-primary-900 font-medium focus:ring-2 focus:ring-secondary focus:outline-none resize-none"
            ></textarea>
          </div>

          {{-- Récapitulatif --}}
          <div class="bg-primary-50 rounded-2xl p-6 mb-8">
            <h3 class="text-primary-900 font-bold text-lg mb-4 uppercase tracking-wide">Récapitulatif</h3>

            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-primary-400 font-medium">Type</span>
                <span class="text-primary-900 font-bold" x-text="mode === 'cruise' ? 'Croisière spécifique' : 'Montant libre'"></span>
              </div>
              <div class="flex justify-between" x-show="mode === 'cruise'">
                <span class="text-primary-400 font-medium">Croisière</span>
                <span class="text-primary-900 font-bold" x-text="cruiseTitle"></span>
              </div>
              <div class="flex justify-between" x-show="mode === 'cruise'">
                <span class="text-primary-400 font-medium">Saison</span>
                <span class="text-primary-900 font-bold" x-text="season === 'high' ? 'Haute Saison' : 'Basse Saison'"></span>
              </div>
              <template x-for="row in pricingRows" :key="row.id">
                <div class="flex justify-between" x-show="passengers[row.id] > 0">
                  <span class="text-primary-400 font-medium" x-text="row.name"></span>
                  <span class="text-primary-900 font-bold" x-text="(passengers[row.id] || 0) + ' × ' + formatPrice(season === 'high' ? row.price_high : row.price_low)"></span>
                </div>
              </template>
              <template x-for="opt in optionsPricing" :key="opt.id">
                <div class="flex justify-between" x-show="options[opt.id] > 0">
                  <span class="text-primary-400 font-medium" x-text="opt.name"></span>
                  <span class="text-primary-900 font-bold" x-text="(options[opt.id] || 0) + ' × ' + formatPrice(season === 'high' ? opt.price_high : opt.price_low)"></span>
                </div>
              </template>
              <div class="flex justify-between" x-show="mode === 'free'">
                <span class="text-primary-400 font-medium">Montant</span>
                <span class="text-primary-900 font-bold" x-text="formatPrice(freeAmount)"></span>
              </div>
            </div>

            <div class="border-t border-primary-200 mt-4 pt-4 flex items-center justify-between">
              <span class="text-primary-900 font-bold text-lg">Total</span>
              <span class="text-primary-900 text-3xl font-extrabold" x-text="formatPrice(finalAmount)"></span>
            </div>
          </div>

          {{-- Message d'erreur global --}}
          <div
            x-show="errorMessage"
            class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm font-bold"
            x-text="errorMessage"
            x-transition
          ></div>

          {{-- Bouton Ajouter au panier --}}
          <button
            @click="addToCart()"
            :disabled="adding || !canAddToCart"
            class="w-full bg-secondary text-primary-900 hover:bg-secondary-hover font-bold px-8 py-4 rounded-full shadow-lg text-xl transition-all hover:-translate-y-1 hover:shadow-xl disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:translate-y-0"
          >
            <span x-show="!adding" class="flex items-center justify-center gap-2">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-16H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
              Ajouter au panier
            </span>
            <span x-show="adding" class="flex items-center justify-center gap-2">
              <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
              Redirection...
            </span>
          </button>
        </div>
      </div>

    </div>
  </div>
</div>
