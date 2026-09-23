{{-- Обёртка: своя горизонтальная прокрутка блока, не всей страницы (docs/16). --}}
<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-lg border border-line']) }}>
    <table class="w-full text-left text-body">
        {{ $slot }}
    </table>
</div>
