<?php

namespace App\Service\base;

use IntlDateFormatter;


class DateHelper
{

    /**
     * It creates a date formatter, sets the locale to French, the timezone to Paris, the calendar to
     * Gregorian, and the format to the one passed as argument
     * https://unicode-org.github.io/icu/userguide/format_parse/datetime/#date-field-symbol-table
     * 
     * @param format The format of the date to return, M (9) , MM (09), MMM (fév.) or MMMM (février) for the month
     * 
     * @return The current date in the format "d/m/Y"
     */
    static function date($format = "dd/MMM/Y")
    {
        return datefmt_format(datefmt_create(
            "fr-FR",
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            'Europe/Paris',
            IntlDateFormatter::GREGORIAN,
            $format
        ), time());
    }
}
