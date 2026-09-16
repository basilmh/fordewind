<?php

namespace App\Data\Responses;

use App\Models\Vote;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class VoteData extends Data
{
    public function __construct(
        public int $id,
        public int $winnerCarId,
        public int $loserCarId,
        public string $createdAt,
    ) {}

    public static function fromModel(Vote $vote): self
    {
        return new self(
            id: $vote->id,
            winnerCarId: $vote->winner_car_id,
            loserCarId: $vote->loser_car_id,
            createdAt: $vote->created_at->toAtomString(),
        );
    }
}
