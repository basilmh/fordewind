<?php

namespace App\Data\Responses;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class CarModelData extends Data
{
    public function __construct(
        public string $model,
        public int $carsCount,
        public bool $isVotable,
    ) {}
}
