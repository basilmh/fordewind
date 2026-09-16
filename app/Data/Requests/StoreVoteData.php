<?php

namespace App\Data\Requests;

use App\Enums\VoteSide;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class StoreVoteData extends Data
{
    public function __construct(
        #[Validation\Required, Validation\StringType, Validation\Max(100), Validation\Exists('cars', 'make')]
        public string $make,
        #[Validation\Required, Validation\StringType, Validation\Max(100), Validation\Exists('cars', 'model')]
        public string $model,
        #[Validation\Required, Validation\IntegerType, Validation\Exists('cars', 'id'), Validation\Different('right_car_id')]
        public int $leftCarId,
        #[Validation\Required, Validation\IntegerType, Validation\Exists('cars', 'id')]
        public int $rightCarId,
        #[Validation\Required, Validation\Enum(VoteSide::class)]
        public VoteSide $winnerSide,
        #[Validation\Required, Validation\StringType, Validation\AlphaNumeric, Validation\Size(64)]
        public string $pairToken,
    ) {}
}
