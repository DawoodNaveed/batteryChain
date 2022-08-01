<?php

namespace App\Repository;

use App\Entity\Battery;
use App\Entity\Shipment;
use App\Entity\TransactionLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class ShipmentRepository
 * @package App\Repository
 * @method Shipment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Shipment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Shipment[]    findAll()
 * @method Shipment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShipmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shipment::class);
    }

    /**
     * @param User $user
     * @param Battery $battery
     * @param TransactionLog|null $transactionLog
     * @return Shipment
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createShipment(User $user, Battery $battery, ?TransactionLog $transactionLog): Shipment
    {
        $shipment = new Shipment();
        $shipment->setUpdated(new \DateTime('now'));
        $shipment->setCreated(new \DateTime('now'));
        $shipment->setShipmentDate(new \DateTime('now'));
        $shipment->setShipmentFrom($user);
        $shipment->setShipmentTo($user);
        $shipment->setBattery($battery);
        $shipment->setTransactionLog($transactionLog);
        $this->_em->persist($shipment);
        $this->_em->flush();

        return $shipment;
    }
}