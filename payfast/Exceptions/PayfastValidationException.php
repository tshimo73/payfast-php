<?php

namespace TshimologoMoeng\Payfast\Exceptions;

use Exception;
use Throwable;

class PayfastValidationException extends Exception
{
    protected array $errors = [];

    const INVALID_PARAMETER = 1000;

    public function __construct(
        string $message = "", 
        array $errors = [], 
        int $code = 0 ,
        Throwable|null $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    function getErrors() : array {
        return $this->errors;
    }
}
