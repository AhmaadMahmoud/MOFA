@props([
    'variant' => 'primary', // primary | accent | ghost
    'href' => null,
])
@php($classes = 'btn btn-'.$variant)
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class($classes) }}>{{ $slot }}</button>
@endif
