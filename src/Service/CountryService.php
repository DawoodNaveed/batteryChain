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
     * @param bool $fetchFullObjects
     * @return null|Manufacturer[]
     */
    public function getCountries($fetchFullObjects = false): ?array
    {
        /** @var Country[]|null $countries */
        $countries = $this->countryRepository->findBy([
            'status' => 1
        ]);

        return $this->toChoiceArray($countries, $fetchFullObjects);
    }

    /**
     * @param Country[] $countries
     * @param bool $fetchFullObjects
     * @return array|null
     */
    private function toChoiceArray($countries, $fetchFullObjects = false): ?array
    {
        $result = null;

        foreach ($countries as $country) {
            if ($fetchFullObjects) {
                $result[$country->getName()] = $country;
            } else {
                $result[$country->getName()] = $country->getId();
            }
        }

        return $result;
    }

    /**
     * @param string $name
     * @return Country|null
     */
    public function getCountryByName(string $name): ?Country
    {
        return $this->countryRepository->findOneBy([
            'name' => $name
        ]);
    }

    /**
     * @param string $code
     * @return Country|null
     */
    public function getCountryByCode(string $code): ?Country
    {
        return $this->countryRepository->findOneBy([
            'zipCode' => $code
        ]);
    }
}