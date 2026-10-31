@props([
    'script' => null,   // handwritten aside on a mauve brush stroke
    'retro' => false,   // chunky 70s headline instead of the calm serif
    'as' => 'h2',
    'align' => 'start', // start | center
])
<div {{ $attributes->class(['flex flex-col gap-3', 'items-center text-center' => $align === 'center', 'items-start' => $align !== 'center']) }}>
    @if ($script)
        <span class="brush -rotate-3 text-[1.75rem] leading-none sm:text-[2.125rem]">{{ $script }}</span>
    @endif
    <{{ $as }} @class([
        'm-0 text-brown text-balance',
        'font-retro font-normal uppercase leading-[0.95] tracking-[-0.01em] text-[2.75rem] sm:text-6xl rtl:font-extrabold rtl:normal-case' => $retro,
        'font-serif font-bold leading-[1.1] text-[2rem] sm:text-[2.625rem]' => ! $retro,
    ])>{{ $slot }}</{{ $as }}>
</div>
