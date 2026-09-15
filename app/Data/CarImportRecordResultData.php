<?php

namespace App\Data;

use Spatie\LaravelData\Data;

final class CarImportRecordResultData extends Data
{
    private function __construct(
        public ?CarImportData $record,
        public ?string $error,
    ) {}

    public static function imported(CarImportData $record): self
    {
        return new self($record, null);
    }

    public static function skipped(string $error): self
    {
        return new self(null, $error);
    }
}
