<?php

namespace App\Repository;

use App\Entity\Battery;
use App\Entity\Manufacturer;
use App\Entity\ModifiedBattery;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class ModifiedBatteryRepository
 * @package App\Repository
 * @method ModifiedBattery|null find($id, $lockMode = null, $lockVersion = null)
 * @method ModifiedBattery|null findOneBy(array $criteria, array $orderBy = null)
 * @method ModifiedBattery[]    findAll()
 * @method ModifiedBattery[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModifiedBatteryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModifiedBattery::class);
    }

    /**
     * @param Battery $battery
     * @param Manufacturer $manufacturer
     * @param User $user
     * @param string $action
     * @return ModifiedBattery
     */
    public function createModifiedBattery(Battery $battery, Manufacturer $manufacturer, User $user, string $action): ModifiedBattery
    {
        $modifiedBattery = new ModifiedBattery();
        $modifiedBattery->setBattery($battery);
        $modifiedBattery->setManufacturer($manufacturer);
        $modifiedBattery->setModifiedBy($user);
        $modifiedBattery->setAction($action);
        $modifiedBattery->setCreated(new DateTime('now'));
        $modifiedBattery->setUpdated(new DateTime('now'));

        $this->_em->persist($modifiedBattery);
        $this->_em->flush();

        return $modifiedBattery;
    }
}