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
    for (let i = 0; i < startOffset; i++) {
      days.push({ empty: true });
    }

    for (let i = 1; i <= lastDay.getDate(); i++) {
      const currentDay = new Date(year, month, i);
      const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;

      // Recherche de l'événement (Sailing) pour ce jour
      // On cherche n'importe quel statut qui est renvoyé par l'API
      const sailing = this.sailings.find(s => s.start && s.start.startsWith(dateStr));

      const isPast = currentDay < new Date().setHours(0,0,0,0);

      days.push({
        empty: false,
        day: i,
        date: dateStr,
        sailing: sailing,
        isPast: isPast,

        // Propriétés étendues pour l'affichage
        available: sailing ? sailing.extendedProps.available : 0,
        status: sailing ? sailing.extendedProps.status : null, // 'Annulé', 'Reporté', 'Actif'
        isSelectable: sailing ? sailing.extendedProps.is_selectable : false, // Vient de l'API

        isSelected: sailing && this.selectedSailingId == sailing.id
      });
    }

    return days;
  },

  changeMonth(step) {
    const newDate = new Date(this.viewDate);
    newDate.setMonth(newDate.getMonth() + step);
    this.viewDate = newDate;
  },

  selectDate(sailingId) {
    if (!sailingId) return;

    // Vérification de sécurité : est-ce que ce sailing est sélectionnable ?
    const sailing = this.sailings.find(s => s.id == sailingId);
    if (!sailing || !sailing.extendedProps.is_selectable) {
      return; //
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
      total += count * fare.price;
    });

    const options = this.currentSailing.extendedProps.options || [];
    options.forEach(opt => {
      const count = this.selectedOptions[opt.id] || 0;
      total += count * opt.price;
    });

    return total;
  },

  formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' });
  },

  formatPrice(amount) {
    if (amount === undefined || amount === null) return '0,00 €';
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
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
