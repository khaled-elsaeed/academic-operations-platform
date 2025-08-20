<?php

namespace App\Exceptions;

use Exception;

class SkipImportRowException extends Exception
{
    // Exception to indicate a row should be skipped during import
}
