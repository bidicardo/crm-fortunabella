{{-- Канбан сделок (задача 28) по референсу docs/design/refs/11-kanban-deals-design.png: вверху мини-аналитика
     (4 плитки в ряд; на телефоне — компактные: короткие подписи и суммы), ниже 5 колонок по этапам с количеством и
     суммой, колонки слегка окрашены цветом этапа. Страница ровно по высоте экрана (h-main), колонки прокручиваются
     сами; на ПК — во всю ширину, на телефоне — блок листается вбок.
     Перенос — wire:sort (атрибут wire:sort раньше wire:sort:group; на касание — удержание 300 мс;
     fallbackOnBody — копия карточки в body, иначе её обрежет блок прокрутки) или список этапа на карточке
     (карточка встаёт в начало колонки).
     Видимые поля карточки — в браузере (localStorage). Скрываются CSS-классом корня (hide-*), а не x-show: копия
     карточки при переносе лежит в body вне x-data, и Alpine спрятал бы в ней все поля. --}}
@php
    $money = fn (?int $v) => App\Models\Deal::money($v);
    $initials = fn (string $name) => mb_strtoupper(collect(preg_split('/\s+/u', trim($name)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode(''));
    $unpaid = $analytics['unpaid']['count'];
    $tiles = [
        ['label' => 'В работе', 'short' => 'В работе', 'icon' => 'deals', 'tone' => 'stage-inwork',
            'value' => $analytics['open']['sum'], 'note' => App\Models\Deal::countLabel($analytics['open']['count'])],
        ['label' => 'Предстоящие брони', 'short' => 'Брони', 'icon' => 'calendar', 'tone' => 'stage-booked',
            'value' => $analytics['upcoming']['sum'],
            'note' => App\Models\Deal::countLabel($analytics['upcoming']['count']).($analytics['upcoming']['next'] ? ' · ближайшая '.$analytics['upcoming']['next']->format('d.m') : '')],
        ['label' => 'Проведено за месяц', 'short' => 'За месяц', 'icon' => 'success', 'tone' => 'stage-new',
            'value' => $analytics['done']['sum'], 'note' => App\Models\Deal::countLabel($analytics['done']['count'])],
    ];
@endphp

<div
    class="flex h-main min-w-0 flex-col gap-3 sm:gap-4"
    x-data="{
        fields: (() => {
            const defaults = { client: true, amount: true, date: true, source: true, prepayment: true, seller: true };
            try { return { ...defaults, ...JSON.parse(localStorage.getItem('deals.board.fields') || '{}') }; } catch (e) { return defaults; }
        })(),
        open: false,
        save() { try { localStorage.setItem('deals.board.fields', JSON.stringify(this.fields)); } catch (e) {} },
        hidden() { return Object.keys(this.fields).filter(k => ! this.fields[k]).map(k => 'hide-' + k); },
    }"
    x-bind:class="hidden()"
>
    @if (session('status'))
        <x-ui.alert kind="success">{{ session('status') }}</x-ui.alert>
    @endif

    {{-- Мини-аналитика: 4 плитки в ряд всегда. На телефоне — одна полоса без промежутков (плитки разделены линиями),
         короткие подписи и суммы мелким шрифтом, без иконок и пояснений; сумма не обрезается, при нехватке места —
         перенос. От sm — отдельные карточки. --}}
    <section aria-label="Сводка по сделкам" class="grid shrink-0 grid-cols-4 divide-x divide-line overflow-hidden rounded-lg border border-line sm:gap-3 sm:divide-x-0 sm:overflow-visible sm:rounded-none sm:border-0">
        @foreach ($tiles as $tile)
            <div class="min-w-0 bg-surface px-2 py-2 sm:rounded-lg sm:border sm:border-line sm:p-4">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-caption leading-tight text-ink-secondary max-sm:text-[0.6875rem]"><span class="sm:hidden">{{ $tile['short'] }}</span><span class="max-sm:hidden">{{ $tile['label'] }}</span></p>
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md max-sm:hidden"
                        style="color: var(--color-{{ $tile['tone'] }}); background-color: color-mix(in srgb, var(--color-{{ $tile['tone'] }}) 12%, var(--color-surface));">
                        <x-icon :name="$tile['icon']" class="h-4 w-4" />
                    </span>
                </div>
                <p class="mt-0.5 font-bold leading-tight tabular-nums max-sm:text-caption sm:mt-2 sm:truncate sm:text-h3"><span class="sm:hidden">{{ App\Models\Deal::moneyShort($tile['value']) }}</span><span class="max-sm:hidden">{{ $money($tile['value']) }}</span></p>
                <p class="mt-0.5 truncate text-caption text-ink-secondary max-sm:hidden">{{ $tile['note'] }}</p>
            </div>
        @endforeach

        {{-- «Предоплата не получена» — сигнал: если есть брони без оплаты, плитка подсвечена цветом ошибки --}}
        <div class="min-w-0 bg-surface px-2 py-2 sm:rounded-lg sm:border sm:border-line sm:p-4"
            @if ($unpaid > 0) style="background-color: color-mix(in srgb, var(--color-error) 7%, var(--color-surface)); border-color: color-mix(in srgb, var(--color-error) 35%, var(--color-line));" @endif>
            <div class="flex items-start justify-between gap-2">
                <p class="text-caption leading-tight text-ink-secondary max-sm:text-[0.6875rem]"><span class="sm:hidden">Без предоплаты</span><span class="max-sm:hidden">Предоплата не получена</span></p>
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md max-sm:hidden"
                    style="color: var(--color-{{ $unpaid > 0 ? 'error' : 'stage-done' }}); background-color: color-mix(in srgb, var(--color-{{ $unpaid > 0 ? 'error' : 'stage-done' }}) 12%, var(--color-surface));">
                    <x-icon name="warning" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-0.5 font-bold leading-tight tabular-nums max-sm:text-caption sm:mt-2 sm:truncate sm:text-h3" @if ($unpaid > 0) style="color: var(--color-error);" @endif><span class="sm:hidden">{{ $unpaid }}</span><span class="max-sm:hidden">{{ App\Models\Deal::countLabel($unpaid) }}</span></p>
            <p class="mt-0.5 truncate text-caption text-ink-secondary max-sm:hidden">{{ $unpaid > 0 ? 'на '.$money($analytics['unpaid']['sum']) : 'всё получено' }}</p>
        </div>
    </section>

    {{-- Панель над колонками; телефон — две строки --}}
    <div class="flex shrink-0 flex-wrap items-center gap-3">
        @if (Route::has('deals.list'))
            <div class="inline-flex shrink-0 rounded-md border border-line p-0.5" role="group" aria-label="Вид">
                <span class="inline-flex min-h-10 items-center rounded-sm bg-accent-fill px-3 font-semibold text-on-accent" aria-current="page">Канбан</span>
                <a href="{{ route('deals.list') }}" class="focus-ring inline-flex min-h-10 items-center rounded-sm px-3 hover:bg-line/50">Список</a>
            </div>
        @endif

        <div class="flex flex-auto items-center gap-3">
            <div class="min-w-0 flex-1 sm:flex-initial">
                <x-ui.select wire:model.live="sort" aria-label="Порядок карточек">
                    <option value="manual">Вручную</option>
                    <option value="event">По дате проведения</option>
                    <option value="amount">По сумме</option>
                    <option value="created">По дате создания</option>
                </x-ui.select>
            </div>

            <div class="relative shrink-0" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false">
                <x-ui.button variant="secondary" x-ref="fieldsButton" aria-haspopup="true" x-bind:aria-expanded="open" x-on:click="open = !open">Поля</x-ui.button>

                <x-ui.panel x-show="open" x-cloak x-anchor.bottom-start.offset.4="$refs.fieldsButton" class="z-20 w-56">
                    @foreach (['client' => 'Клиент', 'amount' => 'Сумма', 'date' => 'Дата и время', 'source' => 'Источник лида', 'prepayment' => 'Предоплата', 'seller' => 'Кто продал'] as $key => $label)
                        <div class="px-3">
                            <x-ui.checkbox id="field-{{ $key }}" :label="$label" x-model="fields.{{ $key }}" x-on:change="save()" />
                        </div>
                    @endforeach
                </x-ui.panel>
            </div>

            <x-ui.button :href="route('deals.create')" icon="plus" class="shrink-0 sm:ml-auto" aria-label="Новая сделка">
                <span class="sm:hidden">Новая</span><span class="hidden sm:inline">Новая сделка</span>
            </x-ui.button>
        </div>
    </div>

    {{-- Колонки; relative: абсолютные элементы внутри прокручиваемого блока не растягивают страницу на iPhone --}}
    <div class="relative -mx-4 flex min-h-0 flex-1 items-stretch gap-3 overflow-x-auto px-4 pb-2 md:mx-0 md:px-0">
        @foreach ($columns as $column)
            @php
                $stage = $column['stage'];
                $tone = 'var(--color-'.$stage->tone().')';
            @endphp
            {{-- Колонка — лёгкий оттенок цвета этапа (6 %), тонкая рамка того же тона и полоса этапа сверху --}}
            <section class="flex w-72 shrink-0 flex-col rounded-lg md:w-auto md:min-w-56 md:flex-1" aria-label="{{ $stage->label() }}" wire:key="column-{{ $stage->value }}"
                style="background-color: color-mix(in srgb, {{ $tone }} 6%, var(--color-surface)); box-shadow: inset 0 3px 0 {{ $tone }}, inset 0 0 0 1px color-mix(in srgb, {{ $tone }} 18%, var(--color-line));">
                <header class="flex items-start justify-between gap-2 px-3 pb-2 pt-3">
                    <div class="min-w-0 space-y-0.5">
                        <div class="flex items-center gap-2">
                            <span class="truncate font-semibold">{{ $stage->label() }}</span>
                            <x-ui.badge :tone="$stage->tone()" aria-label="Сделок: {{ $column['cards']->count() }}">{{ $column['cards']->count() }}</x-ui.badge>
                        </div>
                        <p class="text-caption tabular-nums text-ink-secondary">{{ $money($column['sum']) }}</p>
                    </div>
                    <a href="{{ route('deals.create', ['stage' => $stage->value]) }}" aria-label="Новая сделка: {{ $stage->label() }}" title="Новая сделка"
                        class="focus-ring inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md hover:bg-line/50">
                        <x-icon name="plus" class="h-5 w-5" />
                    </a>
                </header>

                <div class="flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto px-2 pb-2"
                    wire:sort="moveCard"
                    wire:sort:group="deals"
                    wire:sort:group-id="{{ $stage->value }}"
                    wire:sort:config="{ delay: 300, delayOnTouchOnly: true, fallbackOnBody: true }">
                    @foreach ($column['cards'] as $card)
                        @php
                            $prepayment = match (true) {
                                $card->prepayment_paid => ['Предоплата получена', 'success'],
                                $card->stage === App\Enums\DealStage::Booked => ['Предоплата не получена', 'error'],
                                $card->prepayment_amount !== null => ['Предоплата не получена', 'warning'],
                                default => null,
                            };
                            $source = $card->lead_source === App\Enums\LeadSource::Other && $card->lead_source_other ? $card->lead_source_other : $card->lead_source?->label();
                        @endphp
                        <article wire:key="card-{{ $card->id }}" wire:sort:item="{{ $card->id }}"
                            class="cursor-grab space-y-3 rounded-lg border border-line bg-page p-3 shadow-sm transition-colors hover:border-line-accent active:cursor-grabbing">
                            <div class="space-y-1">
                                <a href="{{ route('deals.show', $card) }}" class="focus-ring block break-words rounded-sm font-semibold leading-snug hover:text-accent">{{ $card->title }}</a>
                                @if ($card->amount !== null)
                                    <p class="text-body-md font-bold tabular-nums [.hide-amount_&]:hidden">{{ $money($card->amount) }}</p>
                                @endif
                            </div>

                            <div class="flex min-w-0 items-center gap-2 [.hide-client_&]:hidden">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-line bg-surface text-caption font-semibold text-ink-secondary" aria-hidden="true">{{ $initials($card->client->name) }}</span>
                                <span class="min-w-0 truncate text-caption">{{ $card->client->name }}</span>
                            </div>

                            @if ($card->event_date || $source || $prepayment)
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-caption text-ink-secondary">
                                    @if ($card->event_date)
                                        <span class="inline-flex items-center gap-1 tabular-nums [.hide-date_&]:hidden"><x-icon name="calendar" class="h-3.5 w-3.5 shrink-0" />{{ trim($card->event_date->format('d.m.Y').' '.$card->event_time) }}
                                            {{-- Занятая дата; wire:sort:ignore — нажатие на значок не начинает перенос --}}
                                            @if ($sameDay->has($card->id))
                                                <span wire:sort:ignore class="-my-1"><x-deal-date-conflict :deals="$sameDay[$card->id]" /></span>
                                            @endif
                                        </span>
                                    @endif
                                    @if ($source)
                                        <span class="[.hide-source_&]:hidden">{{ $source }}</span>
                                    @endif
                                    @if ($prepayment)
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 [.hide-prepayment_&]:hidden"
                                            style="color: var(--color-{{ $prepayment[1] }}); background-color: color-mix(in srgb, var(--color-{{ $prepayment[1] }}) 10%, var(--color-page));"><span class="h-1.5 w-1.5 rounded-full" style="background-color: var(--color-{{ $prepayment[1] }});" aria-hidden="true"></span>{{ $prepayment[0] }}</span>
                                    @endif
                                </div>
                            @endif

                            {{-- Низ карточки: смена этапа без перетаскивания (список не начинает перенос) и продавец --}}
                            <div class="flex items-center gap-2 border-t border-line pt-2">
                                <div wire:sort:ignore class="min-w-0 flex-1">
                                    <x-ui.select id="stage-{{ $card->id }}" :tone="$card->stage->tone()" compact aria-label="Этап: {{ $card->title }}"
                                        wire:change="changeStage({{ $card->id }}, $event.target.value)">
                                        @foreach ($stages as $option)
                                            <option value="{{ $option->value }}" @selected($option === $card->stage)>{{ $option->label() }}</option>
                                        @endforeach
                                    </x-ui.select>
                                </div>
                                @if ($card->soldBy)
                                    <span class="relative flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-accent-fill text-caption font-semibold text-on-accent [.hide-seller_&]:hidden"
                                        title="Продал: {{ $card->soldBy->name }}"><span aria-hidden="true">{{ $initials($card->soldBy->name) }}</span><span class="sr-only">Продал: {{ $card->soldBy->name }}</span></span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
