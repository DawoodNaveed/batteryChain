<?php

namespace App\Service;

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

        if ($isResponseDataArray && (
                strtolower($responseData[CustomHelper::STATUS]) === CustomHelper::STATUS_COMPLETE ||
                strtolower($responseData[CustomHelper::STATUS]) === CustomHelper::STATUS_FAIL
            )) {
            $this->logger->info($url . ' Info! Ethereum app sent data successfully.', [
                $postFields, $response
            ]);

            return $responseData;
        }  elseif ($isResponseDataArray && strtolower($responseData[\AppBundle\Helper\CustomHelper::STATUS]) === self::STATUS_SUCCESS) {
            $this->logger->info($url . ' Info! NFT Ethereum app sent data successfully.', [
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

            $postFields = [
                'serial_number' => $log->getBattery()->getSerialNumber(),
                'operation' => self::OPERATION[$log->getTransactionType()]
            ];

            return $this->getResponseFromCurlRequest($url, Request::METHOD_POST, $postFields);
        }

        return null;
    }
}