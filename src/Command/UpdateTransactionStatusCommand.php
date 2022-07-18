<?php

namespace App\Command;

use App\Entity\TransactionLog;
use App\Helper\CustomHelper;
use App\Repository\TransactionLogRepository;
use App\Service\BlockchainService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdateTransactionStatusCommand
 * @package App\Command
 * @property TransactionLogRepository transactionLogRepository
 * @property BlockchainService blockchainService
 */
class UpdateTransactionStatusCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:update-transaction-status';

    /**
     * UpdateTransactionStatusCommand constructor.
     * @param TransactionLogRepository $transactionLogRepository
     * @param BlockchainService $blockchainService
     */
    public function __construct(
        TransactionLogRepository $transactionLogRepository,
        BlockchainService $blockchainService
    ) {
        parent::__construct();
        $this->transactionLogRepository = $transactionLogRepository;
        $this->blockchainService = $blockchainService;
    }

    /**
     * Configure Command
     */
    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Command to update transaction status');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $log = $this->transactionLogRepository->getTransaction();

            if (!empty($log) && isset($log[0])) {
                /** @var TransactionLog $log */
                $log = $log[0];
                $response = $this->blockchainService->getTransactionStatusByRef($log->getTransactionHash());
                $status = strtolower($response[CustomHelper::STATUS]);

                if ($status === CustomHelper::STATUS_PENDING &&
                    is_null(strtolower($response[CustomHelper::DATA]))) {
                    return 0;
                }

                $this->transactionLogRepository->updateTransactionLog($log, $status);

                return 1;
            }

            return 0;
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }
}