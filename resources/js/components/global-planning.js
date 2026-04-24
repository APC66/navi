const globalPlanningData = (nonce) => ({
  loading: true,
  apiNonce: nonce,
  currentDate: new Date(),
  datePickerValue: '', // NOUVEAU: Variable simple pour éviter le bug de x-model
  sailings: [],

  // Nouveaux états pour le menu unifié
  filterMenuOpen: false,
  filters: {
    types: [], // Désormais des tableaux (sélection multiple)
    ports: [],
    tags: []
  },

  // État pour savoir quelle carte est dépliée
  expandedSailingId: null,

  init() {
    // Initialisation de la date
    this.datePickerValue = this.formatDateForPicker(this.currentDate);

    // NOUVEAU: On surveille les changements de l'input date
    this.$watch('datePickerValue', (val) => {
      if (val && val !== this.formatDateForPicker(this.currentDate)) {
        this.currentDate = new Date(val);
        this.fetchWeekData();
      }
    });

    this.fetchWeekData();
  },

  // NOUVEAU: Force l'ouverture du calendrier natif au clic
  openDatePicker(event) {
    try {
      if (typeof event.target.showPicker === 'function') {
        event.target.showPicker();
      }
    } catch (e) {
      // Ignoré silencieusement pour les navigateurs non supportés
    }
  },

  // NOUVEAU: Fonction utilitaire pour formater la date pour l'input
  formatDateForPicker(d) {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
  },

  // Action pour dérouler/replier une carte
  toggleSailing(id) {
    if (this.expandedSailingId === id) {
      this.expandedSailingId = null; // Replie si c'est déjà ouvert
    } else {
      this.expandedSailingId = id; // Déplie la nouvelle carte
    }
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
    this.datePickerValue = this.formatDateForPicker(this.currentDate); // MAJ
    this.fetchWeekData();
  },

  prevWeek() {
    const prev = new Date(this.currentDate);
    prev.setDate(prev.getDate() - 7);
    this.currentDate = prev;
    this.datePickerValue = this.formatDateForPicker(this.currentDate); // MAJ
    this.fetchWeekData();
  },

  goToToday() {
    this.currentDate = new Date();
    this.datePickerValue = this.formatDateForPicker(this.currentDate); // MAJ
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
    const startStr = this.formatDateForPicker(this.weekDays[0]);
    const endStr = this.formatDateForPicker(this.weekDays[6]);

    fetch(`/wp-json/radicle/v1/planning/week?start=${startStr}&end=${endStr}`, {
      headers: { 'X-WP-Nonce': this.apiNonce }
    })
      .then(res => res.json())
      .then(data => {
        this.sailings = data;
        this.loading = false;
        this.expandedSailingId = null; // On referme toutes les cartes quand on change de semaine
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

  // Nouvelle méthode pour vérifier si une date est passée
  isPastDate(datetimeStr) {
    if (!datetimeStr) return false;
    // Remplacer l'espace par T pour compatibilité Safari
    return new Date(datetimeStr.replace(' ', 'T')) < new Date();
  },

  // Utilise la configuration centralisée passée par PHP (wp_add_inline_script)
  getCardStyle(sailing) {
    const isPast = this.isPastDate(sailing.datetime);

    // On sécurise avec l'optional chaining (?.) au cas où la config mettrait du temps à charger
    const config = window.SailingConfig?.[sailing.status] || window.SailingConfig?.['default'] || {
      bg: 'bg-gray-100', text: 'text-gray-600', btnText: 'text-gray-900', label: 'NON DISPONIBLE', isSelectable: false
    };

    // LOGIQUE : Si c'est passé (et que ce n'est pas annulé), on force un style grisé "Terminé"
    if (isPast && sailing.status !== 'Annulé') {
      return {
        bg: 'bg-gray-200',
        text: 'text-gray-500',
        btnText: 'text-gray-500',
        label: 'TERMINÉ',
        buttonLabel: 'Terminé',
        isSelectable: false,
        isPast: true
      };
    }

    return {
      bg: config.bg,
      text: config.text,
      btnText: config.btnText,
      label: config.label,
      buttonLabel: 'Réserver',
      isSelectable: config.isSelectable && !isPast, // On empêche la sélection si c'est passé
      isPast: isPast
    };
  }
});

if (window.Alpine) {
  window.Alpine.data('globalPlanning', globalPlanningData);
} else {
  document.addEventListener('alpine:init', () => {
    window.Alpine.data('globalPlanning', globalPlanningData);
  });
}
