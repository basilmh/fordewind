<?php

namespace App\Actions;

use App\Data\Responses\VotingPairData;
use App\Models\Car;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class GetVotingPairAction
{
    private const string SHOWN_CAR_IDS_SESSION_KEY = 'voting.shown_car_ids_by_model';

    private const string CURRENT_PAIR_SESSION_KEY = 'voting.current_pairs_by_model';

    public function __construct(private Session $session) {}

    public function run(string $model): VotingPairData
    {
        $modelCars = Car::query()
            ->where('model', $model)
            ->limit(2)
            ->get();

        if ($modelCars->count() < 2) {
            $this->forgetCurrentPairs($model);

            return VotingPairData::unavailable($model, $modelCars->first());
        }

        $shownCarIds = $this->shownCarIds($model);
        $unshownCars = $this->randomCars($model, $shownCarIds);

        if ($unshownCars->isEmpty()) {
            $this->forgetCurrentPairs($model);

            return VotingPairData::exhausted($model);
        }

        $cars = $unshownCars;

        if ($cars->count() === 1) {
            $previouslyShownCar = $this->randomPreviouslyShownCar($model, $shownCarIds);

            if ($previouslyShownCar === null) {
                $this->forgetCurrentPairs($model);

                return VotingPairData::exhausted($model);
            }

            // An odd set needs one repeated photo to display its final unseen photo in a pair.
            $cars = $cars->push($previouslyShownCar);
        }

        $carIds = $cars->pluck('id')->all();
        $this->storeShownCarIds($model, [...$shownCarIds, ...$carIds]);
        $pairToken = Str::random(64);
        $this->storeCurrentPair($model, $carIds, $pairToken);

        return VotingPairData::ready($model, $cars->all(), $pairToken);
    }

    /** @param list<int> $shownCarIds */
    private function randomCars(string $model, array $shownCarIds): Collection
    {
        return Car::query()
            ->where('model', $model)
            ->when($shownCarIds !== [], fn ($query) => $query->whereNotIn('id', $shownCarIds))
            ->inRandomOrder()
            ->limit(2)
            ->get();
    }

    /** @param list<int> $shownCarIds */
    private function randomPreviouslyShownCar(string $model, array $shownCarIds): ?Car
    {
        return Car::query()
            ->where('model', $model)
            ->whereIn('id', $shownCarIds)
            ->inRandomOrder()
            ->first();
    }

    /** @return list<int> */
    private function shownCarIds(string $model): array
    {
        $shownCarIdsByModel = $this->session->get(self::SHOWN_CAR_IDS_SESSION_KEY, []);
        $shownCarIds = is_array($shownCarIdsByModel) ? ($shownCarIdsByModel[$model] ?? []) : [];

        if (!is_array($shownCarIds)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $shownCarIds)));
    }

    /** @param list<int> $shownCarIds */
    private function storeShownCarIds(string $model, array $shownCarIds): void
    {
        $shownCarIdsByModel = $this->session->get(self::SHOWN_CAR_IDS_SESSION_KEY, []);
        $shownCarIdsByModel = is_array($shownCarIdsByModel) ? $shownCarIdsByModel : [];
        $shownCarIdsByModel[$model] = array_values(array_unique($shownCarIds));

        $this->session->put(self::SHOWN_CAR_IDS_SESSION_KEY, $shownCarIdsByModel);
    }

    /** @param list<int> $carIds */
    private function storeCurrentPair(string $model, array $carIds, string $pairToken): void
    {
        $pairsByModel = $this->session->get(self::CURRENT_PAIR_SESSION_KEY, []);
        $pairsByModel = is_array($pairsByModel) ? $pairsByModel : [];
        $pairsByModel[$model][hash('sha256', $pairToken)] = $carIds;

        $this->session->put(self::CURRENT_PAIR_SESSION_KEY, $pairsByModel);
    }

    private function forgetCurrentPairs(string $model): void
    {
        $pairsByModel = $this->session->get(self::CURRENT_PAIR_SESSION_KEY, []);

        if (!is_array($pairsByModel)) {
            return;
        }

        unset($pairsByModel[$model]);
        $this->session->put(self::CURRENT_PAIR_SESSION_KEY, $pairsByModel);
    }
}
