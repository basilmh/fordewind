<?php

namespace App\Models;

use Database\Factories\VoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $winner_car_id Автомобиль, получивший голос
 * @property int $loser_car_id Автомобиль, проигравший в паре
 * @property string $voter_session_hash HMAC идентификатора анонимной сессии
 * @property string $pair_token_hash Хеш одноразового токена пары
 * @property string $pair_hash Хеш неупорядоченной пары автомобилей
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Car $loserCar
 * @property-read Car $winnerCar
 *
 * @method static VoteFactory factory($count = null, $state = [])
 * @method static Builder<static>|Vote newModelQuery()
 * @method static Builder<static>|Vote newQuery()
 * @method static Builder<static>|Vote query()
 * @method static Builder<static>|Vote whereCreatedAt($value)
 * @method static Builder<static>|Vote whereId($value)
 * @method static Builder<static>|Vote whereLoserCarId($value)
 * @method static Builder<static>|Vote whereUpdatedAt($value)
 * @method static Builder<static>|Vote whereWinnerCarId($value)
 * @method static Builder<static>|Vote wherePairHash($value)
 * @method static Builder<static>|Vote wherePairTokenHash($value)
 * @method static Builder<static>|Vote whereVoterSessionHash($value)
 *
 * @mixin \Eloquent
 */
class Vote extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /** @return BelongsTo<Car, $this> */
    public function winnerCar(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'winner_car_id');
    }

    /** @return BelongsTo<Car, $this> */
    public function loserCar(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'loser_car_id');
    }
}
