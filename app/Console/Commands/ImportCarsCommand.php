<?php

namespace App\Console\Commands;

use App\Actions\ImportCarsAction;
use App\Data\CarImportResultData;
use App\Exceptions\CarImportValidationException;
use App\Tasks\SynchronizeCarSourceRepositoryTask;
use Illuminate\Console\Command;
use RuntimeException;

class ImportCarsCommand extends Command
{
    protected $signature = 'cars:import
        {--path= : Source directory. Defaults to CARS_IMPORT_SOURCE_PATH.}
        {--no-sync : Read an existing source directory without cloning or updating it.}';

    protected $description = 'Clone/update the configured car source repository, publish images, and import JSON records.';

    public function __construct(
        private readonly ImportCarsAction $importCars,
        private readonly SynchronizeCarSourceRepositoryTask $synchronizeCarSourceRepository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $sourcePath = $this->sourcePath();

            if (!$this->option('no-sync')) {
                $this->line("Synchronizing source repository in {$sourcePath}...");
                $this->synchronizeCarSourceRepository->run($sourcePath, $this->repositoryUrl());
            }

            if (!is_dir($sourcePath)) {
                throw new RuntimeException("Source directory does not exist: {$sourcePath}");
            }

            $result = $this->importCars->run(
                $sourcePath,
                $this->imageSourcePath($sourcePath),
                $this->publicImagesPath(),
                $this->batchSize(),
            );
        } catch (CarImportValidationException $exception) {
            $this->error($exception->getMessage());
            $this->writeErrors($exception->errors);

            return self::FAILURE;
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->writeResult($result, $sourcePath);

        return $result->records === 0 && $result->skippedRecords > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function sourcePath(): string
    {
        $path = $this->option('path') ?: config('imports.cars.source_path');

        return $this->resolvedPath($path, 'CARS_IMPORT_SOURCE_PATH');
    }

    private function imageSourcePath(string $sourcePath): string
    {
        $path = config('imports.cars.images.source_path') ?: $sourcePath;

        return $this->resolvedPath($path, 'CARS_IMPORT_IMAGES_PATH');
    }

    private function publicImagesPath(): string
    {
        return $this->resolvedPath(config('imports.cars.images.public_path'), 'CARS_PUBLIC_IMAGES_PATH');
    }

    private function repositoryUrl(): string
    {
        $url = config('imports.cars.repository_url');

        if (!is_string($url) || $url === '') {
            throw new RuntimeException('CARS_IMPORT_REPOSITORY must be configured to clone the source repository.');
        }

        return $url;
    }

    private function batchSize(): int
    {
        $batchSize = config('imports.cars.batch_size');

        if (!is_int($batchSize) || $batchSize < 1 || $batchSize > 1_000) {
            throw new RuntimeException('CARS_IMPORT_BATCH_SIZE must be an integer between 1 and 1000.');
        }

        return $batchSize;
    }

    private function resolvedPath(mixed $path, string $configurationName): string
    {
        if (!is_string($path) || $path === '') {
            throw new RuntimeException("{$configurationName} must be configured.");
        }

        return str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
    }

    /**
     * @param list<string> $errors
     */
    private function writeErrors(array $errors): void
    {
        foreach (array_slice($errors, 0, 10) as $error) {
            $this->line(" - {$error}");
        }

        if (count($errors) > 10) {
            $this->line(sprintf(' - ... and %d more.', count($errors) - 10));
        }
    }

    private function writeResult(CarImportResultData $result, string $sourcePath): void
    {
        $this->info(sprintf(
            'Imported %d cars from %s: %d created, %d updated.',
            $result->records,
            $sourcePath,
            $result->created,
            $result->updated,
        ));
        $this->line("Copied {$result->copiedImages} image(s) to " . $this->publicImagesPath() . '.');

        if ($result->skippedRecords > 0) {
            $this->warn(sprintf('Skipped %d record(s) during import.', $result->skippedRecords));
            $this->writeErrors($result->skippedErrorSamples);
        }
    }
}
