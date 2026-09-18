@props(['name'])

@php
    $paths = [
        'home' => 'M3 10.5 12 3l9 7.5M5 9.5V21h5v-6h4v6h5V9.5',
        'deals' => 'M3 7h18v12H3zM8 7V5h8v2M3 13h18',
        'clients' => 'M16 19v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 17.5V19M10 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7M20 19v-1.5a3.5 3.5 0 0 0-2.5-3.35M15.5 4.15a3.5 3.5 0 0 1 0 6.7',
        'counterparties' => 'M4 21V6l8-3v18M12 9h8v12M8 9h.01M8 13h.01M8 17h.01M16 13h.01M16 17h.01M2 21h20',
        'tasks' => 'M9 6h11M9 12h11M9 18h11M3.5 6l1 1 2-2M3.5 12l1 1 2-2M3.5 18l1 1 2-2',
        'calendar' => 'M4 6h16v15H4zM4 10h16M8 3v4M16 3v4',
        'documents' => 'M7 3h7l5 5v13H7zM14 3v5h5M10 13h6M10 17h6',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'h-5 w-5 shrink-0']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="{{ $paths[$name] ?? '' }}" />
</svg>
