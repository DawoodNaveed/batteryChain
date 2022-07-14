<?php

namespace App\Repository;

use App\Entity\TransactionLog;
use App\Helper\CustomHelper;
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
     * @return TransactionLog|null
     */
    public function getTransaction($status = CustomHelper::PENDING): ?TransactionLog
    {
        return $this->createQueryBuilder('tl')
            ->where('tl.transactionHash is not null')
            ->andWhere('tl.status = :status')
            ->setParameter('status', $status)
            ->setMaxResults(1)
            ->orderBy('tl.updated', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param TransactionLog|null $log
     * @param string $status
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function updateTransactionLog(?TransactionLog $log, string $status)
    {
        if (!empty($log)) {
            $log->setStatus($status);

            if ($status === CustomHelper::STATUS_COMPLETE) {
                $log->getBattery()->setBlockchainSecured(true);
            }

            $this->_em->flush();
        }
    }

    /**
     * @return TransactionLog|null
     */
    public function getTransactionToCreateHash(): ?TransactionLog
    {
        return $this->createQueryBuilder('tl')
            ->where('tl.transactionHash is null')
            ->orderBy('id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
    }
}