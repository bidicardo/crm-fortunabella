{{-- Канбан контрагентов (задача 23). Колонки — этапы; блок канбана прокручивается вбок сам, страница —
     нет. Перенос — wire:sort Livewire 4: атрибут wire:sort должен идти раньше wire:sort:group
     (Livewire перестаёт разбирать атрибуты на wire:sort:group). На касание — удержание пальцем
     300 мс перед переносом, чтобы прокрутка не начинала перетаскивание. Без перетаскивания этап
     меняется списком на карточке. Видимые поля карточки — в браузере (localStorage).
     Канбан и колонки растянуты до низа экрана: свайп вбок и бросок карточки работают по всей высоте.
     fallbackOnBody: копия переносимой карточки (на iPhone) кладётся в body, иначе её обрезает блок прокрутки. --}}
<div
    class="flex min-w-0 flex-1 flex-col gap-4"
    x-data="{
        fields: (() => {
            const defaults = { type: true, phone: true, contact: true };
            try { return { ...defaults, ...JSON.parse(localStorage.getItem('counterparties.board.fields') || '{}') }; } catch (e) { return defaults; }
        })(),
        open: false,
        save() { try { localStorage.setItem('counterparties.board.fields', JSON.stringify(this.fields)); } catch (e) {} },
    }"
>
    @if (session('status'))
        <x-ui.alert kind="success">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="flex flex-wrap items-center gap-3">
        @if (Route::has('counterparties.list'))
            <div class="inline-flex rounded-md border border-line p-0.5" role="group" aria-label="Вид">
                <span class="inline-flex min-h-10 items-center rounded-sm bg-accent-fill px-3 font-semibold text-white" aria-current="page">Канбан</span>
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

    {{-- relative: абсолютные элементы внутри прокручиваемого блока не растягивают страницу на iPhone --}}
    <div class="relative -mx-4 flex flex-1 items-stretch gap-4 overflow-x-auto px-4 pb-2 md:mx-0 md:px-0">
        @foreach ($columns as $column)
            @php($stage = $column['stage'])
            <section class="flex w-72 shrink-0 flex-col rounded-lg border border-line bg-surface" aria-label="{{ $stage->label() }}" wire:key="column-{{ $stage->value }}">
                <header class="flex items-center justify-between gap-2 border-b border-line px-3 py-2">
                    <div class="flex items-center gap-2">
                        <x-ui.badge :tone="$stage->tone()">{{ $stage->label() }}</x-ui.badge>
                        <span class="text-caption text-ink-secondary" aria-label="Карточек: {{ $column['cards']->count() }}">{{ $column['cards']->count() }}</span>
                    </div>
                    <a href="{{ route('counterparties.create', ['stage' => $stage->value]) }}" aria-label="Новый контрагент: {{ $stage->label() }}" title="Новый контрагент"
                        class="focus-ring inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md hover:bg-line/50">
                        <x-icon name="plus" class="h-5 w-5" />
                    </a>
                </header>

                <div class="flex min-h-24 flex-1 flex-col gap-2 p-2"
                    wire:sort="moveCard"
                    wire:sort:group="counterparties"
                    wire:sort:group-id="{{ $stage->value }}"
                    wire:sort:config="{ delay: 300, delayOnTouchOnly: true, fallbackOnBody: true }">
                    @foreach ($column['cards'] as $card)
                        <article wire:key="card-{{ $card->id }}" wire:sort:item="{{ $card->id }}"
                            class="cursor-grab space-y-1 rounded-md border border-line bg-page p-3 shadow-sm active:cursor-grabbing">
                            <a href="{{ route('counterparties.show', $card) }}" class="focus-ring link block break-words font-semibold">{{ $card->name }}</a>

                            @if ($card->type)
                                <p x-show="fields.type" class="break-words text-caption text-ink-secondary">{{ $card->type }}</p>
                            @endif
                            @if ($card->phone)
                                <p x-show="fields.phone" class="text-caption">{{ $card->phone }}</p>
                            @endif
                            @if ($contact = $card->contacts->first())
                                <p x-show="fields.contact" class="break-words text-caption">
                                    <x-icon name="user" class="inline h-3.5 w-3.5 text-ink-muted" /> {{ $contact->full_name }}
                                </p>
                            @endif

                            {{-- Смена этапа без перетаскивания; список не начинает перенос --}}
                            <div wire:sort:ignore class="pt-1">
                                <x-ui.select id="stage-{{ $card->id }}" aria-label="Этап: {{ $card->name }}"
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
