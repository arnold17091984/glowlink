<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 
 *
 * @property int $id
 * @property int $rich_id
 * @property string $rich_type
 * @property float|null $x
 * @property float|null $y
 * @property string|null $width
 * @property string|null $height
 * @property string|null $offsetTop
 * @property string|null $offsetBottom
 * @property string|null $offsetStart
 * @property string|null $offsetEnd
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $rich
 * @property-read \App\Models\RichAction|null $richAction
 * @method static \Illuminate\Database\Eloquent\Builder|Layout newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Layout newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Layout query()
 * @method static \Illuminate\Database\Eloquent\Builder|Layout whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Layout whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Layout whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Layout whereOffsetBottom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Layout whereOffsetEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Layout whereOffsetStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Layout whereOffsetTop($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Layout whereRichId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Layout whereRichType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Layout whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Layout whereWidth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Layout whereX($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Layout whereY($value)
 * @mixin \Eloquent
 */
class Layout extends Model
{
    use HasFactory;

    protected $fillable = [
        'rich_id',
        'rich_type',
        'x',
        'y',
        'width',
        'height',
        'offsetTop',
        'offsetBottom',
        'offsetStart',
        'offsetEnd',
    ];

    public function rich(): MorphTo
    {
        return $this->morphTo();
    }

    public function richAction(): HasOne
    {
        return $this->hasOne(RichAction::class);
    }
}
