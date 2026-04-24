<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property array $layout
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|LayoutRichMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LayoutRichMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LayoutRichMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder|LayoutRichMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LayoutRichMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LayoutRichMessage whereLayout($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LayoutRichMessage whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class LayoutRichMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'layout',
    ];

    protected $casts = [
        'layout' => 'json',
    ];
}
