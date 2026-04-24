<?php

namespace App\Models;

use App\Enums\FlagEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * App\Models\Talk
 *
 * @property int $id
 * @property int $friend_id
 * @property string $language
 * @property mixed $message
 * @property mixed $reply
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Friend|null $friend
 * @method static \Database\Factories\TalkFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Talk newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Talk newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Talk query()
 * @method static \Illuminate\Database\Eloquent\Builder|Talk whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Talk whereFriendId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Talk whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Talk whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Talk whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Talk whereReply($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Talk whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Talk whereReadAt($value)
 * @property int $sender_id
 * @property string $sender_type
 * @property int|null $receiver_id
 * @property string $receiver_type
 * @property FlagEnum $flag
 * @property-read Model|\Eloquent $receiver
 * @property-read Model|\Eloquent $sender
 * @method static \Illuminate\Database\Eloquent\Builder|Talk whereFlag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Talk whereReceiverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Talk whereReceiverType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Talk whereSenderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Talk whereSenderType($value)
 * @property string|null $read_at
 * @mixin \Illuminate\Database\Eloquent\Model
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @mixin \Eloquent
 */
class Talk extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = ['read_at', 'language', 'message', 'sender_type', 'sender_id', 'receiver_type', 'receiver_id', 'flag'];

    protected $casts = [
        'message' => 'array',
        'flag' => FlagEnum::class,
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('talk');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->keepOriginalImageFormat()
            ->width(100)
            ->height(100);
    }

    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    public function receiver(): MorphTo
    {
        return $this->morphTo();
    }
}
