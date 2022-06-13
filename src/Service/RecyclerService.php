<?php

namespace App\Service;

use App\Entity\Country;
use App\Entity\Manufacturer;
use App\Entity\Recycler;
use App\Repository\RecyclerRepository;

/**
 * Class RecyclerService
 * @package App\Service
 * @property RecyclerRepository recyclerRepository
 */
class RecyclerService
{
    /**
     * RecyclerService constructor.
     * @param RecyclerRepository $recyclerRepository
     */
    public function __construct(RecyclerRepository $recyclerRepository)
    {
        $this->recyclerRepository = $recyclerRepository;
    }

    /**
     * @param Country|null $country
     * @return Recycler[]|null
     */
    public function getRecyclers(?Country $country): ?array
    {
        if ($country) {
            return $this->recyclerRepository->findBy([
                'country' => $country
            ]);
        }

        return null;
    }

    /**
     * @param int $id
     * @return int|mixed[]|string
     */
    public function getRecyclersByCountryId(int $id)
    {
        return $this->toChoiceArray($this->recyclerRepository->getRecyclersByCountryId($id));
    }

    /**
     * @param Recycler[] $recyclers
     * @return array|null
     */
    public function toChoiceArray($recyclers): ?array
    {
        $result = null;

        foreach ($recyclers as $recycler) {
            $result[$recycler->getName()] = $recycler->getId();
        }

        return $result;
    }

    /**
     * @param array $ids
     * @return array
     */
    public function getRecyclerByIds(array $ids): array
    {
        return $this->recyclerRepository->findBy([
            'id' => $ids
        ]);
    }
}