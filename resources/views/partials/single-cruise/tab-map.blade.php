@php
    $mapUrl = is_array($mapImage) ? $mapImage['url'] : (is_numeric($mapImage) ? wp_get_attachment_image_url($mapImage, 'large') : $mapImage);
    $mapAlt = is_array($mapImage) ? ($mapImage['alt'] ?? 'Carte du parcours') : 'Carte du parcours';
@endphp

@if($mapUrl)
    <div class="overflow-hidden shadow-lg">
        <img src="{{ $mapUrl }}" alt="{{ $mapAlt }}" class="w-full h-auto object-cover">
    </div>
@endif
