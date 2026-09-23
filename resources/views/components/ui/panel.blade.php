{{-- Только визуальный контейнер выпадающей панели; открытие/закрытие (Alpine) остаётся у вызывающего кода. --}}
<div {{ $attributes->merge(['class' => 'rounded-lg border border-line bg-surface py-1 shadow-panel']) }}>
    {{ $slot }}
</div>
