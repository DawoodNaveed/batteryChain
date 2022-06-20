<?php

namespace App\Service;

use App\Entity\Battery;
use App\Entity\BatteryReturn;
use App\Entity\Recycler;
use App\Entity\User;
use App\Repository\BatteryReturnRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * Class BatteryReturnService
 * @package App\Service
 * @property BatteryReturnRepository returnRepository
 */
class BatteryReturnService
{
    /**
     * BatteryReturnService constructor.
     * @param BatteryReturnRepository $returnRepository
     */
    public function __construct(BatteryReturnRepository $returnRepository)
    {
        $this->returnRepository = $returnRepository;
    }

    /**
     * @param User $user
     * @param Battery $battery
     * @param Recycler $recycler
     * @return BatteryReturn
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createReturn(User $user, Battery $battery, Recycler $recycler): BatteryReturn
    {
        return $this->returnRepository->createReturn($user, $battery, $recycler);
    }
}