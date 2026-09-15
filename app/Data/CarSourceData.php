<?php

namespace App\Data;

use App\Enums\CarCustomStatus;
use App\ValueObjects\Money;
use Spatie\LaravelData\Data;

final class CarSourceData extends Data
{
    public function __construct(
        public string $auctionItemId,
        public ?Money $currentHighPreBid,
        public CarCustomStatus $customStatus,
        public ?Money $myPreBid,
        public int $year,
        public string $make,
        public string $model,
        public int $odometer,
        public string $units,
        public ?string $vehicleLocation,
        public string $engine,
        public string $transmission,
        public string $color,
        public string $brand,
        public ?Money $winningBidAmount,
        public string $image,
    ) {}

    /**
     * @param array<string, mixed> $values
     */
    public static function fromValidated(array $values): self
    {
        return new self(
            auctionItemId: $values['AuctionItemId'],
            currentHighPreBid: self::money($values['CurrentHighPreBid'] ?? null),
            customStatus: CarCustomStatus::from($values['CustomStatus']),
            myPreBid: self::money($values['MyPreBid'] ?? null),
            year: (int) $values['Year'],
            make: $values['Make'],
            model: $values['Model'],
            odometer: (int) $values['Odometer'],
            units: $values['Units'],
            vehicleLocation: $values['VehicleLocation'] ?: null,
            engine: $values['Engine'],
            transmission: $values['Transmission'],
            color: $values['Color'],
            brand: $values['Brand'],
            winningBidAmount: self::money($values['WinningBidAmount'] ?? null),
            image: $values['Image'],
        );
    }

    public function imageFilename(): string
    {
        return basename($this->image);
    }

    private static function money(int|float|string|null $value): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::fromDecimal($value);
    }
}
