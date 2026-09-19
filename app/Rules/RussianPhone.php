<?php

namespace App\Rules;

use App\Services\PhoneNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RussianPhone implements ValidationRule
{
    // Пустое значение правило пропускает: телефон необязателен (Laravel и так не вызывает правила для пустых полей).
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! (new PhoneNormalizer)->isValid((string) $value)) {
            $fail('Введите российский номер: 11 цифр, например +7 917 123-45-67.');
        }
    }
}
