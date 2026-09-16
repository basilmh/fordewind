<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class VotingApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[TestDox('возвращает доступные модели с количеством автомобилей')]
    public function listsAvailableCarModels(): void
    {
        $this->createCars('X5', 2);
        $this->createCars('A4', 1);

        $this->getJson(route('api.voting.models'))
            ->assertOk()
            ->assertJsonPath('data.0.model', 'A4')
            ->assertJsonPath('data.0.cars_count', 1)
            ->assertJsonPath('data.1.model', 'X5')
            ->assertJsonPath('data.1.cars_count', 2);
    }

    #[Test]
    #[TestDox('не повторяет автомобили в цикле голосования, пока доступны две новые фотографии')]
    public function returnsUniqueCarsUntilTheVotingCycleIsExhausted(): void
    {
        $this->createCars('X5', 4);

        $firstPair = $this->withSession([])
            ->getJson(route('api.voting.pair', ['model' => 'X5']))
            ->assertOk()
            ->assertJsonPath('data.status', 'ready')
            ->assertJsonStructure(['data' => ['pair_token']]);
        $firstPairIds = $this->pairIds($firstPair->json('data'));

        $secondPair = $this->withSession([
            'voting.shown_car_ids_by_model' => ['X5' => $firstPairIds],
        ])->getJson(route('api.voting.pair', ['model' => 'X5']))
            ->assertOk()
            ->assertJsonPath('data.status', 'ready');
        $secondPairIds = $this->pairIds($secondPair->json('data'));

        $this->assertCount(2, $firstPairIds);
        $this->assertCount(2, $secondPairIds);
        $this->assertSame([], array_values(array_intersect($firstPairIds, $secondPairIds)));
    }

    #[Test]
    #[TestDox('возвращает недоступное состояние для модели с одной фотографией')]
    public function returnsUnavailablePairWhenModelHasLessThanTwoCars(): void
    {
        $this->createCars('X5', 1);

        $this->getJson(route('api.voting.pair', ['model' => 'X5']))
            ->assertOk()
            ->assertJsonPath('data.status', 'unavailable')
            ->assertJsonPath('data.left_car', null)
            ->assertJsonPath('data.right_car', null);
    }

    #[Test]
    #[TestDox('показывает последнюю новую фотографию в нечётном наборе и завершает цикл')]
    public function completesOddSetBeforeMarkingTheVotingCycleAsExhausted(): void
    {
        [$firstCar, $secondCar, $lastCar] = $this->createCars('X5', 3);

        $pair = $this->withSession([
            'voting.shown_car_ids_by_model' => ['X5' => [$firstCar->id, $secondCar->id]],
        ])->getJson(route('api.voting.pair', ['model' => 'X5']))
            ->assertOk()
            ->assertJsonPath('data.status', 'ready');

        $this->assertContains($lastCar->id, $this->pairIds($pair->json('data')));

        $this->withSession([
            'voting.shown_car_ids_by_model' => ['X5' => [$firstCar->id, $secondCar->id, $lastCar->id]],
        ])->getJson(route('api.voting.pair', ['model' => 'X5']))
            ->assertOk()
            ->assertJsonPath('data.status', 'exhausted')
            ->assertJsonPath('data.pair_token', null);
    }

    #[Test]
    #[TestDox('валидирует обязательный параметр модели через входной DTO пары')]
    public function validatesVotingPairInputData(): void
    {
        $this->getJson(route('api.voting.pair'))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonPath('errors.model.0', 'The model field is required.');
    }

    #[Test]
    #[TestDox('возвращает отсутствующий API маршрут в едином JSON-формате')]
    public function returnsMissingApiRouteInTheUnifiedErrorFormat(): void
    {
        $this->getJson('/api/missing')
            ->assertNotFound()
            ->assertJsonPath('message', 'Not found.')
            ->assertJsonPath('errors', []);
    }

    #[Test]
    #[TestDox('начинает новый цикл в новой session и возвращает CSRF токен')]
    public function startsVotingCycleInANewSession(): void
    {
        $response = $this->withSession([
            'voting.shown_car_ids_by_model' => ['X5' => [1, 2]],
        ])->postJson(route('api.voting.cycle.start'));

        $response->assertOk()
            ->assertJsonStructure(['data' => ['csrf_token']])
            ->assertSessionMissing('voting.shown_car_ids_by_model');
    }

    #[Test]
    #[TestDox('возвращает непредвиденную ошибку API в едином JSON-формате')]
    public function returnsUnexpectedApiErrorsInTheUnifiedErrorFormat(): void
    {
        Route::get('api/testing-error', static function (): void {
            throw new \RuntimeException('Internal details must not be exposed.');
        });

        $this->getJson('/api/testing-error')
            ->assertServerError()
            ->assertJsonPath('message', 'Server error.')
            ->assertJsonPath('errors', []);
    }

    #[Test]
    #[TestDox('сохраняет голос для текущей пары и возвращает следующую пару')]
    public function storesVoteForCurrentPairAndReturnsNextPair(): void
    {
        [$leftCar, $rightCar] = $this->createCars('X5', 2);
        $pairToken = str_repeat('a', 64);

        $this->withSession($this->currentPairSession('X5', $leftCar, $rightCar, $pairToken))
            ->postJson(route('api.voting.votes.store'), [
            'model' => 'X5',
            'left_car_id' => $leftCar->id,
            'right_car_id' => $rightCar->id,
            'winner_side' => 'right',
            'pair_token' => $pairToken,
        ])->assertCreated()
            ->assertJsonPath('data.winner_car_id', $rightCar->id)
            ->assertJsonPath('data.loser_car_id', $leftCar->id)
            ->assertJsonPath('next_pair.status', 'ready');

        $this->assertDatabaseHas('votes', [
            'winner_car_id' => $rightCar->id,
            'loser_car_id' => $leftCar->id,
        ]);
    }

    #[Test]
    #[TestDox('отклоняет голос для пары, которая не была показана текущей сессии')]
    public function rejectsVoteForPairThatWasNotShownInTheCurrentSession(): void
    {
        [$leftCar, $rightCar, $forgedCar] = $this->createCars('X5', 3);
        $pairToken = str_repeat('a', 64);

        $this->withSession($this->currentPairSession('X5', $leftCar, $rightCar, $pairToken))
            ->postJson(route('api.voting.votes.store'), [
            'model' => 'X5',
            'left_car_id' => $leftCar->id,
            'right_car_id' => $forgedCar->id,
            'winner_side' => 'left',
            'pair_token' => $pairToken,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('left_car_id');

        $this->assertDatabaseCount('votes', 0);
    }

    #[Test]
    #[TestDox('отклоняет повторную отправку ранее использованной пары')]
    public function rejectsReplayOfAnAlreadySubmittedPair(): void
    {
        [$leftCar, $rightCar] = $this->createCars('X5', 2);
        $pairToken = str_repeat('a', 64);
        $payload = [
            'model' => 'X5',
            'left_car_id' => $leftCar->id,
            'right_car_id' => $rightCar->id,
            'winner_side' => 'left',
            'pair_token' => $pairToken,
        ];

        $this->withSession($this->currentPairSession('X5', $leftCar, $rightCar, $pairToken))
            ->postJson(route('api.voting.votes.store'), $payload)
            ->assertCreated();

        $this->withSession($this->currentPairSession('X5', $leftCar, $rightCar, $pairToken))
            ->postJson(route('api.voting.votes.store'), $payload)
            ->assertConflict()
            ->assertJsonPath('message', 'This voting pair has already been submitted.')
            ->assertJsonPath('errors', []);

        $this->assertDatabaseCount('votes', 1);
    }

    #[Test]
    #[TestDox('ограничивает частоту запросов на сохранение голосов')]
    public function rateLimitsVoteSubmissionRequests(): void
    {
        Cache::flush();

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $this->postJson(route('api.voting.votes.store'), [])
                ->assertUnprocessable();
        }

        $this->postJson(route('api.voting.votes.store'), [])
            ->assertTooManyRequests()
            ->assertJsonPath('message', 'Too many voting requests.')
            ->assertJsonPath('errors', []);
    }

    #[Test]
    #[TestDox('фильтрует статистику, считает голоса в SQL и возвращает метаданные пагинации')]
    public function filtersStatisticsAndReturnsVoteAggregation(): void
    {
        [$firstBmw, $secondBmw] = $this->createCars('X5', 2, [2001, 2005]);
        [$audi] = $this->createCars('A4', 1, [2010]);
        $this->createVote($firstBmw, $secondBmw, 2);
        $this->createVote($secondBmw, $firstBmw);
        $this->createVote($audi, $firstBmw, 3);

        $this->getJson(route('api.statistics.index', [
            'model' => 'X5',
            'year_from' => 2000,
            'year_to' => 2005,
            'per_page' => 1,
        ]))->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.votes_received', 2)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total_cars', 2)
            ->assertJsonPath('meta.total_votes', 3);
    }

    #[Test]
    #[TestDox('отклоняет обратный диапазон лет в статистике')]
    public function rejectsInvalidStatisticsYearRange(): void
    {
        $this->getJson(route('api.statistics.index', [
            'year_from' => 2020,
            'year_to' => 2010,
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('year_to');
    }

    #[Test]
    #[TestDox('ограничивает год выпуска диапазоном от 1900 года до текущего года')]
    public function validatesStatisticsYearBounds(): void
    {
        $currentYear = (int) now()->year;

        $this->getJson(route('api.statistics.index', ['year_from' => 1899]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('year_from');

        $this->getJson(route('api.statistics.index', ['year_to' => $currentYear + 1]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('year_to');
    }

    /**
     * @param list<int> $years
     * @return list<Car>
     */
    private function createCars(string $model, int $count, array $years = []): array
    {
        $cars = [];

        for ($index = 0; $index < $count; $index++) {
            $cars[] = Car::factory()->create([
                'model' => $model,
                'year' => $years[$index] ?? 2000 + $index,
            ]);
        }

        return $cars;
    }

    private function createVote(Car $winner, Car $loser, int $count = 1): void
    {
        Vote::factory()
            ->count($count)
            ->create([
                'winner_car_id' => $winner->id,
                'loser_car_id' => $loser->id,
            ]);
    }

    /** @return array<string, array<string, array<string, list<int>>>> */
    private function currentPairSession(string $model, Car $leftCar, Car $rightCar, string $pairToken): array
    {
        return [
            'voting.current_pairs_by_model' => [
                $model => [hash('sha256', $pairToken) => [$leftCar->id, $rightCar->id]],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $pair
     * @return list<int>
     */
    private function pairIds(array $pair): array
    {
        return [(int) $pair['left_car']['id'], (int) $pair['right_car']['id']];
    }
}
