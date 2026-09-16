<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vote>
 */
class VoteFactory extends Factory
{
    protected $model = Vote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'winner_car_id' => Car::factory(),
            'loser_car_id' => Car::factory(),
            'voter_session_hash' => hash('sha256', fake()->uuid()),
            'pair_token_hash' => hash('sha256', Str::random(64)),
            'pair_hash' => hash('sha256', fake()->uuid()),
        ];
    }
}
