<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Models\AutoResponse
 *
 * @property int $id
 * @property int $message_id
 * @property string $name
 * @property bool $is_active
 * @property array $condition
 * @property int $no_of_response
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Message|null $message
 * @method static \Illuminate\Database\Eloquent\Builder|AutoResponse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AutoResponse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AutoResponse query()
 * @method static \Illuminate\Database\Eloquent\Builder|AutoResponse whereCondition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AutoResponse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AutoResponse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AutoResponse whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AutoResponse whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AutoResponse whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AutoResponse whereNoOfResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AutoResponse whereUpdatedAt($value)
 * @mixin \Illuminate\Database\Eloquent\Model
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\MessageDelivery|null $messageDeliveries
 * @property-read \App\Models\MessageDelivery|null $messageDelivery
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RichAction> $richActions
 * @property-read int|null $rich_actions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RichAction> $richAction
 * @property-read int|null $rich_action_count
 * @mixin \Eloquent
 */
class AutoResponse extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['name', 'is_active', 'condition'];

    protected $casts = [
        'condition' => 'array',
        'is_active' => 'bool',
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

    public function richAction(): MorphMany
    {
        return $this->morphMany(RichAction::class, 'model');
    }

    public function isUsed(): bool
    {
        return $this->richAction()->exists();
    }
}
