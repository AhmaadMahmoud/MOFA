@props(['class' => ''])
{{-- The turning "Simple & honest" ring of the story stamp.
     direction:ltr on the text keeps path text visible on Arabic pages (Arabic still joins and reads right).
     textLength closes the ring exactly; Arabic gets a fourth phrase instead, as spacing would break its joins. --}}
<svg class="story-spin-slow {{ $class }}" viewBox="0 0 200 200" aria-hidden="true">
    <defs><path id="stamp-ring" d="M100 100m-78 0a78 78 0 1 1 156 0a78 78 0 1 1-156 0" /></defs>
    <circle cx="100" cy="100" r="96" fill="none" stroke="currentColor" stroke-width="1.5" />
    <circle cx="100" cy="100" r="60" fill="none" stroke="currentColor" stroke-width="1.5" stroke-dasharray="3 6" />
    @if (app()->getLocale() === 'ar')
        <text class="fill-current font-sans text-[17px] font-bold" style="direction: ltr">
            <textPath href="#stamp-ring">{{ __('Simple & honest') }} · {{ __('Made of Faten') }} · {{ __('Real food') }} · {{ __('Fresh sourdough.') }}</textPath>
        </text>
    @else
        <text class="fill-current font-sans text-[15px] font-semibold uppercase" style="direction: ltr">
            <textPath href="#stamp-ring" textLength="486" lengthAdjust="spacing">{{ __('Simple & honest') }} · {{ __('Made of Faten') }} · {{ __('Real food') }} ·</textPath>
        </text>
    @endif
</svg>
