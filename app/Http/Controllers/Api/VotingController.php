<?php

namespace App\Http\Controllers\Api;

use App\Actions\GetVotingPairAction;
use App\Actions\StartVotingCycleAction;
use App\Actions\StoreVoteAction;
use App\Data\Requests\GetVotingPairData;
use App\Data\Requests\StoreVoteData;
use App\Data\Responses\CarModelData;
use App\Data\Responses\CarModelsResponseData;
use App\Data\Responses\StoreVoteResponseData;
use App\Data\Responses\VoteData;
use App\Data\Responses\VotingCycleData;
use App\Data\Responses\VotingCycleResponseData;
use App\Data\Responses\VotingPairResponseData;
use App\Http\Controllers\Controller;
use App\Tasks\GetCarModelsTask;
use Illuminate\Http\JsonResponse;

final class VotingController extends Controller
{
    public function models(GetCarModelsTask $getCarModels): CarModelsResponseData
    {
        return new CarModelsResponseData(
            $getCarModels->run()
                ->map(static fn (array $model): CarModelData => new CarModelData(
                    $model['make'],
                    $model['model'],
                    $model['cars_count'],
                    $model['cars_count'] >= 2,
                ))
                ->values()
                ->all(),
        );
    }

    public function pair(GetVotingPairData $data, GetVotingPairAction $getVotingPair): VotingPairResponseData
    {
        return new VotingPairResponseData($getVotingPair->run($data->make, $data->model));
    }

    public function cycle(StartVotingCycleAction $startVotingCycle): JsonResponse
    {
        return response()->json(
            (new VotingCycleResponseData(new VotingCycleData($startVotingCycle->run())))->toArray(),
        );
    }

    public function store(
        StoreVoteData $data,
        StoreVoteAction $storeVote,
        GetVotingPairAction $getVotingPair,
    ): JsonResponse {
        $vote = $storeVote->run(
            $data->make,
            $data->model,
            $data->leftCarId,
            $data->rightCarId,
            $data->winnerSide,
            $data->pairToken,
        );

        $response = new StoreVoteResponseData(
            data: VoteData::fromModel($vote),
            nextPair: $getVotingPair->run($data->make, $data->model),
        );

        return response()->json($response->toArray(), JsonResponse::HTTP_CREATED);
    }
}
