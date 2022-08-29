<?php

namespace App\Enum;

/**
 * Class BulkImportEnum
 * @package App\Enum
 */
class BulkImportEnum
{
    const PENDING = 'pending';
    const COMPLETE = 'complete';
    const VALIDATED = 'validated';
    const VALIDATING = 'validating';
    const IN_PROGRESS = 'in-progress';
    const ERROR = 'error';
    const MESSAGE = 'message';

    const READ_MODE = "r";
    const CSV_SEPARATOR = ",";
}