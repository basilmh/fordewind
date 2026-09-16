<?php

namespace App\Data\Responses;

use App\Enums\VotingPairStatus;
use App\Models\Car;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class VotingPairData extends Data
{
    public function __construct(
        public VotingPairStatus $status,
        public string $make,
        public string $model,
        public ?VotingCarData $leftCar,
        public ?VotingCarData $rightCar,
        public ?string $pairToken,
    ) {}

    /** @param array<int, Car> $cars */
    public static function ready(string $make, string $model, array $cars, string $pairToken): self
    {
        return new self(
            status: VotingPairStatus::READY,
            make: $make,
            model: $model,
            leftCar: VotingCarData::fromCar($cars[0]),
            rightCar: VotingCarData::fromCar($cars[1]),
            pairToken: $pairToken,
        );
    }

    public static function unavailable(string $make, string $model, ?Car $car = null): self
    {
        return new self(
            VotingPairStatus::UNAVAILABLE,
            $make,
            $model,
            $car === null ? null : VotingCarData::fromCar($car),
            null,
            null,
        );
    }

    public static function exhausted(string $make, string $model): self
    {
        return new self(VotingPairStatus::EXHAUSTED, $make, $model, null, null, null);
    }
}
