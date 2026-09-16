<?php

namespace App\Tasks;

use App\Models\Car;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class GetCarModelsTask
{
    public const string CACHE_KEY = 'voting:car-models:v2';

    /** @return Collection<int, array{make: string, model: string, cars_count: int}> */
    public function run(): Collection
    {
        /** @var Collection<int, array{make: string, model: string, cars_count: int}> $models */
        $models = Cache::remember(self::CACHE_KEY, now()->addDay(), static fn (): Collection => Car::query()
            ->select(['make', 'model'])
            ->selectRaw('COUNT(*) as cars_count')
            ->groupBy('make', 'model')
            ->orderBy('make')
            ->orderBy('model')
            ->get()
            ->map(static fn (Car $car): array => [
                'make' => $car->make,
                'model' => $car->model,
                'cars_count' => (int) $car->getAttribute('cars_count'),
            ]));

        return $models;
    }
}
