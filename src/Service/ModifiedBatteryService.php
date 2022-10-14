<?php

namespace App\Service;

use App\Entity\Battery;
use App\Entity\Manufacturer;
use App\Entity\ModifiedBattery;
use App\Entity\User;
use App\Repository\ModifiedBatteryRepository;

/**
 * Class ModifiedBatteryService
 * @package App\Service
 * @property ModifiedBatteryRepository modifiedBatteryRepository
 */
class ModifiedBatteryService
{
    /**
     * @param ModifiedBatteryRepository $modifiedBatteryRepository
     */
    public function __construct(ModifiedBatteryRepository $modifiedBatteryRepository)
    {
        $this->modifiedBatteryRepository = $modifiedBatteryRepository;
    }

    /**
     * @param Battery $battery
     * @param Manufacturer $manufacturer
     * @param User $user
     * @param string $action
     * @return ModifiedBattery
     */
    public function createModifiedBattery(Battery $battery, Manufacturer $manufacturer, User $user, string $action): ModifiedBattery
    {
        return $this->modifiedBatteryRepository->createModifiedBattery($battery, $manufacturer, $user, $action);
    }
}