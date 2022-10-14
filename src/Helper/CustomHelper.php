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
    const RANDOM_STRING_DEFAULT_LENGTH = 6;
    const RANDOM_STRING_CHARS = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
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
        'height',
        'width',
        'length',
        'mass',
        'delivery_date',
        'is_insured',
        'is_climate_neutral'
    ];

    /** @var array */
    const RECYCLER_CSV_HEADERS = [
        'email',
        'name',
        'phone_number',
        'address',
        'post_code',
        'city',
        'updated_email'
    ];

    /** @var array */
    const DELIVERY_CSV_HEADERS = [
        'serial_number',
        'manufacturer_identifier',
    ];

    /** @var array */
    const RETURN_CSV_HEADERS = [
        'serial_number',
        'manufacturer_identifier'
    ];

    /** @var array */
    const RECYCLE_CSV_HEADERS = [
        'serial_number',
        'manufacturer_identifier'
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
    public const RESULT = 'result';
    public const FIELDS = 'fields';

    public const LOGO_URL = 'https://4art-marketplace-thumb-prelive.s3.eu-central-1.amazonaws.com/thumbnail/batterychain/pdf_logo.png';
    public const PDF_LOGO_URL = 'resources/pdf_logo.png';
    public const HIGH_VOLTAGE_ICON_URL = 'resources/icons/high_voltage.svg';
    public const GARBAGE_CAN_ICON_URL = 'resources/icons/garbage_can.png';
    public const MANUAL_ICON_URL = 'resources/icons/manual.svg';
    public const WARNING_ICON_URL = 'resources/icons/warning.svg';
    public const PDF_TITLE = 'Battery Passport';

    public const YES = 'Yes';
    public const NO = 'No';

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

    /**
     * @return string|null
     */
    public static function get_ip_address(): ?string
    {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
            if (array_key_exists($key, $_SERVER) === true){
                foreach (explode(',', $_SERVER[$key]) as $ip){
                    $ip = trim($ip); // just to be safe

                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 || FILTER_FLAG_IPV6 || FILTER_FLAG_NO_PRIV_RANGE || FILTER_FLAG_NO_RES_RANGE) !== false){
                        return $ip;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param $ip_address
     * @return mixed
     */
    public static function get_ip_details($ip_address)
    {
        //function to find country, city and country code from IP.

        //verify the IP.
        ip2long($ip_address)== -1 || ip2long($ip_address) === false ? trigger_error("Invalid IP", E_USER_ERROR) : "";

        //get the JSON result from hostip.info
        $result = file_get_contents("http://api.hostip.info/get_json.php?ip=".$ip_address);

        $result = json_decode($result, 1);

        //return the array containing city, country and country code
        return $result;
    }

    /**
     * @param $requestType
     * @param array $httpHeader
     * @return array|bool|string
     */
    public static function sendCurlRequestToGetIp(
        $requestType,
        $httpHeader = array()
    ) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.ipify.org?format=json');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestType);

            if (!empty($httpHeader)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
            }

            $response = curl_exec($ch);
            curl_close($ch);

            return json_decode($response);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * @param int $length
     * @return string
     */
    public static function generateRandomString(int $length = self::RANDOM_STRING_DEFAULT_LENGTH): string
    {
        $characters = self::RANDOM_STRING_CHARS . time();
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}