@php
  $mapUrl = is_array($mapImage) ? $mapImage['url'] : (is_numeric($mapImage) ? wp_get_attachment_image_url($mapImage, 'large') : $mapImage);
  $mapAlt = is_array($mapImage) ? $mapImage['alt'] ?? 'Carte du parcours' : 'Carte du parcours';
@endphp

@if ($mapUrl)
  <div class="overflow-hidden">
    <img src="{{ $mapUrl }}" alt="{{ $mapAlt }}" class="h-[600px] w-auto mx-auto object-cover shadow-lg" />
  </div>
@endif
