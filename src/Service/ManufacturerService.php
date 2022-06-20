<?php

namespace App\Service;

use App\Entity\Distributor;
use App\Entity\Manufacturer;
use App\Entity\User;
use App\Repository\ManufacturerRepository;

/**
 * Class ManufacturerService
 * @package App\Service
 * @property ManufacturerRepository manufacturerRepository
 */
class ManufacturerService
{
    public function __construct(ManufacturerRepository $manufacturerRepository)
    {
        $this->manufacturerRepository = $manufacturerRepository;
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
}