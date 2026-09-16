<?php

namespace App\Tasks;

use App\Data\CarImportData;
use App\Data\CarImportRecordResultData;
use App\Enums\CarCustomStatus;
use App\Exceptions\CarImportValidationException;
use FilesystemIterator;
use Generator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use JsonException;
use SplFileInfo;

final class ReadCarImportRecordsTask
{
    /**
     * @return Generator<int, CarImportRecordResultData>
     */
    public function run(string $sourcePath): Generator
    {
        $hasJsonFiles = false;

        foreach (new FilesystemIterator($sourcePath, FilesystemIterator::SKIP_DOTS) as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'json') {
                continue;
            }

            $hasJsonFiles = true;

            yield $this->readRecord($file);
        }

        if (!$hasJsonFiles) {
            throw new CarImportValidationException("No JSON files were found in {$sourcePath}.");
        }
    }

    private function readRecord(SplFileInfo $file): CarImportRecordResultData
    {
        try {
            $decoded = json_decode(File::get($file->getPathname()), true, flags: JSON_THROW_ON_ERROR);

            if (!is_array($decoded) || array_is_list($decoded)) {
                throw new JsonException('JSON root must be an object.');
            }

            return CarImportRecordResultData::imported(
                CarImportData::fromValidated($this->validatedValues($decoded)),
            );
        } catch (JsonException|ValidationException|\ValueError $exception) {
            return CarImportRecordResultData::skipped("{$file->getFilename()}: {$exception->getMessage()}");
        }
    }

    /**
     * @param array<string, mixed> $source
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validatedValues(array $source): array
    {
        return Validator::make($source, [
            'AuctionItemId' => ['required', 'string', 'max:32', 'regex:/^\\d+$/'],
            'CurrentHighPreBid' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'],
            'CustomStatus' => ['required', Rule::enum(CarCustomStatus::class)],
            'MyPreBid' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'],
            'Year' => ['required', 'integer', 'between:1900,' . now()->year],
            'Make' => ['required', 'string', 'max:100'],
            'Model' => ['required', 'string', 'max:100'],
            'Odometer' => ['required', 'integer', 'min:0'],
            'Units' => ['required', 'string', 'max:16'],
            'VehicleLocation' => ['nullable', 'string', 'max:255'],
            'Engine' => ['present', 'string', 'max:32'],
            'Transmission' => ['present', 'string', 'max:32'],
            'Color' => ['required', 'string', 'max:64'],
            'Brand' => ['required', 'string', 'max:64'],
            'WinningBidAmount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'],
            'Image' => ['required', 'string', 'max:255', 'regex:/^[^\\\\\/]+\\.jpe?g$/i'],
        ])->validate();
    }
}
