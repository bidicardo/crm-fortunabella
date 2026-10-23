{{-- Блок «Сделки» в карточках клиента и контрагента (задача 27): сделки записи и «Новая сделка» с предвыбором.
     Параметры: $deals (коллекция Deal), $createUrl. --}}
<x-ui.card title="Сделки">
    <div class="space-y-3">
        @forelse ($deals as $deal)
            <a wire:key="deal-{{ $deal->id }}" href="{{ route('deals.show', $deal) }}"
                class="focus-ring flex flex-wrap items-center gap-x-3 gap-y-1 rounded-md border border-line bg-page px-3 py-2 hover:border-accent">
                <span class="min-w-0 flex-1 break-words font-semibold">{{ $deal->title }}</span>
                <x-ui.badge :tone="$deal->stage->tone()">{{ $deal->stage->label() }}</x-ui.badge>
                <span class="w-full text-caption text-ink-secondary">
                    {{ collect([$deal->event_date?->format('d.m.Y'), App\Models\Deal::money($deal->amount)])->filter()->implode(' · ') ?: 'Дата и сумма не указаны' }}
                </span>
            </a>
        @empty
            <p class="text-ink-secondary">Сделок пока нет.</p>
        @endforelse

        <x-ui.button variant="secondary" icon="plus" :href="$createUrl">Новая сделка</x-ui.button>
    </div>
</x-ui.card>
