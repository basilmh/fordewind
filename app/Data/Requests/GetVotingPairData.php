<?php

namespace App\Data\Requests;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class GetVotingPairData extends Data
{
    public function __construct(
        #[Validation\Required, Validation\StringType, Validation\Max(100), Validation\Exists('cars', 'make')]
        public string $make,
        #[Validation\Required, Validation\StringType, Validation\Max(100), Validation\Exists('cars', 'model')]
        public string $model,
    ) {}
}
