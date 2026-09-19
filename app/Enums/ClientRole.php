<?php

namespace App\Enums;

enum ClientRole: string
{
    case PrivateCustomer = 'private_customer';
    case Bride = 'bride';
    case Organizer = 'organizer';
    case Administrator = 'administrator';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PrivateCustomer => 'Частный заказчик',
            self::Bride => 'Невеста',
            self::Organizer => 'Организатор',
            self::Administrator => 'Администратор',
            self::Other => 'Другое',
        };
    }
}
