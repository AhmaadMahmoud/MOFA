@php
    $user = filament()->auth()->user();
    $initials = str($user->name)->explode(' ')->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->join('');
@endphp
{{-- Who is signed in: a brown card in the sidebar's color, with the initials in cream. --}}
<div class="mofa-profile-card">
    <span class="mofa-profile-avatar" aria-hidden="true">{{ mb_strtoupper($initials) }}</span>
    <div class="mofa-profile-who">
        <p class="mofa-profile-name">{{ $user->name }}</p>
        <p class="mofa-profile-email" dir="ltr">{{ $user->email }}</p>
    </div>
    <p class="mofa-profile-since">{{ __('In the dashboard since :date', ['date' => $user->created_at?->translatedFormat('F Y')]) }}</p>
</div>
