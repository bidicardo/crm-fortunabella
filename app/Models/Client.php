<?php

namespace App\Models;

use App\Enums\ClientLegalType;
use App\Models\Concerns\LogsActivity;
use App\Rules\RussianPhone;
use App\Services\PhoneNormalizer;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

// archived_at и merged_* намеренно не в fillable: их выставляет только слияние (ClientMergeService).
#[Fillable(['name', 'phone', 'email', 'social', 'legal_type', 'role', 'contact_time', 'notes'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'legal_type' => ClientLegalType::class,
            'archived_at' => 'datetime',
            'merged_at' => 'datetime',
        ];
    }

    // Телефон всегда хранится нормализованным; невалидное значение при прямом присвоении даёт null
    // (отклоняет его на уровне формы правило RussianPhone).
    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value) => (new PhoneNormalizer)->normalize($value));
    }

    /** Правила проверки полей: общие для формы создания и правки поля в карточке. */
    public static function validationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', new RussianPhone],
            'email' => ['nullable', 'email', 'max:255'],
            'social' => ['nullable', 'string', 'max:255'],
            'legal_type' => ['nullable', Rule::enum(ClientLegalType::class)],
            'role' => ['nullable', 'string', 'max:255'],
            'contact_time' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /** Подстрока без учёта регистра (в MySQL — за счёт collation); %, _ и ! из запроса экранируются. */
    public function scopeSearch(Builder $query, string $term): void
    {
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        $query->where(function (Builder $q) use ($like, $term) {
            foreach (['name', 'email', 'social', 'role'] as $column) {
                $q->orWhereRaw("$column like ? escape '!'", [$like]);
            }

            // Запрос из одних цифр и знаков номера ищем и по нормализованному телефону: 8 → 7.
            if (preg_match('/^[\d\s+()\-]+$/', $term)) {
                $digits = preg_replace('/\D/', '', $term);
                $digits = str_starts_with($digits, '8') ? '7'.substr($digits, 1) : $digits;

                if ($digits !== '') {
                    $q->orWhere('phone', 'like', "%$digits%");
                }
            }
        });
    }

    public function activityLabels(): array
    {
        return [
            'name' => 'Имя',
            'phone' => 'Телефон',
            'email' => 'Email',
            'social' => 'Соцсеть / мессенджер',
            'legal_type' => 'Тип клиента',
            'role' => 'Роль',
            'contact_time' => 'Удобное время для связи',
            'notes' => 'Примечания',
        ];
    }

    // Архивация и слияние описываются событием merged, отдельными записями updated их не дублируем.
    public function activityIgnored(): array
    {
        return ['archived_at', 'merged_into_id', 'merged_by', 'merged_at'];
    }

    public function activityValue(string $field, mixed $value): ?string
    {
        if ($field === 'legal_type' && filled($value)) {
            return ClientLegalType::tryFrom($value)?->label() ?? $value;
        }

        return filled($value) ? (string) $value : null;
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /** Клиент, в которого влит этот дубль (история объединения). */
    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }

    public function mergedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merged_by');
    }
}
