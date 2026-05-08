<?php

namespace App\Actions\LineMessagingRequest;

use App\Models\RichMenu;
use LINE\Clients\MessagingApi\Model\RichMenuRequest;

/**
 * LINE Messaging API は Rich Menu のサイズとして以下4つしか受け付けない:
 *   - フル:    2500 x 1686 / 1200 x 810
 *   - コンパクト: 2500 x  843 / 1200 x 405
 *
 * 旧実装は 1280 x 863 を渡しており LINE 側で 400 Bad Request となっていた。
 * RichMenu に width/height カラムがあるためそれを優先し、
 * 不正値の場合は安全なデフォルト 2500 x 1686 を返す。
 *
 * bounds が旧 1280x863 座標系で保存されているとき (max x がしきい値以下)、
 * 自動的に 2500x1686 系へスケール変換する。
 */
class BuildRichMenuRequestAction
{
    private const VALID_SIZES = [
        ['width' => 2500, 'height' => 1686],
        ['width' => 1200, 'height' => 810],
        ['width' => 2500, 'height' => 843],
        ['width' => 1200, 'height' => 405],
    ];

    public function execute(RichMenu $richMenu): RichMenuRequest
    {
        [$width, $height] = $this->resolveSize((int) $richMenu->width, (int) $richMenu->height);

        return new RichMenuRequest([
            'size' => [
                'width' => $width,
                'height' => $height,
            ],
            'selected' => (bool) $richMenu->selected,
            'name' => (string) ($richMenu->name ?? $richMenu->rich_menu_id ?? 'rich-menu-'.$richMenu->id),
            'chatBarText' => (string) ($richMenu->chatbar_text ?? 'メニュー'),
            'areas' => $this->normaliseAreas($richMenu->areas ?? [], $width, $height),
        ]);
    }

    /**
     * 入力寸法を LINE 仕様に丸め込む。fall-through default は 2500 x 1686。
     *
     * @return array{0:int,1:int}
     */
    private function resolveSize(int $width, int $height): array
    {
        foreach (self::VALID_SIZES as $valid) {
            if ($width === $valid['width'] && $height === $valid['height']) {
                return [$width, $height];
            }
        }

        // compact 風 (height < 1000) ならコンパクト、そうでなければフル
        return $height > 0 && $height < 1000
            ? [2500, 843]
            : [2500, 1686];
    }

    /**
     * 各 bounds が旧 1280x863 座標系で保存されているケース (max width <= 1280) では
     * targetWidth / targetHeight に合わせて等倍スケール。
     */
    private function normaliseAreas(array $areas, int $targetWidth, int $targetHeight): array
    {
        if (empty($areas)) {
            return [];
        }

        $maxRight = 0;
        $maxBottom = 0;
        foreach ($areas as $area) {
            $b = $area['bounds'] ?? [];
            $maxRight = max($maxRight, (int) ($b['x'] ?? 0) + (int) ($b['width'] ?? 0));
            $maxBottom = max($maxBottom, (int) ($b['y'] ?? 0) + (int) ($b['height'] ?? 0));
        }

        // 2000以上の値が既にあるなら 2500系座標と判定 → スケール不要
        if ($maxRight >= 2000) {
            return $areas;
        }

        $sourceWidth = $maxRight ?: 1280;
        $sourceHeight = $maxBottom ?: ($maxRight ? (int) round($maxRight * 0.6745) : 863);

        $scaleX = $targetWidth / $sourceWidth;
        $scaleY = $targetHeight / $sourceHeight;

        return array_map(function ($area) use ($scaleX, $scaleY) {
            if (isset($area['bounds']) && is_array($area['bounds'])) {
                foreach (['x', 'y', 'width', 'height'] as $key) {
                    if (isset($area['bounds'][$key])) {
                        $multiplier = in_array($key, ['x', 'width'], true) ? $scaleX : $scaleY;
                        $area['bounds'][$key] = (int) round((int) $area['bounds'][$key] * $multiplier);
                    }
                }
            }

            return $area;
        }, $areas);
    }
}
