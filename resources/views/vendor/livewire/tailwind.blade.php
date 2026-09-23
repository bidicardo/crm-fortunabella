@php
// Пагинация Livewire под дизайн-систему (Д1б). Логика и вызовы — как в исходном
// vendor/livewire/.../SupportPagination/views/tailwind.blade.php, изменён только вид.
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';

$page = 'focus-ring inline-flex h-11 min-w-11 items-center justify-center rounded-md px-3 text-body';
$pageName = $paginator->getPageName();
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Страницы" class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-caption text-ink-secondary">
                Показаны {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} из {{ $paginator->total() }}
            </p>

            <div class="flex flex-wrap items-center gap-1">
                @if ($paginator->onFirstPage())
                    <span class="{{ $page }} text-ink-muted opacity-50" aria-disabled="true" aria-label="Предыдущая страница">
                        <x-icon name="chevron-left" />
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $pageName }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled"
                        class="{{ $page }} hover:bg-line/50" aria-label="Предыдущая страница">
                        <x-icon name="chevron-left" />
                    </button>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="{{ $page }} text-ink-muted" aria-disabled="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $number => $url)
                            <span wire:key="paginator-{{ $pageName }}-page{{ $number }}">
                                @if ($number == $paginator->currentPage())
                                    <span class="{{ $page }} bg-accent-fill font-semibold text-white" aria-current="page">{{ $number }}</span>
                                @else
                                    <button type="button" wire:click="gotoPage({{ $number }}, '{{ $pageName }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                        class="{{ $page }} hover:bg-line/50" aria-label="Страница {{ $number }}">{{ $number }}</button>
                                @endif
                            </span>
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $pageName }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled"
                        class="{{ $page }} hover:bg-line/50" aria-label="Следующая страница">
                        <x-icon name="chevron-left" class="rotate-180" />
                    </button>
                @else
                    <span class="{{ $page }} text-ink-muted opacity-50" aria-disabled="true" aria-label="Следующая страница">
                        <x-icon name="chevron-left" class="rotate-180" />
                    </span>
                @endif
            </div>
        </nav>
    @endif
</div>
