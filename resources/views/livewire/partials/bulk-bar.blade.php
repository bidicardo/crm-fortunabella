{{-- Массовое удаление в списке (трейт DeletesSelected): итог последнего удаления и панель выбранных.
     Параметры: $deletedNotice, $selected, $confirm (текст подтверждения). --}}
@if ($deletedNotice)
    <x-ui.alert :kind="str_contains($deletedNotice, 'Не удалены') ? 'warning' : 'success'" class="shrink-0">{{ $deletedNotice }}</x-ui.alert>
@endif

@if ($selected)
    <div class="flex shrink-0 flex-wrap items-center gap-x-3 gap-y-2 rounded-lg border border-line bg-surface px-3 py-2" role="region" aria-label="Выбранные записи">
        <span class="font-semibold">Выбрано: {{ count($selected) }}</span>
        <x-ui.button variant="text" wire:click="clearSelection">Снять выбор</x-ui.button>
        <x-ui.button variant="danger" icon="delete" class="ml-auto" wire:click="deleteSelected"
            wire:confirm="{{ $confirm }} ({{ count($selected) }}) — окончательно, вместе с историей изменений? Записи со сделками будут пропущены. Восстановить будет нельзя.">Удалить выбранные</x-ui.button>
    </div>
@endif
