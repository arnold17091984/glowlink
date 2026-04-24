<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property array $bounds
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|TabBounds newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TabBounds newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TabBounds query()
 * @method static \Illuminate\Database\Eloquent\Builder|TabBounds whereBounds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TabBounds whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TabBounds whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TabBounds whereUpdatedAt($value)
 * @property int $layout_no
 * @property int $tab_no
 * @method static \Illuminate\Database\Eloquent\Builder|TabBounds whereLayoutNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TabBounds whereTabNo($value)
 * @mixin \Eloquent
 */
class TabBounds extends Model
{
    use HasFactory;

    protected $casts = [
        'bounds' => 'array',
    ];
}
