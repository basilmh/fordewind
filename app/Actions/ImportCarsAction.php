<?php

namespace App\Actions;

use App\Data\CarImportBatchResultData;
use App\Data\CarImportData;
use App\Data\CarImportResultData;
use App\Models\Car;
use App\Support\CarImportProgress;
use App\Tasks\CopyCarImagesTask;
use App\Tasks\ReadCarImportRecordsTask;
use App\Tasks\ValidateCarImportImageTask;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final readonly class ImportCarsAction
{
    private const string SEEN_AUCTION_ITEM_IDS_TABLE = 'car_import_seen_auction_item_ids';

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

        $this->createSeenAuctionItemIdsTable();

        try {
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

                if ($this->isDuplicateAuctionItemId($record)) {
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
        } finally {
            $this->dropSeenAuctionItemIdsTable();
        }

        return $progress->result();
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
            $result = $this->persistBatch($records);
            $this->copyImages($records, $imageSourcePath, $publicImagesPath);

            return $result;
        } catch (QueryException) {
            return $this->importRecordsIndividually($records, $imageSourcePath, $publicImagesPath);
        }
    }

    /**
     * @param list<CarImportData> $records
     */
    private function persistBatch(array $records): CarImportBatchResultData
    {
        return DB::transaction(function () use ($records): CarImportBatchResultData {
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
        });
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
                $result = $this->persistBatch([$record]);
                $this->copyCarImages->run($record, $imageSourcePath, $publicImagesPath);
                $importedRecords += $result->records;
                $created += $result->created;
                $updated += $result->updated;
            } catch (QueryException) {
                $skippedRecords++;
                $skippedErrorSamples[] = "{$record->sourceData->auctionItemId}: database persistence failed.";
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

    private function createSeenAuctionItemIdsTable(): void
    {
        DB::statement(
            'CREATE TEMPORARY TABLE ' . self::SEEN_AUCTION_ITEM_IDS_TABLE
            . ' (auction_item_id VARCHAR(32) NOT NULL PRIMARY KEY)',
        );
    }

    private function dropSeenAuctionItemIdsTable(): void
    {
        DB::statement('DROP TEMPORARY TABLE IF EXISTS ' . self::SEEN_AUCTION_ITEM_IDS_TABLE);
    }

    private function isDuplicateAuctionItemId(CarImportData $record): bool
    {
        return DB::table(self::SEEN_AUCTION_ITEM_IDS_TABLE)->insertOrIgnore([
            'auction_item_id' => $record->sourceData->auctionItemId,
        ]) === 0;
    }
}
