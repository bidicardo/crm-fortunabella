<?php

namespace App\Models;

use App\Enums\ClientLegalType;
use App\Services\PhoneNormalizer;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// archived_at намеренно не в fillable: архивирует только слияние (задача 18).
#[Fillable(['name', 'phone', 'email', 'social', 'legal_type', 'role', 'contact_time', 'notes'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'legal_type' => ClientLegalType::class,
            'archived_at' => 'datetime',
        ];
    }

    // Телефон всегда хранится нормализованным; невалидное значение при прямом присвоении даёт null
    // (отклоняет его на уровне формы правило RussianPhone).
    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value) => (new PhoneNormalizer)->normalize($value));
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }
}
