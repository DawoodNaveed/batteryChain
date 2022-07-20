<?php

namespace App\Service;

/**
 * Class CsvService
 * @package App\Service
 */
class CsvService
{
    /**
     * @param $array
     * @param string $filename
     * @param string $delimiter
     */
    public function arrayToCSVDownload($array, $filename = "export.csv", $delimiter = ",")
    {
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        $f = fopen('php://output', 'w');
        $header = ['Serial Number', 'Type', 'Cell Type', 'Module Type', 'Production Date'
            , 'Tray Number', 'Voltage', 'Capacity', 'Energy', 'Acid Volume', 'Co2 - Footprint', 'Status'];
        fputcsv($f, $header);
        foreach ($array as $line) {
            $data = [
                $line->getSerialNumber(),
                $line->getBatteryType(),
                $line->getCellType(),
                $line->getModuleType(),
                $line->getProductionDate()->format('Y-m-d'),
                $line->getTrayNumber(),
                $line->getNominalVoltage(),
                $line->getNominalCapacity(),
                $line->getNominalEnergy(),
                $line->getAcidVolume(),
                (empty($line->getCo2()) ? '' : $line->getCo2()),
                $line->getStatus(),
            ];
            fputcsv($f, $data, $delimiter);
        }
    }
}