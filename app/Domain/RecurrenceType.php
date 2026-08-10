<?php

namespace App\Domain;

enum RecurrenceType: string
{
    case Once = 'once';
    case Daily = 'daily';
    case Weekdays = 'weekdays';
}
