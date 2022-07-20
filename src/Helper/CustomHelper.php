<?php

namespace App\Helper;

use AppBundle\Exception\API\ApiException;
use Symfony\Component\HttpFoundation\Response;

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

    const BATTERY_STATUS_PRE_REGISTERED = 'pre-registered';
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
        'all',
        self::BATTERY_STATUS_REGISTERED,
        self::BATTERY_STATUS_DELIVERED,
        self::BATTERY_STATUS_RETURNED,
        self::BATTERY_STATUS_RECYCLED
    ];

    const IS_VERIFIED = 1;

    const BATTERY_STATUSES_DETAILS = [
        self::BATTERY_STATUS_REGISTERED => 'A manufactured battery was registered in the BatteryChain but not (yet) delivered or processed.',
        self::BATTERY_STATUS_DELIVERED => 'A delivered battery is either in an intermediate station or at the end customer.',
        self::BATTERY_STATUS_RETURNED => 'This battery has been registered for a pickup service or its on his way to a recycler.',
        self::BATTERY_STATUS_RECYCLED => 'The life cycle of the battery ended with recycling or disposal.',
    ];

    public const STATUS = 'status';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_FAIL = 'fail';
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const TRANSACTION_HASH = 'transaction_hash';
    public const DATA = 'data';

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
        if ($mode === 'all') {
            return false;
        }

        if (in_array($mode, self::REPORT_MODE)) {
            return true;
        }

        return false;
    }

    /**
     * @param $requestUrl
     * @param $requestType
     * @param array $httpHeader
     * @param string $postFields
     * @param false $responseHeader
     * @param false $responseWithoutDecoding
     * @return array|bool|string
     */
    public static function sendCurlRequest(
        $requestUrl,
        $requestType,
        $httpHeader = array(),
        $postFields = '',
        $responseHeader = false,
        $responseWithoutDecoding = false
    ) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $requestUrl);
            curl_setopt($ch, CURLOPT_HEADER, $responseHeader);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestType);
            if (!empty($httpHeader)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
            }
            if (!empty($postFields)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            }
            $response = curl_exec($ch);
            if ($responseHeader) {
                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $headers = substr($response, 0, $header_size);
                $body = substr($response, $header_size);
                $response = array('headers' => $headers, 'body' => $body);
            }
            curl_close($ch);

            if ($responseWithoutDecoding) {
                return array('data' => $response, 'status' => true);
            }

            return $response;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * @param $message
     * @throws \Exception
     */
    public static function exceptionBadRequest(
        $message
    ) {
        throw new \Exception(json_encode([
            'http_status_code' => Response::HTTP_BAD_REQUEST,
            'message' => $message
        ]));
    }
}