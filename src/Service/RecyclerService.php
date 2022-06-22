<?php

namespace App\Service;

use App\Entity\Country;
use App\Entity\Manufacturer;
use App\Entity\Recycler;
use App\Helper\CustomHelper;
use App\Repository\RecyclerRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class RecyclerService
 * @package App\Service
 * @property RecyclerRepository recyclerRepository
 * @property LoggerInterface logger
 */
class RecyclerService
{
    /**
     * RecyclerService constructor.
     * @param RecyclerRepository $recyclerRepository
     * @param LoggerInterface $logger
     */
    public function __construct(RecyclerRepository $recyclerRepository, LoggerInterface $logger)
    {
        $this->recyclerRepository = $recyclerRepository;
        $this->logger = $logger;
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
    public function toChoiceArray($recyclers, $fetchFullObject = false): ?array
    {
        $result = null;

        foreach ($recyclers as $recycler) {
            if ($fetchFullObject) {
                $result[$recycler->getName()] = $recycler;
            } else {
                $result[$recycler->getName()] = $recycler->getId();
            }
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

    /**
     * @return Recycler[]|null
     */
    public function getAllRecyclers(): ?array
    {
        return $this->recyclerRepository->findAll();
    }

    /**
     * @param UploadedFile $file
     * @param Manufacturer|null $manufacturer
     * @param $country
     * @return array
     */
    public function extractCsvAndUpdateRecyclers(
        UploadedFile $file,
        ?Manufacturer $manufacturer,
        $country
    ): array {
        $error = [];

        if (($handle = fopen($file, "r")) !== false) {
            $csvHeaders = fgetcsv($handle, 1000, ",");

            if ($csvHeaders !== CustomHelper::RECYCLER_CSV_HEADERS) {
                $error = ['error' => CustomHelper::ERROR, 'message' => 'service.error.invalid_csv_headers'];
                return $error;
            }

            $rowCount = 1;
            $failureCount = 0;

            while (($csvData = fgetcsv($handle, 1000, ",")) !== false) {
                if (count($csvData) !== count(CustomHelper::RECYCLER_CSV_HEADERS)) {
                    $error = ['error' => CustomHelper::ERROR, 'message' => 'service.error.invalid_csv'];
                    return $error;
                }

                $row = [];

                for ($headerIndex = 0; $headerIndex < count($csvHeaders); $headerIndex++) {
                    $row[trim($csvHeaders[$headerIndex])] = $csvData[$headerIndex];
                }

                $name = (string) $row['name'];
                $email = (string) $row['email'];
                $contact = (string) $row['contact'];
                $address = (string) $row['address'];
                $city = (string) $row['city'];
                $newEmail = (string) $row['updated_email'];
                $rowCount++;

                if (empty($email)) {
                    $failureCount++;
                    // Email is mandatory to fetch existing recycler
                    continue;
                }

                $params = ['email' => $email, 'country' => $country];

                if ($manufacturer) {
                    $params['manufacturer'] = $manufacturer;
                }

                $recycler = $this->fetchRecyclerViaParams($params);
                $recycler = $this->createOrUpdateRecycler($recycler, [
                    'email' => $email,
                    'name' => $name,
                    'contact' => $contact,
                    'address' => $address,
                    'city' => $city,
                    'updated_email' => $newEmail,
                    'country' => $country
                ], $manufacturer);
            }
        }

        fclose($handle);

        return array_merge($error, [
            'total' => $rowCount,
            'failure' => $failureCount
        ]);
    }

    /**
     * @param array $params
     * @return Recycler|null
     */
    private function fetchRecyclerViaParams(array $params): ?Recycler
    {
        try {
            return $this->recyclerRepository->fetchRecyclerViaParams($params);
        } catch (\Exception $exception) {
            $this->logger->error('[Bulk Recycler]' . $exception->getMessage());
        }

        return null;
    }

    /**
     * @param Recycler|null $recycler
     * @param array $data
     * @param Manufacturer|null $manufacturer
     * @return Recycler
     */
    private function createOrUpdateRecycler(?Recycler $recycler, array $data, ?Manufacturer $manufacturer = null): Recycler
    {
        try {
            return $this->recyclerRepository->createOrUpdateRecycler($recycler, $data, $manufacturer);
        } catch (\Exception $exception) {
            $this->logger->error('[Bulk Recycler]' . $exception->getMessage());
        }
    }
}