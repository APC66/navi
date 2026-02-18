@if (!$query->have_posts())
    <div class="col-span-1 md:col-span-2 lg:col-span-3 text-center py-20 bg-gray-50 rounded-card border border-gray-100">
        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
        <p class="text-xl text-primary-900 font-bold mb-2">Aucune croisière ne correspond à votre recherche.</p>
        <button @click="resetFilters()" class="text-secondary-800 underline hover:text-secondary-600">Réinitialiser les filtres</button>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 animate-fade-in">
        @while ($query->have_posts()) @php($query->the_post())
        @php( $cruise = \App\Models\Cruise::find(get_the_ID()) )

        <article class="h-full flex flex-col rounded-[20px] overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300 group">
            <div class="relative overflow-hidden h-[280px]">
                <a href="{{ $cruise->permalink }}" class="block h-full">
                    @if($cruise->thumbnail_url)
                        <img src="{{ $cruise->thumbnail_url }}" alt="{{ $cruise->title }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                    @else
                        <div class="w-full h-full bg-primary-100 flex items-center justify-center text-primary-400">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                    @endif
                </a>
                @if($cruise->tags)
                    <div class="absolute top-4 left-4 inline-flex space-x-2">
                        @foreach($cruise->tags as $tag)
                            <span class="bg-white/90 backdrop-blur text-primary-900 text-xs font-bold px-3 py-1 rounded-full flex items-center shadow-xl z-10">{{ $tag['name'] }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="p-6 flex-1 flex flex-col text-left from-primary-1000 to-primary-900 bg-gradient-to-b">
                <h3 class="text-xl font-bold uppercase text-center mb-2 font-heading leading-snug line-clamp-2">
                    <a href="{{ $cruise->permalink }}" class="hover:text-secondary transition-colors">
                        {!! $cruise->title !!}
                    </a>
                </h3>

                <div class="mt-auto pt-4">
                    <div class="flex flex-col text-center font-elms">
                        @if($cruise->harbor)
                            <span class="text-md text-primary-200">au départ de</span>
                            <span class="text-sm font-bold text-secondary">{{ $cruise->harbor->name ?? '' }}</span>
                        @endif
                    </div>
                        <div class="font-elms mt-4 mb-6 text-center">
                            @if($cruise->base_price)
                                <span class="">à partir de</span>
                                <span class="text-lg font-bold">{{ $cruise->base_price }}€</span>
                            @endif
                        </div>
                    <x-partials.button :url="$cruise->permalink" title="Réserver" align="full"/>
                </div>
            </div>
        </article>
        @endwhile
        @php(wp_reset_postdata())
    </div>
@endif
