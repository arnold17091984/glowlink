<?php

namespace App\Domains\LineIntegration\Gateway;

use App\Models\LineChannel;
use Illuminate\Contracts\Container\Container;

/**
 * LineGateway をチャネル別に解決するファクトリ。
 *
 *   $manager = app(LineGatewayManager::class);
 *   $manager->forChannel($channel)->push($req);
 *   $manager->default()->push($req);   // .env チャネル
 *
 * テスト時は LineGatewayManager 自体を fake に差し替えるか、
 * forChannel() の戻り値を Mock するパターンを推奨。
 */
class LineGatewayManager
{
    /** @var array<int|string, LineGateway> */
    private array $cache = [];

    public function __construct(private readonly Container $container)
    {
    }

    /**
     * 特定の LineChannel 用 Gateway を返す。
     */
    public function forChannel(LineChannel $channel): LineGateway
    {
        $key = 'ch:'.$channel->id;

        return $this->cache[$key] ??= LineMessagingApiGateway::fromChannel($channel);
    }

    /**
     * 旧仕様: line_channels テーブルに is_default = true があればそれを優先、
     * 無ければ .env (config('line-bot.channel_access_token')) を使う。
     */
    public function default(): LineGateway
    {
        $key = 'default';
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $defaultChannel = LineChannel::default();
        if ($defaultChannel) {
            return $this->cache[$key] = LineMessagingApiGateway::fromChannel($defaultChannel);
        }

        $token = (string) config('line-bot.channel_access_token');
        if ($token === '') {
            throw new \RuntimeException(
                'No LINE channel configured: set up at least one LineChannel via /admin/line-channels '
                .'(or fill LINE_BOT_CHANNEL_ACCESS_TOKEN in .env for legacy single-channel mode).'
            );
        }

        return $this->cache[$key] = new LineMessagingApiGateway($token);
    }

    /**
     * RichMenu / Friend など line_channel_id を持つモデルから安全に解決する。
     */
    public function forChannelId(?int $channelId): LineGateway
    {
        if ($channelId === null) {
            return $this->default();
        }

        $channel = LineChannel::find($channelId);
        if (! $channel) {
            return $this->default();
        }

        return $this->forChannel($channel);
    }

    public function flush(): void
    {
        $this->cache = [];
    }
}
