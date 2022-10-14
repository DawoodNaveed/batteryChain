<?php

namespace App\Repository;

use App\Entity\Manufacturer;
use App\Entity\User;
use App\Helper\CustomHelper;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class ManufacturerRepository
 * @package App\Repository
 * @method Manufacturer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Manufacturer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Manufacturer[]    findAll()
 * @method Manufacturer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ManufacturerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Manufacturer::class);
    }

    /**
     * @param User $user
     */
    public function createBasicManufacturer(User $user)
    {
        $isNew = false;
        if (empty($user->getManufacturer())) {
            $manufacturer = new Manufacturer();
            $manufacturer->setCreated(new DateTime('now'));
            $isNew = true;
        } else {
            $manufacturer = $user->getManufacturer();
        }

        $manufacturer->setUser($user);
        $manufacturer->setUpdated(new DateTime('now'));
        $manufacturer->setName($user->getFullName());
        $manufacturer->setIdentifier(
            CustomHelper::generateRandomString()
        );

        if ($isNew) {
            $this->_em->persist($manufacturer);
        }

        $this->_em->flush();
    }

    /**
     * @param User $user
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeManufacturer(User $user)
    {
        if (!empty($user->getManufacturer())) {
            $manufacturer = $user->getManufacturer();
            $this->_em->remove($manufacturer);
            $this->_em->flush();
        }
    }

    /**
     * @param User $user
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function updateBasicManufacturer(User $user)
    {
        if (!empty($user->getManufacturer())) {
            $manufacturer = $user->getManufacturer();
            $manufacturer->setName($user->getFullName());
            $manufacturer->setUpdated(new DateTime('now'));
            $this->_em->flush();
        }
    }
}