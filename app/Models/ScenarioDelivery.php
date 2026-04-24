<?php

namespace App\Models;

use App\Enums\ScenarioStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Models\ScenarioDelivery
 *
 * @property int $id
 * @property string $name
 * @property array $messages
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\ScenarioDeliveryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ScenarioDelivery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ScenarioDelivery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ScenarioDelivery query()
 * @method static \Illuminate\Database\Eloquent\Builder|ScenarioDelivery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScenarioDelivery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScenarioDelivery whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScenarioDelivery whereMessages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScenarioDelivery whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScenarioDelivery whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScenarioDelivery whereUpdatedAt($value)
 * @property string $send_to
 * @method static \Illuminate\Database\Eloquent\Builder|ScenarioDelivery whereSendTo($value)
 * @property bool $is_sent
 * @method static \Illuminate\Database\Eloquent\Builder|ScenarioDelivery whereIsSent($value)
 * @mixin \Illuminate\Database\Eloquent\Model
 * @property ScenarioStatusEnum $status
 * @method static \Illuminate\Database\Eloquent\Builder|ScenarioDelivery whereStatus($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MessageDelivery> $messageDeliveries
 * @property-read int|null $message_deliveries_count
 * @mixin \Eloquent
 */
class ScenarioDelivery extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['name', 'send_to', 'status'];

    protected $casts = [
        'start_date' => 'datetime',
        'status' => ScenarioStatusEnum::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(class_basename(self::class))
            ->logFillable()
            ->dontLogIfAttributesChangedOnly(['status']);
    }

    public function messageDeliveries(): MorphMany
    {
        return $this->morphMany(MessageDelivery::class, 'delivery')->orderBy('delivery_date');
    }
}
