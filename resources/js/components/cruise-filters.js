const cruiseFilterData = (initialMaxPages, initialCount) => ({
  filterOpen: false,
  loading: false,
  loadingMore: false,
  currentPage: 1,
  maxPages: initialMaxPages,
  totalCount: initialCount,
  filters: {
    sort: 'price_asc',
    tags: [],
    categories: []
  },
  gridHtml: '',

  init() {
    console.log('Cruise Filter Initialized');
  },

  toggleFilter(type, value) {
    if (type === 'categories') {
      if (this.filters.categories.includes(value)) {
        this.filters.categories = this.filters.categories.filter(i => i !== value);
      } else {
        this.filters.categories.push(value);
      }
    } else {
      if (this.filters[type].includes(value)) {
        this.filters[type] = this.filters[type].filter(i => i !== value);
      } else {
        this.filters[type].push(value);
      }
    }
    this.applyFilters(false);
  },

  buildParams(page = 1) {
    const params = new URLSearchParams();
    params.append('page', page);
    if (this.filters.sort) params.append('sort', this.filters.sort);
    if (this.filters.tags.length) params.append('tags', this.filters.tags.join(','));
    if (this.filters.categories.length) params.append('categories', this.filters.categories.join(','));
    return params;
  },

  applyFilters(closePanel = true) {
    this.loading = true;
    this.currentPage = 1;

    if (closePanel) this.filterOpen = false;
    fetch(`/wp-json/radicle/v1/cruises/search?${this.buildParams(1).toString()}`)
      .then(res => res.json())
      .then(data => {
        this.gridHtml = data.html;
        this.maxPages = data.max_pages;
        this.totalCount = parseInt(data.count) || 0;
        this.loading = false;
        if (closePanel) {
          const anchor = document.getElementById('results-anchor');
          if (anchor) anchor.scrollIntoView({ behavior: 'smooth' });
        }
      })
      .catch(err => {
        console.error('Error applying filters:', err);
        this.loading = false;
      });
  },

  loadMore() {
    if (this.currentPage >= this.maxPages || this.loadingMore) return;

    this.loadingMore = true;
    const nextPage = this.currentPage + 1;

    fetch(`/wp-json/radicle/v1/cruises/search?${this.buildParams(nextPage).toString()}`)
      .then(res => res.json())
      .then(data => {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = data.html;

        const gridContent = tempDiv.querySelector('.grid') ? tempDiv.querySelector('.grid').innerHTML : '';

        if (gridContent) {
          const activeContainer = this.gridHtml ? this.$refs.jsGrid : this.$refs.phpGrid;

          if (activeContainer) {
            const activeGrid = activeContainer.querySelector('.grid');
            if (activeGrid) {
              activeGrid.insertAdjacentHTML('beforeend', gridContent);
            }
          }
        }

        this.currentPage = nextPage;
        this.totalCount = data.count;
        this.maxPages = data.max_pages;
        this.loadingMore = false;
      })
      .catch(err => {
        console.error('Error loading more:', err);
        this.loadingMore = false;
      });
  },

  resetFilters() {
    this.filters = { sort: 'price_asc', tags: [], categories: [] };
    this.applyFilters(false);
  }
});

const cruiseContainer = document.querySelector('[data-component="cruise-filters"]');
 if(cruiseContainer) {
   if (window.Alpine) {
     window.Alpine.data('cruiseFilter', cruiseFilterData);
   } else {
     document.addEventListener('alpine:init', () => {
       window.Alpine.data('cruiseFilter', cruiseFilterData);
     });
   }
 }
