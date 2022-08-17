<?php

namespace App\Twig;

use App\Service\AwsService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class PreSignedUrlExtension
 * @package App\Twig
 * @property AwsService awsService
 */
class PreSignedUrlExtension extends AbstractExtension
{
    /**
     * PreSignedUrlExtension constructor.
     * @param AwsService $awsService
     */
    public function __construct(AwsService $awsService)
    {
        $this->awsService = $awsService;
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('preSignedUrl', [$this, 'preSignedUrl']),
        ];
    }

    /**
     * @param $folder
     * @param $key
     * @return string
     */
    public function preSignedUrl($key, $folder): string
    {
        return $this->awsService->getPreSignedUrl($key, $folder);
    }
}