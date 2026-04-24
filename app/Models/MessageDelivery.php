<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 
 *
 * @property int $id
 * @property int $message_id
 * @property int $delivery_id
 * @property string $delivery_type
 * @property \Illuminate\Support\Carbon|null $delivery_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $delivery
 * @method static \Database\Factories\MessageDeliveryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|MessageDelivery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MessageDelivery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MessageDelivery query()
 * @method static \Illuminate\Database\Eloquent\Builder|MessageDelivery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MessageDelivery whereDeliveryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MessageDelivery whereDeliveryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MessageDelivery whereDeliveryType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MessageDelivery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MessageDelivery whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MessageDelivery whereUpdatedAt($value)
 * @property-read \App\Models\Message $message
 * @property string $message_type
 * @method static \Illuminate\Database\Eloquent\Builder|MessageDelivery whereMessageType($value)
 * @mixin \Eloquent
 */
class MessageDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'message_type',
        'delivery_id',
        'delivery_type',
        'delivery_date',
    ];

    protected $casts = [
        'delivery_date' => 'datetime',
    ];

    public function delivery(): MorphTo
    {
        return $this->morphTo();
    }

    public function message(): MorphTo
    {
        return $this->morphTo('message');
    }
}
