<?php

namespace App\Data\Responses;

use App\Models\Car;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class CarStatisticsData extends Data
{
    public function __construct(
        public int $id,
        public string $auctionItemId,
        public string $make,
        public string $model,
        public int $year,
        public int $odometer,
        public string $units,
        public ?string $vehicleLocation,
        public string $engine,
        public string $transmission,
        public string $color,
        public string $brand,
        public ?string $winningBidAmount,
        public string $imageUrl,
        public int $votesReceived,
    ) {}

    public static function fromCar(Car $car): self
    {
        return new self(
            id: $car->id,
            auctionItemId: $car->auction_item_id,
            make: $car->make,
            model: $car->model,
            year: $car->year,
            odometer: $car->odometer,
            units: $car->units,
            vehicleLocation: $car->vehicle_location,
            engine: $car->engine,
            transmission: $car->transmission,
            color: $car->color,
            brand: $car->brand,
            winningBidAmount: $car->winning_bid_amount?->toDecimal(),
            imageUrl: '/images/cars/' . rawurlencode($car->auction_item_id) . '/' . rawurlencode($car->image_filename),
            votesReceived: (int) $car->received_votes_count,
        );
    }
}
