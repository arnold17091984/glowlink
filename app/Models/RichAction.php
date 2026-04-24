<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 
 *
 * @property int $id
 * @property int|null $coupon_id
 * @property int $layout_id
 * @property string $type
 * @property string|null $label
 * @property string|null $link
 * @property string|null $text
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Layout $layout
 * @method static \Illuminate\Database\Eloquent\Builder|RichAction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RichAction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RichAction query()
 * @method static \Illuminate\Database\Eloquent\Builder|RichAction whereCouponId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichAction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichAction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichAction whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichAction whereLayoutId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichAction whereLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichAction whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichAction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichAction whereUpdatedAt($value)
 * @property int|null $auto_response_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AutoResponse> $autoResponse
 * @property-read int|null $auto_response_count
 * @method static \Illuminate\Database\Eloquent\Builder|RichAction whereAutoResponseId($value)
 * @property int|null $model_id
 * @property string|null $model_type
 * @property-read Model|\Eloquent $model
 * @method static \Illuminate\Database\Eloquent\Builder|RichAction whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichAction whereModelType($value)
 * @mixin \Eloquent
 */
class RichAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_id',
        'model_type',
        'layout_id',
        'type',
        'label',
        'link',
        'text',
    ];

    public function layout(): BelongsTo
    {
        return $this->belongsTo(Layout::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
