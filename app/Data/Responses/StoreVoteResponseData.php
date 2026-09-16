<?php

namespace App\Data\Responses;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class StoreVoteResponseData extends Data
{
    public function __construct(
        public VoteData $data,
        public VotingPairData $nextPair,
    ) {}
}
