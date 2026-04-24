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
 * @property array $card
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MessageDelivery> $messageDeliveries
 * @property-read int|null $message_deliveries_count
 * @method static \Illuminate\Database\Eloquent\Builder|RichCard newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RichCard newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RichCard query()
 * @method static \Illuminate\Database\Eloquent\Builder|RichCard whereCard($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichCard whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichCard whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichCard whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichCard whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RichCard extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'name',
        'card',
    ];

    protected $casts = [
        'card' => 'json',
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
        if ($this->card) {
            foreach ($this?->card as $key => $card) {
                $this->addMediaCollection('rich_cards_'.$key);
            }
        }

    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('rich_cards');
    }

    public function messageDeliveries(): MorphMany
    {
        return $this->morphMany(MessageDelivery::class, 'message');
    }
}
