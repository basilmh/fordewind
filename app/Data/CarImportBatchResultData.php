<?php

namespace App\Data;

use Spatie\LaravelData\Data;

final class CarImportBatchResultData extends Data
{
    public function __construct(
        public int $records,
        public int $created,
        public int $updated,
        /** @var list<string> */
        public array $skippedErrorSamples = [],
        public int $skippedRecords = 0,
    ) {}
}
