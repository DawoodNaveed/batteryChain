<?php

namespace App\Service;

use App\Entity\Battery;
use App\Entity\Import;
use App\Entity\Manufacturer;
use App\Entity\Recycler;
use App\Entity\User;
use App\Enum\BulkImportEnum;
use App\Enum\RoleEnum;
use App\Helper\CustomHelper;
use App\Repository\BatteryRepository;
use Doctrine\ORM\Query\Expr\Join;
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
 * @property ManufacturerService manufacturerService
 * @property ModifiedBatteryService modifiedBatteryService
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
     * @param ManufacturerService $manufacturerService
     * @param ModifiedBatteryService $modifiedBatteryService
     * @param LoggerInterface $logger
     * @param $csvFileUploadSize
     */
    public function __construct(
        BatteryRepository $batteryRepository,
        ShipmentService $shipmentService,
        BatteryReturnService $returnService,
        TransactionLogService $transactionLogService,
        ManufacturerService $manufacturerService,
        ModifiedBatteryService $modifiedBatteryService,
        LoggerInterface $logger,
        $csvFileUploadSize
    ) {
        $this->batteryRepository = $batteryRepository;
        $this->shipmentService = $shipmentService;
        $this->returnService = $returnService;
        $this->transactionLogService = $transactionLogService;
        $this->manufacturerService = $manufacturerService;
        $this->modifiedBatteryService = $modifiedBatteryService;
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
     * @param Import $importObj
     * @param Manufacturer $manufacturer
     * @param null $currentPossessorId
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function extractCsvAndCreateBatteries(Import $importObj, Manufacturer $manufacturer, $currentPossessorId = null): array
    {
        if (($handle = fopen($importObj->getCsvFile(), BulkImportEnum::READ_MODE)) !== false) {
            $csvHeaders = fgetcsv($handle, 1000, BulkImportEnum::CSV_SEPARATOR);
            $rowCount = 1;
            $values = '';

            while (($csvData = fgetcsv($handle, 1000, BulkImportEnum::CSV_SEPARATOR)) !== false) {
                $row = [];

                for ($headerIndex = 0; $headerIndex < count($csvHeaders); $headerIndex++) {
                    $row[trim($csvHeaders[$headerIndex])] = $csvData[$headerIndex];
                }

                $serialNumber = trim((string) $row['serial_number']);
                $internalSerialNumber = $manufacturer->getIdentifier() . '-' . $serialNumber;
                $batteryType = (string) $row['battery_type'];
                $cellType = (string) $row['cell_type'] ?? null;
                $moduleType = (string) $row['module_type'] ?? null;
                $trayNumber = (string) $row['tray_number'] ?? null;
                $productionDate = (string) $row['production_date'] ?? null;
                $deliveryDate = (string) $row['delivery_date'] ?? null;
                $nominalVoltage = (float) $row['nominal_voltage'];
                $nominalCapacity = (float) $row['nominal_capacity'];
                $nominalEnergy = (float) $row['nominal_energy'];
                $acidVolume = (float) $row['acid_volume'] ?? 0;
                $co2 = ((float) $row['CO2']) ?? 0;
                $height = ((float) $row['height']) ?? 0;
                $width = ((float) $row['width']) ?? 0;
                $length = ((float) $row['length']) ?? 0;
                $mass = (float) $row['mass'];
                $isInsured = (int) $row['is_insured'] ?? 0;
                $isClimateNeutral = (int) $row['is_climate_neutral'] ?? 0;
                $status = CustomHelper::BATTERY_STATUS_PRE_REGISTERED;
                $currentPossessorId = $currentPossessorId ?? $manufacturer->getUser()->getId();

                $date = (new \DateTime($productionDate))->format('Y-m-d H:i:s');
                $deliveryDate = (new \DateTime($deliveryDate))->format('Y-m-d H:i:s');
                $values .= "( '" . $serialNumber . "', '" . $internalSerialNumber . "', '" . $batteryType . "', '" . $cellType .
                    "', '" . $moduleType . "', '" . $trayNumber . "', '" . $date .
                    "', '" . $nominalVoltage . "', '" . $nominalCapacity . "', '" . $nominalEnergy .
                    "', '" . $acidVolume . "', '" . $co2 . "', '" . 1 . "', '" . $isInsured . "', '" . $isClimateNeutral
                    . "', '" . $height . "', '" . $width  . "', '" . $length . "', '" . $mass . "', '" . $status
                    . "', '" . $manufacturer->getId() . "', '" . $currentPossessorId . "', '" . $importObj->getId() .
                    "', '" . $deliveryDate . "', now(), now()), ";

                $rowCount++;

                if ($rowCount % 25 === 0) {
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

        if (!empty($error) && $error[CustomHelper::ERROR]) {
            return $error;
        }

        return [ BulkImportEnum::ERROR => CustomHelper::NO_ERROR ];
    }

    /**
     * @param $values
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function createBatteryEntries($values): array
    {
        $error = [];
        try {
            $this->batteryRepository->createNewBattery($values);
        } catch (Exception $e) {
            $this->logger->error('[ERROR][BULK IMPORT]:' . $e->getMessage());
            $error = [
                BulkImportEnum::ERROR => CustomHelper::ERROR,
                BulkImportEnum::MESSAGE => 'service.error.something_went_wrong'
            ];
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
                    $modification = false;
                    $manufacturerIdentifier = null;
                    if (count($csvData) !== count(CustomHelper::DELIVERY_CSV_HEADERS)) {
                        $error['error']['invalid_csv'] = ['message' => 'service.error.invalid_csv'];
                        return $error;
                    }

                    $row = [];

                    for ($headerIndex = 0; $headerIndex < count($csvHeaders); $headerIndex++) {
                        $row[trim($csvHeaders[$headerIndex])] = $csvData[$headerIndex];
                    }

                    $rowCount++;

                    /* If user is admin, it is mandatory to have identifier */
                    if (empty($user->getManufacturer()) && empty($row['manufacturer_identifier'])) {
                        continue;
                    }

                    if (empty($user->getManufacturer()) && !empty($row['manufacturer_identifier'])) {
                        $manufacturerIdentifier = (string) $row['manufacturer_identifier'];
                    } elseif (!empty($user->getManufacturer()) && !empty($row['manufacturer_identifier']) && $user->getManufacturer()->getIdentifier() !== (string) $row['manufacturer_identifier']) {
                        $manufacturerIdentifier = (string) $row['manufacturer_identifier'];
                        $modification = true;
                    } elseif (!empty($user->getManufacturer()) && empty($row['manufacturer_identifier'])) {
                        $manufacturerIdentifier = $user->getManufacturer()->getIdentifier();
                    }

                    $batteryManufacturer = $this->manufacturerService->getManufactureByIdentifier($manufacturerIdentifier);
                    $battery = $this->fetchBatteryBySerialNumber(
                        (string) $row['serial_number'],
                        $batteryManufacturer,
                        $user->getManufacturer() ? false : true);

                    if (empty($battery) || $battery->getStatus() === CustomHelper::BATTERY_STATUS_PRE_REGISTERED) {
                        $notExistCount++;
                        $error['error']['not_exist_error'] = ['message' => $notExistCount . ' Battery(s) may not exist or registered!'];
                        continue;
                    }

                    if (CustomHelper::BATTERY_STATUSES[$battery->getStatus()] >
                        CustomHelper::BATTERY_STATUSES[CustomHelper::BATTERY_STATUS_DELIVERED]) {
                        $alreadyDeliveredCount++;
                        $error['error']['already_delivered_error'] = ['message' => $alreadyDeliveredCount . ' Battery(s) in returned/recycled state!'];
                        continue;
                    }

                    /* If Admin / Super Admin - we will use battery's manufacturer's User */
                    if (in_array(RoleEnum::ROLE_SUPER_ADMIN, $user->getRoles(), true) ||
                        in_array(RoleEnum::ROLE_ADMIN, $user->getRoles(), true)) {
                        $user = $battery->getManufacturer()->getUser();
                    }

                    $transactionLog = $this->transactionLogService
                        ->createDeliveryTransactionLog(
                            $battery,
                            $modification ? $batteryManufacturer->getUser() : $user,
                            null
                        );
                    $battery->setStatus(CustomHelper::BATTERY_STATUS_DELIVERED);
                    $battery->setUpdated(new \DateTime('now'));
                    $battery->setCurrentPossessor($modification ? $batteryManufacturer->getUser() : $user);
                    $shipment = $this->shipmentService->createShipment($modification ? $batteryManufacturer->getUser() : $user, $battery, $transactionLog);

                    /* Create Modification Log */
                    if ($modification) {
                        $shipment->setShipmentTo($user);
                        $this->modifiedBatteryService
                            ->createModifiedBattery(
                                $battery,
                                $batteryManufacturer,
                                $user,
                                CustomHelper::BATTERY_STATUS_DELIVERED
                            );
                    }
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
     * @param Recycler|null $recycler
     * @return array|null
     */
    public function extractCsvAndAddReturns(UploadedFile $file, User $user, ?Recycler $recycler): ?array
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

                    $transactionLog = $this->transactionLogService
                        ->createReturnTransactionLog(
                            $battery,
                            $user,
                            $recycler instanceof Recycler ? $recycler : null,
                            null,
                            CustomHelper::BATTERY_STATUS_RETURNED
                        );
                    $battery->setStatus(CustomHelper::BATTERY_STATUS_RETURNED);
                    $battery->setUpdated(new \DateTime('now'));
                    $battery->setCurrentPossessor($user);
                    $return = $this->returnService->createReturn($user, $battery, $recycler, $transactionLog);
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
        $this->filterByReportModeDate($dqlStatement, $validFilters);
        $this->filterByNominalVoltage($dqlStatement, $validFilters);
        $this->filterByNominalCapacity($dqlStatement, $validFilters);
        $this->filterByNominalEnergy($dqlStatement, $validFilters);
        $this->filterByTrayNumber($dqlStatement, $validFilters);
        $this->filterBySearchText($dqlStatement, $validFilters);
        $this->filterByWidth($dqlStatement, $validFilters);
        $this->filterByHeight($dqlStatement, $validFilters);
        $this->filterByLength($dqlStatement, $validFilters);
        $this->filterByCo2($dqlStatement, $validFilters);
        $this->filterByAcidVolume($dqlStatement, $validFilters);
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
        $this->filterByReportModeDate($dqlStatement, $validFilters);
        $this->filterByNominalVoltage($dqlStatement, $validFilters);
        $this->filterByNominalCapacity($dqlStatement, $validFilters);
        $this->filterByNominalEnergy($dqlStatement, $validFilters);
        $this->filterByTrayNumber($dqlStatement, $validFilters);
        $this->filterBySearchText($dqlStatement, $validFilters);
        $this->filterByWidth($dqlStatement, $validFilters);
        $this->filterByHeight($dqlStatement, $validFilters);
        $this->filterByLength($dqlStatement, $validFilters);
        $this->filterByCo2($dqlStatement, $validFilters);
        $this->filterByAcidVolume($dqlStatement, $validFilters);
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

            $dqlStatement .= "(b.productionDate BETWEEN '" . $startDate . "' AND '" . $endDate . "')";

            if (!empty($filename)) {
                $filename .= $startDate . ' - ' . (new \DateTime($dates[1]))->format('Y-m-d');
            }
        }
    }

    /**
     * @param string $dqlStatement
     * @param array $validFilters
     * @throws \Exception
     */
    private function filterByReportModeDate(string &$dqlStatement, array $validFilters)
    {
        if (isset($validFilters['report_period'])) {
            $dates = explode(' - ', $validFilters['report_period']);
            $startDate = (new \DateTime($dates[0]))->format('Y-m-d');
            $endDate = (new \DateTime('+1 day' . $dates[1]))->format('Y-m-d');

            if (CustomHelper::validateReportMode($validFilters['mode'])) {
                if ($validFilters['mode'] === CustomHelper::BATTERY_STATUS_DELIVERED) {
                    $dqlStatement .= " AND (t.deliveryDate BETWEEN '" . $startDate . "' AND '" . $endDate . "' AND t.transactionType = '" . $validFilters['mode'] . "')";
                } else {
                    $dqlStatement .= " AND (t.created BETWEEN '" . $startDate . "' AND '" . $endDate . "' AND t.transactionType = '" . $validFilters['mode'] . "')";
                }
            } else {
                $dqlStatement .= " AND ((t.created BETWEEN '" . $startDate . "' AND '" . $endDate . "') OR (b.status = 'pre-registered' AND b.created BETWEEN '" . $startDate . "' AND '" . $endDate . "'))";
            }
        }
    }

    /**
     * @param Import $import
     * @return int|mixed|string|null
     */
    public function updateBulkImportField(Import $import)
    {
        try {
            if (empty($import)) {
                return null;
            }

            return $this->batteryRepository->updateBulkImportField($import);
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

    /**
     * @param string $dqlStatement
     * @param array $validFilters
     */
    private function filterByCo2(string &$dqlStatement, array $validFilters)
    {
        if (isset($validFilters['co2_range'])) {
            $ratings = explode(',', $validFilters['co2_range']);

            if (!empty($ratings[1])) {
                $dqlStatement .= " AND (b.co2 BETWEEN " . $ratings[0] . " AND " . $ratings[1] . ")";
            }
        }
    }

    /**
     * @param string $dqlStatement
     * @param array $validFilters
     */
    private function filterByAcidVolume(string &$dqlStatement, array $validFilters)
    {
        if (isset($validFilters['acid_volume_range'])) {
            $ratings = explode(',', $validFilters['acid_volume_range']);

            if (!empty($ratings[1])) {
                $dqlStatement .= " AND (b.acidVolume BETWEEN " . $ratings[0] . " AND " . $ratings[1] . ")";
            }
        }
    }

    /**
     * @param string $dqlStatement
     * @param array $validFilters
     */
    private function filterByWidth(string &$dqlStatement, array $validFilters)
    {
        if (isset($validFilters['width_range'])) {
            $ratings = explode(',', $validFilters['width_range']);

            if (!empty($ratings[1])) {
                $dqlStatement .= " AND (b.width BETWEEN " . $ratings[0] . " AND " . $ratings[1] . ")";
            }
        }
    }

    /**
     * @param string $dqlStatement
     * @param array $validFilters
     */
    private function filterByHeight(string &$dqlStatement, array $validFilters)
    {
        if (isset($validFilters['height_range'])) {
            $ratings = explode(',', $validFilters['height_range']);

            if (!empty($ratings[1])) {
                $dqlStatement .= " AND (b.height BETWEEN " . $ratings[0] . " AND " . $ratings[1] . ")";
            }
        }
    }

    /**
     * @param string $dqlStatement
     * @param array $validFilters
     */
    private function filterByLength(string &$dqlStatement, array $validFilters)
    {
        if (isset($validFilters['length_range'])) {
            $ratings = explode(',', $validFilters['length_range']);

            if (!empty($ratings[1])) {
                $dqlStatement .= " AND (b.length BETWEEN " . $ratings[0] . " AND " . $ratings[1] . ")";
            }
        }
    }

    /**
     * @param $ids
     * @return Battery[]|null
     */
    public function getBatteriesByIds($ids): ?array
    {
        return $this->batteryRepository->createQueryBuilder('b')
            ->select('DISTINCT b as battery', 'bt.type')
            ->join('b.manufacturer', 'm', Join::WITH, 'b.manufacturer = m.id')
            ->join('b.batteryType', 'bt', Join::WITH, 'b.batteryType = bt.id')
            ->leftJoin('b.transactionLogs', 't', Join::WITH, 'b.id = t.battery')
            ->where('b.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param UploadedFile $file
     * @param User $user
     * @param Recycler|null $recycler
     * @return array|null
     */
    public function extractCsvAndAddRecycles(UploadedFile $file, User $user, ?Recycler $recycler): ?array
    {
        try {
            $error = [];
            $rowCount = 1;

            if (($handle = fopen($file, "r")) !== false) {
                $csvHeaders = fgetcsv($handle, 1000, ",");

                if ($csvHeaders !== CustomHelper::RECYCLE_CSV_HEADERS) {
                    $error['error']['invalid_csv_header'] = ['message' => 'service.error.invalid_csv_headers'];
                    return $error;
                }

                $notExistCount = 0;
                $alreadyRecycledCount = 0;

                while (($csvData = fgetcsv($handle, 1000, ",")) !== false) {
                    if (count($csvData) !== count(CustomHelper::RECYCLE_CSV_HEADERS)) {
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

                    if ((CustomHelper::BATTERY_STATUSES[$battery->getStatus()] >=
                            CustomHelper::BATTERY_STATUSES[CustomHelper::BATTERY_STATUS_RECYCLED]) ||
                        ($this->transactionLogService->isExist($battery, CustomHelper::BATTERY_STATUS_RECYCLED))) {
                        $alreadyRecycledCount++;
                        $error['error']['already_delivered_error'] = ['message' => $alreadyRecycledCount . ' Battery(s) already recycled!'];
                        continue;
                    }

                    $battery->setStatus(CustomHelper::BATTERY_STATUS_RECYCLED);
                    $battery->setUpdated(new \DateTime('now'));
                    $battery->setCurrentPossessor($user);
                    $this->transactionLogService
                        ->createReturnTransactionLog(
                            $battery,
                            $user,
                            $recycler instanceof Recycler ? $recycler : null,
                            null,
                            CustomHelper::BATTERY_STATUS_RECYCLED
                        );
                }
            }

            fclose($handle);

            return array_merge($error, [
                'total' => ($rowCount - 1),
                'successful' => (($rowCount - 1) - ($notExistCount + $alreadyRecycledCount))
            ]);
        } catch (\Exception $exception) {
            $this->logger->error('[Bulk Return]' . $exception->getMessage());
        }

        return [];
    }
}
