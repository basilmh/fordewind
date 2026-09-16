<?php

namespace App\Data\Responses;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class CarStatisticsResultData extends Data
{
    /** @param list<CarStatisticsData> $data */
    public function __construct(
        public array $data,
        public StatisticsMetaData $meta,
    ) {}
}
