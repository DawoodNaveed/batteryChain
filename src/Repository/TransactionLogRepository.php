<?php

namespace App\Repository;

use App\Entity\Battery;
use App\Entity\Recycler;
use App\Entity\TransactionLog;
use App\Entity\User;
use App\Helper\CustomHelper;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class TransactionLogRepository
 * @package App\Repository
 * @method TransactionLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method TransactionLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method TransactionLog[]    findAll()
 * @method TransactionLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransactionLog::class);
    }

    /**
     * @param string $status
     * @return array|null
     */
    public function getTransaction($status = CustomHelper::STATUS_PENDING): ?array
    {
        return $this->createQueryBuilder('tl')
            ->where('tl.transactionHash is not null')
            ->andWhere('tl.status = :status')
            ->setParameter('status', $status)
            ->setMaxResults(1)
            ->orderBy('tl.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param TransactionLog|null $log
     * @param string|null $status
     * @param string|null $transactionHash
     * @param string|null $metadata
     */
    public function updateTransactionLog(
        ?TransactionLog $log,
        ?string $status = null,
        ?string $transactionHash = null,
        ?string $metadata = null
    ) {
        if (!empty($log)) {
            if (!empty($status)) {
                $log->setStatus($status);

                if ($status === CustomHelper::STATUS_COMPLETE) {
                    $log->getBattery()->setBlockchainSecured(true);
                    $log->getBattery()->setStatus($log->getTransactionType());
                    $log->getBattery()->setUpdated(new DateTime('now'));
                }
            }

            if (!empty($transactionHash)) {
                $log->setTransactionHash($transactionHash);
            }

            if (!empty($metadata)) {
                $log->setMetaData($metadata);
            }

            $log->setUpdated(new DateTime('now'));
            $this->_em->flush();
        }
    }

    /**
     * @return array|null
     */
    public function getTransactionToCreateHash(): ?array
    {
        return $this->createQueryBuilder('tl')
            ->where('tl.transactionHash is null')
            ->orderBy('tl.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Battery $battery
     * @param string $transactionType
     * @param string $status
     * @return TransactionLog
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createTransactionLog(Battery $battery, string $transactionType, string $status = CustomHelper::STATUS_PENDING): TransactionLog
    {
        $transactionLog = new TransactionLog();
        $transactionLog->setBattery($battery);
        $transactionLog->setTransactionType($transactionType);
        $transactionLog->setStatus($status);
        $transactionLog->setCreated(new DateTime('now'));
        $transactionLog->setUpdated(new DateTime('now'));

        $this->_em->persist($transactionLog);
        $this->_em->flush();

        return $transactionLog;
    }

    /**
     * @param Battery $battery
     * @param User $user
     * @param array|null $formData
     * @param string $transactionType
     * @param string $status
     * @param DateTime|null $deliveryDate
     * @return TransactionLog
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createDeliveryTransactionLog(
        Battery $battery,
        User $user,
        ?array $formData,
        string $transactionType,
        string $status = CustomHelper::STATUS_PENDING,
        ?DateTime $deliveryDate = null): TransactionLog
    {
        $transactionLog = new TransactionLog();
        $transactionLog->setBattery($battery);
        $transactionLog->setTransactionType($transactionType);
        $transactionLog->setFromUser($user);
        $transactionLog->setStatus($status);
        $transactionLog->setCreated(new DateTime('now'));
        $transactionLog->setUpdated(new DateTime('now'));

        if (empty($deliveryDate)) {
            $transactionLog->setDeliveryDate(new DateTime('now'));
        } else {
            $transactionLog->setDeliveryDate($deliveryDate);
        }

        if (!empty($formData)) {
            $transactionLog->setAddress($formData['address'] ?? null);
            $transactionLog->setPostalCode($formData['postalCode'] ?? null);
            $transactionLog->setCity($formData['city'] ?? null);
            $transactionLog->setCountry($formData['country'] ?? null);
        }

        $this->_em->persist($transactionLog);
        $this->_em->flush();

        return $transactionLog;
    }

    /**
     * @param Battery $battery
     * @param User $user
     * @param Recycler|null $recycler
     * @param array|null $formData
     * @param string $transactionType
     * @param string $status
     * @return TransactionLog
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createReturnTransactionLog(
        Battery $battery,
        User $user,
        ?Recycler $recycler,
        ?array $formData,
        string $transactionType,
        string $status = CustomHelper::STATUS_PENDING
    ): TransactionLog {
        $transactionLog = new TransactionLog();
        $transactionLog->setBattery($battery);
        $transactionLog->setTransactionType($transactionType);
        $transactionLog->setFromUser($user);
        $transactionLog->setReturnTo($recycler);
        $transactionLog->setStatus($status);
        $transactionLog->setCreated(new DateTime('now'));
        $transactionLog->setUpdated(new DateTime('now'));

        if (!empty($formData)) {
            $transactionLog->setAddress($formData['address'] ?? null);
            $transactionLog->setPostalCode($formData['postalCode'] ?? null);
            $transactionLog->setCity($formData['city'] ?? null);
            $transactionLog->setCountry($formData['country'] ?? null);
        }

        $this->_em->persist($transactionLog);
        $this->_em->flush();

        return $transactionLog;
    }
}