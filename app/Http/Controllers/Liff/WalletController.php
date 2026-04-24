<?php

namespace App\Http\Controllers\Liff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\RouteAttributes\Attributes\Get;

/**
 * LIFF クーポンウォレット PoC - エントリポイント。
 *
 * LINE アプリ内ブラウザ経由で開かれ、LIFF SDK が初期化されたあと
 * `liff.getIDToken()` / `liff.getProfile()` でユーザーを識別する。
 *
 * ルート: GET /liff/wallet
 * 設定:
 *   .env に LIFF_ID=your-liff-id を追加
 *   LINE Developer Console で LIFF アプリを作成し、Endpoint URL に本ルートを設定
 */
class WalletController extends Controller
{
    #[Get('liff/wallet', name: 'liff.wallet')]
    public function __invoke(Request $request): View
    {
        return view('liff.wallet', [
            'liffId' => config('line-bot.liff_id') ?? env('LIFF_ID'),
        ]);
    }
}
