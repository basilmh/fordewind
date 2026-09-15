<?php

namespace App\Support;

use App\Data\CarImportBatchResultData;
use App\Data\CarImportResultData;

final class CarImportProgress
{
    private const int MAXIMUM_ERROR_SAMPLES = 10;

    /** @var list<string> */
    private array $skippedErrorSamples = [];

    private int $records = 0;

    private int $created = 0;

    private int $updated = 0;

    private int $copiedImages = 0;

    private int $skippedRecords = 0;

    public function addBatch(CarImportBatchResultData $result): void
    {
        $this->records += $result->records;
        $this->created += $result->created;
        $this->updated += $result->updated;

        foreach ($result->skippedErrorSamples as $error) {
            $this->addErrorSample($error);
        }

        $this->skippedRecords += $result->skippedRecords;
    }

    public function skip(string $error): void
    {
        $this->skippedRecords++;

        $this->addErrorSample($error);
    }

    public function addCopiedImages(int $count): void
    {
        $this->copiedImages += $count;
    }

    public function result(): CarImportResultData
    {
        return new CarImportResultData(
            $this->records,
            $this->created,
            $this->updated,
            $this->copiedImages,
            $this->skippedErrorSamples,
            $this->skippedRecords,
        );
    }

    private function addErrorSample(string $error): void
    {
        if (count($this->skippedErrorSamples) < self::MAXIMUM_ERROR_SAMPLES) {
            $this->skippedErrorSamples[] = $error;
        }
    }
}
