<?php


namespace App\Service;

use App\Entity\Country;
use App\Entity\Manufacturer;
use App\Repository\CountryRepository;

/**
 * Class CountryService
 * @package App\Service
 * @property CountryRepository countryRepository
 */
class CountryService
{
    public function __construct(CountryRepository $countryRepository)
    {
        $this->countryRepository = $countryRepository;
    }

    /**
     * @return null|Manufacturer[]
     */
    public function getCountries(): ?array
    {
        /** @var Country[]|null $countries */
        $countries = $this->countryRepository->findBy([
            'status' => 1
        ]);

        return $this->toChoiceArray($countries);
    }

    /**
     * @param Country[] $countries
     * @return array|null
     */
    private function toChoiceArray($countries): ?array
    {
        $result = null;

        foreach ($countries as $country) {
            $result[$country->getName()] = $country->getId();
        }

        return $result;
    }
}