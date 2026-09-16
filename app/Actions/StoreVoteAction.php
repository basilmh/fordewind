<?php

namespace App\Actions;

use App\Enums\VoteSide;
use App\Exceptions\VoteReplayException;
use App\Models\Car;
use App\Models\Vote;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class StoreVoteAction
{
    private const string CURRENT_PAIR_SESSION_KEY = 'voting.current_pair_by_model';

    public function __construct(private Session $session) {}

    public function run(
        string $model,
        int $leftCarId,
        int $rightCarId,
        VoteSide $winnerSide,
        string $pairToken,
    ): Vote {
        $pairTokenHash = hash('sha256', $pairToken);
        $this->ensureCurrentPair($model, $leftCarId, $rightCarId, $pairTokenHash);

        try {
            return DB::transaction(function () use ($model, $leftCarId, $rightCarId, $winnerSide, $pairTokenHash): Vote {
                $cars = Car::query()
                    ->whereKey([$leftCarId, $rightCarId])
                    ->where('model', $model)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($cars->count() !== 2) {
                    throw ValidationException::withMessages([
                        'left_car_id' => 'Cars must be distinct and belong to the selected model.',
                    ]);
                }

                $winnerCarId = $winnerSide === VoteSide::LEFT ? $leftCarId : $rightCarId;
                $loserCarId = $winnerSide === VoteSide::LEFT ? $rightCarId : $leftCarId;
                $vote = Vote::query()->create([
                    'winner_car_id' => $winnerCarId,
                    'loser_car_id' => $loserCarId,
                    'voter_session_hash' => $this->voterSessionHash(),
                    'pair_token_hash' => $pairTokenHash,
                    'pair_hash' => $this->pairHash($leftCarId, $rightCarId),
                ]);

                $this->forgetCurrentPair($model);

                return $vote;
            });
        } catch (QueryException $exception) {
            if (
                str_contains($exception->getMessage(), 'votes_pair_token_hash_unique')
                || str_contains($exception->getMessage(), 'votes_voter_session_pair_unique')
            ) {
                throw new VoteReplayException;
            }

            throw $exception;
        }
    }

    private function ensureCurrentPair(string $model, int $leftCarId, int $rightCarId, string $pairTokenHash): void
    {
        $pairsByModel = $this->session->get(self::CURRENT_PAIR_SESSION_KEY, []);
        $currentPair = is_array($pairsByModel) ? ($pairsByModel[$model] ?? null) : null;

        if (
            !is_array($currentPair)
            || ($currentPair['car_ids'] ?? null) !== [$leftCarId, $rightCarId]
            || !is_string($currentPair['pair_token'] ?? null)
            || !hash_equals(hash('sha256', $currentPair['pair_token']), $pairTokenHash)
        ) {
            throw ValidationException::withMessages([
                'left_car_id' => 'The submitted cars are not the current voting pair.',
            ]);
        }
    }

    private function forgetCurrentPair(string $model): void
    {
        $pairsByModel = $this->session->get(self::CURRENT_PAIR_SESSION_KEY, []);

        if (!is_array($pairsByModel)) {
            return;
        }

        unset($pairsByModel[$model]);

        $this->session->put(self::CURRENT_PAIR_SESSION_KEY, $pairsByModel);
    }

    private function voterSessionHash(): string
    {
        return hash_hmac('sha256', $this->session->getId(), (string) config('app.key'));
    }

    private function pairHash(int $leftCarId, int $rightCarId): string
    {
        $carIds = [$leftCarId, $rightCarId];
        sort($carIds);

        return hash('sha256', "{$carIds[0]}:{$carIds[1]}");
    }
}
