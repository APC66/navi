@php
$layoutConfig = [
    'grid' => [
        'container' => 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6',
        'item' => 'bg-white border border-gray-200 overflow-hidden',
        'image' => 'w-full h-48 object-cover',
        'title' => 'block text-lg font-semibold text-gray-900 hover:text-blue-600 no-underline mb-2 p-4 pb-2',
        'content' => 'text-gray-600 text-sm leading-relaxed px-4 pb-4',
        'element' => 'div',
        'itemElement' => 'article'
    ],
    'list' => [
        'container' => 'list-none p-0 m-0 space-y-6',
        'item' => 'pb-6 border-b border-gray-200 last:border-b-0 last:pb-0 flex gap-4 items-start',
        'image' => 'w-32 h-24 object-cover flex-shrink-0',
        'title' => 'block text-lg font-semibold text-gray-900 hover:text-blue-600 no-underline mb-2',
        'content' => 'text-gray-600 text-sm leading-relaxed',
        'element' => 'ul',
        'itemElement' => 'li'
    ]
];

$config = $layoutConfig[$postLayout];
@endphp

@if (!empty($seeds))
    <{{ $config['element'] }} class="{{ $config['container'] }}" role="feed" aria-label="Latest seeds">
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
                            >
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
                        <div class="{{ str_replace('text-gray-600', 'text-gray-800', $config['content']) }}" id="seed-content-{{ $seed->ID }}">
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
                            >
                        </figure>
                    @endif

                    <article aria-labelledby="seed-title-{{ $seed->ID }}" class="flex-1 min-w-0">
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
                            <div class="{{ str_replace('text-gray-600', 'text-gray-800', $config['content']) }}" id="seed-content-{{ $seed->ID }}">
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