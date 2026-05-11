// =============================================================================
// BOOKING WIDGET
// =============================================================================
const detectSiteLang = () => {
  // return 'en'
  const supportedLangs = ['en', 'de', 'es']
  const segment = window.location.pathname.split('/').filter(Boolean)[0]
  return supportedLangs.includes(segment) ? segment : 'fr'
}
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

  // Messages traduits par langue
  _messages: {
    fr: {
      addedToCart: 'Ajouté au panier ! Redirection...',
      unknownError: 'Erreur inconnue',
      commError: 'Erreur de communication.',
    },
    en: {
      addedToCart: 'Added to cart! Redirecting...',
      unknownError: 'Unknown error',
      commError: 'Communication error.',
    },
    de: {
      addedToCart: 'Zum Warenkorb hinzugefügt! Weiterleitung...',
      unknownError: 'Unbekannter Fehler',
      commError: 'Kommunikationsfehler.',
    },
    es: {
      addedToCart: '¡Añadido al carrito! Redirigiendo...',
      unknownError: 'Error desconocido',
      commError: 'Error de comunicación.',
    },
  },

  // Labels de statut traduits par langue
  _statusLabels: {
    fr: {
      Annulé: 'Annulé',
      Reporté: 'Reporté',
      Complet: 'Complet',
      Limité: 'Limité',
      Dispo: 'Dispo.',
    },
    en: {
      Annulé: 'Cancelled',
      Reporté: 'Postponed',
      Complet: 'Full',
      Limité: 'Limited',
      Dispo: 'Avail.',
    },
    de: {
      Annulé: 'Abgesagt',
      Reporté: 'Verschoben',
      Complet: 'Ausgebucht',
      Limité: 'Begrenzt',
      Dispo: 'Verf.',
    },
    es: {
      Annulé: 'Cancelado',
      Reporté: 'Pospuesto',
      Complet: 'Completo',
      Limité: 'Limitado',
      Dispo: 'Disp.',
    },
  },

  _t(key) {
    const lang = detectSiteLang()
    return this._messages[lang]?.[key] || this._messages['fr'][key]
  },

  _statusLabel(status) {
    const lang = detectSiteLang()
    return this._statusLabels[lang]?.[status] || status
  },

  init() {
    const today = new Date().toISOString()
    const nextYear = new Date()
    nextYear.setFullYear(nextYear.getFullYear() + 1)

    const lang = detectSiteLang()
    const langParam = lang !== 'fr' ? `&lang=${lang}` : ''

    fetch(
      `/wp-json/radicle/v1/calendar/events?cruise_id=${cruiseId}&start=${today}&end=${nextYear.toISOString()}${langParam}`,
      { headers: { 'X-WP-Nonce': this.apiNonce } },
    )
      .then((res) => res.json())
      .then((data) => {
        this.sailings = data
        this.loading = false

        const params = new URLSearchParams(window.location.search)
        const sailingParam = params.get('sailing_id')
        if (sailingParam) {
          this.selectDate(sailingParam)
          const matched = this.sailings.find((s) => s.id == sailingParam)
          if (matched && matched.start) {
            const d = new Date(matched.start)
            this.viewDate = new Date(d.getFullYear(), d.getMonth(), 1)
          }
        }
      })
  },

  get monthName() {
    const locale = detectSiteLang()
    return this.viewDate.toLocaleDateString(locale, { month: 'long', year: 'numeric' })
  },

  get calendarGrid() {
    const year = this.viewDate.getFullYear()
    const month = this.viewDate.getMonth()
    const firstDay = new Date(year, month, 1)
    const lastDay = new Date(year, month + 1, 0)
    let startOffset = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1

    const days = []

    const todayTimestamp = new Date()
    todayTimestamp.setHours(0, 0, 0, 0)

    // Jours du mois précédent
    const prevMonthLastDay = new Date(year, month, 0).getDate()
    for (let i = startOffset - 1; i >= 0; i--) {
      days.push({ empty: true, day: prevMonthLastDay - i })
    }

    // Jours du mois en cours
    for (let i = 1; i <= lastDay.getDate(); i++) {
      const currentDay = new Date(year, month, i)
      const isPast = currentDay.getTime() < todayTimestamp.getTime()

      const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`

      const sailing = this.sailings.find((s) => {
        if (!s.start) return false
        const startDay = s.start.includes('T') ? s.start.split('T')[0] : s.start.split(' ')[0]
        return startDay === dateStr
      })

      let status = null
      let statusLabel = ''
      let isSelectable = false
      let available = 0

      if (sailing && !isPast) {
        available = parseInt(sailing.extendedProps.available)
        if (isNaN(available)) available = 999

        let apiStatus = sailing.extendedProps.status || 'Actif'

        if (apiStatus === 'Annulé') {
          status = 'Annulé'
          statusLabel = this._statusLabel('Annulé')
        } else if (apiStatus === 'Reporté') {
          status = 'Reporté'
          statusLabel = this._statusLabel('Reporté')
        } else if (apiStatus === 'Complet' || available <= 0) {
          status = 'Complet'
          statusLabel = this._statusLabel('Complet')
        } else if (available > 0 && available <= 5) {
          status = 'Limité'
          statusLabel = this._statusLabel('Limité')
          isSelectable = true
        } else {
          status = 'Dispo'
          statusLabel = this._statusLabel('Dispo')
          isSelectable = true
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
        isSelected: sailing && this.selectedSailingId == sailing.id,
      })
    }

    // Jours du mois suivant
    const totalCells = days.length
    const remainingCells = Math.ceil(totalCells / 7) * 7 - totalCells
    for (let i = 1; i <= remainingCells; i++) {
      days.push({ empty: true, day: i })
    }

    return days
  },

  getDayClasses(dayObj) {
    let classes = []

    if (dayObj.empty || !dayObj.sailing || dayObj.isPast) {
      classes.push('border-[#E5E8EF] bg-[#E5E8EF] text-primary-400')
    } else {
      switch (dayObj.status) {
        case 'Dispo':
          classes.push('border-[#C5F8A5] bg-[#C5F8A5] text-primary-1000 cursor-pointer')
          break
        case 'Limité':
          classes.push('border-[#FFA632] bg-[#FFA632] text-primary-1000 cursor-pointer')
          break
        case 'Complet':
          classes.push('border-[#C33149] bg-[#C33149] text-white cursor-not-allowed')
          break
        case 'Reporté':
          classes.push('border-[#FBF166] bg-[#FBF166] text-primary-1000 cursor-not-allowed')
          break
        case 'Annulé':
          classes.push('border-[#60386B] bg-[#60386B] text-white cursor-not-allowed')
          break
      }
    }

    if (dayObj.isSelected) {
      classes.push('border-primary-400 scale-105 z-10 shadow-lg')
    }

    return classes.join(' ')
  },

  handleDayClick(dayObj) {
    if (!dayObj.empty && dayObj.sailing && !dayObj.isPast && dayObj.isSelectable) {
      this.selectDate(dayObj.sailing.id)
    }
  },

  changeMonth(step) {
    const newDate = new Date(this.viewDate)
    newDate.setMonth(newDate.getMonth() + step)
    this.viewDate = newDate
  },

  selectDate(sailingId) {
    if (!sailingId) return

    const sailing = this.sailings.find((s) => s.id == sailingId)

    if (
      !sailing ||
      sailing.extendedProps.status === 'Annulé' ||
      sailing.extendedProps.status === 'Reporté' ||
      parseInt(sailing.extendedProps.available) <= 0
    ) {
      return
    }

    this.selectedSailingId = sailingId
    this.updateSelectedSailing()
  },

  updateSelectedSailing() {
    this.currentSailing = this.sailings.find((s) => s.id == this.selectedSailingId)
    this.passengers = {}
    this.selectedOptions = {}
    this.message = ''
  },

  incrementPassenger(fareId) {
    const currentTotal = Object.values(this.passengers).reduce((a, b) => a + b, 0)
    if (this.currentSailing && currentTotal >= this.currentSailing.extendedProps.available) return
    this.passengers[fareId] = (this.passengers[fareId] || 0) + 1
  },

  decrementPassenger(fareId) {
    if (this.passengers[fareId] > 0) this.passengers[fareId]--
  },

  incrementOption(optId, maxQuota) {
    const currentQty = this.selectedOptions[optId] || 0
    if (currentQty < maxQuota) {
      this.selectedOptions[optId] = currentQty + 1
    }
  },

  decrementOption(optId) {
    if (this.selectedOptions[optId] > 0) this.selectedOptions[optId]--
  },

  get totalPrice() {
    if (!this.currentSailing || !this.currentSailing.extendedProps) return 0
    let total = 0

    const fares = this.currentSailing.extendedProps.fares || []
    fares.forEach((fare) => {
      const count = this.passengers[fare.id] || 0
      total += count * parseFloat(fare.price)
    })

    const options = this.currentSailing.extendedProps.options || []
    options.forEach((opt) => {
      const count = this.selectedOptions[opt.id] || 0
      total += count * parseFloat(opt.price)
    })

    return total
  },

  // Format : "MER. 25 FÉVRIER À 07:00" — traduit via Intl
  formatHeaderDate(dateStr) {
    if (!dateStr) return ''
    const locale = detectSiteLang()
    const date = new Date(dateStr)

    const weekday = date
      .toLocaleDateString(locale, { weekday: 'short' })
      .toUpperCase()
      .replace('.', '')
    const day = String(date.getDate()).padStart(2, '0')
    const month = date.toLocaleDateString(locale, { month: 'long' }).toUpperCase()
    const time = date.toLocaleTimeString(locale, { hour: '2-digit', minute: '2-digit' })

    // Connecteur "À" / "AT" / "UM" / "A LAS"
    const at = { fr: 'À', en: 'AT', de: 'UM', es: 'A LAS' }[locale] || 'À'

    return `${weekday}. ${day} ${month} ${at} ${time}`
  },

  formatPrice(amount) {
    if (amount === undefined || amount === null) return '0,00 €'
    const numAmount = parseFloat(amount)
    return numAmount.toFixed(2).replace('.', ',') + ' €'
  },

  addToCart() {
    this.adding = true
    this.message = ''

    const payload = {
      sailing_id: this.selectedSailingId,
      passengers: this.passengers,
      options: this.selectedOptions,
    }

    fetch('/wp-json/radicle/v1/booking/add-to-cart', {
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
          this.message = this._t('addedToCart')
          this.messageType = 'success'
          window.location.href = data.data.redirect || '/panier'
        } else {
          this.message = data.message || this._t('unknownError')
          this.messageType = 'error'
        }
      })
      .catch(() => {
        this.adding = false
        this.message = this._t('commError')
        this.messageType = 'error'
      })
  },
})

if (window.Alpine) {
  window.Alpine.data('bookingWidget', bookingWidgetData)
} else {
  document.addEventListener('alpine:init', () => {
    window.Alpine.data('bookingWidget', bookingWidgetData)
  })
}
