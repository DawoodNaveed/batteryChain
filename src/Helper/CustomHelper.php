<?php

namespace App\Helper;

/**
 * Class CustomHelper
 * @package App\Helper
 */
class CustomHelper
{
    // Bulk Import CSV Params
    /** constant for Csv extension */
    const CSV_TEXT = 'csv';

    /** constant for No Errors */
    const NO_ERROR = 0;

    /** constant for Error */
    const ERROR = 1;

    /** @var array */
    const CSV_HEADERS = [
        'serial_number',
        'battery_type',
        'nominal_voltage',
        'nominal_capacity',
        'nominal_energy',
        'cycle_life',
        'height',
        'width',
        'mass',
        'status'
    ];
}