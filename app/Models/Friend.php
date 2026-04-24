<?php

namespace App\Models;

use App\Enums\FlagEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * App\Models\Friend
 *
 * @property int $id
 * @property string $name
 * @property string $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Talk> $talk
 * @property-read int|null $talk_count
 * @method static \Database\Factories\FriendFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Friend newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Friend newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Friend query()
 * @method static \Illuminate\Database\Eloquent\Builder|Friend whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Friend whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Friend whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Friend whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Friend whereUserId($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Talk> $talks
 * @property-read int|null $talks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Talk> $receivedBy
 * @property-read int|null $received_by_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Talk> $sendBy
 * @property-read int|null $send_by_count
 * @property string|null $profile_url
 * @property FlagEnum $mark
 * @method static \Illuminate\Database\Eloquent\Builder|Friend whereMark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Friend whereProfileUrl($value)
 * @mixin \Illuminate\Database\Eloquent\Model
 * @property int|null $referred_by
 * @property int $referral_count
 * @property int $points
 * @method static \Illuminate\Database\Eloquent\Builder|Friend wherePoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Friend whereReferralCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Friend whereReferredBy($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AwardPointsLogs> $awardPointsLogs
 * @property-read int|null $award_points_logs_count
 * @property-read Friend|null $referredBy
 * @mixin \Eloquent
 */
class Friend extends Model
{
    use HasFactory;

    protected $fillable = ['name',
        'user_id',
        'profile_url',
        'mark',
        'referred_by',
        'reason',
        'points',
        'referral_count',
    ];

    protected $casts = [
        'mark' => FlagEnum::class,
    ];

    public function sendBy(): MorphMany
    {
        return $this->morphMany(Talk::class, 'sender');
    }

    public function receivedBy(): MorphMany
    {
        return $this->morphMany(Talk::class, 'reciever');
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(Friend::class, 'referred_by');
    }

    public function awardPointsLogs(): HasMany
    {
        return $this->hasMany(AwardPointsLogs::class);
    }
}
