<?php

namespace App\Service;

use Aws\Exception\AwsException;
use Aws\Sqs\SqsClient;
use Psr\Log\LoggerInterface;

/**
 * Class ImportQueueService
 * @package App\Service
 * @property SqsClient awsSqsClient
 * @property string $queueUrl
 * @property LoggerInterface logger
 */
class ImportQueueService
{
    public const VALIDATE_BULK_IMPORT = 'validate_bulk_import';
    public const BULK_IMPORT = 'bulk_import';

    /**
     * ImportQueueService constructor.
     * @param SqsClient $awsSqsClient
     * @param $queueUrl
     * @param LoggerInterface $logger
     */
    public function __construct(SqsClient $awsSqsClient, $queueUrl, LoggerInterface $logger)
    {
        $this->awsSqsClient = $awsSqsClient;
        $this->queueUrl = $queueUrl;
        $this->logger = $logger;
    }

    /**
     * @return array|null
     */
    public function receive(): ?array
    {
        $message = null;

        try {
            $result = $this->awsSqsClient->receiveMessage([
                'AttributeNames' => ['SentTimestamp'],
                'MaxNumberOfMessages' => 1,
                'MessageAttributeNames' => ['All'],
                'QueueUrl' => $this->queueUrl,
                'WaitTimeSeconds' => 20,
            ]);

            if (!empty($result->get('Messages'))) {
                $message = $result->get('Messages')[0];
                $result = $this->awsSqsClient->deleteMessage([
                    'QueueUrl' => $this->queueUrl,
                    'ReceiptHandle' => $result->get('Messages')[0]['ReceiptHandle']
                ]);
                $this->logger->info('aws_sqs_receive | import_queue | message.', [$message]);
            }
        } catch (AwsException $e) {
            $this->logger->error('aws_sqs_receive | import_queue | error.', [$e->getMessage()]);
        }

        return $message;
    }

    /**
     * @param $data
     */
    public function dispatchMessage($data)
    {
        $dataEncoded = json_encode($data);
        $params = [
            'MessageGroupId' => 1,
            'MessageDeduplicationId' => md5($dataEncoded),
            'MessageBody' => $dataEncoded,
            'QueueUrl' => $this->queueUrl
        ];
        $this->awsSqsClient->sendMessage($params);
    }

    /**
     * @param array $data
     * @return bool
     */
    public function dispatchValidateBulkImportRequest(array $data): bool
    {
        $data = array_merge(['type' => self::VALIDATE_BULK_IMPORT, 'time' => time()], $data);

        try {
            $this->dispatchMessage($data);
        } catch (AwsException $e) {
            $this->logger->error('aws_sqs_validate_bulk_import | error.', [$e->getMessage()]);
        }

        $this->logger->info('aws_sqs_validate_bulk_import | exiting method.', [__METHOD__]);

        return true;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function dispatchBulkImportRequest(array $data): bool
    {
        $data = array_merge(['type' => self::BULK_IMPORT, 'time' => time()], $data);

        try {
            $this->dispatchMessage($data);
        } catch (AwsException $e) {
            $this->logger->error('aws_sqs_bulk_import | error.', [$e->getMessage()]);
        }

        $this->logger->info('aws_sqs_bulk_import | exiting method.', [__METHOD__]);

        return true;
    }
}