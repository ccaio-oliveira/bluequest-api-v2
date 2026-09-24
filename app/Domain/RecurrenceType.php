<?php

namespace App\Domain;

enum RecurrenceType: string
{
    case Dates = 'dates';
    case Daily = 'daily';
    case Weekdays = 'weekdays';
}
