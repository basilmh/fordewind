<?php

namespace App\Data\Responses;

use App\Models\Car;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class VotingCarData extends Data
{
    public function __construct(
        public int $id,
        public string $auctionItemId,
        public string $make,
        public string $model,
        public int $year,
        public string $imageUrl,
    ) {}

    public static function fromCar(Car $car): self
    {
        return new self(
            id: $car->id,
            auctionItemId: $car->auction_item_id,
            make: $car->make,
            model: $car->model,
            year: $car->year,
            imageUrl: '/images/cars/' . rawurlencode($car->auction_item_id) . '/' . rawurlencode($car->image_filename),
        );
    }
}
