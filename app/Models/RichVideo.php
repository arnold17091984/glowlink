<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string|null $title
 * @property string|null $description
 * @property array|null $button
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MessageDelivery> $messageDeliveries
 * @property-read int|null $message_deliveries_count
 * @method static \Illuminate\Database\Eloquent\Builder|RichVideo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RichVideo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RichVideo query()
 * @method static \Illuminate\Database\Eloquent\Builder|RichVideo whereButton($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichVideo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichVideo whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichVideo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichVideo whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichVideo whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichVideo whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RichVideo extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'name',
        'title',
        'description',
        'button',
    ];

    protected $casts = [
        'button' => 'json',
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
        $this->addMediaCollection('rich_videos');
        $this->addMediaCollection('rich_video_thumbnails');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('rich_videos');
        $this->addMediaConversion('rich_video_thumbnails');
    }

    public function messageDeliveries(): MorphMany
    {
        return $this->morphMany(MessageDelivery::class, 'message');
    }
}
