<?php

namespace App\Http\Controllers\Liff;

use App\Actions\Coupon\RedeemRewardAction;
use App\Http\Controllers\Controller;
use App\Models\Friend;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;

/**
 * LIFF クーポンウォレット PoC - バックエンド API。
 *
 * フロー:
 *   1. Blade 画面上で liff.getIDToken() を取得
 *   2. 本 API に Bearer として送信
 *   3. LINE Verify API で id_token を検証 → userId を信頼
 *   4. 既存の RedeemRewardAction を呼び出してクーポン抽選/付与
 *
 * 本番運用では id_token のキャッシュ・nonce 検証・CSRF 無効化の代替として
 * Sanctum のステートレス API トークンに昇格させることを推奨。
 */
class CouponApiController extends Controller
{
    #[Get('liff/api/coupons/mine', middleware: ['throttle:liff-api'])]
    public function mine(Request $request): JsonResponse
    {
        $friend = $this->authenticateViaIdToken($request);
        $coupons = $friend->relationLoaded('friendCoupons')
            ? $friend->friendCoupons
            : \App\Models\FriendCoupon::with('coupon')
                ->where('friend_id', $friend->id)
                ->latest()
                ->get();

        return response()->json([
            'friend' => [
                'id' => $friend->id,
                'name' => $friend->name,
                'points' => $friend->points,
            ],
            'coupons' => $coupons->map(fn ($fc) => [
                'code' => $fc->coupon->coupon_code ?? null,
                'name' => $fc->coupon->name ?? null,
                'status' => $fc->status?->value ?? $fc->status,
                'expires_at' => $fc->coupon->till ?? null,
            ]),
        ]);
    }

    #[Post('liff/api/coupons/redeem', middleware: ['throttle:liff-api'])]
    public function redeem(Request $request, RedeemRewardAction $redeem): JsonResponse
    {
        $data = $request->validate([
            'coupon_code' => 'required|string|max:32',
        ]);

        $friend = $this->authenticateViaIdToken($request);

        $result = $redeem->execute($data['coupon_code'], $friend->user_id);

        return response()->json([
            'result' => $result,
        ]);
    }

    /**
     * LINE ID Token Verify API で id_token を検証し、Friend を解決する。
     *
     * @see https://developers.line.biz/en/reference/line-login/#verify-id-token
     */
    private function authenticateViaIdToken(Request $request): Friend
    {
        $idToken = $request->bearerToken() ?: $request->input('id_token');
        abort_if(empty($idToken), 401, 'Missing LIFF id_token');

        $channelId = config('line-bot.channel_id');
        abort_if(empty($channelId), 500, 'LINE channel_id not configured');

        $response = Http::asForm()->post('https://api.line.me/oauth2/v2.1/verify', [
            'id_token' => $idToken,
            'client_id' => $channelId,
        ]);

        if (! $response->successful()) {
            abort(401, 'Invalid LIFF id_token');
        }

        $payload = $response->json();
        $userId = $payload['sub'] ?? null;
        abort_if(empty($userId), 401, 'id_token missing sub claim');

        $friend = Friend::whereUserId($userId)->first();
        abort_if(! $friend, 404, 'LINE user has not registered as Friend');

        return $friend;
    }
}
