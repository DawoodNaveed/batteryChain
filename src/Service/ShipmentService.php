<?php

namespace App\Service;

use App\Entity\Battery;
use App\Entity\Shipment;
use App\Entity\TransactionLog;
use App\Entity\User;
use App\Repository\ShipmentRepository;
use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * Class ShipmentService
 * @package App\Service
 * @property ShipmentRepository shipmentRepository
 */
class ShipmentService
{
    /**
     * ShipmentService constructor.
     * @param ShipmentRepository $shipmentRepository
     */
    public function __construct(ShipmentRepository $shipmentRepository)
    {
        $this->shipmentRepository = $shipmentRepository;
    }

    /**
     * @param User $user
     * @param Battery $battery
     * @param TransactionLog|null $transactionLog
     * @param DateTime|null $deliveryDate
     * @return Shipment
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createShipment(User $user, Battery $battery, ?TransactionLog $transactionLog, ?DateTime $deliveryDate = null): Shipment
    {
        return $this->shipmentRepository->createShipment($user, $battery, $transactionLog, $deliveryDate);
    }
}