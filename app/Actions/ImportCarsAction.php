<?php

namespace App\Actions;

use App\Data\CarImportBatchResultData;
use App\Data\CarImportData;
use App\Data\CarImportResultData;
use App\Models\Car;
use App\Support\CarImportProgress;
use App\Tasks\CopyCarImagesTask;
use App\Tasks\GetCarModelsTask;
use App\Tasks\ReadCarImportRecordsTask;
use App\Tasks\ValidateCarImportImageTask;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class ImportCarsAction
{
    public function __construct(
        private ReadCarImportRecordsTask $readCarImportRecords,
        private ValidateCarImportImageTask $validateCarImportImage,
        private CopyCarImagesTask $copyCarImages,
    ) {}

    public function run(
        string $sourcePath,
        string $imageSourcePath,
        string $publicImagesPath,
        int $batchSize,
    ): CarImportResultData {
        $progress = new CarImportProgress;
        $batch = [];
        $duplicateCacheKeyPrefix = 'car-import:seen:' . Str::uuid();
        $duplicateCacheExpiresAt = now()->addSeconds($this->duplicateCacheTtl());

        foreach ($this->readCarImportRecords->run($sourcePath) as $readResult) {
            if ($readResult->error !== null) {
                $progress->skip($readResult->error);

                continue;
            }

            $record = $readResult->record;

            if ($record === null) {
                $progress->skip('Import record is missing.');

                continue;
            }

            if ($this->isDuplicateAuctionItemId($record, $duplicateCacheKeyPrefix, $duplicateCacheExpiresAt)) {
                $progress->skip("{$record->sourceData->auctionItemId}: duplicate AuctionItemId in source.");

                continue;
            }

            $imageError = $this->validateCarImportImage->run($record, $imageSourcePath);

            if ($imageError !== null) {
                $progress->skip($imageError);

                continue;
            }

            $batch[] = $record;

            if (count($batch) < $batchSize) {
                continue;
            }

            $this->addImportedBatchToProgress($progress, $batch, $imageSourcePath, $publicImagesPath);
            $batch = [];
        }

        if ($batch !== []) {
            $this->addImportedBatchToProgress($progress, $batch, $imageSourcePath, $publicImagesPath);
        }

        $result = $progress->result();

        if ($result->records > 0) {
            Cache::forget(GetCarModelsTask::CACHE_KEY);
        }

        return $result;
    }

    /**
     * @param list<CarImportData> $records
     */
    private function importBatch(
        array $records,
        string $imageSourcePath,
        string $publicImagesPath,
    ): CarImportBatchResultData {
        try {
            return DB::transaction(function () use ($records, $imageSourcePath, $publicImagesPath): CarImportBatchResultData {
                $result = $this->persistBatch($records);
                $this->copyImages($records, $imageSourcePath, $publicImagesPath);

                return $result;
            });
        } catch (QueryException|RuntimeException) {
            return $this->importRecordsIndividually($records, $imageSourcePath, $publicImagesPath);
        }
    }

    /**
     * @param list<CarImportData> $records
     */
    private function persistBatch(array $records): CarImportBatchResultData
    {
        $auctionItemIds = array_map(
            static fn (CarImportData $record): string => $record->sourceData->auctionItemId,
            $records,
        );
        $existingLookup = array_fill_keys(
            Car::query()->whereIn('auction_item_id', $auctionItemIds)->pluck('auction_item_id')->all(),
            true,
        );
        $created = count(array_filter(
            $auctionItemIds,
            static fn (string $auctionItemId): bool => !isset($existingLookup[$auctionItemId]),
        ));

        Car::query()->upsert(
            array_map(
                static fn (CarImportData $record): array => $record->toDatabaseValues(),
                $records,
            ),
            ['auction_item_id'],
            [
                'current_high_pre_bid',
                'custom_status',
                'my_pre_bid',
                'year',
                'make',
                'model',
                'odometer',
                'units',
                'vehicle_location',
                'engine',
                'transmission',
                'color',
                'brand',
                'winning_bid_amount',
                'image_filename',
            ],
        );

        return new CarImportBatchResultData(
            count($records),
            $created,
            count($records) - $created,
        );
    }

    /**
     * @param list<CarImportData> $records
     */
    private function importRecordsIndividually(
        array $records,
        string $imageSourcePath,
        string $publicImagesPath,
    ): CarImportBatchResultData {
        $importedRecords = 0;
        $created = 0;
        $updated = 0;
        $skippedRecords = 0;
        $skippedErrorSamples = [];

        foreach ($records as $record) {
            try {
                $result = DB::transaction(function () use ($record, $imageSourcePath, $publicImagesPath): CarImportBatchResultData {
                    $result = $this->persistBatch([$record]);
                    $this->copyCarImages->run($record, $imageSourcePath, $publicImagesPath);

                    return $result;
                });
                $importedRecords += $result->records;
                $created += $result->created;
                $updated += $result->updated;
            } catch (QueryException) {
                $skippedRecords++;
                $skippedErrorSamples[] = "{$record->sourceData->auctionItemId}: database persistence failed.";
            } catch (RuntimeException) {
                $skippedRecords++;
                $skippedErrorSamples[] = "{$record->sourceData->auctionItemId}: image copy failed.";
            }
        }

        return new CarImportBatchResultData(
            $importedRecords,
            $created,
            $updated,
            $skippedErrorSamples,
            $skippedRecords,
        );
    }

    /**
     * @param list<CarImportData> $records
     */
    private function addImportedBatchToProgress(
        CarImportProgress $progress,
        array $records,
        string $imageSourcePath,
        string $publicImagesPath,
    ): void {
        $result = $this->importBatch($records, $imageSourcePath, $publicImagesPath);
        $progress->addBatch($result);
        $progress->addCopiedImages($result->records);
    }

    /**
     * @param list<CarImportData> $records
     */
    private function copyImages(array $records, string $imageSourcePath, string $publicImagesPath): void
    {
        foreach ($records as $record) {
            $this->copyCarImages->run($record, $imageSourcePath, $publicImagesPath);
        }
    }

    private function duplicateCacheTtl(): int
    {
        $ttl = config('imports.cars.duplicate_cache_ttl');

        if (!is_int($ttl) || $ttl < 1) {
            throw new RuntimeException('CARS_IMPORT_DUPLICATE_CACHE_TTL must be a positive integer.');
        }

        return $ttl;
    }

    private function isDuplicateAuctionItemId(CarImportData $record, string $cacheKeyPrefix, \DateTimeInterface $expiresAt): bool
    {
        return !Cache::add(
            "{$cacheKeyPrefix}:{$record->sourceData->auctionItemId}",
            true,
            $expiresAt,
        );
    }
}
