@if (! $query->have_posts())
  <div
    class="rounded-card col-span-1 border border-gray-100 bg-gray-50 py-20 text-center md:col-span-2 lg:col-span-3"
  >
    <svg
      class="mx-auto mb-4 h-16 w-16 text-gray-300"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
      ></path>
    </svg>
    <p class="text-primary-900 mb-2 text-xl font-bold">
      Aucune croisière ne correspond à votre recherche.
    </p>
    <button @click="resetFilters()" class="text-secondary-800 hover:text-secondary-600 underline">
      Réinitialiser les filtres
    </button>
  </div>
@else
  <div class="animate-fade-in grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    @while ($query->have_posts())
      @php($query->the_post())
      @php($cruise = \App\Models\Cruise::find(get_the_ID()))

      <article
        class="group flex h-full flex-col overflow-hidden rounded-[20px] shadow-lg transition-all duration-300 hover:shadow-xl"
      >
        <div class="relative h-[280px] overflow-hidden">
          <a href="{{ $cruise->permalink }}" class="block h-full">
            @if ($cruise->thumbnail_url)
              <img
                src="{{ $cruise->thumbnail_url }}"
                alt="{!! $cruise->title !!}"
                class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
              />
            @else
              <div
                class="bg-primary-100 text-primary-400 flex h-full w-full items-center justify-center"
              >
                <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                  ></path>
                </svg>
              </div>
            @endif
          </a>
{{--          @if ($cruise->tags)--}}
{{--            <div class="absolute top-4 left-4 inline-flex space-x-2">--}}
{{--              @foreach ($cruise->tags as $tag)--}}
{{--                <span--}}
{{--                  class="text-primary-900 z-10 flex items-center rounded-full bg-white/90 px-3 py-1 text-xs font-bold shadow-xl backdrop-blur"--}}
{{--                >--}}
{{--                  {{ $tag['name'] }}--}}
{{--                </span>--}}
{{--              @endforeach--}}
{{--            </div>--}}
{{--          @endif--}}
        </div>

        <div
          class="from-primary-1000 to-primary-900 flex flex-1 flex-col bg-gradient-to-b p-6 text-left"
        >
          <h3
            class="font-heading mb-2 line-clamp-2 text-center text-xl leading-snug font-bold uppercase"
          >
            <a href="{{ $cruise->permalink }}" class="hover:text-secondary transition-colors">
              {!! $cruise->title !!}
            </a>
          </h3>

          <div class="mt-auto pt-4">
            <div class="font-elms flex flex-col text-center">
              @if ($cruise->harbor)
                <span class="text-md text-primary-200">au départ de</span>
                <span class="text-secondary text-sm font-bold">
                  {{ $cruise->harbor->name ?? '' }}
                </span>
              @endif
            </div>
            <div class="font-elms mt-4 mb-6 text-center">
              @if ($cruise->base_price)
                <span class="">à partir de</span>
                <span class="text-lg font-bold">{{ $cruise->base_price }}€</span>
              @endif
            </div>
            <x-partials.button :url="$cruise->permalink" title="Réserver" align="full" />
          </div>
        </div>
      </article>
    @endwhile

    @php(wp_reset_postdata())
  </div>
@endif
