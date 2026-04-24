<?php

namespace App\Models;

use App\Enums\AwardTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property int $friend_id
 * @property int|null $referral_id
 * @property float $awarded_points
 * @property AwardTypeEnum $type
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Friend|null $friend
 * @property-read \App\Models\Referral|null $referral
 * @method static \Illuminate\Database\Eloquent\Builder|AwardPointsLogs newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AwardPointsLogs newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AwardPointsLogs query()
 * @method static \Illuminate\Database\Eloquent\Builder|AwardPointsLogs whereAwardedPoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AwardPointsLogs whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AwardPointsLogs whereFriendId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AwardPointsLogs whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AwardPointsLogs whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AwardPointsLogs whereReferralId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AwardPointsLogs whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AwardPointsLogs whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AwardPointsLogs extends Model
{
    use HasFactory;

    protected $fillable = [
        'friend_id',
        'referral_id',
        'awarded_points',
        'reason',
        'type',
    ];

    protected $casts = [
        'type' => AwardTypeEnum::class,
    ];

    public function friend(): BelongsTo
    {
        return $this->belongsTo(Friend::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }
}
