<?php

namespace App\Enums;

enum ClientLegalType: string
{
    case Individual = 'individual';
    case Organization = 'organization';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Физическое лицо',
            self::Organization => 'Организация',
        };
    }
}
