<?php

namespace App\Models;

use App\Enums\MessagingTypeEnum;
use App\Enums\UsedForEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * App\Models\Message
 *
 * @property int $id
 * @property string $message
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AutoResponse> $autoResponses
 * @property-read int|null $auto_responses_count
 * @method static \Database\Factories\MessageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Message newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message query()
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereUpdatedAt($value)
 * @property UsedForEnum $used_for
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereUsedFor($value)
 * @mixin \Illuminate\Database\Eloquent\Model
 * @property string $name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Broadcast> $broadcasts
 * @property-read int|null $broadcasts_count
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereName($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MessageDelivery> $messageDeliveries
 * @property-read int|null $message_deliveries_count
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @mixin \Eloquent
 */
class Message extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = ['message', 'type', 'used_for', 'name'];

    protected $casts = [
        'type' => MessagingTypeEnum::class,
        'used_for' => UsedForEnum::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(class_basename(self::class))
            ->logFillable()
            ->logOnlyDirty();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('messages');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('messages');
    }

    public function messageDeliveries(): MorphMany
    {
        return $this->morphMany(MessageDelivery::class, 'message');
    }
}
