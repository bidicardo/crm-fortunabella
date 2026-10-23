{{-- Канбан контрагентов (задача 23). Колонки — этапы; блок канбана прокручивается вбок сам, страница —
     нет. Перенос — wire:sort Livewire 4: атрибут wire:sort должен идти раньше wire:sort:group
     (Livewire перестаёт разбирать атрибуты на wire:sort:group). На касание — удержание пальцем
     300 мс перед переносом, чтобы прокрутка не начинала перетаскивание. Без перетаскивания этап
     меняется списком на карточке. Видимые поля карточки — в браузере (localStorage); скрываются CSS-классом корня
     (hide-*), а не x-show: копия карточки при переносе лежит в body вне x-data, и Alpine спрятал бы в ней поля.
     Канбан ровно по высоте экрана (h-main, страница не прокручивается), каждая колонка прокручивается
     сама, заголовок колонки на месте.
     На компьютере колонки делят ширину поровну, уже min-w-64 — прокрутка блока вбок; телефон — w-72.
     Оформление — как у канбана сделок: колонки с оттенком цвета этапа и полосой сверху, список этапа на карточке —
     цвет этапа (tone, в тёмной теме приглушён --stage-mute).
     fallbackOnBody: копия переносимой карточки (на iPhone) кладётся в body, иначе её обрезает блок прокрутки. --}}
