<?php

namespace App\Livewire\Concerns;

use App\Models\Client;
use App\Models\Counterparty;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Общее для создания сделки (DealForm) и её карточки (DealShow): поиск клиента и контрагента,
 * пользователи для «Кто продал» / «Держатель предоплаты», выбор длительности «1–10 ч или Другое».
 */
trait EditsDealParties
{
    public string $clientSearch = '';

    public string $counterpartySearch = '';

    /** Список длительности: '' (не указана), '1'…'10' или 'other' — тогда число вводится в поле. */
    public string $durationChoice = '';

    /** Найденные клиенты (без архивных) — до 8, пока в поиске меньше 2 символов — пусто. */
    protected function clientOptions(): Collection
    {
        $term = trim($this->clientSearch);

        return mb_strlen($term) < 2 ? collect() : Client::active()->search($term)->orderBy('name')->limit(8)->get();
    }

    protected function counterpartyOptions(): Collection
    {
        $term = trim($this->counterpartySearch);

        return mb_strlen($term) < 2 ? collect() : Counterparty::search($term)->orderBy('name')->limit(8)->get();
    }

    /** Пользователи для выбора: без заблокированных. */
    protected function userOptions(): Collection
    {
        return User::whereNull('blocked_at')->orderBy('name')->get(['id', 'name']);
    }

    /** Значение списка длительности для сохранённого числа часов. */
    protected static function durationChoiceFor(?int $hours): string
    {
        return match (true) {
            $hours === null => '',
            $hours <= 10 => (string) $hours,
            default => 'other',
        };
    }

    protected function partyData(): array
    {
        return [
            'clientOptions' => $this->clientOptions(),
            'counterpartyOptions' => $this->counterpartyOptions(),
            'users' => $this->userOptions(),
        ];
    }
}
