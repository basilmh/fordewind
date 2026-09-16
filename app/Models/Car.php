<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\CarCustomStatus;
use App\ValueObjects\Money;
use Database\Factories\CarFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $auction_item_id
 * @property Money|null $current_high_pre_bid
 * @property CarCustomStatus $custom_status
 * @property Money|null $my_pre_bid
 * @property int $year
 * @property string $make
 * @property string $model
 * @property int $odometer
 * @property string $units
 * @property string|null $vehicle_location
 * @property string $engine
 * @property string $transmission
 * @property string $color
 * @property string $brand
 * @property Money|null $winning_bid_amount
 * @property string $image_filename
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static>|Car newModelQuery()
 * @method static Builder<static>|Car newQuery()
 * @method static Builder<static>|Car query()
 * @method static Builder<static>|Car whereAuctionItemId($value)
 * @method static Builder<static>|Car whereBrand($value)
 * @method static Builder<static>|Car whereColor($value)
 * @method static Builder<static>|Car whereCreatedAt($value)
 * @method static Builder<static>|Car whereCurrentHighPreBid($value)
 * @method static Builder<static>|Car whereCustomStatus($value)
 * @method static Builder<static>|Car whereEngine($value)
 * @method static Builder<static>|Car whereId($value)
 * @method static Builder<static>|Car whereImageFilename($value)
 * @method static Builder<static>|Car whereMake($value)
 * @method static Builder<static>|Car whereModel($value)
 * @method static Builder<static>|Car whereMyPreBid($value)
 * @method static Builder<static>|Car whereOdometer($value)
 * @method static Builder<static>|Car whereTransmission($value)
 * @method static Builder<static>|Car whereUnits($value)
 * @method static Builder<static>|Car whereUpdatedAt($value)
 * @method static Builder<static>|Car whereVehicleLocation($value)
 * @method static Builder<static>|Car whereWinningBidAmount($value)
 * @method static Builder<static>|Car whereYear($value)
 *
 * @property-read Collection<int, Vote> $lostVotes
 * @property-read int|null $lost_votes_count
 * @property-read Collection<int, Vote> $receivedVotes
 * @property-read int|null $received_votes_count
 *
 * @method static CarFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class Car extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'current_high_pre_bid' => MoneyCast::class,
            'my_pre_bid' => MoneyCast::class,
            'winning_bid_amount' => MoneyCast::class,
            'custom_status' => CarCustomStatus::class,
        ];
    }

    /** @return HasMany<Vote, $this> */
    public function receivedVotes(): HasMany
    {
        return $this->hasMany(Vote::class, 'winner_car_id');
    }

    /** @return HasMany<Vote, $this> */
    public function lostVotes(): HasMany
    {
        return $this->hasMany(Vote::class, 'loser_car_id');
    }
}
