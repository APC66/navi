const globalPlanningData = (nonce) => ({
  loading: true,
  apiNonce: nonce,
  currentDate: new Date(),
  sailings: [],

  // Nouveaux états pour le menu unifié
  filterMenuOpen: false,
  filters: {
    types: [], // Désormais des tableaux (sélection multiple)
    ports: [],
    tags: []
  },

  init() {
    this.fetchWeekData();
  },

  // Compteur de filtres actifs pour la pastille
  get activeFiltersCount() {
    return this.filters.types.length + this.filters.ports.length + this.filters.tags.length;
  },

  // Calcule les 7 jours de la semaine courante (du Lundi au Dimanche)
  get weekDays() {
    const curr = new Date(this.currentDate);
    curr.setHours(0, 0, 0, 0); // Reset time to midnight
    const day = curr.getDay(); // 0 = Dimanche, 1 = Lundi
    const diff = curr.getDate() - day + (day === 0 ? -6 : 1);
    const monday = new Date(curr.setDate(diff));

    const days = [];
    for (let i = 0; i < 7; i++) {
      const d = new Date(monday);
      d.setDate(monday.getDate() + i);
      days.push(d);
    }
    return days;
  },

  // Formate l'entête : "Du 02 au 08 mars 2026"
  get weekRangeLabel() {
    const days = this.weekDays;
    const start = days[0];
    const end = days[6];

    const startDay = String(start.getDate()).padStart(2, '0');
    const endDay = String(end.getDate()).padStart(2, '0');

    const months = ['janv.', 'fév.', 'mars', 'avril', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

    if (start.getMonth() === end.getMonth() && start.getFullYear() === end.getFullYear()) {
      return `Du ${startDay} au ${endDay} ${start.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })}`;
    } else if (start.getFullYear() === end.getFullYear()) {
      return `Du ${startDay} ${months[start.getMonth()]} au ${endDay} ${months[end.getMonth()]} ${end.getFullYear()}`;
    } else {
      return `Du ${startDay} ${months[start.getMonth()]} ${start.getFullYear()} au ${endDay} ${months[end.getMonth()]} ${end.getFullYear()}`;
    }
  },

  // Date formattée pour l'input natif HTML (YYYY-MM-DD)
  get datePickerValue() {
    const d = this.currentDate;
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
  },

  set datePickerValue(val) {
    if (val) {
      this.currentDate = new Date(val);
      this.fetchWeekData();
    }
  },

  formatDayHeader(date) {
    const days = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];
    const dayName = days[date.getDay()];
    const dayNum = String(date.getDate()).padStart(2, '0');
    const monthNum = String(date.getMonth() + 1).padStart(2, '0');
    return `${dayName} ${dayNum}/${monthNum}`;
  },

  formatTime(datetimeStr) {
    if (!datetimeStr) return '';
    const timePart = datetimeStr.includes('T') ? datetimeStr.split('T')[1] : datetimeStr.split(' ')[1];
    if (!timePart) return '';
    return timePart.substring(0, 5);
  },

  isToday(date) {
    const today = new Date();
    return date.getDate() === today.getDate() &&
      date.getMonth() === today.getMonth() &&
      date.getFullYear() === today.getFullYear();
  },

  nextWeek() {
    const next = new Date(this.currentDate);
    next.setDate(next.getDate() + 7);
    this.currentDate = next;
    this.fetchWeekData();
  },

  prevWeek() {
    const prev = new Date(this.currentDate);
    prev.setDate(prev.getDate() - 7);
    this.currentDate = prev;
    this.fetchWeekData();
  },

  goToToday() {
    this.currentDate = new Date();
    this.fetchWeekData();
  },

  // Toggle multi-sélection pour les filtres
  toggleFilter(filterCategory, termId) {
    const index = this.filters[filterCategory].indexOf(termId);
    if (index > -1) {
      this.filters[filterCategory].splice(index, 1);
    } else {
      this.filters[filterCategory].push(termId);
    }
  },

  resetFilters() {
    this.filters = { types: [], ports: [], tags: [] };
  },

  fetchWeekData() {
    this.loading = true;
    const startStr = this.weekDays[0].toISOString().split('T')[0];
    const endStr = this.weekDays[6].toISOString().split('T')[0];

    fetch(`/wp-json/radicle/v1/planning/week?start=${startStr}&end=${endStr}`, {
      headers: { 'X-WP-Nonce': this.apiNonce }
    })
      .then(res => res.json())
      .then(data => {
        this.sailings = data;
        this.loading = false;
      })
      .catch(err => {
        console.error('Erreur API Planning:', err);
        this.loading = false;
      });
  },

  // Retourne les croisières filtrées pour un jour spécifique
  getFilteredSailingsForDay(dateObj) {
    const targetDateStr = `${dateObj.getFullYear()}-${String(dateObj.getMonth() + 1).padStart(2, '0')}-${String(dateObj.getDate()).padStart(2, '0')}`;

    return this.sailings.filter(sailing => {
      // Vérification de la date
      const sailingDateStr = sailing.datetime.split(/[T ]/)[0];
      if (sailingDateStr !== targetDateStr) return false;

      // Filtres multiples (Logique "OU" au sein d'une catégorie, et "ET" entre catégories)
      if (this.filters.types.length > 0 && !this.filters.types.includes(sailing.type_id)) return false;
      if (this.filters.ports.length > 0 && !this.filters.ports.includes(sailing.port_id)) return false;

      // Pour les tags (le sailing.tags est un tableau, on vérifie si au moins un correspond)
      if (this.filters.tags.length > 0) {
        const sailingTags = sailing.tags || [];
        const hasMatchingTag = this.filters.tags.some(tagId => sailingTags.includes(tagId));
        if (!hasMatchingTag) return false;
      }

      return true;
    });
  },

  getCardStyle(status) {
    switch(status) {
      case 'Dispo':
        return { bg: 'bg-[#C5F8A5]', text: 'text-[#166534]', btnText: 'text-[#166534]' };
      case 'Limité':
        return { bg: 'bg-[#FFA632]', text: 'text-[#9A3B0D]', btnText: 'text-[#9A3B0D]' };
      case 'Reporté':
        return { bg: 'bg-[#FBF166]', text: 'text-[#744210]', btnText: 'text-[#744210]' };
      case 'Annulé':
        return { bg: 'bg-[#60386B]', text: 'text-white', btnText: 'text-[#60386B]' };
      case 'Complet':
        return { bg: 'bg-[#C33149]', text: 'text-white', btnText: 'text-[#C33149]' };
      default:
        return { bg: 'bg-gray-100', text: 'text-gray-600', btnText: 'text-gray-900' };
    }
  }
});

if (window.Alpine) {
  window.Alpine.data('globalPlanning', globalPlanningData);
} else {
  document.addEventListener('alpine:init', () => {
    window.Alpine.data('globalPlanning', globalPlanningData);
  });
}
