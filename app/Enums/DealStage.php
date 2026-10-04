<?php

namespace App\Enums;

/** Этап сделки; порядок cases() — порядок колонок канбана (docs/06-sales-pipeline.md). */
enum DealStage: string
{
    case New = 'new';
    case InWork = 'in_work';
    case Booked = 'booked';
    case Done = 'done';
    case Refused = 'refused';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новая заявка',
            self::InWork => 'В работе',
            self::Booked => 'Бронь',
            self::Done => 'Проведена',
            self::Refused => 'Отказ',
        };
    }

    /** Тон <x-ui.badge>: зелёный, синий, фиолетовый, оранжевый, красный (docs/design/01-design-system.md §1.3). */
    public function tone(): string
    {
        return match ($this) {
            self::New => 'stage-new',
            self::InWork => 'stage-inwork',
            self::Booked => 'stage-booked',
            self::Done => 'stage-done',
            self::Refused => 'stage-refused',
        };
    }
}
