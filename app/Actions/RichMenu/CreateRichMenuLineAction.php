<?php

namespace App\Actions\RichMenu;

use App\Actions\LineMessagingRequest\BuildRichMenuAliasRequestAction;
use App\Actions\LineMessagingRequest\BuildRichMenuRequestAction;
use App\Domains\LineIntegration\Gateway\LineGatewayManager;
use App\Models\RichMenu;
use Illuminate\Support\Facades\Log;

/**
 * RichMenu を LINE Messaging API に作成・画像アップロード・alias 作成・default 設定する。
 *
 * 旧実装は LINEMessagingApi facade 直呼びで .env チャネル固定だったが、
 * RichMenuSet->line_channel_id を見て LineGatewayManager 経由で
 * 適切なチャネルへ呼び出すよう変更 (multi-tenant 対応)。
 */
class CreateRichMenuLineAction
{
    public function __construct(
        protected BuildRichMenuRequestAction $buildRichMenuRequestAction,
        protected BuildRichMenuAliasRequestAction $buildRichMenuAliasRequestAction,
        protected GetActionsAction $getActionsAction,
        protected GetTabAction $getTabAction,
        protected LineGatewayManager $gateways,
    ) {
    }

    public function execute(RichMenu $richMenu, ?array $image): void
    {
        $channelId = optional($richMenu->richMenuSet)->line_channel_id;
        $gateway = $this->gateways->forChannelId($channelId);

        $richMenuRequest = $this->buildRichMenuRequestAction->execute($richMenu);

        try {
            $richMenuId = $gateway->createRichMenu($richMenuRequest);
        } catch (\Throwable $e) {
            Log::error('CreateRichMenuLineAction: createRichMenu failed', [
                'rich_menu_id' => $richMenu->id,
                'channel_id' => $channelId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $richMenu->update(['reference' => $richMenuId]);

        // 画像アップロード: media-library 経由 or アップロードされた一時ファイル
        $imagePath = $this->resolveImagePath($richMenu, $image);
        if ($imagePath) {
            $gateway->setRichMenuImage($richMenuId, $imagePath, $this->detectMime($imagePath));
        } else {
            Log::warning('CreateRichMenuLineAction: no image available', ['rich_menu_id' => $richMenu->id]);
        }

        // tab_no = '1' (root) なら default Rich Menu に
        if ((string) $richMenu->tab_no === '1' && empty($richMenu->parent_id)) {
            $gateway->setDefaultRichMenu($richMenuId);
        }

        // alias 作成 (タブ切替用)
        if ($richMenu->rich_menu_alias) {
            $aliasRequest = $this->buildRichMenuAliasRequestAction->execute($richMenu->rich_menu_alias, $richMenuId);
            $gateway->createRichMenuAlias($aliasRequest);
        }
    }

    private function resolveImagePath(RichMenu $richMenu, ?array $image): ?string
    {
        if (! is_null($image)) {
            $first = reset($image);
            if ($first && method_exists($first, 'getPathName')) {
                return $first->getPathName();
            }
        }

        $media = $richMenu->getFirstMedia('richmenus');

        return $media ? $media->getPath() : null;
    }

    private function detectMime(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'image/png',
        };
    }
}
