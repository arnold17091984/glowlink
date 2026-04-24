<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * 
 *
 * @property int $id
 * @property string $reference
 * @property int $tab_no
 * @property string $name
 * @property int $selected
 * @property string $chatbar_text
 * @property string $width
 * @property string $height
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\RichMenuFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu query()
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu whereChatbarText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu whereSelected($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu whereTabNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu whereWidth($value)
 * @property int $selected_layout
 * @property array $areas
 * @property array $actions
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu whereActions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu whereAreas($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu whereSelectedLayout($value)
 * @property-read \App\Models\RichMenuAlias|null $richMenuAlias
 * @property int $rich_menu_set_id
 * @property-read \App\Models\RichMenuSet|null $richMenuSet
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu whereRichMenuSetId($value)
 * @property string $rich_menu_alias
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu whereRichMenuAlias($value)
 * @property string|null $rich_menu_id
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu whereRichMenuId($value)
 * @property int|null $parent_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, RichMenu> $children
 * @property-read int|null $children_count
 * @property-read RichMenu|null $parent
 * @method static \Illuminate\Database\Eloquent\Builder|RichMenu whereParentId($value)
 * @mixin \Eloquent
 */
class RichMenu extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'rich_menu_set_id',
        'tab_no',
        'rich_menu_id',
        'name',
        'rich_menu_alias',
        'selected',
        'chatbar_text',
        'width',
        'height',
        'areas',
        'reference',
        'actions',
        'selected_layout',
    ];

    protected $casts = [
        'areas' => 'array',
        'actions' => 'array',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('richmenus');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('richmenus');
    }

    public function richMenuSet(): BelongsTo
    {
        return $this->belongsTo(RichMenuSet::class, 'rich_menu_set_id');
    }

    public function parent()
    {
        return $this->belongsTo(RichMenu::class, 'parent_id');
    }

    /**
     * Get the child tabs.
     */
    public function children()
    {
        return $this->hasMany(RichMenu::class, 'parent_id');
    }
}
