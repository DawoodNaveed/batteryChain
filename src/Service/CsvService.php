<?php

namespace App\Service;

use App\Entity\Recycler;

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
                (string) $line['battery']->getSerialNumber(),
                $line['battery']->getBatteryType(),
                $line['battery']->getCellType(),
                $line['battery']->getModuleType(),
                $line['battery']->getProductionDate()->format('Y-m-d'),
                $line['battery']->getTrayNumber(),
                $line['battery']->getNominalVoltage(),
                $line['battery']->getNominalCapacity(),
                $line['battery']->getNominalEnergy(),
                $line['battery']->getAcidVolume(),
                $line['battery']->getCo2(),
                ucwords($line['battery']->getStatus()),
            ];
            fputcsv($f, $data, $delimiter);
        }
    }

    /**
     * @param Recycler[] $recyclers
     * @param string $filename
     * @param string $delimiter
     */
    public function downloadRecyclersCsv($recyclers, $filename = "export.csv", $delimiter = ",")
    {
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        $f = fopen('php://output', 'w');
        $header = ['Name', 'Email', 'Address','Post Code', 'City', 'Country', 'Contact'];
        fputcsv($f, $header);
        /** @var Recycler $recycler */
        foreach ($recyclers as $recycler) {
            $data = [
                $recycler->getName(),
                $recycler->getEmail(),
                $recycler->getAddress(),
                $recycler->getPostalCode(),
                $recycler->getCity(),
                $recycler->getCountry()->getName(),
                (string) $recycler->getContact(),
            ];
            fputcsv($f, $data, $delimiter);
        }
    }

    /**
     * @param array $recyclers
     * @param string $filename
     * @param string $delimiter
     */
    public function downloadFallbackRecyclersCsv(array $recyclers, string $filename = "export.csv", string $delimiter = ",")
    {
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        $f = fopen('php://output', 'w');
        $header = ['Name', 'Email', 'Address', 'Post Code', 'City', 'Country', 'Contact'];
        fputcsv($f, $header);
        /** @var Recycler $recycler */
        foreach ($recyclers as $recycler) {
            $data = [
                $recycler['name'],
                $recycler['email'],
                $recycler['address'],
                $recycler['postalCode'],
                $recycler['city'],
                $recycler['country_name'],
                (string) $recycler['contact'],
            ];
            fputcsv($f, $data, $delimiter);
        }
    }
}