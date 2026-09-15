<?php

namespace App\Data;

use Spatie\LaravelData\Data;

final class CarImportResultData extends Data
{
    public function __construct(
        public int $records,
        public int $created,
        public int $updated,
        public int $copiedImages,
        /** @var list<string> */
        public array $skippedErrorSamples,
        public int $skippedRecords,
    ) {}
}
