<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property array $bounds
 * @property bool $is_with_tab
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\RichMenuLayoutFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuLayout newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuLayout newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuLayout query()
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuLayout whereBounds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuLayout whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuLayout whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuLayout whereIsWithTab($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuLayout whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RichMenuLayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'bounds',
        'is_with_tab',
    ];

    protected $casts = [
        'bounds' => 'array',
        'is_with_tab' => 'boolean',
    ];
}
