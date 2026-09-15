<?php

namespace App\Data;

use Spatie\LaravelData\Data;

final class CarImportData extends Data
{
    public function __construct(
        public CarSourceData $sourceData,
    ) {}

    /**
     * @param array<string, mixed> $source
     */
    public static function fromValidated(array $source): self
    {
        $sourceData = CarSourceData::fromValidated($source);

        return new self(sourceData: $sourceData);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabaseValues(): array
    {
        return [
            'auction_item_id' => $this->sourceData->auctionItemId,
            'current_high_pre_bid' => $this->sourceData->currentHighPreBid?->toDecimal(),
            'custom_status' => $this->sourceData->customStatus->value,
            'my_pre_bid' => $this->sourceData->myPreBid?->toDecimal(),
            'year' => $this->sourceData->year,
            'make' => $this->sourceData->make,
            'model' => $this->sourceData->model,
            'odometer' => $this->sourceData->odometer,
            'units' => $this->sourceData->units,
            'vehicle_location' => $this->sourceData->vehicleLocation,
            'engine' => $this->sourceData->engine,
            'transmission' => $this->sourceData->transmission,
            'color' => $this->sourceData->color,
            'brand' => $this->sourceData->brand,
            'winning_bid_amount' => $this->sourceData->winningBidAmount?->toDecimal(),
            'image_filename' => $this->sourceData->imageFilename(),
        ];
    }
}
