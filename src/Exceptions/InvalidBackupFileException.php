<?php

namespace AMDarter\SimplyBackItUp\Exceptions;

class InvalidBackupFileException extends \Exception
{
    public function __construct($message = 'Invalid backup file', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}