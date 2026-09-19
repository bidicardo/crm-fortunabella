<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use BackedEnum;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ClientMergeService
{
    /** Поля клиента, которые пользователь выбирает при слиянии. */
    public const FIELDS = ['name', 'phone', 'email', 'social', 'legal_type', 'role', 'contact_time', 'notes'];

    /**
     * Связанные записи, которые переезжают от дубля к основному клиенту: классы моделей с колонкой client_id.
     * В Фазах 4, 6, 7 сюда добавляются сделки, задачи и документы (Deal::class, Task::class, Document::class);
     * перенос — один запрос UPDATE client_id на связь.
     *
     * @return list<class-string<Model>>
     */
    protected function relations(): array
    {
        return [];
    }

    /** Значение поля клиента в виде строки (enum → value), пустое → null. */
    public static function value(Client $client, string $field): ?string
    {
        $value = $client->{$field};
        $value = $value instanceof BackedEnum ? $value->value : $value;

        return filled($value) ? (string) $value : null;
    }

    /**
     * Сливает дубль в основного клиента. $fieldChoices: поле => 'main' | 'duplicate' (для notes ещё 'both').
     * Без выбора берётся значение основного, а если оно пустое — дубля; примечания объединяются.
     *
     * @param  array<string, string>  $fieldChoices
     *
     * @throws DomainException если слияние недопустимо
     */
    public function merge(Client $main, Client $duplicate, array $fieldChoices, User $by): void
    {
        DB::transaction(function () use ($main, $duplicate, $fieldChoices, $by) {
            if ($main->is($duplicate)) {
                throw new DomainException('Нельзя объединить клиента с самим собой.');
            }

            // Свежие данные под блокировкой: карточки могли измениться или быть влиты другим пользователем.
            $main = Client::lockForUpdate()->findOrFail($main->id);
            $duplicate = Client::lockForUpdate()->findOrFail($duplicate->id);

            if ($main->isArchived() || $duplicate->isArchived()) {
                throw new DomainException('Нельзя объединять архивную карточку или уже влитого клиента.');
            }

            $values = [];
            foreach (self::FIELDS as $field) {
                $values[$field] = $this->resolve($field, $fieldChoices[$field] ?? null, self::value($main, $field), self::value($duplicate, $field));
            }

            $main->fill($values)->save();

            foreach ($this->relations() as $model) {
                $model::where('client_id', $duplicate->id)->update(['client_id' => $main->id]);
            }

            // Данные дубля не трогаем: он архивируется и хранит, куда, кем и когда влит.
            $duplicate->forceFill([
                'archived_at' => now(),
                'merged_into_id' => $main->id,
                'merged_by' => $by->id,
                'merged_at' => now(),
            ])->save();

            // История: на основной карточке — «влит клиент X» (с выбранными значениями), на дубле — «влит в клиента Y».
            $main->logActivity('merged', [
                'merged_client' => ['id' => $duplicate->id, 'name' => $duplicate->name],
                'values' => $values,
            ], $by->id);
            $duplicate->logActivity('merged', [
                'merged_into' => ['id' => $main->id, 'name' => $main->name],
            ], $by->id);
        });
    }

    private function resolve(string $field, ?string $choice, ?string $main, ?string $duplicate): ?string
    {
        return match (true) {
            $choice === 'main' => $main,
            $choice === 'duplicate' => $duplicate,
            $field === 'notes' && $main !== null && $duplicate !== null && $main !== $duplicate => $main."\n\n".$duplicate,
            default => $main ?? $duplicate,
        };
    }
}
