<?php

namespace App\Service;

use App\Entity\Battery;
use App\Entity\BatteryReturn;
use App\Entity\Recycler;
use App\Entity\User;
use App\Repository\BatteryReturnRepository;
use Psr\Log\LoggerInterface;

/**
 * Class BatteryReturnService
 * @package App\Service
 * @property BatteryReturnRepository returnRepository
 * @property LoggerInterface logger
 */
class BatteryReturnService
{
    /**
     * BatteryReturnService constructor.
     * @param BatteryReturnRepository $returnRepository
     * @param LoggerInterface $logger
     */
    public function __construct(BatteryReturnRepository $returnRepository, LoggerInterface $logger)
    {
        $this->returnRepository = $returnRepository;
        $this->logger = $logger;
    }

    /**
     * @param User $user
     * @param Battery $battery
     * @param Recycler $recycler
     * @return BatteryReturn
     */
    public function createReturn(User $user, Battery $battery, Recycler $recycler): BatteryReturn
    {
        try {
            return $this->returnRepository->createReturn($user, $battery, $recycler);
        } catch (\Exception $exception) {
            $this->logger->error('[ERROR][CREATE RETURN]:' . $exception->getMessage());
        }
    }
}