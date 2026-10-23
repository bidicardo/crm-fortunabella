@props([
    // Другие сделки в тот же день (Deal::sameDayOthers / Deal::othersOn), с клиентом.
    'deals',
])

{{-- Занятая дата (задача 29, docs/05): маленький жёлтый треугольник в строке даты; подсказка со сделками этого
     дня. Сохранение не блокирует.
     ПК: наведение мышью показывает подсказку; она держится, пока курсор над треугольником или над подсказкой
     (задержка 200 мс на переход между ними — можно нажать ссылку); щелчок по треугольнику закрепляет подсказку
     до щелчка вне её, повторного щелчка или Esc. Телефон: нажатие открывает и закрепляет (наведение — только
     для мыши, pointerType, иначе касание открыло бы наведением и тут же закрыло щелчком).
     Подсказка переносится в body (x-teleport) и позиционируется x-anchor: иначе её обрезала бы прокручиваемая
     колонка канбана или таблица. --}}
@if ($deals->isNotEmpty())
    <span class="inline-flex"
        x-data="{
            hover: false,
            pinned: false,
            timer: null,
            get open() { return this.hover || this.pinned },
            enter(e) { if (e.pointerType !== 'mouse') return; clearTimeout(this.timer); this.hover = true },
            leave(e) { if (e.pointerType !== 'mouse') return; this.timer = setTimeout(() => this.hover = false, 200) },
            toggle() { this.pinned = ! this.pinned; if (! this.pinned) this.hover = false },
            close() { this.pinned = false; this.hover = false },
            outside(e) { if (! this.$refs.panel.contains(e.target)) this.close() },
        }"
        x-on:keydown.escape.window="close()">
        <button type="button" x-ref="trigger" x-on:click.stop.prevent="toggle()" x-on:click.outside="outside($event)"
            x-on:pointerenter="enter($event)" x-on:pointerleave="leave($event)"
            class="focus-ring inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-sm hover:bg-line/50" style="color: var(--color-warning);"
            aria-label="Дата занята: {{ App\Models\Deal::countLabel($deals->count()) }} в этот день" title="Дата занята" x-bind:aria-expanded="open">
            <x-icon name="warning" class="h-4 w-4" />
        </button>

        <template x-teleport="body">
            <div x-ref="panel" x-show="open" x-cloak x-anchor.bottom-start.offset.4="$refs.trigger" role="tooltip"
                x-on:pointerenter="enter($event)" x-on:pointerleave="leave($event)"
                class="z-50 w-72 max-w-[calc(100vw-2rem)] space-y-2 rounded-lg border border-line bg-surface p-3 text-caption text-ink shadow-panel">
                <p class="font-semibold">В этот день есть {{ $deals->count() === 1 ? 'другая сделка' : 'другие сделки' }}:</p>
                <ul class="space-y-1.5">
                    @foreach ($deals as $other)
                        <li>
                            <a href="{{ route('deals.show', $other) }}" class="focus-ring link break-words font-semibold">{{ $other->title }}</a>
                            <span class="text-ink-secondary">· {{ collect([$other->client?->name, $other->event_time])->filter()->implode(' · ') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </template>
    </span>
@endif
