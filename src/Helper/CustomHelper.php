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
        'cell_type',
        'module_type',
        'tray_number',
        'production_date',
        'nominal_voltage',
        'nominal_capacity',
        'nominal_energy',
        'acid_volume',
        'CO2',
        'cycle_life',
        'height',
        'width',
        'length',
        'mass'
    ];

    /** @var array */
    const RECYCLER_CSV_HEADERS = [
        'email',
        'name',
        'contact',
        'address',
        'city',
        'updated_email'
    ];

    /** @var array */
    const DELIVERY_CSV_HEADERS = [
        'serial_number'
    ];

    /** @var array */
    const RETURN_CSV_HEADERS = [
        'serial_number'
    ];

    const BATTERY_STATUS_REGISTERED = 'registered';
    const BATTERY_STATUS_DELIVERED = 'delivered';
    const BATTERY_STATUS_BLOCKCHAIN_SECURED = 'blockchain-secured';
    const BATTERY_STATUS_SHIPPED = 'shipped';
    const BATTERY_STATUS_RETURNED = 'returned';
    const BATTERY_STATUS_RECYCLED = 'recycled';

    const BATTERY_STATUSES = [
        self::BATTERY_STATUS_REGISTERED => 0,
        self::BATTERY_STATUS_DELIVERED => 1,
        self::BATTERY_STATUS_BLOCKCHAIN_SECURED => 2,
        self::BATTERY_STATUS_SHIPPED => 3,
        self::BATTERY_STATUS_RETURNED => 4,
        self::BATTERY_STATUS_RECYCLED => 5,
    ];

    const REPORT_MODE = [
        self::BATTERY_STATUS_REGISTERED,
        self::BATTERY_STATUS_DELIVERED,
        self::BATTERY_STATUS_RETURNED
    ];

    const IS_VERIFIED = 1;

    const BATTERY_STATUSES_DETAILS = [
        self::BATTERY_STATUS_REGISTERED => 'A registered battery has been manufactured but not (yet) delivered.',
        self::BATTERY_STATUS_DELIVERED => 'A delivered battery is either in an intermediate station or at the end customer.',
        self::BATTERY_STATUS_RETURNED => 'This battery has been registered for a pickup service or its on his way to a recycler.',
        self::BATTERY_STATUS_RECYCLED => 'The life cycle of the battery ended with recycling or disposal.',
    ];

    /**
     * @param array $filters
     * @return array
     */
    public static function getValidValuesByFilters($filters)
    {
        return array_filter(
            $filters,
            function ($value) {
                return $value !== '';
            }
        );
    }

    /**
     * @param $mode
     * @return bool
     */
    public static function validateReportMode($mode): bool
    {
        if (in_array($mode, self::REPORT_MODE)) {
            return true;
        }

        return false;
    }
}