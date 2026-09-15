# Fordewind Migration Patterns

## Schema migration

```php
private function canRunMigration(): bool
{
    return !Schema::hasTable('cars');
}

public function up(): void
{
    if (!$this->canRunMigration()) {
        return;
    }

Schema::create('cars', function (Blueprint $table): void {
    $table->id();
    $table->string('source_id')->unique()->comment('Идентификатор в исходных данных');
    $table->string('model')->index()->comment('Модель автомобиля');
    $table->unsignedSmallInteger('year')->index()->comment('Год выпуска');
    $table->string('image_path')->comment('Путь к фотографии');
    $table->timestamps();
});
}
```

Use `canRunMigration()` for the applicability of `up()`, not as a generic migration status method. A column alteration must check both the table and its required column; an index change must check the table and the relevant index state.

## Guarded data migration

Use this only for a small, deploy-bound correction. JSON ingestion stays in `cars:import`.

```php
private function canRunMigration(): bool
{
    if (!Schema::hasTable('cars')) {
        return false;
    }

    return DB::table('cars')->whereNull('model')->exists();
}

public function up(): void
{
    if (!$this->canRunMigration()) {
        return;
    }

    DB::table('cars')
        ->whereNull('model')
        ->update(['model' => 'Unknown']);
}

public function down(): void
{
    if (!Schema::hasTable('cars')) {
        return;
    }

    // This data correction cannot be reversed safely.
}
```

## Chunked backfill

```php
DB::table('cars')
    ->select('id', 'image_path')
    ->orderBy('id')
    ->chunkById(200, function (Collection $cars): void {
        foreach ($cars as $car) {
            // Perform a deterministic, local database update only.
        }
    });
```

## Vote indexes

For expected statistics queries, consider indexes on `cars.model`, `cars.year`, and the vote recipient foreign key. Confirm the real query plan before adding speculative composite indexes.
