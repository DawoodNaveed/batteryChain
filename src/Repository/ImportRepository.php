<?php

namespace App\Repository;

use App\Entity\Import;
use App\Entity\Manufacturer;
use App\Enum\BulkImportEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class ImportRepository
 * @package App\Repository
 * @method Import|null find($id, $lockMode = null, $lockVersion = null)
 * @method Import|null findOneBy(array $criteria, array $orderBy = null)
 * @method Import[]    findAll()
 * @method Import[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Import::class);
    }

    /**
     * @param Manufacturer $manufacturer
     * @param array $status
     * @return array|null
     */
    public function findOneByFilter(
        Manufacturer $manufacturer,
        array $status = [BulkImportEnum::COMPLETE, BulkImportEnum::ERROR]
    ): ?array {
        return $this->createQueryBuilder('import')
            ->where('import.manufacturer = :manufacturer')
            ->andWhere('import.status NOT IN (:status)')
            ->setParameter('manufacturer', $manufacturer)
            ->setParameter('status', $status)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Preventing from injecting Entity manager in service
     */
    public function flush()
    {
        $this->_em->flush();
    }

    /**
     * @param Import $importObject
     * @param string $status
     */
    public function updateStatus(Import $importObject, $status = 'pending')
    {
        $importObject->setStatus($status);
        $this->flush();
    }
}