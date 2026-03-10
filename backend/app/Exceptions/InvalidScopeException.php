<?php
namespace App\Exceptions;

use Exception;

class InvalidScopeException extends Exception
{
    public function __construct($message = 'Invalid Scope.', $code = 400, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
