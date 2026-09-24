<?php

namespace App\Livewire;

use App\Enums\ClientLegalType;
use App\Models\Client;
use BackedEnum;
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

    /** Поле, которое сейчас правится прямо в карточке (null — только просмотр). Отдельной страницы правки нет. */
    #[Locked]
    public ?string $editing = null;

    /** Черновик значения правящегося поля. */
    public string $value = '';

    public function showMoreHistory(): void
    {
        $this->historyExpanded = true;
    }

    public function collapseHistory(): void
    {
        $this->historyExpanded = false;
    }

    public function edit(string $field): void
    {
        abort_if($this->client->isArchived(), 403);
        abort_unless(array_key_exists($field, Client::validationRules()), 404);

        // Щелчок по другому полю сначала сохраняет текущее; при ошибке проверки правка остаётся на нём.
        if ($this->editing !== null && $this->editing !== $field) {
            $this->save();
        }

        $current = $this->client->getAttribute($field);
        $this->value = (string) ($current instanceof BackedEnum ? $current->value : $current);
        $this->editing = $field;
        $this->resetValidation();
    }

    public function save(): void
    {
        abort_if($this->client->isArchived(), 403);

        if ($this->editing === null) {
            return;
        }

        $field = $this->editing;

        // Те же правила, что при создании; в сообщении — имя поля, как в форме.
        $this->validate(['value' => Client::validationRules()[$field]], [], ['value' => str_replace('_', ' ', $field)]);

        // Пустую строку храним как null; изменение попадает в историю (LogsActivity).
        $this->client->update([$field => $this->value === '' ? null : $this->value]);

        $this->cancel();
    }

    public function cancel(): void
    {
        $this->editing = null;
        $this->value = '';
        $this->resetValidation();
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
            'legalTypes' => ClientLegalType::cases(),
        ])->title('Клиент');
    }
}
