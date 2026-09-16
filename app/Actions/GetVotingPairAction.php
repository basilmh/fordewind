<?php

namespace App\Actions;

use App\Data\Responses\VotingPairData;
use App\Models\Car;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class GetVotingPairAction
{
    private const string CYCLE_SESSION_KEY = 'voting.cycles_by_model';

    private const string CURRENT_PAIR_SESSION_KEY = 'voting.current_pair_by_model';

    public function __construct(private Session $session) {}

    public function run(string $make, string $model): VotingPairData
    {
        $cycleKey = $this->cycleKey($make, $model);
        $currentPair = $this->currentPair($cycleKey);

        if ($currentPair !== null) {
            $cars = $this->carsByIds($make, $model, $currentPair['car_ids']);

            if ($cars !== null) {
                return VotingPairData::ready($make, $model, $cars, $currentPair['pair_token']);
            }

            $this->forgetCurrentPair($cycleKey);
        }

        $totalCars = Car::query()->where('make', $make)->where('model', $model)->count();

        if ($totalCars < 2) {
            $this->forgetCurrentPair($cycleKey);

            return VotingPairData::unavailable(
                $make,
                $model,
                Car::query()->where('make', $make)->where('model', $model)->orderBy('id')->first(),
            );
        }

        $cycle = $this->cycle($cycleKey);

        if ($cycle !== null && $cycle['shown_cars_count'] >= $cycle['total_cars']) {
            $this->forgetCurrentPair($cycleKey);

            return VotingPairData::exhausted($make, $model);
        }

        [$cars, $nextCycle] = $this->nextCars($make, $model, $cycle, $totalCars);

        if ($cars->isEmpty()) {
            $this->forgetCurrentPair($cycleKey);

            return VotingPairData::exhausted($make, $model);
        }

        $pairCars = $cars;

        if ($cars->count() === 1) {
            $repeatCar = Car::query()->find($nextCycle['first_car_id']);

            if ($repeatCar === null) {
                $this->forgetCurrentPair($cycleKey);

                return VotingPairData::exhausted($make, $model);
            }

            $pairCars = $cars->push($repeatCar);
        }

        $pairToken = Str::random(64);
        $this->storeCycle($cycleKey, $nextCycle);
        $this->storeCurrentPair($cycleKey, $pairCars->pluck('id')->all(), $pairToken);

        return VotingPairData::ready($make, $model, $pairCars->all(), $pairToken);
    }

    /**
     * @param array{first_car_id: int, last_car_id: int, shown_cars_count: int, total_cars: int}|null $cycle
     * @return array{0: Collection<int, Car>, 1: array{first_car_id: int, last_car_id: int, shown_cars_count: int, total_cars: int}}
     */
    private function nextCars(string $make, string $model, ?array $cycle, int $totalCars): array
    {
        if ($cycle === null) {
            $firstCar = $this->randomStartingCar($make, $model);

            if ($firstCar === null) {
                return [new Collection, [
                    'first_car_id' => 0,
                    'last_car_id' => 0,
                    'shown_cars_count' => 0,
                    'total_cars' => $totalCars,
                ]];
            }

            $cars = collect([$firstCar]);
            $cars = $cars->concat(
                Car::query()
                    ->where('make', $make)->where('model', $model)
                    ->where('id', '>', $firstCar->id)
                    ->orderBy('id')
                    ->limit(1)
                    ->get(),
            );

            if ($cars->count() < 2) {
                $cars = $cars->concat(
                    Car::query()
                        ->where('make', $make)->where('model', $model)
                        ->where('id', '<', $firstCar->id)
                        ->orderBy('id')
                        ->limit(2 - $cars->count())
                        ->get(),
                );
            }

            return [$cars, [
                'first_car_id' => $firstCar->id,
                'last_car_id' => $cars->last()->id,
                'shown_cars_count' => $cars->count(),
                'total_cars' => $totalCars,
            ]];
        }

        $remainingCars = $cycle['total_cars'] - $cycle['shown_cars_count'];
        $limit = min(2, $remainingCars);
        $cars = $this->carsAfterLastShown($make, $model, $cycle, $limit);

        return [$cars, [
            ...$cycle,
            'last_car_id' => $cars->last()?->id ?? $cycle['last_car_id'],
            'shown_cars_count' => $cycle['shown_cars_count'] + $cars->count(),
        ]];
    }

    /** @param array{first_car_id: int, last_car_id: int, shown_cars_count: int, total_cars: int} $cycle */
    private function carsAfterLastShown(string $make, string $model, array $cycle, int $limit): Collection
    {
        if ($cycle['last_car_id'] < $cycle['first_car_id']) {
            return Car::query()
                ->where('make', $make)->where('model', $model)
                ->whereBetween('id', [$cycle['last_car_id'] + 1, $cycle['first_car_id'] - 1])
                ->orderBy('id')
                ->limit($limit)
                ->get();
        }

        $cars = Car::query()
            ->where('make', $make)->where('model', $model)
            ->where('id', '>', $cycle['last_car_id'])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($cars->count() < $limit) {
            $cars = $cars->concat(
                Car::query()
                    ->where('make', $make)->where('model', $model)
                    ->where('id', '<', $cycle['first_car_id'])
                    ->orderBy('id')
                    ->limit($limit - $cars->count())
                    ->get(),
            );
        }

        return $cars;
    }

    private function randomStartingCar(string $make, string $model): ?Car
    {
        $bounds = Car::query()
            ->where('make', $make)->where('model', $model)
            ->selectRaw('MIN(id) as min_id, MAX(id) as max_id')
            ->first();

        if ($bounds?->min_id === null || $bounds->max_id === null) {
            return null;
        }

        $randomId = random_int((int) $bounds->min_id, (int) $bounds->max_id);

        return Car::query()
            ->where('make', $make)->where('model', $model)
            ->where('id', '>=', $randomId)
            ->orderBy('id')
            ->first()
            ?? Car::query()->where('make', $make)->where('model', $model)->orderBy('id')->first();
    }

    /** @param list<int> $carIds */
    private function carsByIds(string $make, string $model, array $carIds): ?array
    {
        if (count($carIds) !== 2 || $carIds[0] === $carIds[1]) {
            return null;
        }

        $cars = Car::query()
            ->where('make', $make)->where('model', $model)
            ->whereKey($carIds)
            ->get()
            ->keyBy('id');

        if ($cars->count() !== 2) {
            return null;
        }

        return [$cars->get($carIds[0]), $cars->get($carIds[1])];
    }

    /** @return array{first_car_id: int, last_car_id: int, shown_cars_count: int, total_cars: int}|null */
    private function cycle(string $cycleKey): ?array
    {
        $cyclesByModel = $this->session->get(self::CYCLE_SESSION_KEY, []);
        $cycle = is_array($cyclesByModel) ? ($cyclesByModel[$cycleKey] ?? null) : null;

        if (!is_array($cycle)) {
            return null;
        }

        $firstCarId = $cycle['first_car_id'] ?? null;
        $lastCarId = $cycle['last_car_id'] ?? null;
        $shownCarsCount = $cycle['shown_cars_count'] ?? null;
        $totalCars = $cycle['total_cars'] ?? null;

        if (!is_int($firstCarId) || !is_int($lastCarId) || !is_int($shownCarsCount) || !is_int($totalCars)) {
            return null;
        }

        return [
            'first_car_id' => $firstCarId,
            'last_car_id' => $lastCarId,
            'shown_cars_count' => $shownCarsCount,
            'total_cars' => $totalCars,
        ];
    }

    /** @param array{first_car_id: int, last_car_id: int, shown_cars_count: int, total_cars: int} $cycle */
    private function storeCycle(string $cycleKey, array $cycle): void
    {
        $cyclesByModel = $this->session->get(self::CYCLE_SESSION_KEY, []);
        $cyclesByModel = is_array($cyclesByModel) ? $cyclesByModel : [];
        $cyclesByModel[$cycleKey] = $cycle;

        $this->session->put(self::CYCLE_SESSION_KEY, $cyclesByModel);
    }

    /** @return array{car_ids: list<int>, pair_token: string}|null */
    private function currentPair(string $cycleKey): ?array
    {
        $pairsByModel = $this->session->get(self::CURRENT_PAIR_SESSION_KEY, []);
        $pair = is_array($pairsByModel) ? ($pairsByModel[$cycleKey] ?? null) : null;

        if (!is_array($pair) || !is_array($pair['car_ids'] ?? null) || !is_string($pair['pair_token'] ?? null)) {
            return null;
        }

        $carIds = array_values($pair['car_ids']);

        if (count($carIds) !== 2 || !is_int($carIds[0]) || !is_int($carIds[1])) {
            return null;
        }

        return ['car_ids' => $carIds, 'pair_token' => $pair['pair_token']];
    }

    /** @param list<int> $carIds */
    private function storeCurrentPair(string $cycleKey, array $carIds, string $pairToken): void
    {
        $pairsByModel = $this->session->get(self::CURRENT_PAIR_SESSION_KEY, []);
        $pairsByModel = is_array($pairsByModel) ? $pairsByModel : [];
        $pairsByModel[$cycleKey] = ['car_ids' => $carIds, 'pair_token' => $pairToken];

        $this->session->put(self::CURRENT_PAIR_SESSION_KEY, $pairsByModel);
    }

    private function forgetCurrentPair(string $cycleKey): void
    {
        $pairsByModel = $this->session->get(self::CURRENT_PAIR_SESSION_KEY, []);

        if (!is_array($pairsByModel)) {
            return;
        }

        unset($pairsByModel[$cycleKey]);
        $this->session->put(self::CURRENT_PAIR_SESSION_KEY, $pairsByModel);
    }

    private function cycleKey(string $make, string $model): string
    {
        return "{$make}\0{$model}";
    }
}
