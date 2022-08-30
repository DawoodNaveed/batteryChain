<?php

namespace App\Service;

use App\Entity\Import;
use App\Entity\Manufacturer;
use App\Enum\BulkImportEnum;
use App\Helper\CustomHelper;
use App\Repository\ImportRepository;
use Doctrine\DBAL\Exception;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ImportService
 * @package App\Service
 * @property ImportRepository importRepository
 * @property AwsService awsService
 * @property BatteryService batteryService
 * @property TranslatorInterface translator
 * @property $awsCsvFolder
 */
class ImportService
{
    /**
     * ImportService constructor.
     * @param ImportRepository $importRepository
     * @param AwsService $awsService
     * @param BatteryService $batteryService
     * @param TranslatorInterface $translator
     * @param $awsCsvFolder
     */
    public function __construct(
        ImportRepository $importRepository,
        AwsService $awsService,
        BatteryService $batteryService,
        TranslatorInterface $translator,
        $awsCsvFolder
    ) {
        $this->importRepository = $importRepository;
        $this->awsService = $awsService;
        $this->batteryService = $batteryService;
        $this->translator = $translator;
        $this->awsCsvFolder = $awsCsvFolder;
    }

    /**
     * @param Manufacturer $manufacturer
     * @param array $status
     * @return Import|array|null
     */
    public function findOneByFilter(Manufacturer $manufacturer, array $status = [BulkImportEnum::COMPLETE, BulkImportEnum::ERROR])
    {
        return $this->importRepository->findOneByFilter($manufacturer, $status);
    }

    /**
     * @param $data
     * @return bool
     */
    public function validateImport($data): bool
    {
        $isValidated = false;
        $importObj = $this->importRepository->find($data['import_id']);
        $this->importRepository->updateStatus($importObj, BulkImportEnum::VALIDATING);

        /** @var Manufacturer $manufacturer */
        $manufacturer = $importObj->getManufacturer();
        $result = $this->awsService->isExistCsvFile($this->awsCsvFolder, $importObj->getCsv());

        if ($result) {
            $response = $this->validateBulkImportCsv($importObj->getCsvFile(), $manufacturer);

            if ($response[BulkImportEnum::ERROR] === CustomHelper::NO_ERROR) {
                $importObj->setStatus(BulkImportEnum::VALIDATED);
                $importObj->setReason(null);
                $isValidated = true;
            } else {
                $importObj->setStatus(BulkImportEnum::ERROR);
                $importObj->setReason($response[BulkImportEnum::MESSAGE]);
            }
        } else {
            $importObj->setStatus(BulkImportEnum::ERROR);
            $importObj->setReason(
                $this->translator->trans(
                    'Error! Csv file does not exists!'
                )
            );
        }

        $this->importRepository->flush();

        return $isValidated;
    }

    /**
     * @param $file
     * @param Manufacturer $manufacturer
     * @return array
     */
    private function validateBulkImportCsv($file, Manufacturer $manufacturer): array
    {
        if (($handle = fopen($file, BulkImportEnum::READ_MODE)) !== false) {
            $csvHeaders = fgetcsv($handle, 1000, BulkImportEnum::CSV_SEPARATOR);

            if ($csvHeaders !== CustomHelper::CSV_HEADERS) {
                fclose($handle);

                return [
                    'error' => CustomHelper::ERROR,
                    'message' => 'service.error.invalid_csv_headers'
                ];
            }

            while (($csvData = fgetcsv($handle, 1000, BulkImportEnum::CSV_SEPARATOR)) !== false) {
                if (count($csvData) !== count(CustomHelper::CSV_HEADERS)) {
                    fclose($handle);

                    return [
                        BulkImportEnum::ERROR => CustomHelper::ERROR,
                        BulkImportEnum::MESSAGE => 'service.error.invalid_csv'
                    ];
                }

                $row = [];

                for ($headerIndex = 0; $headerIndex < count($csvHeaders); $headerIndex++) {
                    $row[trim($csvHeaders[$headerIndex])] = $csvData[$headerIndex];
                }

                $serialNumber = trim((string) $row['serial_number']);

                if (empty($serialNumber)) {
                    fclose($handle);

                    return [
                        BulkImportEnum::ERROR => CustomHelper::ERROR,
                        BulkImportEnum::MESSAGE => 'service.error.must_contain_serial_number'
                    ];
                }

                // Battery with similar serial number and manufacturer
                $battery = $this->batteryService
                    ->batteryRepository
                    ->findOneBy([
                        'serialNumber' => $serialNumber,
                        'manufacturer' => $manufacturer
                    ]);

                // If battery exists then do not create new Battery
                if (!empty($battery)) {
                    fclose($handle);

                    return [
                        BulkImportEnum::ERROR => CustomHelper::ERROR,
                        BulkImportEnum::MESSAGE => $this->translator
                            ->trans('service.error.error_serial_number_exists',
                                [
                                    '%serial_number%' => $serialNumber
                                ]
                            )
                    ];
                }
            }
        }

        fclose($handle);

        return [BulkImportEnum::ERROR => CustomHelper::NO_ERROR];
    }

    /**
     * @param $data
     * @throws Exception
     */
    public function bulkImport($data)
    {
        $importObj = $this->importRepository->find($data['import_id']);
        $this->importRepository->updateStatus($importObj, BulkImportEnum::IN_PROGRESS);

        /** @var Manufacturer $manufacturer */
        $manufacturer = $importObj->getManufacturer();
        $result = $this->awsService->isExistCsvFile($this->awsCsvFolder, $importObj->getCsv());

        if ($result) {
            $response = $this->batteryService->extractCsvAndCreateBatteries($importObj, $manufacturer);

            if ($response[BulkImportEnum::ERROR] === CustomHelper::NO_ERROR) {
                $importObj->setStatus(BulkImportEnum::COMPLETE);
            } else {
                $importObj->setStatus(BulkImportEnum::ERROR);
                $importObj->setReason($response[BulkImportEnum::MESSAGE]);
            }
        }

        $this->importRepository->flush();
    }
}