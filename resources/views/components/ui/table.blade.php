{{-- Обёртка: своя горизонтальная прокрутка блока, не всей страницы (docs/16).
     relative обязателен: иначе абсолютно позиционированный элемент внутри (например sr-only
     в заголовке колонки) выходит из прокрутки и растягивает всю страницу на iPhone. --}}
<div {{ $attributes->merge(['class' => 'relative overflow-x-auto rounded-lg border border-line']) }}>
    <table class="w-full text-left text-body">
        {{ $slot }}
    </table>
</div>
