<?php

namespace App\Enums;

/** Тип игрового стола; у сделки — множественный выбор (docs/10-deals.md). */
enum TableType: string
{
    case EuropeanRoulette = 'european_roulette';
    case Blackjack = 'blackjack';
    case RussianPoker = 'russian_poker';
    case Texas = 'texas';

    public function label(): string
    {
        return match ($this) {
            self::EuropeanRoulette => 'Европейская рулетка',
            self::Blackjack => 'Блэк-джек',
            self::RussianPoker => 'Русский покер',
            self::Texas => 'Техас',
        };
    }
}
