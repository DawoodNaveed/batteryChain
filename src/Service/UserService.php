<?php

namespace App\Service;

use App\Entity\Manufacturer;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class UserService
 * @package App\Service
 * @property TokenStorageInterface authorizationChecker
 */
class UserService
{
    /**
     * UserService constructor.
     * @param TokenStorageInterface $authorizationChecker
     */
    public function __construct(TokenStorageInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @return boolean
     */
    public function isAuthenticated(): bool
    {
        $tokenInterface = $this->authorizationChecker->getToken();

        if (!empty($tokenInterface) && $tokenInterface->getUser()) {
            return true;
        }

        return false;
    }

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