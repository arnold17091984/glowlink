<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property int $is_active
 * @property float $awarded_points
 * @property string|null $message
 * @property string $link
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RichAction> $richAction
 * @property-read int|null $rich_action_count
 * @method static \Illuminate\Database\Eloquent\Builder|Referral newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Referral newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Referral query()
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereAwardedPoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereUpdatedAt($value)
 * @property float $referrer_awarded_points
 * @property int $referral_acceptance_points
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereReferralAcceptancePoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereReferrerAwardedPoints($value)
 * @mixin \Eloquent
 */
class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'message',
        'is_active',
        'referral_acceptance_points',
        'link',
        'referrer_awarded_points',
    ];

    public function richAction(): MorphMany
    {
        return $this->morphMany(RichAction::class, 'model');
    }

    public function isUsed(): bool
    {
        return $this->richAction()->exists();
    }
}
