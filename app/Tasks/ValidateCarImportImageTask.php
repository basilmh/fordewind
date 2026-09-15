<?php

namespace App\Tasks;

use App\Data\CarImportData;
use App\Exceptions\CarImportValidationException;

final class ValidateCarImportImageTask
{
    public function run(CarImportData $record, string $sourcePath): ?string
    {
        if (!is_dir($sourcePath)) {
            throw new CarImportValidationException("Image source directory does not exist: {$sourcePath}");
        }

        $filename = $record->sourceData->imageFilename();

        if (!is_file($sourcePath . DIRECTORY_SEPARATOR . $filename)) {
            return "{$record->sourceData->auctionItemId}: image {$filename} is missing.";
        }

        return null;
    }
}
