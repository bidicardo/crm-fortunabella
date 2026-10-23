<?php

namespace App\Livewire\Concerns;

use App\Services\RecordDeleter;
use DomainException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Массовое удаление из списка (клиенты, контрагенты, сделки): флажки строк ($selected — id), «выбрать всё на
 * странице», «Удалить выбранные» после подтверждения (wire:confirm). Удаление — как одиночное (RecordDeleter:
 * сразу окончательно, с историей); записи со сделками пропускаются, их названия — в сообщении (решение владельца).
 */
trait DeletesSelected
{
    /** @var list<string> id отмеченных строк — строками, как их отдают флажки (wire:model). */
    public array $selected = [];

    /** Итог последнего удаления — показывается над таблицей. */
    public string $deletedNotice = '';

    /** Какие записи этого списка можно удалять (например, клиенты — только не архивные). */
    abstract protected function deletableQuery(): Builder;

    /** Отметить все строки страницы или, если все уже отмечены, снять с них отметку. */
    public function togglePage(array $ids): void
    {
        $ids = array_values(array_unique(array_map(fn ($id) => (string) (int) $id, $ids)));
        $selected = array_map('strval', $this->selected);

        $this->selected = array_diff($ids, $selected) === []
            ? array_values(array_diff($selected, $ids))
            : array_values(array_unique([...$selected, ...$ids]));
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function deleteSelected(RecordDeleter $deleter): void
    {
        $deleted = 0;
        $skipped = [];

        foreach ($this->deletableQuery()->whereKey(array_map('intval', $this->selected))->get() as $record) {
            try {
                $deleter->delete($record);
                $deleted++;
            } catch (DomainException) {
                $skipped[] = $record->name ?? $record->title;
            }
        }

        $this->selected = [];
        $this->deletedNotice = 'Удалено: '.$deleted.'.'.($skipped ? ' Не удалены (есть сделки): '.implode(', ', $skipped).'.' : '');
        $this->resetPage();
    }
}
