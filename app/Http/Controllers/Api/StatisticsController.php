<?php

namespace App\Http\Controllers\Api;

use App\Actions\GetCarStatisticsAction;
use App\Data\Requests\StatisticsFilterData;
use App\Data\Responses\CarStatisticsResultData;
use App\Http\Controllers\Controller;

final class StatisticsController extends Controller
{
    public function index(StatisticsFilterData $data, GetCarStatisticsAction $getCarStatistics): CarStatisticsResultData
    {
        return $getCarStatistics->run($data);
    }
}
