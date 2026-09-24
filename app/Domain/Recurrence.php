<?php

namespace App\Domain;

use Carbon\CarbonImmutable;

final class Recurrence
{
    /**
     * @param string[] $dates datas no formato Y-m-d
     * @param int[] $weekdays 1 = domingo ... 7 = sábado
     * */
    public function __construct(
        public readonly RecurrenceType $type,
        public readonly array $dates = [],
        public readonly array $weekdays = [],
    )
    {}

    public function occursOn(CarbonImmutable $date): bool
    {
        return match ($this->type) {
            RecurrenceType::Dates => in_array($date->toDateString(), $this->dates, true),
            RecurrenceType::Daily => true,
            RecurrenceType::Weekdays => in_array($date->dayOfWeek + 1, $this->weekdays, true),
        };
    }
}
