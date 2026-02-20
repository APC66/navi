const bookingWidgetData = (cruiseId, nonce) => ({
  loading: true,
  adding: false,
  sailings: [],
  selectedSailingId: '',
  currentSailing: null,
  passengers: {},
  selectedOptions: {},
  message: '',
  messageType: '',
  apiNonce: nonce,
  viewDate: new Date(),

  init() {
    const today = new Date().toISOString();
    const nextYear = new Date();
    nextYear.setFullYear(nextYear.getFullYear() + 1);

    fetch(`/wp-json/radicle/v1/calendar/events?cruise_id=${cruiseId}&start=${today}&end=${nextYear.toISOString()}`, {
      headers: { 'X-WP-Nonce': this.apiNonce }
    })
      .then(res => res.json())
      .then(data => {
        this.sailings = data;
        this.loading = false;
      });
  },

  get monthName() {
    return this.viewDate.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
  },

  get calendarGrid() {
    const year = this.viewDate.getFullYear();
    const month = this.viewDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    let startOffset = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;

    const days = [];

    // Date d'aujourd'hui à minuit (pour bloquer les dates passées)
    const todayTimestamp = new Date();
    todayTimestamp.setHours(0, 0, 0, 0);

    // Jours du mois précédent pour combler la première semaine
    const prevMonthLastDay = new Date(year, month, 0).getDate();
    for (let i = startOffset - 1; i >= 0; i--) {
      days.push({ empty: true, day: prevMonthLastDay - i });
    }

    // Jours du mois en cours
    for (let i = 1; i <= lastDay.getDate(); i++) {
      const currentDay = new Date(year, month, i);
      const isPast = currentDay.getTime() < todayTimestamp.getTime();

      const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;

      // Sécurisation de la recherche de date (gère 'YYYY-MM-DDTHH:mm:ss' et 'YYYY-MM-DD HH:mm:ss')
      const sailing = this.sailings.find(s => {
        if (!s.start) return false;
        const startDay = s.start.includes('T') ? s.start.split('T')[0] : s.start.split(' ')[0];
        return startDay === dateStr;
      });

      let status = null;
      let statusLabel = '';
      let isSelectable = false;
      let available = 0;

      // Définition rigoureuse de l'état selon la maquette
      if (sailing && !isPast) {
        available = parseInt(sailing.extendedProps.available);
        if (isNaN(available)) available = 999; // Fallback de sécurité

        let apiStatus = sailing.extendedProps.status || 'Actif';

        if (apiStatus === 'Annulé') {
          status = 'Annulé';
          statusLabel = 'Annulé';
        } else if (apiStatus === 'Reporté') {
          status = 'Reporté';
          statusLabel = 'Reporté';
        } else if (apiStatus === 'Complet' || available <= 0) {
          status = 'Complet';
          statusLabel = 'Complet';
        } else if (available > 0 && available <= 5) {
          status = 'Limité';
          statusLabel = 'Limité'; // Rendu modifiable visuellement
          isSelectable = true; // On peut toujours cliquer
        } else {
          status = 'Dispo';
          statusLabel = 'Dispo.';
          isSelectable = true;
        }
      }

      days.push({
        empty: false,
        day: i,
        date: dateStr,
        sailing: sailing,
        isPast: isPast,
        available: available,
        status: status,
        statusLabel: statusLabel,
        isSelectable: isSelectable,
        isSelected: sailing && this.selectedSailingId == sailing.id
      });
    }

    // Jours du mois suivant pour compléter la grille
    const totalCells = days.length;
    const remainingCells = (Math.ceil(totalCells / 7) * 7) - totalCells;
    for(let i = 1; i <= remainingCells; i++) {
      days.push({ empty: true, day: i });
    }

    return days;
  },

  // Gère les classes CSS dynamiques
  getDayClasses(dayObj) {
    let classes = [];

    if (dayObj.empty || !dayObj.sailing || dayObj.isPast) {
      classes.push('border-[#E5E8EF] bg-[#E5E8EF] text-primary-400');
    } else {
      switch(dayObj.status) {
        case 'Dispo': classes.push('border-[#C5F8A5] bg-[#C5F8A5] text-primary-1000 cursor-pointer'); break;
        case 'Limité': classes.push('border-[#FFA632] bg-[#FFA632] t text-primary-1000 cursor-pointer'); break;
        case 'Complet': classes.push('border-[#C33149] bg-[#C33149] text-white cursor-not-allowed'); break;
        case 'Reporté': classes.push('border-[#FBF166] bg-[#FBF166] text-primary-1000 cursor-not-allowed'); break;
        case 'Annulé': classes.push('border-[#60386B] bg-[#60386B] text-white cursor-not-allowed'); break;
      }
    }

    if (dayObj.isSelected) {
      classes.push('border-primary-400 cale-105 z-10 shadow-lg');
    }

    return classes.join(' ');
  },

  // Gère le clic sur une date
  handleDayClick(dayObj) {
    if (!dayObj.empty && dayObj.sailing && !dayObj.isPast && dayObj.isSelectable) {
      this.selectDate(dayObj.sailing.id);
    }
  },

  changeMonth(step) {
    const newDate = new Date(this.viewDate);
    newDate.setMonth(newDate.getMonth() + step);
    this.viewDate = newDate;
  },

  selectDate(sailingId) {
    if (!sailingId) return;

    const sailing = this.sailings.find(s => s.id == sailingId);

    // Vérification de sécurité pour empêcher la sélection d'une date impossible
    if (!sailing || sailing.extendedProps.status === 'Annulé' || sailing.extendedProps.status === 'Reporté' || parseInt(sailing.extendedProps.available) <= 0) {
      return;
    }

    this.selectedSailingId = sailingId;
    this.updateSelectedSailing();
  },

  updateSelectedSailing() {
    this.currentSailing = this.sailings.find(s => s.id == this.selectedSailingId);
    this.passengers = {};
    this.selectedOptions = {};
    this.message = '';
  },

  incrementPassenger(fareId) {
    const currentTotal = Object.values(this.passengers).reduce((a, b) => a + b, 0);
    if (this.currentSailing && currentTotal >= this.currentSailing.extendedProps.available) return;
    this.passengers[fareId] = (this.passengers[fareId] || 0) + 1;
  },

  decrementPassenger(fareId) {
    if (this.passengers[fareId] > 0) this.passengers[fareId]--;
  },

  incrementOption(optId, maxQuota) {
    const currentQty = this.selectedOptions[optId] || 0;
    if (currentQty < maxQuota) {
      this.selectedOptions[optId] = currentQty + 1;
    }
  },

  decrementOption(optId) {
    if (this.selectedOptions[optId] > 0) this.selectedOptions[optId]--;
  },

  get totalPrice() {
    if (!this.currentSailing || !this.currentSailing.extendedProps) return 0;
    let total = 0;

    const fares = this.currentSailing.extendedProps.fares || [];
    fares.forEach(fare => {
      const count = this.passengers[fare.id] || 0;
      total += count * parseFloat(fare.price);
    });

    const options = this.currentSailing.extendedProps.options || [];
    options.forEach(opt => {
      const count = this.selectedOptions[opt.id] || 0;
      total += count * parseFloat(opt.price);
    });

    return total;
  },

  // Format attendu : "MER. 25 FÉVRIER À 07:00"
  formatHeaderDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const days = ['DIM.', 'LUN.', 'MAR.', 'MER.', 'JEU.', 'VEN.', 'SAM.'];
    const months = ['JANVIER', 'FÉVRIER', 'MARS', 'AVRIL', 'MAI', 'JUIN', 'JUILLET', 'AOÛT', 'SEPTEMBRE', 'OCTOBRE', 'NOVEMBRE', 'DÉCEMBRE'];

    const dayName = days[date.getDay()];
    const dayNum = String(date.getDate()).padStart(2, '0');
    const monthName = months[date.getMonth()];
    const time = date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }).replace(':', ':');

    return `${dayName} ${dayNum} ${monthName} À ${time}`;
  },

  // Format attendu : "92.00 €" ou "Gratuit"
  formatPrice(amount) {
    if (amount === undefined || amount === null) return '0.00 €';
    const numAmount = parseFloat(amount);
    if (numAmount === 0) return 'Gratuit';
    // Utilisation de toFixed pour forcer le point décimal comme sur la maquette
    return numAmount.toFixed(2) + ' €';
  },

  addToCart() {
    this.adding = true;
    this.message = '';

    const payload = {
      sailing_id: this.selectedSailingId,
      passengers: this.passengers,
      options: this.selectedOptions
    };

    fetch('/wp-json/radicle/v1/booking/add-to-cart', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.apiNonce
      },
      body: JSON.stringify(payload)
    })
      .then(res => res.json())
      .then(data => {
        this.adding = false;
        if (data.success) {
          this.message = 'Ajouté au panier ! Redirection...';
          this.messageType = 'success';
          window.location.href = data.data.redirect || '/panier';
        } else {
          this.message = data.message || 'Erreur inconnue';
          this.messageType = 'error';
        }
      })
      .catch(err => {
        this.adding = false;
        this.message = 'Erreur de communication.';
        this.messageType = 'error';
      });
  }
});

if (window.Alpine) {
  window.Alpine.data('bookingWidget', bookingWidgetData);
} else {
  document.addEventListener('alpine:init', () => {
    window.Alpine.data('bookingWidget', bookingWidgetData);
  });
}