@php
    $initials = fn (string $name) => mb_strtoupper(collect(preg_split('/\s+/u', trim($name)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode(''));
@endphp

<div
    class="flex h-main min-w-0 flex-col gap-4"
    x-data="{
        fields: (() => {
            const defaults = { type: true, phone: true, contact: true };
            try { return { ...defaults, ...JSON.parse(localStorage.getItem('counterparties.board.fields') || '{}') }; } catch (e) { return defaults; }
        })(),
        open: false,
        save() { try { localStorage.setItem('counterparties.board.fields', JSON.stringify(this.fields)); } catch (e) {} },
        hidden() { return Object.keys(this.fields).filter(k => ! this.fields[k]).map(k => 'hide-' + k); },
    }"
    x-bind:class="hidden()"
>
    @if (session('status'))
        <x-ui.alert kind="success">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="flex flex-wrap items-center gap-3">
        @if (Route::has('counterparties.list'))
            <div class="inline-flex rounded-md border border-line p-0.5" role="group" aria-label="Вид">
                <span class="inline-flex min-h-10 items-center rounded-sm bg-accent-fill px-3 font-semibold text-on-accent" aria-current="page">Канбан</span>
                <a href="{{ route('counterparties.list') }}" class="focus-ring inline-flex min-h-10 items-center rounded-sm px-3 hover:bg-line/50">Список</a>
            </div>
        @endif

        <div>
            <x-ui.select wire:model.live="sort" aria-label="Порядок карточек">
                <option value="manual">Вручную</option>
                <option value="name">По названию</option>
                <option value="created">По дате создания</option>
            </x-ui.select>
        </div>

        <div class="relative" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false">
            <x-ui.button variant="secondary" x-ref="fieldsButton" aria-haspopup="true" x-bind:aria-expanded="open" x-on:click="open = !open">Поля карточки</x-ui.button>

            <x-ui.panel x-show="open" x-cloak x-anchor.bottom-start.offset.4="$refs.fieldsButton" class="z-10 w-56">
                @foreach (['type' => 'Тип', 'phone' => 'Телефон', 'contact' => 'Контактное лицо'] as $key => $label)
                    <div class="px-3">
                        <x-ui.checkbox id="field-{{ $key }}" :label="$label" x-model="fields.{{ $key }}" x-on:change="save()" />
                    </div>
                @endforeach
            </x-ui.panel>
        </div>

        <x-ui.button :href="route('counterparties.create')" icon="plus" class="sm:ml-auto">Новый контрагент</x-ui.button>
    </div>

    {{-- Колонки — в стиле канбана сделок; relative: абсолютные элементы внутри прокручиваемого блока не растягивают страницу на iPhone --}}
    <div class="relative -mx-4 flex min-h-0 flex-1 items-stretch gap-3 overflow-x-auto px-4 pb-2 md:mx-0 md:px-0">
        @foreach ($columns as $column)
            @php
                $stage = $column['stage'];
                $tone = 'var(--color-'.$stage->tone().')';
            @endphp
            {{-- Колонка — лёгкий оттенок цвета этапа (6 %), тонкая рамка того же тона и полоса этапа сверху --}}
            <section class="flex w-72 shrink-0 flex-col rounded-lg md:w-auto md:min-w-64 md:flex-1" aria-label="{{ $stage->label() }}" wire:key="column-{{ $stage->value }}"
                style="background-color: color-mix(in srgb, {{ $tone }} 6%, var(--color-surface)); box-shadow: inset 0 3px 0 {{ $tone }}, inset 0 0 0 1px color-mix(in srgb, {{ $tone }} 18%, var(--color-line));">
                <header class="flex items-center justify-between gap-2 px-3 pb-2 pt-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="truncate font-semibold">{{ $stage->label() }}</span>
                        <x-ui.badge :tone="$stage->tone()" aria-label="Карточек: {{ $column['cards']->count() }}">{{ $column['cards']->count() }}</x-ui.badge>
                    </div>
                    <a href="{{ route('counterparties.create', ['stage' => $stage->value]) }}" aria-label="Новый контрагент: {{ $stage->label() }}" title="Новый контрагент"
                        class="focus-ring inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md hover:bg-line/50">
                        <x-icon name="plus" class="h-5 w-5" />
                    </a>
                </header>

                <div class="flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto px-2 pb-2"
                    wire:sort="moveCard"
                    wire:sort:group="counterparties"
                    wire:sort:group-id="{{ $stage->value }}"
                    wire:sort:config="{ delay: 300, delayOnTouchOnly: true, fallbackOnBody: true }">
                    @foreach ($column['cards'] as $card)
                        @php($contact = $card->contacts->first())
                        <article wire:key="card-{{ $card->id }}" wire:sort:item="{{ $card->id }}"
                            class="cursor-grab space-y-3 rounded-lg border border-line bg-page p-3 shadow-sm transition-colors hover:border-line-accent active:cursor-grabbing">
                            <div class="space-y-0.5">
                                <a href="{{ route('counterparties.show', $card) }}" class="focus-ring block break-words rounded-sm font-semibold leading-snug hover:text-accent">{{ $card->name }}</a>
                                @if ($card->type)
                                    <p class="break-words text-caption text-ink-secondary [.hide-type_&]:hidden">{{ $card->type }}</p>
                                @endif
                            </div>

                            @if ($contact)
                                <div class="flex min-w-0 items-center gap-2 [.hide-contact_&]:hidden">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-line bg-surface text-caption font-semibold text-ink-secondary" aria-hidden="true">{{ $initials($contact->full_name) }}</span>
                                    <span class="min-w-0 truncate text-caption">{{ $contact->full_name }}</span>
                                </div>
                            @endif

                            @if ($card->phone)
                                <p class="text-caption tabular-nums text-ink-secondary [.hide-phone_&]:hidden">{{ $card->phone }}</p>
                            @endif

                            {{-- Низ карточки: смена этапа без перетаскивания (список не начинает перенос) --}}
                            <div wire:sort:ignore class="border-t border-line pt-2">
                                <x-ui.select id="stage-{{ $card->id }}" :tone="$card->stage->tone()" compact aria-label="Этап: {{ $card->name }}"
                                    wire:change="changeStage({{ $card->id }}, $event.target.value)">
                                    @foreach ($stages as $option)
                                        <option value="{{ $option->value }}" @selected($option === $card->stage)>{{ $option->label() }}</option>
                                    @endforeach
                                </x-ui.select>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
