<?php

namespace Database\Factories;

use App\Enums\CarCustomStatus;
use App\Models\Car;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Car>
 */
class CarFactory extends Factory
{
    protected $model = Car::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'auction_item_id' => fake()->unique()->numerify('##########'),
            'current_high_pre_bid' => Money::fromDecimal('0.00'),
            'custom_status' => CarCustomStatus::SOLD,
            'my_pre_bid' => Money::fromDecimal('0.00'),
            'year' => 2000,
            'make' => 'BMW',
            'model' => 'X5',
            'odometer' => 100000,
            'units' => 'Km',
            'vehicle_location' => 'Toronto',
            'engine' => 'GAS',
            'transmission' => 'Auto',
            'color' => 'BLACK',
            'brand' => 'AB-SALVAGE',
            'winning_bid_amount' => Money::fromDecimal('1000.00'),
            'image_filename' => fake()->uuid() . '.jpg',
        ];
    }
}
