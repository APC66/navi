@if($url && $title)
    <a
        href="{{ $url }}"
        target="{{ $target }}"
        {{ $attributes->merge(['class' => "inline-flex items-center justify-center px-8 py-3 rounded-full font-bold transition-all duration-300 shadow-button $variantClasses $alignClasses"]) }}
    >
        {{ $title }}
    </a>
@endif
