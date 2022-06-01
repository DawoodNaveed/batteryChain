<?php

namespace App\Service;

use App\Entity\Distributor;
use App\Entity\Manufacturer;
use App\Entity\User;

/**
 * Class UserService
 * @package App\Service
 */
class UserService
{
    /**
     * @param User $user
     * @return int|null
     */
    public function getManufacturerId(User $user): ?int
    {
        if ($user->getManufacturer() instanceof Manufacturer) {
            return $user->getManufacturer()->getId();
        }

        if ($user->getDistributor() instanceof Distributor) {
            $manufacturers = $user->getDistributor()->getManufacturers();

            return $manufacturers[0]->getId();
        }

        return null;
    }
}