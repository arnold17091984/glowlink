<?php

namespace App\Http\Middleware;

use App\Models\LineChannel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * LINE Messaging API Webhook の署名検証ミドルウェア。
 *
 * 優先順位:
 *   1. ルートに {channel} パラメータ (slug) があれば line_channels テーブルから channel_secret を取得
 *   2. それ以外は config('line-bot.channel_secret') (レガシー単一チャネル向け)
 *
 * 参考: https://developers.line.biz/en/reference/messaging-api/#signature-validation
 */
class VerifyLineSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-Line-Signature');

        if (empty($signature)) {
            abort(401, 'Missing X-Line-Signature header.');
        }

        $secret = $this->resolveSecret($request);

        if (empty($secret)) {
            abort(500, 'LINE channel secret is not configured.');
        }

        $expected = base64_encode(
            hash_hmac('sha256', $request->getContent(), $secret, true)
        );

        if (! hash_equals($expected, (string) $signature)) {
            abort(401, 'Invalid LINE webhook signature.');
        }

        // 管理画面で接続状態を可視化するため、成功時にチャネルへ印を付ける
        if ($channel = $request->attributes->get('line_channel')) {
            /** @var LineChannel $channel */
            $channel->update(['last_connected_at' => now()]);
        }

        return $next($request);
    }

    private function resolveSecret(Request $request): ?string
    {
        $slug = $request->route('channel');

        if ($slug) {
            $channel = LineChannel::findBySlug($slug);
            if (! $channel) {
                abort(404, "Unknown LINE channel slug: {$slug}");
            }
            $request->attributes->set('line_channel', $channel);

            return $channel->channel_secret;
        }

        return config('line-bot.channel_secret') ?: optional(LineChannel::default())->channel_secret;
    }
}
