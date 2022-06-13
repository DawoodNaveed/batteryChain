<?php

namespace App\Repository;

use App\Entity\Recycler;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class RecyclerRepository
 * @package App\Repository
 * @method Recycler|null find($id, $lockMode = null, $lockVersion = null)
 * @method Recycler|null findOneBy(array $criteria, array $orderBy = null)
 * @method Recycler[]    findAll()
 * @method Recycler[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecyclerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recycler::class);
    }

    /**
     * @param int $id
     * @return int|mixed[]|string
     */
    public function getRecyclersByCountryId(int $id)
    {
        return $this->createQueryBuilder('recycler')
            ->where('recycler.country = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }
}