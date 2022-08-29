<?php

namespace App\Repository;

use App\Entity\Battery;
use App\Entity\Manufacturer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class BatteryRepository
 * @package App\Repository
 * @method Battery|null find($id, $lockMode = null, $lockVersion = null)
 * @method Battery|null findOneBy(array $criteria, array $orderBy = null)
 * @method Battery[]    findAll()
 * @method Battery[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BatteryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Battery::class);
    }

    /**
     * @param $values
     * @throws Exception
     */
    public function createNewBattery($values)
    {
        $query = "INSERT INTO battery" .
            "(serial_number, internal_serial_number, battery_type_id, cell_type, module_type, tray_number, production_date," .
            " nominal_voltage, nominal_capacity, nominal_energy, acid_volume," .
            " co2, is_bulk_import, is_insured, is_climate_neutral, height, width, length, mass, status," .
            "manufacturer_id, current_possessor_id, import_id, delivery_date, created, updated)" .
            " VALUES " . $values;
        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->prepare($query);
        $stmt->execute();
    }

    /**
     * @param string $serialNumber
     * @return Battery|null
     */
    public function isExist(string $serialNumber): ?Battery
    {
        return $this->findOneBy([
            'serialNumber' => $serialNumber
        ]);
    }

    /**
     * @param string $name
     */
    public function disableFilter(string $name)
    {
        $this->_em->getFilters()->disable($name);
    }

    /**
     * @param string $name
     */
    public function enableFilter(string $name)
    {
        $this->_em->getFilters()->enable($name);
    }

    /**
     * @param string $dqlStatement
     * @return int|mixed|string
     */
    public function getBatteriesByFilters(string $dqlStatement)
    {
        return $this->createQueryBuilder('b')
            ->select('DISTINCT b as battery', 'bt.type')
            ->join('b.manufacturer', 'm', Join::WITH, 'b.manufacturer = m.id')
            ->join('b.batteryType', 'bt', Join::WITH, 'b.batteryType = bt.id')
            ->leftJoin('b.transactionLogs', 't', Join::WITH, 'b.id = t.battery')
            ->where($dqlStatement)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $dqlStatement
     * @return int|mixed|string
     */
    public function getBatteriesArrayByFilters(string $dqlStatement)
    {
        return $this->createQueryBuilder('b')
            ->select('DISTINCT b as battery', 'bt.type')
            ->join('b.manufacturer', 'm', Join::WITH, 'b.manufacturer = m.id')
            ->join('b.batteryType', 'bt', Join::WITH, 'b.batteryType = bt.id')
            ->leftJoin('b.transactionLogs', 't', Join::WITH, 'b.id = t.battery')
            ->where($dqlStatement)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param Manufacturer $manufacturer
     * @return int|mixed|string
     */
    public function updateBulkImportField(Manufacturer $manufacturer)
    {
        return $this->createQueryBuilder('battery')
            ->update()
            ->set('battery.isBulkImport', 0)
            ->where('battery.manufacturer = :manufacturer')
            ->setParameter('manufacturer', $manufacturer)
            ->getQuery()
            ->execute();
    }

    /**
     * @param string $decryptedNumber
     * @return int|mixed|string
     */
    public function findBatteriesByBothSerialNumber(string $decryptedNumber)
    {
        $qb = $this->createQueryBuilder('battery');

        return
            $qb
                ->where($qb->expr()->orX(
                    $qb->expr()->eq('battery.serialNumber', ':serialNumber'),
                    $qb->expr()->eq('battery.internalSerialNumber', ':serialNumber')
                ))
                ->andWhere('battery.blockchainSecured = 1')
                ->setParameter('serialNumber', $decryptedNumber)
                ->getQuery()->getResult();
    }

    /**
     * @param string $decryptedNumber
     * @return Battery|null
     */
    public function findBatteryByInternalSerialNumber(string $decryptedNumber): ?Battery
    {
        return $this->findOneBy([
            'internalSerialNumber' => $decryptedNumber,
            'blockchainSecured' => true
        ]);
    }
}