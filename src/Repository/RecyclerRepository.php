<?php

namespace App\Repository;

use App\Entity\Country;
use App\Entity\Manufacturer;
use App\Entity\Recycler;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use PDO;

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
     * @param Manufacturer $manufacturer
     * @return Recycler[]|null
     */
    public function getRecyclersByManufacturer(Manufacturer $manufacturer)
    {
        return $this->createQueryBuilder('recycler')
            ->join('recycler.manufacturers', 'manufacturers')
            ->where('manufacturers = :manufacturer')
            ->setParameter('manufacturer', $manufacturer)
            ->getQuery()
            ->execute();
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
            $recycler->setEmail($data['email']);
            $isNew = true;
        }

        if (!$isNew && !empty($data['updated_email']) && $data['updated_email'] !== $data['email'])
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

        if (!empty($data['postal_code']))
        {
            $recycler->setPostalCode($data['postal_code']);
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
     * @param bool $fetchOneObject
     * @return Recycler|null|Recycler[]
     * @throws NonUniqueResultException
     */
    public function fetchRecyclerViaParams(array $params, $fetchOneObject = true)
    {
        $qb = $this->createQueryBuilder('recycler')
            ->where('recycler.country = :country')
            ->setParameter('country', $params['country']);

        if (isset($params['email']) && !empty($params['email'])) {
            $qb
                ->andWhere('recycler.email = :email')
                ->setParameter('email', $params['email']);
        }

        if (isset($params['manufacturer']) && !empty($params['manufacturer'])) {
            $qb
                ->join('recycler.manufacturers', 'manufacturers')
                ->andWhere('manufacturers = :manufacturer')
                ->setParameter('manufacturer', $params['manufacturer']);
        }

        if ($fetchOneObject) {
            return $qb
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();
        }

        return $qb
            ->getQuery()
            ->getResult();
    }


    /**
     * @param Country $country
     * @return array|mixed[]
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function fetchFallbackRecyclersByCountry(Country $country): array
    {
        $query = 'SELECT r.* from recycler r left outer join manufacturers_recyclers ' .
            'ON (r.id = manufacturers_recyclers.recycler_id) ' .
            'WHERE manufacturers_recyclers.manufacturer_id is null and r.country_id = ' . $country->getId() .' and r.deleted_at is null';
        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt = $stmt->executeQuery();

        return $stmt->fetchAllAssociative();
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function fetchFallbackRecyclers()
    {
        $query = 'SELECT r.name, r.address, r.contact, r.email, r.postal_code as postalCode, r.city, c.name as country_name from recycler r left outer join manufacturers_recyclers ' .
            'ON (r.id = manufacturers_recyclers.recycler_id) join country c ON r.country_id = c.id ' .
            'WHERE manufacturers_recyclers.manufacturer_id is null and r.deleted_at is null';
        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->executeQuery();

        return $stmt->executeQuery()->fetchAllAssociative();
    }
}