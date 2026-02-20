{{-- Chargement des assets Lightbox2 uniquement quand cet onglet est utilisé --}}
@once
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox-plus-jquery.min.js" defer></script>

    <style>
        .lb-details { font-family: var(--font-sans, sans-serif); }
        .lb-data .lb-caption { font-weight: bold; font-size: 1.1rem; }
    </style>
@endonce

<div class="grid grid-cols-2 md:grid-cols-4 auto-rows-[150px] md:auto-rows-[220px] gap-4 grid-flow-dense">

    @foreach($gallery as $image)
        @php
            $imageId = is_array($image) ? $image['ID'] : $image;

            $imgData = wp_get_attachment_image_src($imageId, 'large');
            $fullData = wp_get_attachment_image_src($imageId, 'full');

            $imageUrl = $imgData[0] ?? '';
            $fullUrl = $fullData[0] ?? '';
            $alt = get_post_meta($imageId, '_wp_attachment_image_alt', true) ?: 'Image galerie';

            $patterns = [
                'col-span-2 row-span-1',
                'col-span-1 row-span-1',
                'col-span-1 row-span-1',
                'col-span-1 row-span-1',
                'col-span-1 row-span-2',
                'col-span-2 row-span-1',
                'col-span-1 row-span-1',
                'col-span-1 row-span-1',
                'col-span-1 row-span-1',
            ];
            $spanClass = $patterns[$loop->index % 9];
        @endphp

        @if($imageUrl)
            <a href="{{ $fullUrl }}"
               data-lightbox="cruise-gallery"
               data-title="{{ $alt }}"
               class="block relative overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group {{ $spanClass }}">

                <img src="{{ $imageUrl }}"
                     alt="{{ $alt }}"
                     class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-in-out">

                <div class="absolute inset-0 bg-primary-900/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                    <svg class="w-10 h-10 text-white transform scale-50 group-hover:scale-100 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                </div>
            </a>
        @endif
    @endforeach

</div>
