<?php

namespace App\Service;

use App\Entity\Import;
use App\Entity\Manufacturer;
use App\Repository\ImportRepository;

/**
 * Class ImportService
 * @package App\Service
 * @property ImportRepository importRepository
 */
class ImportService
{
    /**
     * ImportService constructor.
     * @param ImportRepository $importRepository
     */
    public function __construct(ImportRepository $importRepository)
    {
        $this->importRepository = $importRepository;
    }

    /**
     * @param Manufacturer $manufacturer
     * @param string $status
     * @return Import|array|null
     */
    public function findOneByFilter(Manufacturer $manufacturer, string $status = 'complete')
    {
        return $this->importRepository->findOneByFilter($manufacturer, $status);
    }
}