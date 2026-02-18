<div
    id="booking-widget-{{ $cruiseId }}"
    class="booking-widget p-6 bg-white rounded-xl shadow-lg border border-gray-100"
    x-data="bookingWidget({{ $cruiseId }}, '{{ wp_create_nonce('wp_rest') }}')"
    x-init="init()"
>
    <h3 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-4">Réserver votre croisière</h3>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">

        <!-- COLONNE GAUCHE : CALENDRIER -->
        <div class="calendar-section">
            <div class="mb-4 border border-gray-200 rounded-lg overflow-hidden bg-white shadow-sm">
                <div class="flex justify-between items-center bg-gray-50 p-4 border-b border-gray-200">
                    <button @click="changeMonth(-1)" class="p-2 hover:bg-white hover:shadow rounded text-gray-600 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <span class="font-bold text-gray-800 capitalize text-lg" x-text="monthName"></span>
                    <button @click="changeMonth(1)" class="p-2 hover:bg-white hover:shadow rounded text-gray-600 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>

                <div class="grid grid-cols-7 bg-gray-50 text-xs text-gray-500 font-semibold text-center py-3 border-b border-gray-100 uppercase tracking-wider">
                    <div>Lun</div><div>Mar</div><div>Mer</div><div>Jeu</div><div>Ven</div><div>Sam</div><div>Dim</div>
                </div>

                <div class="grid grid-cols-7 text-sm bg-white">
                    <template x-for="(dayObj, index) in calendarGrid" :key="index">
                        <div class="relative aspect-square border-b border-r border-gray-100 last:border-r-0">
                            <template x-if="!dayObj.empty">
                                <button
                                    @click="selectDate(dayObj.sailing?.id)"
                                    :disabled="!dayObj.sailing || dayObj.isPast || !dayObj.isSelectable"
                                    class="w-full h-full flex flex-col items-center justify-center transition-all duration-200 relative group"
                                    :class="{
                                        'bg-blue-600 text-white font-bold ring-2 ring-blue-600 ring-offset-2 z-10': dayObj.isSelected,
                                        'hover:bg-blue-50 text-gray-700 cursor-pointer': !dayObj.isSelected && dayObj.sailing && !dayObj.isPast && dayObj.isSelectable,
                                        'bg-red-50 text-red-400 cursor-not-allowed': dayObj.sailing && ( dayObj.status === 'Annulé' || dayObj.status === 'Complet' || (dayObj.available <= 0 && dayObj.status === 'Actif')),
                                        'bg-orange-50 text-orange-400 cursor-not-allowed': dayObj.sailing && dayObj.status === 'Reporté',
                                        'bg-gray-50 text-gray-300 cursor-not-allowed': !dayObj.sailing || dayObj.isPast
                                    }"
                                >
                                    <span x-text="dayObj.day" class="text-sm" :class="{'line-through': dayObj.sailing && dayObj.status === 'Annulé'}"></span>

                                    <template x-if="dayObj.sailing && !dayObj.isPast && dayObj.isSelectable && !dayObj.isSelected">
                                        <div class="absolute bottom-2 flex flex-col items-center">
                                            <span class="w-1.5 h-1.5 rounded-full mb-0.5"
                                                  :class="dayObj.available < 10 ? 'bg-orange-400' : 'bg-green-500'"></span>
                                        </div>
                                    </template>

                                    <template x-if="dayObj.sailing && (dayObj.status === 'Annulé' || dayObj.status === 'Reporté' || (dayObj.status === 'Complet' || (dayObj.available <= 0 && dayObj.status === 'Actif')))">
                                        <span class="absolute bottom-1 text-[8px] uppercase font-bold tracking-tighter" x-text="dayObj.status"></span>
                                    </template>

                                </button>
                            </template>
                        </div>
                    </template>
                </div>

                <div x-show="loading" class="p-4 text-center text-sm text-gray-500 bg-white italic flex justify-center items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Chargement...
                </div>
            </div>

            <!-- Légende Enrichie -->
            <div class="flex flex-wrap justify-center gap-4 text-xs text-gray-500 mt-4">
                <span class="flex items-center"><span class="w-2 h-2 rounded-full bg-green-500 mr-1.5"></span> Disponible</span>
                <span class="flex items-center"><span class="w-2 h-2 rounded-full bg-orange-400 mr-1.5"></span> Dernières places</span>
                <span class="flex items-center"><span class="w-2 h-2 rounded-full bg-red-400 mr-1.5 opacity-50"></span> Annulé/Reporté/Complet</span>
            </div>
        </div>

        <!-- COLONNE DROITE : FORMULAIRE -->
        <div class="form-section bg-gray-50 rounded-xl p-6 h-full border border-gray-100 relative">
            <div x-show="!currentSailing" class="absolute inset-0 flex flex-col items-center justify-center p-8 text-center text-gray-400 z-0">
                <div class="bg-white p-4 rounded-full shadow-sm mb-4">
                    <svg class="w-8 h-8 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <p class="font-medium text-gray-500">Sélectionnez une date sur le calendrier<br>pour configurer votre réservation.</p>
            </div>

            <template x-if="currentSailing">
                <div class="space-y-6 animate-fade-in relative z-10">
                    <div class="flex justify-between items-end border-b border-gray-200 pb-4">
                        <div>
                            <span class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1">Votre départ</span>
                            <span class="text-2xl font-bold text-gray-800 capitalize" x-text="formatDate(currentSailing.start)"></span>
                        </div>
                        <span class="text-xs font-bold bg-green-100 text-green-700 px-3 py-1 rounded-full">
                            <span x-text="currentSailing.extendedProps.available"></span> places dispo
                        </span>
                    </div>

                    <!-- PASSAGERS -->
                    <div>
                        <h4 class="font-bold text-gray-800 mb-3 text-sm uppercase tracking-wide flex items-center">Passagers</h4>
                        <div class="space-y-3 bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                            <template x-for="fare in currentSailing.extendedProps.fares" :key="fare.id">
                                <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0 last:pb-0">
                                    <div>
                                        <span class="block font-medium text-gray-700" x-text="fare.name"></span>
                                        <span class="text-sm text-gray-500" x-text="formatPrice(fare.price)"></span>
                                    </div>
                                    <div class="flex items-center bg-gray-50 rounded-lg border border-gray-200 h-9 px-1">
                                        <button type="button" @click="decrementPassenger(fare.id)" class="w-8 h-full text-gray-500 hover:text-blue-600 disabled:opacity-30 transition-colors font-bold" :disabled="!passengers[fare.id]">-</button>
                                        <span class="w-8 text-center text-sm font-bold text-gray-800" x-text="passengers[fare.id] || 0"></span>
                                        <button type="button" @click="incrementPassenger(fare.id)" class="w-8 h-full text-gray-500 hover:text-blue-600 transition-colors font-bold">+</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- OPTIONS -->
                    <div x-show="currentSailing.extendedProps.options && currentSailing.extendedProps.options.length > 0">
                        <h4 class="font-bold text-gray-800 mb-3 text-sm uppercase tracking-wide flex items-center mt-6">Options</h4>
                        <div class="space-y-3 bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                            <template x-for="option in currentSailing.extendedProps.options" :key="option.id">
                                <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0 last:pb-0">
                                    <div class="leading-tight">
                                        <span class="block font-medium text-gray-700" x-text="option.name"></span>
                                        <div class="text-sm text-gray-500 flex items-center mt-0.5">
                                            <span x-text="formatPrice(option.price)"></span>
                                            <template x-if="option.has_quota">
                                                <span class="text-[9px] bg-orange-50 text-orange-700 px-1.5 py-0.5 rounded ml-2 border border-orange-100">Max: <span x-text="option.quota"></span></span>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="flex items-center bg-gray-50 rounded-lg border border-gray-200 h-9 px-1">
                                        <button type="button" @click="decrementOption(option.id)" class="w-8 h-full text-gray-500 hover:text-blue-600 disabled:opacity-30 transition-colors font-bold" :disabled="!selectedOptions[option.id]">-</button>
                                        <span class="w-8 text-center text-sm font-bold text-gray-800" x-text="selectedOptions[option.id] || 0"></span>
                                        <button type="button" @click="incrementOption(option.id, option.has_quota ? option.quota : 999)" class="w-8 h-full text-gray-500 hover:text-blue-600 transition-colors font-bold">+</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- TOTAL -->
                    <div class="pt-6 mt-8 border-t border-gray-200">
                        <div class="flex justify-between items-end mb-6">
                            <span class="text-gray-500 font-medium uppercase text-sm tracking-wider">Total</span>
                            <span class="text-3xl font-extrabold text-blue-600 tracking-tight" x-text="formatPrice(totalPrice)"></span>
                        </div>

                        <button
                            @click="addToCart"
                            :disabled="totalPrice <= 0 || adding"
                            class="w-full py-4 px-6 bg-blue-600 text-white font-bold text-lg rounded-xl hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg hover:shadow-xl flex justify-center items-center transform hover:-translate-y-0.5 active:translate-y-0"
                        >
                            <span x-show="!adding">Réserver maintenant</span>
                            <span x-show="adding">Ajout en cours...</span>
                        </button>

                        <div x-show="message" class="mt-4 text-center text-sm font-medium p-3 rounded-lg border" :class="messageType === 'error' ? 'text-red-700 bg-red-50 border-red-100' : 'text-green-700 bg-green-50 border-green-100'" x-text="message" x-transition></div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
