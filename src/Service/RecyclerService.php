<?php

namespace App\Service;

use App\Entity\Battery;
use App\Entity\Country;
use App\Entity\Manufacturer;
use App\Entity\Recycler;
use App\Helper\CustomHelper;
use App\Repository\RecyclerRepository;
use Doctrine\DBAL\Driver\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class RecyclerService
 * @package App\Service
 * @property RecyclerRepository recyclerRepository
 * @property LoggerInterface logger
 * @property EmailService emailService
 * @property TranslatorInterface translator
 */
class RecyclerService
{
    /**
     * RecyclerService constructor.
     * @param RecyclerRepository $recyclerRepository
     * @param LoggerInterface $logger
     * @param EmailService $emailService
     * @param TranslatorInterface $translator
     */
    public function __construct(
        RecyclerRepository $recyclerRepository,
        LoggerInterface $logger,
        EmailService $emailService,
        TranslatorInterface $translator
    ) {
        $this->recyclerRepository = $recyclerRepository;
        $this->logger = $logger;
        $this->emailService = $emailService;
        $this->translator = $translator;
    }

    /**
     * @param Country|null $country
     * @return Recycler[]|null
     */
    public function getRecyclers(?Country $country): ?array
    {
        if ($country) {
            return $this->recyclerRepository->findBy([
                'country' => $country,
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
     * @param mixed $recyclers
     * @param bool $fetchFullObject
     * @return array|null
     */
    public function toChoiceArray($recyclers, $fetchFullObject = false): ?array
    {
        $result = [];

        foreach ($recyclers as $recycler) {
            if ($recycler instanceof Recycler) {
                $key = $recycler->getName();

                if (key_exists($key, $result)) {
                    $key = $recycler->getName() . ' | ' . $recycler->getEmail();
                }

                if ($fetchFullObject) {
                    $result[$key] = $recycler;
                } else {
                    $result[$key] = $recycler->getId();
                }
            } else {
                $key = $recycler->name;

                if (key_exists($key, $result)) {
                    $key = $recycler->name . ' | ' . $recycler->email;
                }

                // for fallback - we got PHP Objects rather than Recycler Objects
                if ($fetchFullObject) {
                    $result[$key] = $recycler;
                } else {
                    $result[$key] = $recycler->id;
                }
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
            'id' => $ids,
        ]);
    }

    /**
     * @param int $id
     * @return Recycler|null
     */
    public function getRecyclerById(int $id): ?Recycler
    {
        return $this->recyclerRepository->find($id);
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
                $contact = (string) $row['phone_number'];
                $address = (string) $row['address'];
                $postalCode = (string) $row['post_code'];
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
                    'postal_code' => $postalCode,
                    'city' => $city,
                    'updated_email' => $newEmail,
                    'country' => $country,
                ], $manufacturer);
            }
        }

        fclose($handle);

        return array_merge($error, [
            'total' => $rowCount,
            'failure' => $failureCount,
        ]);
    }

    /**
     * @param array $params
     * @param bool $fetchOneObject
     * @return Recycler|null|Recycler[]
     */
    public function fetchRecyclerViaParams(array $params, $fetchOneObject = true)
    {
        try {
            return $this->recyclerRepository->fetchRecyclerViaParams($params, $fetchOneObject);
        } catch (\Exception $exception) {
            $this->logger->error('[Fetch Recycler]' . $exception->getMessage());
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

    /**
     * @param Manufacturer $manufacturer
     * @param Country $country
     * @return Recycler|Recycler[]|null
     */
    public function fetchManufacturerRecyclersByCountry(Manufacturer $manufacturer, Country $country)
    {
       return $this->fetchRecyclerViaParams([
           'country' => $country,
           'manufacturer' => $manufacturer,
       ], false);
    }

    /**
     * @param Country $country
     * @return Recycler|Recycler[]|null
     * @throws Exception
     */
    public function fetchFallbackRecyclersByCountry(Country $country)
    {
        try {
            return $this->recyclerRepository->fetchFallbackRecyclersByCountry($country);
        } catch (\Exception $exception) {
            $this->logger->error('[Fetch Fallback Recycler] ' . $exception->getMessage());
        }
    }

    /**
     * @param Recycler $recycler
     * @param Battery $battery
     * @param array $formData
     * @param string $detailPath
     */
    public function sendNewBatteryReturnEmail(Recycler $recycler, Battery $battery, array $formData, string $detailPath)
    {
        $data = [
            'user' => [
                'name' => $formData['information']['name'],
                'email' => $formData['information']['email'] ?? null,
                'contact' => $formData['information']['contact'] ?? null,
            ],
            'battery' => $battery,
            'detail' => $detailPath,
            'recyclerName' => $recycler->getName()
        ];

        $subject = $this->translator->trans('New Battery Return', [], 'messages');
        $params = [
            'subject' => $subject,
            'email' => $recycler->getEmail(),
            'template_name' => 'email_templates/new-battery-return.html.twig',
            'body' => $data,
        ];
        $this->emailService->sendEmail($params);
    }
}