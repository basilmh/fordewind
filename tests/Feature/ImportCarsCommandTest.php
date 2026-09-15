<?php

namespace Tests\Feature;

use App\Enums\CarCustomStatus;
use App\Enums\Currency;
use App\Models\Car;
use App\ValueObjects\Money;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\PendingCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class ImportCarsCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $fixtureRoot;

    private string $imageSourcePath;

    private string $publicImagePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureRoot = storage_path('framework/testing/import-cars');
        File::deleteDirectory($this->fixtureRoot);
        File::ensureDirectoryExists($this->fixtureRoot);

        $this->imageSourcePath = $this->fixtureRoot . '/source-images';
        $this->publicImagePath = $this->fixtureRoot . '/public-images';
        config()->set('imports.cars.images.source_path', $this->imageSourcePath);
        config()->set('imports.cars.images.public_path', $this->publicImagePath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->fixtureRoot);

        parent::tearDown();
    }

    #[Test]
    #[TestDox('импортирует JSON и копирует изображения в каталог автомобиля')]
    public function importsJsonRecordsAndCopiesImagesToCarDirectory(): void
    {
        config()->set('imports.cars.batch_size', 1);

        $this->importFixture($this->createValidSourceFixture())
            ->expectsOutputToContain('2 created, 0 updated.')
            ->expectsOutputToContain("Copied 2 image(s) to {$this->publicImagePath}.")
            ->assertExitCode(0);

        $this->assertDatabaseCount('cars', 2);
        $this->assertDatabaseHas('cars', [
            'auction_item_id' => '145243',
            'make' => 'BMW',
            'model' => 'X5 3.0I',
            'image_filename' => 'first-car.jpg',
            'vehicle_location' => null,
        ]);

        $car = Car::query()->where('auction_item_id', '145243')->firstOrFail();

        $this->assertSame(CarCustomStatus::SOLD, $car->custom_status);
        $this->assertInstanceOf(Money::class, $car->winning_bid_amount);
        $this->assertSame('800.00', $car->winning_bid_amount->toDecimal());
        $this->assertSame(Currency::USD, $car->winning_bid_amount->currency);
        $this->assertFileExists($this->publicImagePath . '/145243/first-car.jpg');
        $this->assertSame('first image', File::get($this->publicImagePath . '/145243/first-car.jpg'));
        $this->assertFileExists($this->publicImagePath . '/145244/first-car.jpg');
        $this->assertSame('first image', File::get($this->publicImagePath . '/145244/first-car.jpg'));

        $passCar = Car::query()->where('auction_item_id', '145244')->firstOrFail();
        $this->assertSame(CarCustomStatus::PASS, $passCar->custom_status);

        $this->importFixture($this->createValidSourceFixture())
            ->expectsOutputToContain('0 created, 2 updated.')
            ->expectsOutputToContain("Copied 2 image(s) to {$this->publicImagePath}.")
            ->assertExitCode(0);

        $this->assertDatabaseCount('cars', 2);
    }

    #[Test]
    #[TestDox('пропускает невалидный JSON и импортирует остальные записи')]
    public function skipsInvalidRecordsAndImportsValidRecords(): void
    {
        $this->importFixture($this->createInvalidSourceFixture())
            ->expectsOutputToContain('2 created, 0 updated.')
            ->expectsOutputToContain('Skipped 1 record(s) during import.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('cars', 2);
    }

    #[Test]
    #[TestDox('пропускает записи с невалидной денежной суммой')]
    public function skipsInvalidMoneyAndImportsValidRecords(): void
    {
        $this->importFixture($this->createInvalidMoneySourceFixture())
            ->expectsOutputToContain('2 created, 0 updated.')
            ->expectsOutputToContain('Skipped 1 record(s) during import.')
            ->expectsOutputToContain('The current high pre bid field must have 0-2 decimal places.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('cars', 2);
        $this->assertFileExists($this->publicImagePath . '/145243/first-car.jpg');
    }

    #[Test]
    #[TestDox('пропускает запись с отсутствующим изображением')]
    public function skipsRecordsWithMissingImageAndImportsValidRecords(): void
    {
        $this->importFixture($this->createSourceFixtureWithMissingImage())
            ->expectsOutputToContain('1 created, 0 updated.')
            ->expectsOutputToContain('Skipped 1 record(s) during import.')
            ->expectsOutputToContain('145244: image missing-car.jpg is missing.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('cars', 1);
        $this->assertFileExists($this->publicImagePath . '/145243/first-car.jpg');
        $this->assertFileDoesNotExist($this->publicImagePath . '/145244/first-car.jpg');
    }

    #[Test]
    #[TestDox('пропускает записи, в которых Image не является именем файла')]
    public function skipsRecordsWithImagePathAndImportsValidRecords(): void
    {
        $sourcePath = $this->createValidSourceFixture();
        $this->writeJsonFixture($sourcePath . '/invalid-image-path.json', $this->carPayload([
            'AuctionItemId' => '145245',
            'Image' => 'images/first-car.jpg',
        ]));

        $this->importFixture($sourcePath)
            ->expectsOutputToContain('2 created, 0 updated.')
            ->expectsOutputToContain('Skipped 1 record(s) during import.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('cars', 2);
        $this->assertDatabaseMissing('cars', ['auction_item_id' => '145245']);
    }

    #[Test]
    #[TestDox('отклоняет дублирующийся AuctionItemId и импортирует уникальные записи')]
    public function skipsDuplicateAuctionItemIdsAndImportsUniqueRecords(): void
    {
        $sourcePath = $this->createValidSourceFixture();
        $this->writeJsonFixture($sourcePath . '/second-car.json', $this->carPayload([
            'AuctionItemId' => '145243',
            'Make' => 'Audi',
        ]));

        $this->importFixture($sourcePath)
            ->expectsOutputToContain('1 created, 0 updated.')
            ->expectsOutputToContain('Skipped 1 record(s) during import.')
            ->expectsOutputToContain('145243: duplicate AuctionItemId in source.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('cars', 1);
    }

    #[Test]
    #[TestDox('завершается с ошибкой, если все записи были пропущены')]
    public function failsWhenAllRecordsAreSkipped(): void
    {
        $sourcePath = $this->createFixtureDirectory('all-invalid');
        $this->writeJsonFixture($sourcePath . '/invalid.json', [
            'AuctionItemId' => 'missing-required-fields',
        ]);

        $this->importFixture($sourcePath)
            ->expectsOutputToContain('Imported 0 cars')
            ->expectsOutputToContain('Skipped 1 record(s) during import.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('cars', 0);
    }

    #[Test]
    #[TestDox('изолирует ошибку записи в БД внутри batch')]
    public function isolatesDatabaseWriteFailuresWithinBatch(): void
    {
        $simulateFailure = true;
        DB::listen(static function (QueryExecuted $query) use (&$simulateFailure): void {
            if ($simulateFailure && in_array('Forbidden', $query->bindings, true)) {
                throw new QueryException(
                    $query->connectionName,
                    $query->sql,
                    $query->bindings,
                    new \RuntimeException('Simulated database failure.'),
                );
            }
        });

        try {
            $sourcePath = $this->createValidSourceFixture();
            $this->writeJsonFixture($sourcePath . '/second-car.json', $this->carPayload([
                'AuctionItemId' => '145244',
                'Make' => 'Forbidden',
            ]));

            $this->importFixture($sourcePath)
                ->expectsOutputToContain('1 created, 0 updated.')
            ->expectsOutputToContain('Copied 1 image(s) to ' . $this->publicImagePath . '.')
                ->expectsOutputToContain('Skipped 1 record(s) during import.')
                ->expectsOutputToContain('145244: database persistence failed.')
                ->assertExitCode(0);

            $this->assertDatabaseCount('cars', 1);
            $this->assertDatabaseHas('cars', ['auction_item_id' => '145243']);
            $this->assertFileDoesNotExist($this->publicImagePath . '/145244/first-car.jpg');
        } finally {
            $simulateFailure = false;
        }
    }

    private function importFixture(string $sourcePath): PendingCommand
    {
        return $this->artisan('cars:import', [
            '--path' => $sourcePath,
            '--no-sync' => true,
        ]);
    }

    private function createValidSourceFixture(): string
    {
        $sourcePath = $this->createFixtureDirectory('valid');

        $this->writeJsonFixture($sourcePath . '/first-car.json', $this->carPayload([
            'Image' => 'first-car.jpg',
        ]));
        $this->writeJsonFixture($sourcePath . '/second-car.json', $this->carPayload([
            'AuctionItemId' => '145244',
            'CurrentHighPreBid' => 1000.5,
            'CustomStatus' => 'Pass',
            'IsWatched' => true,
            'SequenceNumber' => 30,
            'LastCustomStatusSetAt' => '2017-02-22T14:38:23.393446Z',
            'Year' => 2010,
            'Make' => 'Audi',
            'Model' => 'A4',
            'Odometer' => 120000,
            'VehicleLocation' => 'Toronto',
            'Engine' => 'DIESEL',
            'Transmission' => 'Manual',
            'Color' => 'BLACK',
            'ExternalAuctionItemId' => '10453371',
            'WinningBidAmount' => 0,
            'WinningBidLocation' => null,
            'IsBiddable' => false,
            'Status' => 4,
            'Image' => 'first-car.jpg',
        ]));
        $this->writeImageFixture('first-car.jpg', 'first image');

        return $sourcePath;
    }

    private function createSourceFixtureWithMissingImage(): string
    {
        $sourcePath = $this->createValidSourceFixture();
        $this->writeJsonFixture($sourcePath . '/second-car.json', $this->carPayload([
            'AuctionItemId' => '145244',
            'Image' => 'missing-car.jpg',
        ]));

        return $sourcePath;
    }

    private function createInvalidSourceFixture(): string
    {
        $sourcePath = $this->createValidSourceFixture();
        $this->writeJsonFixture($sourcePath . '/invalid.json', [
            'AuctionItemId' => 'missing-required-fields',
        ]);

        return $sourcePath;
    }

    private function createInvalidMoneySourceFixture(): string
    {
        $sourcePath = $this->createValidSourceFixture();
        $this->writeJsonFixture($sourcePath . '/invalid-money.json', $this->carPayload([
            'AuctionItemId' => '145245',
            'CurrentHighPreBid' => 100.123,
        ]));

        return $sourcePath;
    }

    private function createFixtureDirectory(string $name): string
    {
        $sourcePath = $this->fixtureRoot . '/' . $name;
        File::ensureDirectoryExists($sourcePath);

        return $sourcePath;
    }

    private function writeImageFixture(string $filename, string $contents): void
    {
        File::ensureDirectoryExists($this->imageSourcePath);
        File::put($this->imageSourcePath . '/' . $filename, $contents);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeJsonFixture(string $path, array $payload): void
    {
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function carPayload(array $overrides = []): array
    {
        return array_replace([
            'AuctionItemId' => '145243',
            'AuctionId' => '143576',
            'CurrentHighPreBid' => 700,
            'CustomStatus' => 'Sold',
            'MyPreBid' => 0,
            'IsWatched' => false,
            'SequenceNumber' => 29,
            'LastCustomStatusSetAt' => '2017-02-21T14:38:23.393446Z',
            'Year' => 2002,
            'Make' => 'BMW',
            'Model' => 'X5 3.0I',
            'Odometer' => 204404,
            'Units' => 'Km',
            'VehicleLocation' => '',
            'Engine' => 'GAS',
            'Transmission' => 'Auto',
            'Color' => 'SILVER',
            'Brand' => 'ON-SALVAGE',
            'ExternalAuctionItemId' => '10453370',
            'WinningBidAmount' => 800,
            'WinningBidLocation' => 'Montreal',
            'IsBiddable' => true,
            'Status' => 4,
            'Image' => 'first-car.jpg',
        ], $overrides);
    }
}
