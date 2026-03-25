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
                <svg class="w-8 h-8" :class="mode === 'cruise' ? 'text-primary-900' : 'text-primary-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
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
                <svg class="w-8 h-8" :class="mode === 'free' ? 'text-primary-900' : 'text-primary-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
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
                <option value="{{ $cruise['id'] }}">{{ $cruise['title'] }}@if($cruise['base_price']) — À partir de {{ number_format($cruise['base_price'], 2, ',', ' ') }} €@endif</option>
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
