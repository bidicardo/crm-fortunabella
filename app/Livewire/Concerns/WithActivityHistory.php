<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

/**
 * Блок «История изменений» на карточке (лента — <x-activity-history>): сначала 3 последние записи,
 * по «Показать ещё» — до 10 с прокруткой внутри блока, «Свернуть» — снова 3.
 */
trait WithActivityHistory
{
    #[Locked]
    public bool $historyExpanded = false;

    public function showMoreHistory(): void
    {
        $this->historyExpanded = true;
    }

    public function collapseHistory(): void
    {
        $this->historyExpanded = false;
    }

    /** Данные для шаблона: logs и hasMoreHistory. $subject — модель с LogsActivity. */
    protected function historyData(Model $subject): array
    {
        $limit = $this->historyExpanded ? 10 : 3;

        // Берём на одну запись больше лимита, чтобы понять, нужна ли кнопка «Показать ещё».
        $logs = $subject->activityLogs()->with('user')->latest('id')->limit($limit + 1)->get();

        return [
            'logs' => $logs->take($limit),
            'hasMoreHistory' => ! $this->historyExpanded && $logs->count() > $limit,
        ];
    }
}
