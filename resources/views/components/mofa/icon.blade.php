@props(['name', 'size' => 24])
{{-- Inline stroke icons, drawn in currentColor. Decorative unless an aria-label is passed. --}}
<svg {{ $attributes->merge(['width' => $size, 'height' => $size, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round']) }} @unless ($attributes->has('aria-label')) aria-hidden="true" @endunless>
    @switch($name)
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h10" />
            @break
        @case('close')
            <path d="M6 6l12 12M18 6L6 18" />
            @break
        @case('bag')
            <path d="M5 8h14l-1.2 11.2a1 1 0 0 1-1 .8H7.2a1 1 0 0 1-1-.8z" /><path d="M9 8V6a3 3 0 0 1 6 0v2" />
            @break
        @case('arrow')
            <path d="M5 12h14M13 6l6 6-6 6" />
            @break
        @case('leaf')
            <path d="M12 20V10" /><path d="M12 13c-4 0-6-3-6-7 4 0 6 3 6 7z" /><path d="M12 11c0-4 2-7 6-7 0 4-2 7-6 7z" />
            @break
        @case('no-oil')
            <path d="M12 3s-5 6-5 10a5 5 0 0 0 10 0c0-4-5-10-5-10z" /><path d="M4 4l16 16" />
            @break
        @case('heart')
            <path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z" />
            @break
        @case('instagram')
            <rect x="3.5" y="3.5" width="17" height="17" rx="5" /><circle cx="12" cy="12" r="4" /><circle cx="17.2" cy="6.8" r="0.6" fill="currentColor" />
            @break
        @case('mail')
            <rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3.5 6.5L12 13l8.5-6.5" />
            @break
        @case('calendar')
            <rect x="3.5" y="5" width="17" height="15" rx="2" /><path d="M3.5 10h17M8 3v4M16 3v4" />
            @break
        @case('basket')
            <path d="M4 10h16l-1.5 9a1 1 0 0 1-1 .8h-11a1 1 0 0 1-1-.8z" /><path d="M8 10l3-6M16 10l-3-6" />
            @break
        @case('phone-pay')
            <rect x="6" y="2.5" width="12" height="19" rx="2.5" /><path d="M10 18h4" /><path d="M9.5 10.5l2 2 3.5-4" />
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break
        @case('minus')
            <path d="M5 12h14" />
            @break
        @case('trash')
            <path d="M4 7h16M10 11v6M14 11v6" /><path d="M6 7l1 12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-12M9 7V4h6v3" />
            @break
        @case('copy')
            <rect x="8" y="8" width="12" height="12" rx="2" /><path d="M16 8V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3" />
            @break
        @case('check')
            <path d="M5 12.5l4.5 4.5L19 7.5" />
            @break
        @case('upload')
            <path d="M12 16V4M7 9l5-5 5 5" /><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="8.5" /><path d="M12 7.5V12l3 2" />
            @break
        @case('pin')
            <path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11z" /><circle cx="12" cy="10" r="2.3" />
            @break
        @case('chat')
            <path d="M4.5 19.5l1.2-3.6A8 8 0 1 1 8.4 18.6z" />
            @break
        @case('chat-send')
            <path d="M4.5 19.5l1.2-3.6A8 8 0 1 1 8.4 18.6z" /><path d="M9 12.2l2 2 4-4.4" />
            @break
        @case('alert')
            <circle cx="12" cy="12" r="8.5" /><path d="M12 7.5v5.5M12 16.2v.3" />
            @break
    @endswitch
</svg>
