<?php

namespace App\Service;

use App\Entity\BatteryType;
use App\Repository\BatteryTypeRepository;

/**
 * Class BatteryTypeService
 * @package App\Service
 * @property BatteryTypeRepository batteryTypeRepository
 */
class BatteryTypeService
{
    /**
     * BatteryTypeService constructor.
     * @param BatteryTypeRepository $batteryTypeRepository
     */
    public function __construct(BatteryTypeRepository $batteryTypeRepository)
    {
        $this->batteryTypeRepository = $batteryTypeRepository;
    }

    /**
     * @return BatteryType[]|null
     */
    public function getAvailableBatteryTypes()
    {
        return $this->batteryTypeRepository->findBy([
            'status' => true
        ]);
    }
}