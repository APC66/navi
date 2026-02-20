<div
    id="booking-widget-{{ $cruiseId }}"
    class="booking-widget p-6 md:p-8 lg:p-10 bg-[#EBF0F5] rounded-[30px] shadow-2xl relative font-sans text-primary-900"
    x-data="bookingWidget({{ $cruiseId }}, '{{ wp_create_nonce('wp_rest') }}')"
    x-init="init()"
>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">

        <!-- ========================================= -->
        <!-- COLONNE GAUCHE : CALENDRIER               -->
        <!-- ========================================= -->
        <div class="calendar-section flex flex-col h-full">

            <div class="bg-white rounded-2xl p-6 shadow-sm flex-1">
                <!-- En-tête Calendrier -->
                <div class="flex justify-between items-center mb-6">
                    <button @click="changeMonth(-1)" class="w-10 h-10 flex items-center justify-center bg-white border border-gray-200 rounded-xl shadow-sm text-primary-900 hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <span class="font-bold text-xl md:text-2xl text-primary-900 capitalize font-heading" x-text="monthName"></span>
                    <button @click="changeMonth(1)" class="w-10 h-10 flex items-center justify-center bg-white border border-gray-200 rounded-xl shadow-sm text-primary-900 hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>

                <!-- Jours Semaine -->
                <div class="grid grid-cols-7 text-center text-xs font-bold text-primary-900 uppercase tracking-widest mb-2">
                    <div>Lun</div><div>Mar</div><div>Mer</div><div>Jeu</div><div>Ven</div><div>Sam</div><div>Dim</div>
                </div>

                <!-- Grille des Jours -->
                <div class="grid grid-cols-7 gap-1.5 md:gap-2 text-sm relative">

                    <!-- Loader Overlay -->
                    <div x-show="loading" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-20 flex flex-col items-center justify-center rounded-xl">
                        <svg class="animate-spin mb-2 h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span class="text-sm font-bold text-primary-600">Chargement...</span>
                    </div>

                    <template x-for="(dayObj, index) in calendarGrid" :key="index">
                        <div class="relative w-full aspect-square border-4 rounded-xl overflow-hidden shadow-sm transition-transform duration-200"
                             :class="getDayClasses(dayObj)"
                             @click="handleDayClick(dayObj)"
                        >
                            <button class="w-full h-full flex flex-col items-center justify-center p-1 disabled:cursor-not-allowed" :disabled="dayObj.empty || !dayObj.sailing || dayObj.isPast || !dayObj.isSelectable">
                                <span x-text="dayObj.day" class="text-sm md:text-base font-bold"></span>

                                <template x-if="dayObj.statusLabel">
                                    <span class="hidden xs:block text-[9px] uppercase font-extrabold tracking-tighter mt-1" x-text="dayObj.statusLabel"></span>
                                </template>

                                <!-- Barre de rayure pour Annulé -->
                                <template x-if="dayObj.status === 'Annulé'">
                                    <svg class="absolute inset-0 w-full h-full text-red-500 opacity-90 pointer-events-none" preserveAspectRatio="none" viewBox="0 0 100 100">
                                        <line x1="0" y1="100" x2="100" y2="0" stroke="currentColor" stroke-width="4" />
                                    </svg>
                                </template>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Légende -->
            <div class="bg-white rounded-2xl p-5 mt-6 grid grid-cols-2 gap-y-3 gap-x-4 text-[11px] font-bold text-primary-900 shadow-sm">
                <div class="flex items-center"><span class="w-4 h-4 rounded bg-[#C5F8A5] mr-3 shadow-sm"></span> Disponible</div>
                <div class="flex items-center"><span class="w-4 h-4 rounded bg-[#FFA632] mr-3 shadow-sm"></span> Dernières places disponibles</div>
                <div class="flex items-center"><span class="w-4 h-4 rounded bg-[#FBF166] mr-3 shadow-sm"></span> Reporté</div>
                <div class="flex items-center"><span class="w-4 h-4 rounded bg-[#60386B] mr-3 shadow-sm relative overflow-hidden"><svg class="absolute inset-0 w-full h-full text-red-500" preserveAspectRatio="none" viewBox="0 0 100 100"><line x1="0" y1="100" x2="100" y2="0" stroke="currentColor" stroke-width="8" /></svg></span> Annulé</div>
                <div class="flex items-center"><span class="w-4 h-4 rounded bg-[#C33149] mr-3 shadow-sm"></span> Complet</div>
            </div>

        </div>

        <div class="form-section flex flex-col h-full lg:pl-4">

            <div x-show="!currentSailing" class="flex-1 flex flex-col items-center justify-center text-center text-primary-600/50 min-h-[400px]">
                <svg class="w-16 h-16 mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <p class="font-bold text-lg">Sélectionnez une date<br>sur le calendrier.</p>
            </div>

            <template x-if="currentSailing">
                <div class="flex flex-col h-full animate-fade-in">

                    <!-- En-tête Départ -->
                    <div class="mb-8">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h4 class="text-xs font-bold text-primary-600 uppercase tracking-widest mb-1">Votre départ</h4>
                                <div class="text-2xl md:text-3xl font-bold text-primary-900 font-heading uppercase leading-none" x-text="formatHeaderDate(currentSailing.start)"></div>
                            </div>
                            <div class="bg-[#C6F6D5] text-[#166534] px-4 py-1.5 rounded-full text-xs font-bold whitespace-nowrap ml-4 shadow-sm">
                                <span x-text="currentSailing.extendedProps.available"></span> places dispo
                            </div>
                        </div>
                        <div class="text-[10px] font-bold text-primary-900 uppercase tracking-widest mt-3 opacity-80">
                            DÉPART DEPUIS <span x-text="currentSailing.extendedProps.port || 'VOTRE PORT DE CROISIÈRE'"></span>
                        </div>
                    </div>

                    <!-- PASSAGERS -->
                    <div class="mb-8">
                        <h4 class="text-sm font-bold text-primary-900 uppercase tracking-widest mb-4">Passagers</h4>
                        <div class="space-y-0">
                            <template x-for="(fare, index) in currentSailing.extendedProps.fares" :key="fare.id">
                                <div>
                                    <div class="flex items-center justify-between py-2">
                                        <div class="flex flex-col">
                                            <span class="block font-bold text-primary-900" x-text="fare.name"></span>
                                            <span class="text-sm font-medium text-primary-600" x-text="formatPrice(fare.price)"></span>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <button type="button" @click="decrementPassenger(fare.id)" class="w-8 h-8 flex items-center justify-center bg-white border border-gray-200 rounded-lg text-primary-900 font-bold hover:bg-gray-50 transition-colors shadow-sm disabled:opacity-50" :disabled="!passengers[fare.id]">-</button>
                                            <span class="w-4 text-center font-bold text-primary-900" x-text="passengers[fare.id] || 0"></span>
                                            <button type="button" @click="incrementPassenger(fare.id)" class="w-8 h-8 flex items-center justify-center bg-white border border-gray-200 rounded-lg text-primary-900 font-bold hover:bg-gray-50 transition-colors shadow-sm">+</button>
                                        </div>
                                    </div>
                                    <hr class="border-primary-200/50 my-2" x-show="index < currentSailing.extendedProps.fares.length - 1">
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- OPTIONS -->
                    <div x-show="currentSailing.extendedProps.options && currentSailing.extendedProps.options.length > 0" class="mb-8">
                        <h4 class="text-sm font-bold text-primary-900 uppercase tracking-widest mb-4">Options</h4>
                        <div class="space-y-0">
                            <template x-for="(option, index) in currentSailing.extendedProps.options" :key="option.id">
                                <div>
                                    <div class="flex items-center justify-between py-2">
                                        <div class="flex flex-col">
                                            <span class="block font-bold text-primary-900" x-text="option.name"></span>
                                            <span class="text-sm font-medium text-primary-600" x-text="formatPrice(option.price)"></span>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <button type="button" @click="decrementOption(option.id)" class="w-8 h-8 flex items-center justify-center bg-white border border-gray-200 rounded-lg text-primary-900 font-bold hover:bg-gray-50 transition-colors shadow-sm disabled:opacity-50" :disabled="!selectedOptions[option.id]">-</button>
                                            <span class="w-4 text-center font-bold text-primary-900" x-text="selectedOptions[option.id] || 0"></span>
                                            <button type="button" @click="incrementOption(option.id, option.has_quota ? option.quota : 999)" class="w-8 h-8 flex items-center justify-center bg-white border border-gray-200 rounded-lg text-primary-900 font-bold hover:bg-gray-50 transition-colors shadow-sm">+</button>
                                        </div>
                                    </div>
                                    <hr class="border-primary-200/50 my-2" x-show="index < currentSailing.extendedProps.options.length - 1">
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- TOTAL & ACTION -->
                    <div class="mt-auto pt-6 border-t border-primary-200">
                        <div class="flex justify-between items-end mb-8">
                            <span class="text-xl font-bold text-primary-900 uppercase tracking-widest">Total</span>
                            <span class="text-3xl lg:text-4xl font-extrabold text-primary-900 leading-none" x-text="formatPrice(totalPrice)"></span>
                        </div>

                        <button
                            @click="addToCart"
                            :disabled="totalPrice <= 0 || adding"
                            class="w-full py-4 px-6 bg-secondary text-primary-900 font-bold text-xl rounded-full hover:bg-secondary-hover disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1 active:translate-y-0 flex justify-center items-center"
                        >
                            <span x-show="!adding">Réserver maintenant</span>
                            <span x-show="adding" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Redirection...
                            </span>
                        </button>

                        <div x-show="message" class="mt-4 text-center text-sm font-bold" :class="messageType === 'error' ? 'text-red-600' : 'text-green-600'" x-text="message" x-transition></div>
                    </div>

                </div>
            </template>

        </div>
    </div>
</div>
