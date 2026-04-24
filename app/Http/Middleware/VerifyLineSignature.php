<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * LINE Messaging API Webhook の署名検証ミドルウェア。
 *
 * HMAC-SHA256(channel_secret, raw_body) の base64 値を
 * X-Line-Signature ヘッダと時間一定比較する。
 *
 * 参考: https://developers.line.biz/en/reference/messaging-api/#signature-validation
 */
class VerifyLineSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-Line-Signature');
        $secret = (string) config('line-bot.channel_secret');

        if (empty($secret)) {
            abort(500, 'LINE channel secret is not configured.');
        }

        if (empty($signature)) {
            abort(401, 'Missing X-Line-Signature header.');
        }

        $expected = base64_encode(
            hash_hmac('sha256', $request->getContent(), $secret, true)
        );

        if (! hash_equals($expected, (string) $signature)) {
            abort(401, 'Invalid LINE webhook signature.');
        }

        return $next($request);
    }
}
