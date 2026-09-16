<?php

use App\Http\Controllers\Api\StatisticsController;
use App\Http\Controllers\Api\VotingController;
use App\Http\Middleware\ThrottleVotingVotes;
use Illuminate\Support\Facades\Route;

Route::view('/', 'vote')->name('home');
Route::view('vote', 'vote')->name('vote');
Route::view('statistics', 'statistics')->name('statistics');

Route::prefix('api')->name('api.')->group(function (): void {
    Route::get('voting/models', [VotingController::class, 'models'])->name('voting.models');
    Route::get('voting/pair', [VotingController::class, 'pair'])->name('voting.pair');
    Route::post('voting/cycle', [VotingController::class, 'cycle'])->name('voting.cycle.start');
    Route::post('voting/votes', [VotingController::class, 'store'])
        ->middleware(ThrottleVotingVotes::class)
        ->name('voting.votes.store');
    Route::get('statistics', [StatisticsController::class, 'index'])->name('statistics.index');
});
