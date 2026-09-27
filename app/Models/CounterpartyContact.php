<?php

namespace App\Models;

use App\Rules\RussianPhone;
use App\Services\PhoneNormalizer;
use Database\Factories\CounterpartyContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Контактное лицо контрагента. Своей истории нет: добавление, правка и удаление пишутся
 * в историю контрагента событиями contact_added, contact_updated, contact_removed.
 */
#[Fillable(['full_name', 'position_title', 'phone', 'email', 'social', 'contact_time', 'notes'])]
class CounterpartyContact extends Model
{
    /** @use HasFactory<CounterpartyContactFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::created(fn (self $contact) => $contact->counterparty->logActivity('contact_added', ['contact' => $contact->full_name]));

        static::updated(function (self $contact) {
            $fields = [];

            foreach (array_intersect_key($contact->getChanges(), $contact->activityLabels()) as $field => $new) {
                $fields[$field] = ['old' => $contact->getRawOriginal($field), 'new' => $new];
            }

            if ($fields) {
                $contact->counterparty->logActivity('contact_updated', ['contact' => $contact->full_name, 'fields' => $fields]);
            }
        });

        // Каскадное удаление вместе с контрагентом идёт в БД и сюда не попадает: история уходит вместе с ним.
        static::deleted(fn (self $contact) => $contact->counterparty->logActivity('contact_removed', ['contact' => $contact->full_name]));
    }

    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value) => (new PhoneNormalizer)->normalize($value));
    }

    /** Правила проверки полей: общие для добавления и правки контактного лица в карточке. */
    public static function validationRules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'position_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', new RussianPhone],
            'email' => ['nullable', 'email', 'max:255'],
            'social' => ['nullable', 'string', 'max:255'],
            'contact_time' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class);
    }

    /** Подписи полей для истории контрагента; изменения других полей не логируются. */
    public function activityLabels(): array
    {
        return [
            'full_name' => 'ФИО',
            'position_title' => 'Должность',
            'phone' => 'Телефон',
            'email' => 'Email',
            'social' => 'Соцсеть / мессенджер',
            'contact_time' => 'Удобное время для связи',
            'notes' => 'Примечание',
        ];
    }
}
