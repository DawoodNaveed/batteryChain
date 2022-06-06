<?php

namespace App\Repository;

use App\Entity\Battery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
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
     * @throws DBALException|Exception
     */
    public function createNewBattery($values)
    {
        $query = "INSERT INTO battery" .
            "(serial_number, battery_type, nominal_voltage, nominal_capacity, nominal_energy, cycle_life, height, width, mass, status, manufacturer_id, current_owner, created, updated)" .
            " VALUES " . $values;
        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->prepare($query);
        $stmt->execute();
    }
}