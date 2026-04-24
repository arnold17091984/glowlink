<?php

namespace App\Models;

use App\Enums\CouponAmountTypeEnum;
use App\Enums\CouponTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $from
 * @property string $till
 * @property string $description
 * @property CouponAmountTypeEnum $amount_type
 * @property int $amount
 * @property bool $is_lottery
 * @property int|null $win_rate
 * @property bool|null $is_limited
 * @property int|null $no_of_users
 * @property int $unlimited
 * @property bool $is_edit_coupon
 * @property string $coupon_code
 * @property CouponTypeEnum $coupon_type
 * @property string $required_points
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RichAction> $richAction
 * @property-read int|null $rich_action_count
 * @method static \Database\Factories\CouponFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon query()
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereAmountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereCouponCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereCouponType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereIsEditCoupon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereIsLimited($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereIsLottery($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereNoOfUsers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereRequiredPoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereTill($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereUnlimited($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereWinRate($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MessageDelivery> $messageDeliveries
 * @property-read int|null $message_deliveries_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FriendCoupon> $friendCoupons
 * @property-read int|null $friend_coupons_count
 * @mixin \Eloquent
 */
class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'from',
        'till',
        'image',
        'description',
        'is_lottery',
        'win_rate',
        'is_limited',
        'no_of_users',
        'unlimited',
        'is_edit_coupon',
        'coupon_code',
        'coupon_type',
        'amount_type',
        'amount',
        'required_points',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_lottery' => 'boolean',
        'is_limited' => 'boolean',
        'is_limited' => 'boolean',
        'is_edit_coupon' => 'boolean',
        'amount_type' => CouponAmountTypeEnum::class,
        'coupon_type' => CouponTypeEnum::class,
    ];

    public function messageDeliveries(): MorphMany
    {
        return $this->morphMany(MessageDelivery::class, 'message');
    }

    public function friendCoupons(): HasMany
    {
        return $this->hasMany(FriendCoupon::class);
    }

    public function isUsed(): bool
    {
        return $this->messageDeliveries()->exists() || $this->friendCoupons()->exists();
    }
}
