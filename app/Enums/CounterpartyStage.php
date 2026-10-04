<?php

namespace App\Enums;

/** Этап воронки контрагентов; порядок cases() — порядок колонок канбана. */
enum CounterpartyStage: string
{
    case FirstContact = 'first_contact';
    case Pushing = 'pushing';
    case Cooperating = 'cooperating';
    case Refused = 'refused';

    public function label(): string
    {
        return match ($this) {
            self::FirstContact => 'Первый контакт',
            self::Pushing => 'Дожим',
            self::Cooperating => 'Сотрудничаем',
            self::Refused => 'Отказ',
        };
    }

    /** Тон <x-ui.badge>: синий, фиолетовый, зелёный, красный (docs/design/01-design-system.md §1.3). */
    public function tone(): string
    {
        return match ($this) {
            self::FirstContact => 'stage-inwork',
            self::Pushing => 'stage-booked',
            self::Cooperating => 'stage-new',
            self::Refused => 'stage-refused',
        };
    }
}
