<?php

namespace App\Livewire;

use App\Models\Client;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ClientShow extends Component
{
    // Сначала показываем 3 последние записи истории, по «Показать ещё» — до 10 (с прокруткой внутри блока).
    private const HISTORY_PREVIEW = 3;

    private const HISTORY_MAX = 10;

    public Client $client;

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

    public function render()
    {
        $this->client->loadMissing('mergedInto', 'mergedBy');

        $limit = $this->historyExpanded ? self::HISTORY_MAX : self::HISTORY_PREVIEW;

        // Берём на одну запись больше лимита, чтобы понять, нужна ли кнопка «Показать ещё».
        $logs = $this->client->activityLogs()->with('user')->latest('id')->limit($limit + 1)->get();

        return view('livewire.client-show', [
            'logs' => $logs->take($limit),
            'hasMoreHistory' => ! $this->historyExpanded && $logs->count() > $limit,
        ])->title('Клиент');
    }
}
