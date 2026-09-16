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
        $imagePath = $sourcePath . DIRECTORY_SEPARATOR . $filename;

        if (!is_file($imagePath) || !is_readable($imagePath)) {
            return "{$record->sourceData->auctionItemId}: image {$filename} is missing.";
        }

        $image = @getimagesize($imagePath);

        if ($image === false || ($image['mime'] ?? null) !== 'image/jpeg') {
            return "{$record->sourceData->auctionItemId}: image {$filename} is not a valid JPEG.";
        }

        return null;
    }
}
