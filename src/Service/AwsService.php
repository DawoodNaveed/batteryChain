<?php

namespace App\Service;

use Aws\S3\S3Client;

/**
 * Class AwsService
 * @package App\Service
 * @property S3Client awsClient
 * @property $awsBucket
 */
class AwsService
{
    const AWS_GET_OBJECT = 'GetObject';
    const AWS_BUCKET = 'Bucket';
    const AWS_KEY = 'Key';
    const AWS_PRE_SIGNED_URL_EXPIRY = '+120 minutes';

    /**
     * AwsService constructor.
     * @param S3Client $awsClient
     * @param $awsBucket
     */
    public function __construct(S3Client $awsClient, $awsBucket)
    {
        $this->awsClient = $awsClient;
        $this->awsBucket = $awsBucket;
    }

    /**
     * @param string $key
     * @param string $folder
     * @param string|null $bucket
     * @return string
     */
    public function getPreSignedUrl(string $key, string $folder, ?string $bucket = null): string
    {
        $command = $this->awsClient->getCommand(
            self::AWS_GET_OBJECT,
            [
                self::AWS_BUCKET => $bucket ?? $this->awsBucket,
                self::AWS_KEY => $folder . $key,
            ]
        );
        $request = $this->awsClient->createPresignedRequest($command, self::AWS_PRE_SIGNED_URL_EXPIRY);

        return (string)$request->getUri() ?? '';
    }
}