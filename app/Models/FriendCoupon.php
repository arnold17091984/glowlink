<?php

namespace App\Models;

use App\Enums\RedeemCouponStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property int $friend_id
 * @property int $coupon_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|FriendCoupon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FriendCoupon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FriendCoupon query()
 * @method static \Illuminate\Database\Eloquent\Builder|FriendCoupon whereCouponId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FriendCoupon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FriendCoupon whereFriendId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FriendCoupon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FriendCoupon whereUpdatedAt($value)
 * @property string $status
 * @method static \Illuminate\Database\Eloquent\Builder|FriendCoupon whereStatus($value)
 * @property-read \App\Models\Coupon|null $coupon
 * @property-read \App\Models\Friend|null $friend
 * @mixin \Eloquent
 */
class FriendCoupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'friend_id',
        'coupon_id',
        'status',
    ];

    protected $casts = [
        'status' => RedeemCouponStatusEnum::class,
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function friend(): BelongsTo
    {
        return $this->belongsTo(Friend::class);
    }
}
