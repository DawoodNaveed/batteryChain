<?php

namespace App\Repository;

use App\Entity\Battery;
use App\Entity\BatteryReturn;
use App\Entity\Recycler;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class BatteryReturnRepository
 * @package App\Repository
 * @method BatteryReturn|null find($id, $lockMode = null, $lockVersion = null)
 * @method BatteryReturn|null findOneBy(array $criteria, array $orderBy = null)
 * @method BatteryReturn[]    findAll()
 * @method BatteryReturn[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BatteryReturnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BatteryReturn::class);
    }

    /**
     * @param User $user
     * @param Battery $battery
     * @param Recycler $recycler
     * @return BatteryReturn
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createReturn(User $user, Battery $battery, Recycler $recycler): BatteryReturn
    {
        $shipment = new BatteryReturn();
        $shipment->setUpdated(new \DateTime('now'));
        $shipment->setCreated(new \DateTime('now'));
        $shipment->setReturnDate(new \DateTime('now'));
        $shipment->setReturnFrom($user);
        $shipment->setReturnTo($recycler);
        $shipment->setBattery($battery);
        $this->_em->persist($shipment);
        $this->_em->flush();

        return $shipment;
    }
}