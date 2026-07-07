// =============================================================================
// PLANNING WIDGET
// =============================================================================
const detectSiteLang = () => {
  // return 'en'
  const supportedLangs = ['en', 'de', 'es']
  const segment = window.location.pathname.split('/').filter(Boolean)[0]
  return supportedLangs.includes(segment) ? segment : 'fr'
}
const globalPlanningData = () => ({
  loading: true,
  currentDate: new Date(),
  datePickerValue: '',
  sailings: [],
  labels: {}, // Labels traduits retournés par l'API

  filterMenuOpen: false,
  filters: {
    types: [],
    ports: [],
    tags: [],
  },

  expandedSailingId: null,

  init() {
    this.datePickerValue = this.formatDateForPicker(this.currentDate)

    this.$watch('datePickerValue', (val) => {
      if (val && val !== this.formatDateForPicker(this.currentDate)) {
        this.currentDate = new Date(val)
        this.fetchWeekData()
      }
    })

    this.fetchWeekData()
  },

  openDatePicker(event) {
    try {
      if (typeof event.target.showPicker === 'function') {
        event.target.showPicker()
      }
    } catch (e) {}
  },

  formatDateForPicker(d) {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
  },

  toggleSailing(id) {
    this.expandedSailingId = this.expandedSailingId === id ? null : id
  },

  get activeFiltersCount() {
    return this.filters.types.length + this.filters.ports.length + this.filters.tags.length
  },

  get weekDays() {
    // Fenêtre glissante de 7 jours à partir de la date courante (jour J en 1re colonne).
    const start = new Date(this.currentDate)
    start.setHours(0, 0, 0, 0)

    const days = []
    for (let i = 0; i < 7; i++) {
      const d = new Date(start)
      d.setDate(start.getDate() + i)
      days.push(d)
    }
    return days
  },

  get weekRangeLabel() {
    const locale = detectSiteLang()
    const days = this.weekDays
    const start = days[0]
    const end = days[6]

    const startStr = start.toLocaleDateString(locale, { day: '2-digit', month: 'long' })
    const endStr = end.toLocaleDateString(locale, {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    })

    const connector = { fr: 'Du', en: 'From', de: 'Vom', es: 'Del' }[locale] || 'Du'
    const separator = { fr: 'au', en: 'to', de: 'bis', es: 'al' }[locale] || 'au'

    return `${connector} ${startStr} ${separator} ${endStr}`
  },

  formatDayHeader(date) {
    const locale = detectSiteLang()
    const weekday = date.toLocaleDateString(locale, { weekday: 'short' })
    const dayNum = String(date.getDate()).padStart(2, '0')
    const monthNum = String(date.getMonth() + 1).padStart(2, '0')
    return `${weekday} ${dayNum}/${monthNum}`
  },

  formatTime(datetimeStr) {
    if (!datetimeStr) return ''
    const timePart = datetimeStr.includes('T')
      ? datetimeStr.split('T')[1]
      : datetimeStr.split(' ')[1]
    if (!timePart) return ''
    return timePart.substring(0, 5)
  },

  isToday(date) {
    const today = new Date()
    return (
      date.getDate() === today.getDate() &&
      date.getMonth() === today.getMonth() &&
      date.getFullYear() === today.getFullYear()
    )
  },

  nextWeek() {
    const next = new Date(this.currentDate)
    next.setDate(next.getDate() + 7)
    this.currentDate = next
    this.datePickerValue = this.formatDateForPicker(this.currentDate)
    this.fetchWeekData()
  },

  prevWeek() {
    const prev = new Date(this.currentDate)
    prev.setDate(prev.getDate() - 7)
    this.currentDate = prev
    this.datePickerValue = this.formatDateForPicker(this.currentDate)
    this.fetchWeekData()
  },

  goToToday() {
    this.currentDate = new Date()
    this.datePickerValue = this.formatDateForPicker(this.currentDate)
    this.fetchWeekData()
  },

  toggleFilter(filterCategory, termId) {
    const index = this.filters[filterCategory].indexOf(termId)
    if (index > -1) {
      this.filters[filterCategory].splice(index, 1)
    } else {
      this.filters[filterCategory].push(termId)
    }
  },

  resetFilters() {
    this.filters = { types: [], ports: [], tags: [] }
  },

  fetchWeekData() {
    this.loading = true
    const startStr = this.formatDateForPicker(this.weekDays[0])
    const endStr = this.formatDateForPicker(this.weekDays[6])

    const lang = detectSiteLang()
    const langParam = lang !== 'fr' ? `&lang=${lang}` : ''

    fetch(`/wp-json/radicle/v1/planning/week?start=${startStr}&end=${endStr}${langParam}`)
      .then((res) => res.json())
      .then((data) => {
        if (Array.isArray(data)) {
          this.sailings = data
          this.labels = {}
        } else {
          this.sailings = data.sailings ?? []
          this.labels = data.labels ?? {}
        }
        this.loading = false
        this.expandedSailingId = null
      })
      .catch((err) => {
        console.error('Erreur API Planning:', err)
        this.loading = false
      })
  },

  getFilteredSailingsForDay(dateObj) {
    const targetDateStr = `${dateObj.getFullYear()}-${String(dateObj.getMonth() + 1).padStart(2, '0')}-${String(dateObj.getDate()).padStart(2, '0')}`

    return this.sailings.filter((sailing) => {
      const sailingDateStr = sailing.datetime.split(/[T ]/)[0]
      if (sailingDateStr !== targetDateStr) return false

      if (this.filters.types.length > 0 && !this.filters.types.includes(sailing.type_id))
        return false
      if (this.filters.ports.length > 0 && !this.filters.ports.includes(sailing.port_id))
        return false

      if (this.filters.tags.length > 0) {
        const sailingTags = sailing.tags || []
        const hasMatchingTag = this.filters.tags.every((tagId) => sailingTags.includes(tagId))
        if (!hasMatchingTag) return false
      }

      return true
    })
  },

  isPastDate(datetimeStr) {
    if (!datetimeStr) return false
    return new Date(datetimeStr.replace(' ', 'T')) < new Date()
  },

  getCardStyle(sailing) {
    const isPast = this.isPastDate(sailing.datetime)

    const config = window.SailingConfig?.[sailing.status] ||
      window.SailingConfig?.['default'] || {
        bg: 'bg-gray-100',
        text: 'text-gray-600',
        btnText: 'text-gray-900',
        label: sailing.status_label || this.labels.unavailable || 'NON DISPONIBLE',
        isSelectable: false,
      }

    if (isPast && sailing.status !== 'Annulé') {
      return {
        bg: 'bg-gray-200',
        text: 'text-gray-500',
        btnText: 'text-gray-500',
        label: this.labels.finished || 'TERMINÉ',
        buttonLabel: this.labels.finishedBtn || 'Terminé',
        isSelectable: false,
        isPast: true,
      }
    }

    return {
      bg: config.bg,
      text: config.text,
      btnText: config.btnText,
      label: sailing.status_label || config.label,
      buttonLabel: this.labels.book || 'Réserver',
      isSelectable: config.isSelectable && !isPast,
      isPast: isPast,
    }
  },
})

if (window.Alpine) {
  window.Alpine.data('globalPlanning', globalPlanningData)
} else {
  document.addEventListener('alpine:init', () => {
    window.Alpine.data('globalPlanning', globalPlanningData)
  })
}
