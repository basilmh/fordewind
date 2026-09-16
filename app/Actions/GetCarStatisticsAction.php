<?php

namespace App\Actions;

use App\Data\Requests\StatisticsFilterData;
use App\Data\Responses\CarStatisticsData;
use App\Data\Responses\CarStatisticsResultData;
use App\Data\Responses\StatisticsMetaData;
use App\Models\Car;

final readonly class GetCarStatisticsAction
{
    public function run(StatisticsFilterData $filters): CarStatisticsResultData
    {
        $carsQuery = Car::query()
            ->when($filters->make !== null, fn ($query) => $query->where('make', $filters->make))
            ->when($filters->model !== null, fn ($query) => $query->where('model', $filters->model))
            ->when($filters->yearFrom !== null, fn ($query) => $query->where('year', '>=', $filters->yearFrom))
            ->when($filters->yearTo !== null, fn ($query) => $query->where('year', '<=', $filters->yearTo));

        $totalVotes = (clone $carsQuery)
            ->join('votes', 'votes.winner_car_id', '=', 'cars.id')
            ->count('votes.id');
        $paginator = $carsQuery
            ->withCount('receivedVotes')
            ->orderByDesc('received_votes_count')
            ->orderBy('model')
            ->orderBy('year')
            ->orderBy('id')
            ->paginate($filters->perPage, ['cars.*'], 'page', $filters->page);

        return new CarStatisticsResultData(
            data: $paginator->getCollection()
                ->map(static fn (Car $car): CarStatisticsData => CarStatisticsData::fromCar($car))
                ->values()
                ->all(),
            meta: new StatisticsMetaData(
                currentPage: $paginator->currentPage(),
                lastPage: $paginator->lastPage(),
                perPage: $paginator->perPage(),
                totalCars: $paginator->total(),
                totalVotes: $totalVotes,
            ),
        );
    }
}
