<?php

namespace App\Service;

use App\Entity\Battery;
use App\Entity\TransactionLog;
use App\Helper\CustomHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class BlockchainService
 * @package App\Service
 * @property LoggerInterface logger
 * @property TranslatorInterface translator
 * @property $blockchainUrl
 * @property $blockchainAccessCode
 */
class BlockchainService
{
    public const TRANSACTION_STATUS_URL = '/transaction-status';
    public const BATTERY_URL = '/battery';
    const OPERATION = [
        CustomHelper::BATTERY_STATUS_REGISTERED => 1,
        CustomHelper::BATTERY_STATUS_DELIVERED => 2,
        CustomHelper::BATTERY_STATUS_RETURNED => 2,
        CustomHelper::BATTERY_STATUS_RECYCLED => 3,
    ];

    /**
     * BlockchainService constructor.
     * @param LoggerInterface $logger
     * @param TranslatorInterface $translator
     * @param $blockchainUrl
     * @param $blockchainAccessCode
     */
    public function __construct(LoggerInterface $logger, TranslatorInterface $translator, $blockchainUrl, $blockchainAccessCode)
    {
        $this->logger = $logger;
        $this->translator = $translator;
        $this->blockchainUrl = $blockchainUrl;
        $this->blockchainAccessCode = $blockchainAccessCode;
    }

    /**
     * @return string[]
     */
    public function getHeaders(): array
    {
        return array(
            'access_code: ' . $this->blockchainAccessCode,
            'Content-Type: application/json'
        );
    }

    /**
     * @param string|null $transactionHash
     * @return array|null
     * @throws \Exception
     */
    public function getTransactionStatusByRef(
        ?string $transactionHash = null
    ): ?array {
        if (!is_null($transactionHash)) {
            $url = $this->blockchainUrl . self::TRANSACTION_STATUS_URL;

            $postFields = [
                'transaction_hash' => trim($transactionHash),
            ];

            return $this->getResponseFromCurlRequest($url, Request::METHOD_GET, $postFields, true);
        }

        return null;
    }

    /**
     * @param $url
     * @param $method
     * @param array|null $postFields
     * @param bool|null $getHashStatus
     * @return array|mixed|void
     * @throws \Exception
     */
    public function getResponseFromCurlRequest($url, $method, ?array $postFields = [], ?bool $getHashStatus = false): array
    {
        $postFieldsJson = json_encode($postFields);
        $httpHeader = $this->getHeaders();
        $response = CustomHelper::sendCurlRequest($url, $method, $httpHeader, $postFieldsJson);
        $responseData = json_decode(stripslashes($response), true);
        $isResponseDataArray = is_array($responseData);

        if ($getHashStatus) {
            if ($isResponseDataArray && (
                    strtolower($responseData[CustomHelper::STATUS]) === CustomHelper::STATUS_COMPLETE ||
                    strtolower($responseData[CustomHelper::STATUS]) === CustomHelper::STATUS_FAIL
                )) {
                $this->logger->info($url . ' Info! Ethereum app sent data successfully.', [
                    $postFields, $response
                ]);

                return $responseData;
            }
        } elseif ($isResponseDataArray && strtolower($responseData[CustomHelper::STATUS]) === CustomHelper::STATUS_SUCCESS) {
            $this->logger->info($url . ' Info! Ethereum app sent data successfully.', [
                $postFields, $response
            ]);

            return $responseData;
        }

        $this->logger->error($url . ' Error! Ethereum app did not send expected data.', [
            $postFields, $response
        ]);

        CustomHelper::exceptionBadRequest(
            $this->translator->trans('Error! Ethereum app did not send expected data. (' . empty($responseData['message']) ? $response : $responseData['message'] .  ')',
                [], 'messages')
        );
    }

    /**
     * @param TransactionLog|null $log
     * @return array|null
     * @throws \Exception
     */
    public function createTransactionHash(?TransactionLog $log): ?array
    {
        if (!is_null($log)) {
            $url = $this->blockchainUrl . self::BATTERY_URL;
            /** @var Battery $battery */
            $battery = $log->getBattery();

            $postFields = [
                'serial_number' => $battery->getInternalSerialNumber(),
                'meta_data' => [
                    'main_serial_number' => $battery->getSerialNumber(),
                    'type_id' => $battery->getBatteryType()->getId(),
                    'kwh' => $battery->getNominalEnergy(),
                    'voltage' => $battery->getNominalVoltage(),
                    'capacity' => $battery->getNominalCapacity(),
                    'weight' => $battery->getMass(),
                    'height' => $battery->getHeight(),
                    'length' => $battery->getLength(),
                    'width' => $battery->getWidth(),
                    'manufacturer_name' => $battery->getManufacturer()->getName(),
                    'state' => $log->getTransactionType(),
                    'cell_type' => $battery->getCellType(),
                    'module_type' => $battery->getModuleType(),
                    'co2' => $battery->getCo2(),
                    'acid_volume' => $battery->getAcidVolume(),
                    'production_date' => $battery->getProductionDate()->format('Y-m-d H:i:s'),
                    'tray_number' => $battery->getTrayNumber(),
                    'climate_neutral' => $battery->getIsClimateNeutral() ? CustomHelper::YES : CustomHelper::NO,
                    'insured' => $battery->getIsInsured() ? CustomHelper::YES : CustomHelper::NO
                ],
                'operation' => self::OPERATION[$log->getTransactionType()]
            ];

            return [
                CustomHelper::RESULT => $this->getResponseFromCurlRequest($url, Request::METHOD_POST, $postFields),
                CustomHelper::FIELDS => $postFields
            ];
        }

        return null;
    }
}