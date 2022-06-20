<?php

namespace App\Service;

use App\Entity\Battery;
use App\Entity\User;
use App\Helper\CustomHelper;
use App\Repository\BatteryRepository;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class BatteryService
 * @package App\Service
 * @property BatteryRepository batteryRepository
 * @property $csvFileUploadSize
 */
class BatteryService
{
    /**
     * BatteryService constructor.
     * @param BatteryRepository $batteryRepository
     * @param $csvFileUploadSize
     */
    public function __construct(BatteryRepository $batteryRepository, $csvFileUploadSize)
    {
        $this->batteryRepository = $batteryRepository;
        $this->csvFileUploadSize = $csvFileUploadSize;
    }

    /**
     * @param UploadedFile $file
     * @return array
     */
    public function isValidCsv(UploadedFile $file): array
    {
        $returnArray = ['error' => CustomHelper::NO_ERROR];

        if ($file instanceof UploadedFile && $file->getError() === 0) {
            $originalFilename = $file->getClientOriginalName();
            $nameArray = explode('.', $originalFilename);
            $extension = $nameArray[sizeof($nameArray) - 1];

            if ($extension != CustomHelper::CSV_TEXT) {
                $returnArray = ['error' => CustomHelper::ERROR, 'message' => 'service.error.invalid_csv'];
            }

            if ($file->getSize() > $this->csvFileUploadSize) {
                $returnArray = ['error' => CustomHelper::ERROR, 'message' => 'service.error.file_size_exceeds'];
            }
        } else {
            $returnArray = ['error' => CustomHelper::ERROR, 'message' => 'service.error.something_went_wrong'];
        }

        return $returnArray;
    }

    /**
     * @param UploadedFile $file
     * @param $manufacturerId
     * @param $currentPossessorId
     * @return array
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function extractCsvAndCreateBatteries(UploadedFile $file, $manufacturerId, $currentPossessorId): array
    {
        $error = [];

        if (($handle = fopen($file, "r")) !== false) {
            $csvHeaders = fgetcsv($handle, 1000, ",");

            if ($csvHeaders !== CustomHelper::CSV_HEADERS) {
                $error = ['error' => CustomHelper::ERROR, 'message' => 'service.error.invalid_csv_headers'];
                return $error;
            }

            $rowCount = 1;
            $failureCount = 0;
            $values = '';

            while (($csvData = fgetcsv($handle, 1000, ",")) !== false) {
                if (count($csvData) !== count(CustomHelper::CSV_HEADERS)) {
                    $error = ['error' => CustomHelper::ERROR, 'message' => 'service.error.invalid_csv'];
                    return $error;
                }

                $row = [];

                for ($headerIndex = 0; $headerIndex < count($csvHeaders); $headerIndex++) {
                    $row[trim($csvHeaders[$headerIndex])] = $csvData[$headerIndex];
                }

                if (!empty($this->isExist((string) $row['serial_number']))) {
                    $failureCount++;
                    continue;
                }

                $serialNumber = (string) $row['serial_number'];
                $batteryType = (string) $row['battery_type'];
                $nominalVoltage = (float) $row['nominal_voltage'];
                $nominalCapacity = (float) $row['nominal_capacity'];
                $nominalEnergy = (float) $row['nominal_energy'];
                $cycleLife = (float) $row['cycle_life'];
                $height = (float) $row['height'];
                $width = (float) $row['width'];
                $mass = (float) $row['mass'];
                $status = CustomHelper::BATTERY_STATUS_REGISTERED;

                $values .= "( '" . $serialNumber . "', '" . $batteryType . "', '" . $nominalVoltage .
                    "', '" . $nominalCapacity . "', '" . $nominalEnergy . "', '" . $cycleLife
                    . "', '" . $height . "', '" . $width . "', '" . $mass . "', '" . $status
                    . "', '" . $manufacturerId . "', '" . $currentPossessorId . "', now(), now()), ";

                $rowCount++;

                if ($rowCount % 20 === 0) {
                    $values = rtrim($values, ', ');
                    $error = $this->createBatteryEntries($values);
                    $values = '';
                }
            }
        }

        if (!empty($values)) {
            $values = rtrim($values, ', ');
            $error = $this->createBatteryEntries($values);
        }

        fclose($handle);

        return array_merge($error, [
            'total' => ($rowCount - 1) + $failureCount,
            'failure' => $failureCount
        ]);
    }

    /**
     * @param $values
     * @return array
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function createBatteryEntries($values): array
    {
        $error = [];
        try {
            $this->batteryRepository->createNewBattery($values);
        } catch (Exception $e) {
            $error = ['error' => CustomHelper::ERROR, 'message' => 'service.error.something_went_wrong'];
        }

        return $error;
    }

    /**
     * @param User $user
     * @return array|null
     */
    public function getCurrentPossessedBatteries(User $user): ?array
    {
        return $this->batteryRepository->findBy([
            'currentPossessor' => $user
            ]
        );
    }

    /**
     * @param string $serialNumber
     * @return Battery[]|null
     */
    private function isExist(string $serialNumber): ?Battery
    {
        return $this->batteryRepository->findOneBy([
            'serialNumber' => $serialNumber
        ]);
    }

    /**
     * @param $serialNumber
     * @return Battery|null
     */
    public function fetchBatteryBySerialNumber($serialNumber): ?Battery
    {
        return $this->batteryRepository->findOneBy([
            'serialNumber' => $serialNumber
        ]);
    }
}
