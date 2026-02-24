@php
  $layoutConfig = [
    'grid' => [
      'container' => 'grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3',
      'item' => 'overflow-hidden border border-gray-200 bg-white',
      'image' => 'h-48 w-full object-cover',
      'title' => 'mb-2 block p-4 pb-2 text-lg font-semibold text-gray-900 no-underline hover:text-blue-600',
      'content' => 'px-4 pb-4 text-sm leading-relaxed text-gray-600',
      'element' => 'div',
      'itemElement' => 'article',
    ],
    'list' => [
      'container' => 'm-0 list-none space-y-6 p-0',
      'item' => 'flex items-start gap-4 border-b border-gray-200 pb-6 last:border-b-0 last:pb-0',
      'image' => 'h-24 w-32 flex-shrink-0 object-cover',
      'title' => 'mb-2 block text-lg font-semibold text-gray-900 no-underline hover:text-blue-600',
      'content' => 'text-sm leading-relaxed text-gray-600',
      'element' => 'ul',
      'itemElement' => 'li',
    ],
  ];

  $config = $layoutConfig[$postLayout];
@endphp

@if (! empty($seeds))
  <{{ $config['element'] }}
    class="{{ $config['container'] }}"
    role="feed"
    aria-label="Latest seeds"
  >
    @foreach ($seeds as $seed)
      @php
        $permalink = esc_url(get_permalink($seed));
        $title = esc_html(get_the_title($seed));
        $excerpt = wp_kses_post(get_the_excerpt($seed));
        $featuredImageUrl = get_the_post_thumbnail_url($seed, 'medium');
        $altText = $featuredImageUrl ? esc_attr(get_post_meta(get_post_thumbnail_id($seed), '_wp_attachment_image_alt', true) ?: $title) : '';
      @endphp

      @if ($postLayout === 'grid')
        <article class="{{ $config['item'] }}" aria-labelledby="seed-title-{{ $seed->ID }}">
          @if ($displayFeaturedImage && $featuredImageUrl)
            <figure>
              <img
                src="{{ $featuredImageUrl }}"
                alt="{{ $altText }}"
                class="{{ $config['image'] }}"
                loading="lazy"
                decoding="async"
              />
            </figure>
          @endif

          <a
            class="{{ $config['title'] }}"
            href="{{ $permalink }}"
            id="seed-title-{{ $seed->ID }}"
            aria-describedby="{{ $displayPostContent !== 'none' ? 'seed-content-' . $seed->ID : '' }}"
          >
            {{ $title }}
          </a>

          @if ($displayPostContent === 'excerpt' && $excerpt)
            <div class="{{ $config['content'] }}" id="seed-content-{{ $seed->ID }}">
              {!! $excerpt !!}
            </div>
          @endif

          @if ($displayPostContent === 'content')
            <div
              class="{{ str_replace('text-gray-600', 'text-gray-800', $config['content']) }}"
              id="seed-content-{{ $seed->ID }}"
            >
              {!! wp_kses_post(apply_filters('the_content', $seed->post_content)) !!}
            </div>
          @endif
        </article>
      @else
        <li class="{{ $config['item'] }}">
          @if ($displayFeaturedImage && $featuredImageUrl)
            <figure>
              <img
                src="{{ $featuredImageUrl }}"
                alt="{{ $altText }}"
                class="{{ $config['image'] }}"
                loading="lazy"
                decoding="async"
              />
            </figure>
          @endif

          <article aria-labelledby="seed-title-{{ $seed->ID }}" class="min-w-0 flex-1">
            <a
              class="{{ $config['title'] }}"
              href="{{ $permalink }}"
              id="seed-title-{{ $seed->ID }}"
              aria-describedby="{{ $displayPostContent !== 'none' ? 'seed-content-' . $seed->ID : '' }}"
            >
              {{ $title }}
            </a>

            @if ($displayPostContent === 'excerpt' && $excerpt)
              <div class="{{ $config['content'] }}" id="seed-content-{{ $seed->ID }}">
                {!! $excerpt !!}
              </div>
            @endif

            @if ($displayPostContent === 'content')
              <div
                class="{{ str_replace('text-gray-600', 'text-gray-800', $config['content']) }}"
                id="seed-content-{{ $seed->ID }}"
              >
                {!! wp_kses_post(apply_filters('the_content', $seed->post_content)) !!}
              </div>
            @endif
          </article>
        </li>
      @endif
    @endforeach
  </{{ $config['element'] }}>
@else
  <p>No seeds found.</p>
@endif
