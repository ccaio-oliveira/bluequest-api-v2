<?php

namespace App\Domain;

use Carbon\CarbonImmutable;

final class Recurrence
{
    /** @param int[] $weekdays 1 = domingo ... 7 = sábado */
    public function __construct(
        public readonly RecurrenceType $type,
        public readonly ?CarbonImmutable $date = null,
        public readonly array $weekdays = [],
    )
    {}

    public function occursOn(CarbonImmutable $date): bool
    {
        return match ($this->type) {
            RecurrenceType::Once => $this->date?->isSameDay($date) ?? false,
            RecurrenceType::Daily => true,
            RecurrenceType::Weekdays => in_array($date->dayOfWeek + 1, $this->weekdays, true),
        };
    }
}
