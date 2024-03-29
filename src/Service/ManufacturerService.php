<?php

namespace App\Service;

use App\Entity\Distributor;
use App\Entity\Manufacturer;
use App\Entity\User;
use App\Repository\ManufacturerRepository;
use Psr\Log\LoggerInterface;

/**
 * Class ManufacturerService
 * @package App\Service
 * @property ManufacturerRepository manufacturerRepository
 * @property LoggerInterface logger
 */
class ManufacturerService
{
    /**
     * ManufacturerService constructor.
     * @param ManufacturerRepository $manufacturerRepository
     * @param LoggerInterface $logger
     */
    public function __construct(ManufacturerRepository $manufacturerRepository, LoggerInterface $logger)
    {
        $this->manufacturerRepository = $manufacturerRepository;
        $this->logger = $logger;
    }

    /**
     * @param User $user
     * @param bool $isFetchIds
     * @return null|Manufacturer[]
     */
    public function getManufactures(User $user, $isFetchIds = false): ?array
    {
        $manufacturers = $this->manufacturerRepository->findAll();

        return $this->toChoiceArray($manufacturers, $isFetchIds);
    }

    /**
     * @param Manufacturer[] $manufacturers
     * @param bool $isFetchIds
     * @return array
     */
    public function toChoiceArray($manufacturers, $isFetchIds = false): array
    {
        $result = null;

        foreach ($manufacturers as $manufacturer) {
            if ($isFetchIds) {
                $result[$manufacturer->getName()] = $manufacturer->getId();
            } else {
                $result[$manufacturer->getName()] = $manufacturer;
            }
        }

        return $result;
    }

    /**
     * @param User $user
     */
    public function createBasicManufacturer(User $user)
    {
        try {
            $this->manufacturerRepository->createBasicManufacturer($user);
        } catch (\Exception $exception) {
            $this->logger->error('[Error][Create Manufacturer] ' . $exception->getMessage());
        }
    }

    /**
     * @param User $user
     */
    public function removeManufacturer(User $user)
    {
        try {
            $this->manufacturerRepository->removeManufacturer($user);
        } catch (\Exception $exception) {
            $this->logger->error('[Error][Remove Manufacturer] ' . $exception->getMessage());
        }
    }

    /**
     * @param User $user
     */
    public function updateBasicManufacturer(User $user)
    {
        try {
            $this->manufacturerRepository->updateBasicManufacturer($user);
        } catch (\Exception $exception) {
            $this->logger->error('[Error][Update Manufacturer] ' . $exception->getMessage());
        }
    }

    /**
     * @param string|null $manufacturerIdentifier
     * @return Manufacturer|null
     */
    public function getManufactureByIdentifier(?string $manufacturerIdentifier): ?Manufacturer
    {
        return $this->manufacturerRepository->findOneBy(['identifier' => $manufacturerIdentifier]);
    }
}