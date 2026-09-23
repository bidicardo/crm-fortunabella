@props([
    'title' => null,
])

<section {{ $attributes->merge(['class' => 'rounded-lg border border-line bg-surface p-4 sm:p-6']) }}>
    @if ($title)
        <h2 class="mb-3 text-h3 font-semibold text-ink">{{ $title }}</h2>
    @endif

    {{ $slot }}
</section>
