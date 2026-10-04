<?php

namespace App\Enums;

/** Источник лида сделки; «Другое» — со своим вариантом в lead_source_other. Заявки с лендинга — «Сайт». */
enum LeadSource: string
{
    case Avito = 'avito';
    case Site = 'site';
    case WordOfMouth = 'word_of_mouth';
    case Counterparty = 'counterparty';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Avito => 'Авито',
            self::Site => 'Сайт',
            self::WordOfMouth => 'Сарафан',
            self::Counterparty => 'Контрагент',
            self::Other => 'Другое',
        };
    }
}
