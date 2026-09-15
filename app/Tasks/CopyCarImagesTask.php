<?php

namespace App\Tasks;

use App\Data\CarImportData;
use Illuminate\Support\Facades\File;
use RuntimeException;

final class CopyCarImagesTask
{
    public function run(CarImportData $record, string $sourcePath, string $publicPath): void
    {
        $filename = $record->sourceData->imageFilename();
        $destinationDirectory = $publicPath . DIRECTORY_SEPARATOR . $record->sourceData->auctionItemId;

        File::ensureDirectoryExists($destinationDirectory);

        if (!File::copy($sourcePath . DIRECTORY_SEPARATOR . $filename, $destinationDirectory . DIRECTORY_SEPARATOR . $filename)) {
            throw new RuntimeException("Unable to copy image {$filename} for {$record->sourceData->auctionItemId}.");
        }
    }
}
