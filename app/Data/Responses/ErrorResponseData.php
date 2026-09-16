<?php

namespace App\Data\Responses;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class ErrorResponseData extends Data
{
    /** @param array<string, list<string>> $errors */
    public function __construct(
        public string $message,
        public array $errors = [],
    ) {}
}
