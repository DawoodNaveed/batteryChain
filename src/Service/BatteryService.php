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
 * @property TransactionLogService transactionLogService
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
     * @param TransactionLogService $transactionLogService
     * @param LoggerInterface $logger
     * @param $csvFileUploadSize
     */
    public function __construct(
        BatteryRepository $batteryRepository,
        ShipmentService $shipmentService,
        BatteryReturnService $returnService,
        TransactionLogService $transactionLogService,
        LoggerInterface $logger,
        $csvFileUploadSize
    ) {
        $this->batteryRepository = $batteryRepository;
        $this->shipmentService = $shipmentService;
        $this->returnService = $returnService;
        $this->transactionLogService = $transactionLogService;
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
                $length = ((float) $row['length']) ?? null;
                $mass = (float) $row['mass'];
                $status = CustomHelper::BATTERY_STATUS_PRE_REGISTERED;

                $date = (new \DateTime($productionDate))->format('Y-m-d H:i:s');
                $values .= "( '" . $serialNumber . "', '" . $batteryType . "', '" . $cellType .
                    "', '" . $moduleType . "', '" . $trayNumber . "', '" . $date .
                    "', '" . $nominalVoltage . "', '" . $nominalCapacity . "', '" . $nominalEnergy .
                    "', '" . $acidVolume . "', '" . $co2 . "', '" . 1 . "', '" . $cycleLife
                    . "', '" . $height . "', '" . $width  . "', '" . $length . "', '" . $mass . "', '" . $status
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
    public function fetchBatteryBySerialNumber($serialNumber, ?Manufacturer $manufacturer = null, $isAdmin = false): ?Battery
    {
        $params = [
            'serialNumber' => $serialNumber,
        ];

        if ($manufacturer) {
            $params['manufacturer'] = $manufacturer;
        }

        if (!$manufacturer && !$isAdmin) {
            $params['blockchainSecured'] = true;
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
                    $battery = $this->fetchBatteryBySerialNumber(
                        (string) $row['serial_number'],
                        $user->getManufacturer() ?? null,
                        $user->getManufacturer() ? false : true);

                    if (empty($battery) || $battery->getStatus() === CustomHelper::BATTERY_STATUS_PRE_REGISTERED) {
                        $notExistCount++;
                        $error['error']['not_exist_error'] = ['message' => $notExistCount . ' Battery(s) may not exist or registered!'];
                        continue;
                    }

                    if (CustomHelper::BATTERY_STATUSES[$battery->getStatus()] >=
                        CustomHelper::BATTERY_STATUSES[CustomHelper::BATTERY_STATUS_DELIVERED] ||
                        ($this->transactionLogService->isExist($battery, CustomHelper::BATTERY_STATUS_DELIVERED))) {
                        $alreadyDeliveredCount++;
                        $error['error']['already_delivered_error'] = ['message' => $alreadyDeliveredCount . ' Battery(s) already delivered!'];
                        continue;
                    }

                    $this->transactionLogService->createTransactionLog($battery, CustomHelper::BATTERY_STATUS_DELIVERED);
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
                    $battery = $this->fetchBatteryBySerialNumber(
                        (string) $row['serial_number'],
                        $user->getManufacturer() ?? null,
                        $user->getManufacturer() ? false : true);

                    if (empty($battery) || $battery->getStatus() === CustomHelper::BATTERY_STATUS_PRE_REGISTERED) {
                        $notExistCount++;
                        $error['error']['not_exist_error'] = ['message' => $notExistCount . ' Battery may not exist or registered!'];
                        continue;
                    }

                    if ((CustomHelper::BATTERY_STATUSES[$battery->getStatus()] >=
                        CustomHelper::BATTERY_STATUSES[CustomHelper::BATTERY_STATUS_RETURNED]) ||
                        ($this->transactionLogService->isExist($battery, CustomHelper::BATTERY_STATUS_RETURNED))) {
                        $alreadyReturnedCount++;
                        $error['error']['already_delivered_error'] = ['message' => $alreadyReturnedCount . ' Battery(s) already returned!'];
                        continue;
                    }

                    $this->transactionLogService->createTransactionLog($battery, CustomHelper::BATTERY_STATUS_RETURNED);
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

    /**
     * @param array $filters
     * @param string|null $filename
     * @param null $manufacturer
     * @return int|mixed|string
     * @throws \Exception
     */
    public function getBatteriesByFilters(array $filters, string &$filename = null, $manufacturer = null)
    {
        // get only valid fields from the filters array and return it as selected fields
        $validFilters = CustomHelper::getValidValuesByFilters($filters);
        $dqlStatement = '';
        $this->filterByManufacturer($dqlStatement, $validFilters, $manufacturer);
        $this->filterByMode($dqlStatement, $validFilters);
        $this->filterByType($dqlStatement, $validFilters);
        $this->filterByDates($dqlStatement, $validFilters, $filename);
        $this->filterByNominalVoltage($dqlStatement, $validFilters);
        $this->filterByNominalCapacity($dqlStatement, $validFilters);
        $this->filterByNominalEnergy($dqlStatement, $validFilters);
        $this->filterByTrayNumber($dqlStatement, $validFilters);
        $this->filterBySearchText($dqlStatement, $validFilters);
        return $this->batteryRepository->getBatteriesByFilters($dqlStatement);
    }

    /**
     * @param array $filters
     * @param Manufacturer|null $manufacturer
     * @return int|mixed|string
     * @throws \Exception
     */
    public function getBatteriesArrayByFilters(array $filters, ?Manufacturer $manufacturer = null)
    {
        // get only valid fields from the filters array and return it as selected fields
        $validFilters = CustomHelper::getValidValuesByFilters($filters);
        $dqlStatement = '';
        $this->filterByManufacturer($dqlStatement, $validFilters, $manufacturer);
        $this->filterByMode($dqlStatement, $validFilters);
        $this->filterByType($dqlStatement, $validFilters);
        $this->filterByDates($dqlStatement, $validFilters);
        $this->filterByNominalVoltage($dqlStatement, $validFilters);
        $this->filterByNominalCapacity($dqlStatement, $validFilters);
        $this->filterByNominalEnergy($dqlStatement, $validFilters);
        $this->filterByTrayNumber($dqlStatement, $validFilters);
        $this->filterBySearchText($dqlStatement, $validFilters);
        return $this->batteryRepository->getBatteriesArrayByFilters($dqlStatement);
    }

    /**
     * @param string $dqlStatement
     * @param array $validFilters
     * @param Manufacturer|null $manufacturer
     */
    private function filterByManufacturer(string &$dqlStatement, array $validFilters, ?Manufacturer $manufacturer = null)
    {
        if (isset($validFilters['manufacturer'])) {
            $dqlStatement .= "(m.name = '" . $validFilters['manufacturer'] . "')";
        } elseif (!empty($manufacturer)) {
            $dqlStatement .= "(m.name = '" . $manufacturer->getName() . "')";
        }
    }

    /**
     * @param string $dqlStatement
     * @param array $validFilters
     */
    private function filterByMode(string &$dqlStatement, array $validFilters)
    {
        if (isset($validFilters['mode']) && CustomHelper::validateReportMode($validFilters['mode'])) {
            if (!empty($dqlStatement)) {
                $dqlStatement .= "AND ";
            }

            $dqlStatement .= "(b.status = '" . $validFilters['mode'] . "')";
        }
    }

    /**
     * @param string $dqlStatement
     * @param array $validFilters
     */
    private function filterByTrayNumber(string &$dqlStatement, array $validFilters)
    {
        if (isset($validFilters['tray_number']) && !empty($validFilters['tray_number'])) {
            $dqlStatement .= " AND (b.trayNumber Like '%" . $validFilters['tray_number'] . "%')";
        }
    }

    /**
     * @param string $dqlStatement
     * @param array $validFilters
     */
    private function filterBySearchText(string &$dqlStatement, array $validFilters)
    {
        if (isset($validFilters['search_text']) && !empty($validFilters['search_text'])) {
            $dqlStatement .= " AND (b.cellType Like '%" . $validFilters['search_text'] . "%' OR b.moduleType Like '%" . $validFilters['search_text'] . "%')";
        }
    }

    /**
     * @param string $dqlStatement
     * @param array $validFilters
     */
    private function filterByType(string &$dqlStatement, array $validFilters)
    {
        if (isset($validFilters['type'])) {
            if ($validFilters['type'] === 'all') {
                return;
            }

            $dqlStatement .= " AND (bt.type = '" . $validFilters['type'] . "') ";
        }
    }

    /**
     * @param string $dqlStatement
     * @param array $validFilters
     * @param string|null $filename
     * @throws \Exception
     */
    private function filterByDates(string &$dqlStatement, array $validFilters, string &$filename = null)
    {
        if (isset($validFilters['period'])) {
            $dates = explode(' - ', $validFilters['period']);
            $startDate = (new \DateTime($dates[0]))->format('Y-m-d');
            $endDate = (new \DateTime('+1 day' . $dates[1]))->format('Y-m-d');

            if (!empty($dqlStatement)) {
                $dqlStatement .= "AND ";
            }

            $dqlStatement .= "(b.created BETWEEN '" . $startDate . "' AND '" . $endDate . "')";

            if (!empty($filename)) {
                $filename .= $startDate . ' - ' . (new \DateTime($dates[1]))->format('Y-m-d');
            }
        }
    }

    /**
     * @param Manufacturer|null $manufacturer
     * @return int|mixed|string|null
     */
    public function updateBulkImportField(?Manufacturer $manufacturer)
    {
        try {
            if (empty($manufacturer)) {
                return null;
            }

            return $this->batteryRepository->updateBulkImportField($manufacturer);
        } catch (\Exception $exception) {
            $this->logger->error('[ERROR][UPDATE BATTERY FIELD]' . $exception->getMessage());
        }
    }

    /**
     * @param string $dqlStatement
     * @param array $validFilters
     */
    private function filterByNominalCapacity(string &$dqlStatement, array $validFilters)
    {
        if (isset($validFilters['nominal_capacity_range'])) {
            $ratings = explode(',', $validFilters['nominal_capacity_range']);

            if (!empty($ratings[1])) {
                $dqlStatement .= " AND (b.nominalCapacity BETWEEN " . $ratings[0] . " AND " . $ratings[1] . ")";
            }
        }
    }

    /**
     * @param string $dqlStatement
     * @param array $validFilters
     */
    private function filterByNominalVoltage(string &$dqlStatement, array $validFilters)
    {
        if (isset($validFilters['nominal_voltage_range'])) {
            $ratings = explode(',', $validFilters['nominal_voltage_range']);

            if (!empty($ratings[1]) ) {
                $dqlStatement .= " AND (b.nominalVoltage BETWEEN " . $ratings[0] . " AND " . $ratings[1] . ")";
            }
        }
    }

    /**
     * @param string $dqlStatement
     * @param array $validFilters
     */
    private function filterByNominalEnergy(string &$dqlStatement, array $validFilters)
    {
        if (isset($validFilters['nominal_energy_range'])) {
            $ratings = explode(',', $validFilters['nominal_energy_range']);

            if (!empty($ratings[1])) {
                $dqlStatement .= " AND (b.nominalEnergy BETWEEN " . $ratings[0] . " AND " . $ratings[1] . ")";
            }
        }
    }
}
