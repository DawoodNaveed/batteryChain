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
     * @return null|Manufacturer[]
     */
    public function getManufactures(User $user): ?array
    {
        $manufacturers = $this->manufacturerRepository->findAll();

        return $this->toChoiceArray($manufacturers);
    }

    /**
     * @param Manufacturer[] $manufacturers
     * @return array
     */
    private function toChoiceArray($manufacturers): array
    {
        $result = null;

        foreach ($manufacturers as $manufacturer) {
            $result[$manufacturer->getName()] = $manufacturer;
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
}