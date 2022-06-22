<?php

namespace App\Repository;

use App\Entity\Manufacturer;
use App\Entity\Recycler;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
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

    /**
     * @param Recycler|null $recycler
     * @param array $data
     * @param Manufacturer|null $manufacturer
     * @return Recycler
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createOrUpdateRecycler(?Recycler $recycler, array $data, ?Manufacturer $manufacturer = null): Recycler
    {
        $isNew = false;

        if (!$recycler instanceof Recycler) {
            $recycler = new Recycler();
            $isNew = true;
        }

        if (!empty($data['updated_email']) && $data['updated_email'] !== $data['email'])
        {
            $recycler->setEmail($data['updated_email']);
        }

        if (!empty($data['name']))
        {
            $recycler->setName($data['name']);
        }

        if (!empty($data['contact']))
        {
            $recycler->setContact($data['contact']);
        }

        if (!empty($data['address']))
        {
            $recycler->setAddress($data['address']);
        }

        if (!empty($data['city']))
        {
            $recycler->setCity($data['city']);
        }

        if (!empty($data['country']))
        {
            $recycler->setCountry($data['country']);
        }

        if ($isNew) {
            $recycler->setCreated(new \DateTime('now'));
            $this->_em->persist($recycler);
        }

        if (!empty($manufacturer)) {
            $recycler->addManufacturer($manufacturer);
        }

        $recycler->setUpdated(new \DateTime('now'));
        $this->_em->flush();

        return $recycler;
    }

    /**
     * @param array $params
     * @return Recycler|null
     * @throws NonUniqueResultException
     */
    public function fetchRecyclerViaParams(array $params): ?Recycler
    {
        $qb = $this->createQueryBuilder('recycler')
            ->join('recycler.manufacturers', 'manufacturers')
            ->where('recycler.email = :email')
            ->andWhere('recycler.country = :country')
            ->setParameter('email', $params['email'])
            ->setParameter('country', $params['country']);

        if (isset($params['manufacturer']) && !empty($params['manufacturer'])) {
            $qb
                ->andWhere('manufacturers = :manufacturer')
                ->setParameter('manufacturer', $params['manufacturer']);
        }

        return $qb
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }
}