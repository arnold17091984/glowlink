<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\RichMenuAliasFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuAlias newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuAlias newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuAlias query()
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuAlias whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuAlias whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuAlias whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuAlias whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuAlias whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RichMenu> $richMenus
 * @property-read int|null $rich_menus_count
 * @property string $reference
 * @property int $layout_no
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuSet whereLayoutNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuSet whereReference($value)
 * @property int $is_active
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenuSet whereIsActive($value)
 * @mixin \Eloquent
 */
class RichMenuSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'layout_no',
        'name',
        'is_active',
        'reference',
    ];

    public function richMenus(): HasMany
    {
        return $this->hasMany(RichMenu::class);
    }
}
