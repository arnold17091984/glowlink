<?php

namespace App\Http\Controllers;

use App\Actions\RichMenu\DeleteAllRichMenuLineAction;
use App\Models\LineChannel;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Delete;

/**
 * 危険な「全 Rich Menu 削除」操作。
 * クエリ ?channel={slug} で対象チャネルを限定可能。指定なしは default channel。
 */
class DeleteAllRichMenuController extends Controller
{
    public function __construct(protected DeleteAllRichMenuLineAction $deleteAllRichMenuLineAction)
    {
    }

    #[Delete('RichMenu', middleware: ['auth'])]
    public function __invoke(Request $request)
    {
        $slug = $request->query('channel');
        $channel = $slug ? LineChannel::findBySlug($slug) : null;

        $this->deleteAllRichMenuLineAction->execute($channel);

        return response()->json(['status' => 'ok']);
    }
}
