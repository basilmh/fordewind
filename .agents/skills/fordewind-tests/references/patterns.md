# Fordewind Testing Patterns

## Feature test with database state

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class VoteControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[TestDox('сохраняет голос для корректной пары')]
    public function persistsVoteForValidPair(): void
    {
        [$left, $right] = $this->createVotingPair();

        $response = $this->postJson(route('vote.store'), [
            'model' => 'Focus',
            'left_car_id' => $left->id,
            'right_car_id' => $right->id,
            'winner_side' => 'left',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('votes', ['winner_car_id' => $left->id]);
    }

    /** @return array{Car, Car} */
    private function createVotingPair(): array
    {
        return [
            Car::factory()->create(['model' => 'Focus']),
            Car::factory()->create(['model' => 'Focus']),
        ];
    }
}
```

## Scenario matrix

```php
#[Test]
#[TestDox('отклоняет некорректный диапазон годов')]
#[DataProvider('yearRangeCasesProvider')]
public function rejectsInvalidYearRange(array $payload): void
{
    $this->getJson(route('statistics.index', $payload))
        ->assertUnprocessable();
}

public static function yearRangeCasesProvider(): array
{
    return [
        'from is later than to' => [[
            'year_from' => 2024,
            'year_to' => 2020,
        ]],
        'year contains text' => [[
            'year_from' => 'not-a-year',
        ]],
    ];
}
```

## Pair-selection assertions

For a selected model, repeatedly request a pair until exhaustion and collect shown IDs. Assert that no ID appears twice in the cycle, each pair contains two different cars, and a new cycle starts only after every available photo was shown.
