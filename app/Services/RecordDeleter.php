<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Counterparty;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Окончательное удаление клиента или контрагента (решение владельца: любой пользователь, после
 * подтверждения, без корзины). Вместе с записью удаляются её история, у клиента — влитые в него
 * архивные дубли (с их историей), у контрагента — контактные лица (каскад в БД).
 */
class RecordDeleter
{
    /** Сколько сделок у записи: пока сделки есть, удалять нельзя. */
    public function dealsCount(Client|Counterparty $record): int
    {
        return $record->deals()->count();
    }

    /** id влитых в клиента архивных дублей, включая влитых в них раньше (цепочка слияний). */
    public function duplicateIds(Client $client): array
    {
        $ids = [];
        $level = [$client->id];

        while ($level = Client::whereIn('merged_into_id', $level)->pluck('id')->diff($ids)->all()) {
            array_push($ids, ...$level);
        }

        return $ids;
    }

    /** @throws DomainException если у записи есть сделки — тогда ничего не удаляется */
    public function delete(Client|Counterparty $record): void
    {
        DB::transaction(function () use ($record) {
            $deals = $this->dealsCount($record);

            if ($deals > 0) {
                throw new DomainException("Сначала удалите или перенесите сделки: $deals.");
            }

            $ids = [$record->getKey(), ...($record instanceof Client ? $this->duplicateIds($record) : [])];

            ActivityLog::where('subject_type', $record->getMorphClass())->whereIn('subject_id', $ids)->delete();

            if ($record instanceof Client) {
                // Сначала рвём ссылки дублей друг на друга, чтобы порядок удаления строк не мешал внешнему ключу.
                Client::whereKey($ids)->update(['merged_into_id' => null]);
                Client::whereKey($ids)->delete();
            } else {
                $record->delete();
            }
        });
    }
}
