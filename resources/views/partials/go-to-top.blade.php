<div class="fixed right-4 bottom-10 z-50 flex flex-col items-center gap-3">
  <a
    href="tel:{{ formatPhoneTel(get_field('phone', 'option')) }}"
    class="flex h-14 w-14 items-center justify-center rounded-full bg-secondary shadow-image-card transition-transform hover:scale-105"
    style=""
  >
    <x-bi-telephone class="h-6 w-6 text-primary-900" />
  </a>

  <a
    href="{{get_permalink(get_page_by_path('nous-contacter'))}}"
    class="flex h-14 w-14 items-center justify-center rounded-full shadow-image-card bg-secondary transition-transform hover:scale-105"
    style=""
  >
    <x-bi-send class="h-6 w-6 text-primary-900" />
  </a>

  <button
    onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
    class="mt-4 flex h-14 w-14 items-center justify-center rounded-full bg-white shadow-image-card transition-transform hover:scale-105"
  >
    <x-bi-chevron-up class="h-6 w-6 text-primary-900" />
  </button>
</div>
