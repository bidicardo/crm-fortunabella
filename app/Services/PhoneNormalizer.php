<?php

namespace App\Services;

class PhoneNormalizer
{
    /** Российский номер из 11 цифр (первая 7 или 8) → +7XXXXXXXXXX, иначе null. */
    public function normalize(?string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $raw);

        if (strlen($digits) !== 11 || ! in_array($digits[0], ['7', '8'], true)) {
            return null;
        }

        return '+7'.substr($digits, 1);
    }

    public function isValid(?string $raw): bool
    {
        return $this->normalize($raw) !== null;
    }
}
