<?php

namespace App\Models;

use App\Enums\RepeatEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Models\Broadcast
 *
 * @property int $id
 * @property int $message_id
 * @property string $name
 * @property string $send_to
 * @property int|null $every
 * @property RepeatEnum $repeat
 * @property \Illuminate\Support\Carbon $start_date
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Message|null $message
 *
 * @method static \Database\Factories\BroadcastFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Broadcast newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Broadcast newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Broadcast query()
 * @method static \Illuminate\Database\Eloquent\Builder|Broadcast whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Broadcast whereEvery($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Broadcast whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Broadcast whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Broadcast whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Broadcast whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Broadcast whereRepeat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Broadcast whereSendTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Broadcast whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Broadcast whereUpdatedAt($value)
 *
 * @property \Illuminate\Support\Carbon|null $next_date
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\MessageDelivery|null $messageDeliveries
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Broadcast whereNextDate($value)
 *
 * @property-read \App\Models\MessageDelivery|null $messageDelivery
 * @property bool $is_send_now
 * @property string|null $last_date
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Broadcast whereIsSendNow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Broadcast whereLastDeliveryDate($value)
 *
 * @mixin \Eloquent
 */
class Broadcast extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'send_to',
        'repeat',
        'is_send_now',
        'every',
        'start_date',
        'is_active',
        'next_date',
        'last_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_send_now' => 'boolean',
        'start_date' => 'datetime',
        'next_date' => 'datetime',
        'repeat' => RepeatEnum::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(class_basename(self::class))
            ->logFillable()
            ->logOnlyDirty();
    }

    public function messageDelivery(): MorphOne
    {
        return $this->morphOne(MessageDelivery::class, 'delivery');
    }
}
