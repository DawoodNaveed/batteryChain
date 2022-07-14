<?php

namespace App\Command;

use App\Repository\TransactionLogRepository;
use App\Service\BlockchainService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CreateTransactionHashCommand
 * @package App\Command
 * @property TransactionLogRepository transactionLogRepository
 * @property BlockchainService blockchainService
 */
class CreateTransactionHashCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:create-transaction-hash';

    /**
     * CreateTransactionHashCommand constructor.
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
        $log = $this->transactionLogRepository->getTransactionToCreateHash();
        $this->blockchainService->createTransactionHash($log);

    }
}