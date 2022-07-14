<?php

namespace App\Command;

use App\Entity\TransactionLog;
use App\Helper\CustomHelper;
use App\Repository\TransactionLogRepository;
use App\Service\BlockchainService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CreateTransactionHashCommand
 * @package App\Command
 * @property TransactionLogRepository transactionLogRepository
 * @property BlockchainService blockchainService
 * @property LoggerInterface logger
 */
class CreateTransactionHashCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:create-transaction-hash';

    /**
     * CreateTransactionHashCommand constructor.
     * @param TransactionLogRepository $transactionLogRepository
     * @param BlockchainService $blockchainService
     * @param LoggerInterface $logger
     */
    public function __construct(
        TransactionLogRepository $transactionLogRepository,
        BlockchainService $blockchainService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->transactionLogRepository = $transactionLogRepository;
        $this->blockchainService = $blockchainService;
        $this->logger = $logger;
    }

    /**
     * Configure Command
     */
    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Command to create transaction hash');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var TransactionLog $log */
        $log = $this->transactionLogRepository->getTransactionToCreateHash();

        if (!empty($log) && isset($log[0])) {
            /** @var TransactionLog $log */
            $log = $log[0];
            $response = $this->blockchainService->createTransactionHash($log);

            if ($response[CustomHelper::STATUS] === CustomHelper::STATUS_SUCCESS) {
                $this->transactionLogRepository
                    ->updateTransactionLog(
                        $log,
                        null,
                        $response[CustomHelper::DATA][CustomHelper::TRANSACTION_HASH]);
            } else {
                $this->logger->error('[ERROR:CREATE TRANSACTION HASH]:', $response);
            }
        }
    }
}