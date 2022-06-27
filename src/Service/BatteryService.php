<?php

namespace App\Service;

use App\Entity\Battery;
use App\Entity\Manufacturer;
use App\Entity\Recycler;
use App\Entity\User;
use App\Helper\CustomHelper;
use App\Repository\BatteryRepository;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class BatteryService
 * @package App\Service
 * @property BatteryRepository batteryRepository
 * @property ShipmentService shipmentService
 * @property BatteryReturnService returnService
 * @property LoggerInterface logger
 * @property $csvFileUploadSize
 */
class BatteryService
{
    /**
     * BatteryService constructor.
     * @param BatteryRepository $batteryRepository
     * @param ShipmentService $shipmentService
     * @param BatteryReturnService $returnService
     * @param LoggerInterface $logger
     * @param $csvFileUploadSize
     */
    public function __construct(
        BatteryRepository $batteryRepository,
        ShipmentService $shipmentService,
        BatteryReturnService $returnService,
        LoggerInterface $logger,
        $csvFileUploadSize
    ) {
        $this->batteryRepository = $batteryRepository;
        $this->shipmentService = $shipmentService;
        $this->returnService = $returnService;
        $this->logger = $logger;
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

            //Disable SoftDelete Filter to Check is Exist
            $this->batteryRepository->disableFilter('softdeleteable');
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
                $cellType = (string) $row['cell_type'] ?? null;
                $moduleType = (string) $row['module_type'] ?? null;
                $trayNumber = (string) $row['tray_number'] ?? null;
                $productionDate = (string) $row['production_date'] ?? null;
                $nominalVoltage = (float) $row['nominal_voltage'];
                $nominalCapacity = (float) $row['nominal_capacity'];
                $nominalEnergy = (float) $row['nominal_energy'];
                $acidVolume = (string) $row['acid_volume'] ?? null;
                $co2 = ((string) $row['CO2']) ?? null;
                $cycleLife = (float) $row['cycle_life'] ?? null;
                $height = ((float) $row['height']) ?? null;
                $width = ((float) $row['width']) ?? null;
                $mass = (float) $row['mass'];
                $status = CustomHelper::BATTERY_STATUS_REGISTERED;

                $date = (new \DateTime($productionDate))->format('Y-m-d H:i:s');
                $values .= "( '" . $serialNumber . "', '" . $batteryType . "', '" . $cellType .
                    "', '" . $moduleType . "', '" . $trayNumber . "', '" . $date .
                    "', '" . $nominalVoltage . "', '" . $nominalCapacity . "', '" . $nominalEnergy .
                    "', '" . $acidVolume . "', '" . $co2 . "', '" . $cycleLife
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

        //Enable SoftDelete Filter
        $this->batteryRepository->enableFilter('softdeleteable');
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
     * @return Battery|null
     */
    private function isExist(string $serialNumber): ?Battery
    {
        return $this->batteryRepository->isExist($serialNumber);
    }

    /**
     * @param $serialNumber
     * @param Manufacturer|null $manufacturer
     * @return Battery|null
     */
    public function fetchBatteryBySerialNumber($serialNumber, ?Manufacturer $manufacturer = null): ?Battery
    {
        $params = [
            'serialNumber' => $serialNumber
        ];

        if ($manufacturer) {
            $params['manufacturer'] = $manufacturer;
        }

        return $this->batteryRepository->findOneBy($params);
    }

    /**
     * @param UploadedFile $file
     * @param User $user
     * @return array|null
     */
    public function extractCsvAndAddDeliveries(UploadedFile $file, User $user): ?array
    {
        try {
            $error = [];
            $rowCount = 1;

            if (($handle = fopen($file, "r")) !== false) {
                $csvHeaders = fgetcsv($handle, 1000, ",");

                if ($csvHeaders !== CustomHelper::DELIVERY_CSV_HEADERS) {
                    $error['error']['invalid_csv_header'] = ['message' => 'service.error.invalid_csv_headers'];
                    return $error;
                }

                $notExistCount = 0;
                $alreadyDeliveredCount = 0;

                while (($csvData = fgetcsv($handle, 1000, ",")) !== false) {
                    if (count($csvData) !== count(CustomHelper::DELIVERY_CSV_HEADERS)) {
                        $error['error']['invalid_csv'] = ['message' => 'service.error.invalid_csv'];
                        return $error;
                    }

                    $row = [];

                    for ($headerIndex = 0; $headerIndex < count($csvHeaders); $headerIndex++) {
                        $row[trim($csvHeaders[$headerIndex])] = $csvData[$headerIndex];
                    }

                    $rowCount++;
                    $battery = $this->fetchBatteryBySerialNumber((string) $row['serial_number'],
                        $user->getManufacturer() ?? null);

                    if (empty($battery)) {
                        $notExistCount++;
                        $error['error']['not_exist_error'] = ['message' => $notExistCount . ' Battery(s) does not exist!'];
                        continue;
                    }

                    if (CustomHelper::BATTERY_STATUSES[$battery->getStatus()] >=
                        CustomHelper::BATTERY_STATUSES[CustomHelper::BATTERY_STATUS_DELIVERED]) {
                        $alreadyDeliveredCount++;
                        $error['error']['already_delivered_error'] = ['message' => $alreadyDeliveredCount . ' Battery(s) already delivered!'];
                        continue;
                    }

                    $battery->setStatus(CustomHelper::BATTERY_STATUS_DELIVERED);
                    $battery->setCurrentPossessor($user);
                    $shipment = $this->shipmentService->createShipment($user, $battery);
                }
            }

            fclose($handle);

            return array_merge($error, [
                'total' => ($rowCount - 1)
            ]);
        } catch (\Exception $exception) {
            $this->logger->error('[Bulk Delivery]' . $exception->getMessage());
        }

        return [];
    }

    /**
     * @param UploadedFile $file
     * @param User $user
     * @param Recycler $recycler
     * @return array|null
     */
    public function extractCsvAndAddReturns(UploadedFile $file, User $user, Recycler $recycler): ?array
    {
        try {
            $error = [];
            $rowCount = 1;

            if (($handle = fopen($file, "r")) !== false) {
                $csvHeaders = fgetcsv($handle, 1000, ",");

                if ($csvHeaders !== CustomHelper::RETURN_CSV_HEADERS) {
                    $error['error']['invalid_csv_header'] = ['message' => 'service.error.invalid_csv_headers'];
                    return $error;
                }

                $notExistCount = 0;
                $alreadyReturnedCount = 0;

                while (($csvData = fgetcsv($handle, 1000, ",")) !== false) {
                    if (count($csvData) !== count(CustomHelper::RETURN_CSV_HEADERS)) {
                        $error['error']['invalid_csv'] = ['message' => 'service.error.invalid_csv'];
                        return $error;
                    }

                    $row = [];

                    for ($headerIndex = 0; $headerIndex < count($csvHeaders); $headerIndex++) {
                        $row[trim($csvHeaders[$headerIndex])] = $csvData[$headerIndex];
                    }

                    $rowCount++;
                    $battery = $this->fetchBatteryBySerialNumber((string) $row['serial_number'],
                        $user->getManufacturer() ?? null);

                    if (empty($battery)) {
                        $notExistCount++;
                        $error['error']['not_exist_error'] = ['message' => $notExistCount . ' Battery(s) does not exist!'];
                        continue;
                    }

                    if (CustomHelper::BATTERY_STATUSES[$battery->getStatus()] >=
                        CustomHelper::BATTERY_STATUSES[CustomHelper::BATTERY_STATUS_RETURNED]) {
                        $alreadyReturnedCount++;
                        $error['error']['already_delivered_error'] = ['message' => $alreadyReturnedCount . ' Battery(s) already delivered!'];
                        continue;
                    }

                    $battery->setStatus(CustomHelper::BATTERY_STATUS_RETURNED);
                    $battery->setCurrentPossessor($user);
                    $return = $this->returnService->createReturn($user, $battery, $recycler);
                }
            }

            fclose($handle);

            return array_merge($error, [
                'total' => ($rowCount - 1)
            ]);
        } catch (\Exception $exception) {
            $this->logger->error('[Bulk Return]' . $exception->getMessage());
        }

        return [];
    }
}
