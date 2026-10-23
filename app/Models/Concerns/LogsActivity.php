<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * История изменений модели: created и updated (только реально изменившиеся поля) пишутся автоматически.
 * Модель может переопределить activityLabels(), activityIgnored() и activityValue().
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn ($model) => $model->logActivity('created'));

        static::updated(function ($model) {
            $excluded = $model->activityExcluded();
            $changes = [];

            // В событии updated getChanges() — новые значения, getRawOriginal() — ещё старые.
            foreach ($model->getChanges() as $field => $new) {
                if (! in_array($field, $excluded, true)) {
                    $changes[$field] = $model->activityChange($field, $model->getRawOriginal($field), $new);
                }
            }

            if ($changes) {
                $model->logActivity('updated', $changes);
            }
        });
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    /** Записывает событие; автор — текущий пользователь, если он есть (или переданный явно). */
    public function logActivity(string $event, ?array $changes = null, ?int $userId = null): ActivityLog
    {
        return $this->activityLogs()->create([
            'user_id' => $userId ?? auth()->id(),
            'event' => $event,
            'changes' => $changes,
        ]);
    }

    /** Поля, которые в историю не попадают: служебные и скрытые (пароли, токены). */
    public function activityExcluded(): array
    {
        return array_merge(['created_at', 'updated_at', 'remember_token'], $this->getHidden(), $this->activityIgnored());
    }

    /** Дополнительные поля, изменения которых не логируются (у клиента — поля слияния, для них есть событие merged). */
    public function activityIgnored(): array
    {
        return [];
    }

    /** Человекочитаемые названия полей: поле => подпись. */
    public function activityLabels(): array
    {
        return [];
    }

    public function activityLabel(string $field): string
    {
        return $this->activityLabels()[$field] ?? $field;
    }

    /**
     * Запись изменения поля: old и new — сырые значения. Модель может добавить подписи на момент записи
     * (old_label, new_label) — например, имена связанных записей, которые потом могут быть удалены.
     */
    public function activityChange(string $field, mixed $old, mixed $new): array
    {
        return ['old' => $old, 'new' => $new];
    }

    /** Ссылка для значения в ленте (например, на карточку клиента); нет — null. */
    public function activityLink(string $field, mixed $value): ?string
    {
        return null;
    }

    /** Значение для показа в ленте (enum → русская подпись и т. п.); пустое → null. */
    public function activityValue(string $field, mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }
}
