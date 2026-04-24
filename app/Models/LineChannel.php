<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * LineChannel — 1 件の LINE 公式アカウント接続設定。
 *
 * channel_secret と channel_access_token は自動的に encrypted cast で
 * AES-256-CBC 暗号化されて DB に保存される (APP_KEY で鍵付け)。
 *
 * Webhook URL は $channel->webhook_url で取得: https://{app_url}/messages/{slug}
 */
class LineChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'basic_id',
        'channel_id',
        'channel_secret',
        'channel_access_token',
        'liff_id',
        'is_default',
        'is_active',
        'last_connected_at',
        'notes',
    ];

    protected $casts = [
        'channel_secret' => 'encrypted',
        'channel_access_token' => 'encrypted',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'last_connected_at' => 'datetime',
    ];

    /**
     * API レスポンスで暗号化カラム全文を返さないよう隠蔽。
     * Filament フォームには $casts で自動復号された値が渡る。
     */
    protected $hidden = ['channel_secret', 'channel_access_token'];

    protected static function booted(): void
    {
        // 作成時に slug 未指定なら name から生成
        static::creating(function (LineChannel $channel) {
            if (empty($channel->slug)) {
                $channel->slug = Str::slug($channel->name, '-') ?: 'channel-'.Str::random(6);
            }
        });

        // is_default を ON にしたら他を OFF に
        static::saving(function (LineChannel $channel) {
            if ($channel->is_default) {
                static::where('id', '!=', $channel->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function webhookUrl(): string
    {
        return rtrim(config('app.url'), '/').'/messages/'.$this->slug;
    }

    public function getWebhookUrlAttribute(): string
    {
        return $this->webhookUrl();
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->where('is_active', true)->first();
    }

    public static function default(): ?self
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }

    /**
     * 外部表示用に access_token をマスクした文字列を返す。
     */
    public function maskedAccessToken(): string
    {
        $token = $this->channel_access_token ?? '';
        if (strlen($token) < 12) {
            return str_repeat('•', strlen($token));
        }

        return substr($token, 0, 6).str_repeat('•', 24).substr($token, -4);
    }
}
