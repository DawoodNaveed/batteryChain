<?php

namespace App\Repository;

use App\Entity\BatteryType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class BatteryTypeRepository
 * @package App\Repository
 * @method BatteryType|null find($id, $lockMode = null, $lockVersion = null)
 * @method BatteryType|null findOneBy(array $criteria, array $orderBy = null)
 * @method BatteryType[]    findAll()
 * @method BatteryType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BatteryTypeRepository extends ServiceEntityRepository
{
    /**
     * BatteryTypeRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BatteryType::class);
    }
}