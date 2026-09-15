<?php

namespace App\Exceptions;

use RuntimeException;

final class CarImportValidationException extends RuntimeException
{
    /**
     * @param list<string> $errors
     */
    public function __construct(string $message, public readonly array $errors = [])
    {
        parent::__construct($message);
    }
}
