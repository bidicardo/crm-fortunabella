<?php

namespace App\Models;

use App\Enums\CounterpartyStage;
use App\Models\Concerns\LogsActivity;
use App\Rules\RussianPhone;
use App\Services\PhoneNormalizer;
use Database\Factories\CounterpartyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

// stage и position намеренно не в fillable: их меняет только moveTo() (смена этапа, канбан).
#[Fillable(['name', 'type', 'phone', 'email', 'social', 'cooperation_terms', 'cooperation_started_at', 'website', 'telegram', 'address'])]
class Counterparty extends Model
{
    /** @use HasFactory<CounterpartyFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'stage' => 'first_contact',
        'position' => 0,
    ];

    protected static function booted(): void
    {
        // Новая карточка встаёт в конец колонки своего этапа (если порядок не задан явно).
        static::creating(function (self $counterparty) {
            if (! $counterparty->isDirty('position')) {
                $max = static::stage($counterparty->stage)->max('position');
                $counterparty->position = $max === null ? 0 : $max + 1;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'stage' => CounterpartyStage::class,
            'cooperation_started_at' => 'date',
        ];
    }

    // Телефон хранится нормализованным, как у клиента.
    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value) => (new PhoneNormalizer)->normalize($value));
    }

    /** Правила проверки полей: общие для формы создания и правки поля в карточке. */
    public static function validationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', new RussianPhone],
            'email' => ['nullable', 'email', 'max:255'],
            'social' => ['nullable', 'string', 'max:255'],
            'cooperation_terms' => ['nullable', 'string'],
            'cooperation_started_at' => ['nullable', 'date'],
            'website' => ['nullable', 'string', 'max:255'],
            'telegram' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CounterpartyContact::class)->orderBy('full_name');
    }

    /** Смена этапа (из любого в любой); без позиции — в конец колонки. */
    public function moveTo(CounterpartyStage $stage, ?int $position = null): void
    {
        $this->forceFill([
            'stage' => $stage,
            'position' => $position ?? (int) static::stage($stage)->whereKeyNot($this->getKey())->max('position') + 1,
        ])->save();
    }

    public function scopeStage(Builder $query, CounterpartyStage $stage): void
    {
        $query->where('stage', $stage);
    }

    /**
     * Подстрока без учёта регистра по названию, типу, email, соцсети, телефону и по контактным лицам
     * (ФИО, телефон, email); %, _ и ! из запроса экранируются.
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
        $digits = '';

        // Запрос из одних цифр и знаков номера ищем и по нормализованному телефону: 8 → 7.
        if (preg_match('/^[\d\s+()\-]+$/', $term)) {
            $digits = preg_replace('/\D/', '', $term);
            $digits = str_starts_with($digits, '8') ? '7'.substr($digits, 1) : $digits;
        }

        $match = function (Builder $q, array $columns) use ($like, $digits) {
            foreach ($columns as $column) {
                $q->orWhereRaw("$column like ? escape '!'", [$like]);
            }

            if ($digits !== '') {
                $q->orWhere('phone', 'like', "%$digits%");
            }
        };

        $query->where(function (Builder $q) use ($match) {
            $match($q, ['name', 'type', 'email', 'social']);
            $q->orWhereHas('contacts', fn (Builder $c) => $c->where(fn (Builder $c) => $match($c, ['full_name', 'email'])));
        });
    }

    public function activityLabels(): array
    {
        return [
            'name' => 'Название',
            'type' => 'Тип',
            'phone' => 'Телефон',
            'email' => 'Email',
            'social' => 'Соцсеть / мессенджер',
            'cooperation_terms' => 'Условия сотрудничества',
            'cooperation_started_at' => 'Дата начала сотрудничества',
            'website' => 'Сайт',
            'telegram' => 'Telegram-канал или группа',
            'address' => 'Адрес',
            'stage' => 'Этап',
        ];
    }

    // Порядок карточки в колонке канбана — служебный, в историю не пишется.
    public function activityIgnored(): array
    {
        return ['position'];
    }

    public function activityValue(string $field, mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return match ($field) {
            'stage' => CounterpartyStage::tryFrom($value)?->label() ?? $value,
            'cooperation_started_at' => Carbon::parse($value)->format('d.m.Y'),
            default => (string) $value,
        };
    }
}
