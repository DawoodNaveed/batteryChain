<?php

namespace App\Service;

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

        return null;
    }
}