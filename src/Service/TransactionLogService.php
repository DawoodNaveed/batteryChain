<?php

namespace App\Service;

use App\Entity\Battery;
use App\Helper\CustomHelper;
use App\Repository\TransactionLogRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * Class TransactionLogService
 * @package App\Service
 * @property TransactionLogRepository transactionLogRepository
 */
class TransactionLogService
{
    /**
     * TransactionLogService constructor.
     * @param TransactionLogRepository $transactionLogRepository
     */
    public function __construct(TransactionLogRepository $transactionLogRepository)
    {
        $this->transactionLogRepository = $transactionLogRepository;
    }

    /**
     * @param Battery $battery
     * @param string $transactionType
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createTransactionLog(Battery $battery, string $transactionType = CustomHelper::BATTERY_STATUS_REGISTERED)
    {
        $this->transactionLogRepository->createTransactionLog($battery, $transactionType);
    }

    /**
     * @param Battery $battery
     * @param string $transactionType
     * @return bool
     */
    public function isExist(Battery $battery, string $transactionType): bool
    {
        return $this->transactionLogRepository->findOneBy([
            'battery' => $battery,
            'transactionType' => $transactionType
        ]) ? true : false;
    }
}