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
 * @property string $title
 * @property array $layout
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|RichMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RichMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RichMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder|RichMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMessage whereLayout($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMessage whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMessage whereUpdatedAt($value)
 * @property int $layout_rich_message_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @method static \Illuminate\Database\Eloquent\Builder|RichMessage whereLayoutRichMessageId($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Layout> $layouts
 * @property-read int|null $layouts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MessageDelivery> $messageDeliveries
 * @property-read int|null $message_deliveries_count
 * @mixin \Eloquent
 */
class RichMessage extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'title',
        'layout_rich_message_id',
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

    public function layouts(): MorphMany
    {
        return $this->morphMany(Layout::class, 'rich');
    }

    public function messageDeliveries(): MorphMany
    {
        return $this->morphMany(MessageDelivery::class, 'message');
    }

    public function isUsed(): bool
    {
        return $this->messageDeliveries()->exists();
    }
}
