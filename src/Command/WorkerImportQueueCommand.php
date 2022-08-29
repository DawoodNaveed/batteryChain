<?php

namespace App\Command;

use App\Service\ImportQueueService;
use App\Service\ImportService;
use App\Service\NotificationQueueService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class WorkerImportQueueCommand
 * @package App\Command
 * @property LoggerInterface logger
 * @property ImportQueueService importQueueService
 * @property ImportService importService
 */
class WorkerImportQueueCommand extends Command
{
    /**
     * Name of the command.
     */
    protected static $defaultName = 'app:worker:import';

    /**
     * WorkerImportQueueCommand constructor.
     * @param LoggerInterface $logger
     * @param ImportQueueService $importQueueService
     * @param ImportService $importService
     */
    public function __construct(
        LoggerInterface $logger,
        ImportQueueService $importQueueService,
        ImportService $importService
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->importQueueService = $importQueueService;
        $this->importService = $importService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Worker to process notification queue.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        while (true) {
            $message = $this->importQueueService->receive();
            if ($message !== null) {
                $data = json_decode($message['Body'], true);
                $this->logger->info(self::$defaultName . ' | Received message.', [$data]);

                if ($data['type'] === ImportQueueService::VALIDATE_BULK_IMPORT) {
                    $this->importService->validateImport($data["data"]);
                }

                if ($data['type'] === ImportQueueService::BULK_IMPORT) {
                    $this->importService->bulkImport($data["data"]);
                }
            }
        }
    }
}