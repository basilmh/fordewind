<?php

namespace App\Exceptions;

use RuntimeException;

final class VoteReplayException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This voting pair has already been submitted.');
    }
}
