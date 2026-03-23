const giftCardData = (nonce, buyerEmail) => ({
  // Navigation
  step: 1,
  stepLabels: ['Choix', 'Configuration', 'Destinataire'],

  // Mode
  mode: '', // 'cruise' | 'free'

  // Étape 1 — Croisière
  selectedCruiseId: '',
  loadingPricing: false,
  cruiseTitle: '',
  cruiseDescription: '',
  lowSeasonLabel: '',
  highSeasonLabel: '',
  pricingRows: [],
  optionsPricing: [],

  // Étape 1 — Montant libre
  freeAmount: 0,

  // Étape 2 — Configuration croisière
  season: 'low',
  passengers: {},
  options: {},

  // Étape 3 — Destinataire
  sendToSelf: false,
  buyerEmail: buyerEmail || '',
  recipientEmail: '',
  recipientEmailError: '',
  recipientMessage: '',

  // Panier
  adding: false,
  errorMessage: '',

  // Nonce WP REST
  apiNonce: nonce,

  // ─── Getters ────────────────────────────────────────────────────────────────

  get totalPrice() {
    if (this.mode !== 'cruise') return 0
    let total = 0
    const priceKey = this.season === 'high' ? 'price_high' : 'price_low'

    this.pricingRows.forEach((row) => {
      const qty = this.passengers[row.id] || 0
      total += qty * parseFloat(row[priceKey] || 0)
    })

    this.optionsPricing.forEach((opt) => {
      const qty = this.options[opt.id] || 0
      total += qty * parseFloat(opt[priceKey] || 0)
    })

    return total
  },

  get finalAmount() {
    return this.mode === 'cruise' ? this.totalPrice : parseFloat(this.freeAmount || 0)
  },

  get canGoToStep2() {
    if (this.mode === 'cruise') {
      return !!this.selectedCruiseId && this.pricingRows.length > 0
    }
    if (this.mode === 'free') {
      return parseFloat(this.freeAmount) > 0
    }
    return false
  },

  get canGoToStep3() {
    if (this.mode !== 'cruise') return false
    const totalPassengers = Object.values(this.passengers).reduce((a, b) => a + b, 0)
    return totalPassengers > 0 && !!this.season && this.totalPrice > 0
  },

  get canAddToCart() {
    if (this.finalAmount <= 0) return false
    if (this.sendToSelf) return !!this.buyerEmail
    return this.isValidEmail(this.recipientEmail)
  },

  // ─── Méthodes ───────────────────────────────────────────────────────────────

  selectMode(newMode) {
    this.mode = newMode
    // Reset des données liées à l'autre mode
    if (newMode === 'cruise') {
      this.freeAmount = 0
    } else {
      this.selectedCruiseId = ''
      this.pricingRows = []
      this.optionsPricing = []
      this.cruiseTitle = ''
    }
  },

  loadPricing() {
    if (!this.selectedCruiseId) return

    this.loadingPricing = true
    this.pricingRows = []
    this.optionsPricing = []
    this.passengers = {}
    this.options = {}
    this.cruiseTitle = ''

    fetch(`/wp-json/radicle/v1/gift-card/pricing/${this.selectedCruiseId}`, {
      headers: { 'X-WP-Nonce': this.apiNonce },
    })
      .then((res) => res.json())
      .then((data) => {
        this.loadingPricing = false
        if (data.success) {
          this.cruiseTitle = data.cruise_title || ''
          this.cruiseDescription = data.gift_card_description || ''
          this.lowSeasonLabel = data.low_season_label || ''
          this.highSeasonLabel = data.high_season_label || ''
          this.pricingRows = data.pricing_rows || []
          this.optionsPricing = data.options_pricing || []
        }
      })
      .catch(() => {
        this.loadingPricing = false
      })
  },

  goToStep2() {
    if (!this.canGoToStep2) return
    if (this.mode === 'free') {
      // Mode libre : on saute l'étape 2 et on va directement à l'étape 3
      this.step = 2
    } else {
      this.step = 2
    }
  },

  goToStep3() {
    if (!this.canGoToStep3) return
    this.step = 3
  },

  incrementPassenger(id) {
    this.passengers[id] = (this.passengers[id] || 0) + 1
    // Force la réactivité Alpine
    this.passengers = { ...this.passengers }
  },

  decrementPassenger(id) {
    if ((this.passengers[id] || 0) > 0) {
      this.passengers[id]--
      this.passengers = { ...this.passengers }
    }
  },

  incrementOption(id) {
    this.options[id] = (this.options[id] || 0) + 1
    this.options = { ...this.options }
  },

  decrementOption(id) {
    if ((this.options[id] || 0) > 0) {
      this.options[id]--
      this.options = { ...this.options }
    }
  },

  handleSendToSelf() {
    if (this.sendToSelf) {
      this.recipientEmail = this.buyerEmail
      this.recipientEmailError = ''
    } else {
      this.recipientEmail = ''
    }
  },

  validateRecipientEmail() {
    if (this.sendToSelf) {
      this.recipientEmailError = ''
      return true
    }
    if (!this.recipientEmail) {
      this.recipientEmailError = 'L\'email du destinataire est requis.'
      return false
    }
    if (!this.isValidEmail(this.recipientEmail)) {
      this.recipientEmailError = 'Veuillez saisir un email valide.'
      return false
    }
    this.recipientEmailError = ''
    return true
  },

  isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
  },

  formatPrice(amount) {
    if (amount === undefined || amount === null) return '0,00 €'
    return parseFloat(amount).toFixed(2).replace('.', ',') + ' €'
  },

  addToCart() {
    if (!this.validateRecipientEmail()) return
    if (!this.canAddToCart) return

    this.adding = true
    this.errorMessage = ''

    const payload = {
      mode: this.mode,
      cruise_id: this.mode === 'cruise' ? parseInt(this.selectedCruiseId) : 0,
      season: this.season,
      passengers: this.mode === 'cruise' ? this.passengers : {},
      options: this.mode === 'cruise' ? this.options : {},
      amount: this.mode === 'free' ? parseFloat(this.freeAmount) : 0,
      recipient_email: this.sendToSelf ? '' : this.recipientEmail,
      recipient_message: this.recipientMessage,
      send_to_self: this.sendToSelf,
    }

    fetch('/wp-json/radicle/v1/gift-card/add-to-cart', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.apiNonce,
      },
      body: JSON.stringify(payload),
    })
      .then((res) => res.json())
      .then((data) => {
        this.adding = false
        if (data.success) {
          window.location.href = data.data.redirect || '/panier'
        } else {
          this.errorMessage = data.message || 'Une erreur est survenue.'
        }
      })
      .catch(() => {
        this.adding = false
        this.errorMessage = 'Erreur de communication avec le serveur.'
      })
  },
})

if (window.Alpine) {
  window.Alpine.data('giftCard', giftCardData)
} else {
  document.addEventListener('alpine:init', () => {
    window.Alpine.data('giftCard', giftCardData)
  })
}
