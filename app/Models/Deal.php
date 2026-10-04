<?php

namespace App\Models;

use App\Enums\DealStage;
use App\Enums\LeadSource;
use App\Enums\TableType;
use App\Models\Concerns\LogsActivity;
use Database\Factories\DealFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** Сделка = мероприятие (docs/10-deals.md). stage и position не в fillable: их меняет только moveTo(). */
#[Fillable(['title', 'client_id', 'counterparty_id', 'amount', 'event_date', 'event_time', 'table_types', 'duration_hours', 'event_kind', 'guests', 'address', 'lead_source', 'lead_source_other', 'prepayment_amount', 'prepayment_paid', 'prepayment_holder_id', 'sold_by_id', 'notes'])]
class Deal extends Model
{
    /** @use HasFactory<DealFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'stage' => 'new',
        'position' => 0,
        'prepayment_paid' => false,
    ];

    protected static function booted(): void
    {
        // Новая карточка встаёт в конец колонки своего этапа (если порядок не задан явно).
        static::creating(function (self $deal) {
            if (! $deal->isDirty('position')) {
                $max = static::stage($deal->stage)->max('position');
                $deal->position = $max === null ? 0 : $max + 1;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'stage' => DealStage::class,
            'lead_source' => LeadSource::class,
            'table_types' => 'array',
            'event_date' => 'date',
            'amount' => 'integer',
            'prepayment_amount' => 'integer',
            'duration_hours' => 'integer',
            'guests' => 'integer',
            'prepayment_paid' => 'boolean',
        ];
    }

    /** Правила проверки полей: общие для формы создания и правки поля в карточке. Обязательны название и клиент. */
    public static function validationRules(): array
    {
        // Пользователи для «Кто продал» и «Держатель предоплаты» — только незаблокированные.
        $activeUser = Rule::exists('users', 'id')->whereNull('blocked_at');
        $money = ['nullable', 'integer', 'min:0', 'max:4294967295'];

        return [
            'title' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->whereNull('archived_at')],
            'counterparty_id' => ['nullable', 'integer', Rule::exists('counterparties', 'id')],
            'amount' => $money,
            'event_date' => ['nullable', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'table_types' => ['nullable', 'array'],
            'table_types.*' => [Rule::enum(TableType::class)],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:12'],
            'event_kind' => ['nullable', 'string'],
            'guests' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'address' => ['nullable', 'string', 'max:255'],
            'lead_source' => ['nullable', Rule::enum(LeadSource::class)],
            'lead_source_other' => ['nullable', 'string', 'max:255'],
            'prepayment_amount' => $money,
            'prepayment_paid' => ['boolean'],
            'prepayment_holder_id' => ['nullable', 'integer', $activeUser],
            'sold_by_id' => ['nullable', 'integer', $activeUser],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class);
    }

    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by_id');
    }

    public function prepaymentHolder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepayment_holder_id');
    }

    /** Смена этапа (из любого в любой); без позиции — в конец колонки. */
    public function moveTo(DealStage $stage, ?int $position = null): void
    {
        $this->forceFill([
            'stage' => $stage,
            'position' => $position ?? (int) static::stage($stage)->whereKeyNot($this->getKey())->max('position') + 1,
        ])->save();
    }

    public function scopeStage(Builder $query, DealStage $stage): void
    {
        $query->where('stage', $stage);
    }

    /** Целые рубли с разрядами: 1250000 → «1 250 000 ₽». */
    public static function money(?int $value): ?string
    {
        return $value === null ? null : number_format($value, 0, ',', ' ').' ₽';
    }

    public function activityLabels(): array
    {
        return [
            'title' => 'Название',
            'client_id' => 'Клиент',
            'counterparty_id' => 'Контрагент',
            'amount' => 'Сумма',
            'event_date' => 'Дата проведения',
            'event_time' => 'Время проведения',
            'table_types' => 'Типы столов',
            'duration_hours' => 'Длительность',
            'event_kind' => 'Характер мероприятия',
            'guests' => 'Количество гостей',
            'address' => 'Адрес проведения',
            'lead_source' => 'Источник лида',
            'lead_source_other' => 'Источник лида (свой вариант)',
            'prepayment_amount' => 'Предоплата',
            'prepayment_paid' => 'Предоплата оплачена',
            'prepayment_holder_id' => 'Держатель предоплаты',
            'sold_by_id' => 'Кто продал',
            'notes' => 'Примечания',
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
            'stage' => DealStage::tryFrom($value)?->label() ?? $value,
            'lead_source' => LeadSource::tryFrom($value)?->label() ?? $value,
            'table_types' => collect(is_array($value) ? $value : json_decode($value, true) ?? [])
                ->map(fn ($type) => TableType::tryFrom($type)?->label() ?? $type)->implode(', ') ?: null,
            'amount', 'prepayment_amount' => self::money((int) $value),
            'duration_hours' => $value.' ч',
            'event_date' => Carbon::parse($value)->format('d.m.Y'),
            'event_time' => substr((string) $value, 0, 5),
            'prepayment_paid' => $value ? 'да' : 'нет',
            'client_id' => self::nameOf(Client::class, $value),
            'counterparty_id' => self::nameOf(Counterparty::class, $value),
            'sold_by_id', 'prepayment_holder_id' => self::nameOf(User::class, $value),
            default => (string) $value,
        };
    }

    /**
     * Имя связанной записи для ленты истории; удалённая — «№ id».
     * ponytail: запрос на каждое значение (once() — кеш по аргументам на время запроса) — хватает для
     * ленты в 10 записей; при росте — хранить имена в changes в момент записи.
     */
    private static function nameOf(string $model, mixed $id): string
    {
        return once(fn () => $model::query()->whereKey($id)->value('name') ?? '№ '.$id);
    }
}
