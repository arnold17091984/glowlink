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
        // size は: 1) 添付画像のアスペクト比 (最も信頼できる情報源)
        //         2) なければ richMenu->width/height (legacy)
        //         3) それでもなければ full サイズ
        [$width, $height] = $this->resolveSizeFromImage($richMenu)
            ?? $this->resolveSize((int) $richMenu->width, (int) $richMenu->height);

        $areas = $this->normaliseAreas($richMenu->areas ?? [], $width, $height);
        $areas = $this->sanitiseActions($areas);

        return new RichMenuRequest([
            'size' => [
                'width' => $width,
                'height' => $height,
            ],
            'selected' => (bool) $richMenu->selected,
            'name' => (string) ($richMenu->name ?? $richMenu->rich_menu_id ?? 'rich-menu-'.$richMenu->id),
            'chatBarText' => (string) ($richMenu->chatbar_text ?? 'メニュー'),
            'areas' => $areas,
        ]);
    }

    /**
     * 添付画像から実アスペクト比を読み取り、最も近い LINE 公式サイズを返す。
     */
    private function resolveSizeFromImage(RichMenu $richMenu): ?array
    {
        try {
            $media = $richMenu->getFirstMedia('richmenus');
            if (! $media) {
                return null;
            }
            $path = $media->getPath();
            if (! $path || ! is_file($path)) {
                return null;
            }
            $info = @getimagesize($path);
            if (! $info) {
                return null;
            }
            [$srcW, $srcH] = $info;
            if ($srcW <= 0 || $srcH <= 0) {
                return null;
            }
            $srcRatio = $srcW / $srcH;

            // (width, height, ratio)
            $candidates = [
                [2500, 1686, 2500 / 1686],
                [2500, 843,  2500 / 843],
                [1200, 810,  1200 / 810],
                [1200, 405,  1200 / 405],
            ];
            $best = $candidates[0];
            $bestDiff = abs($srcRatio - $best[2]);
            foreach ($candidates as $c) {
                $d = abs($srcRatio - $c[2]);
                if ($d < $bestDiff) {
                    $best = $c;
                    $bestDiff = $d;
                }
            }

            return [$best[0], $best[1]];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 保存済の areas[].action を LINE 仕様に合わせて最終チェック。
     * - uri アクションでスキーム抜けの URL を https:// 補完
     * - 受け付けスキーム外は無効として安全な fallback (https://example.com) に変換
     *   (areas を完全に削るとレイアウトが崩れるためダミー URL にする)
     */
    private function sanitiseActions(array $areas): array
    {
        return array_map(function ($area) {
            if (! is_array($area) || ! isset($area['action'])) {
                return $area;
            }
            $action = $area['action'];
            $type = $action['type'] ?? null;

            if ($type === 'uri' && isset($action['uri'])) {
                $area['action']['uri'] = $this->normaliseUriString((string) $action['uri']);
            }

            return $area;
        }, $areas);
    }

    private function normaliseUriString(string $raw): string
    {
        $uri = trim($raw);
        if ($uri === '') {
            return 'https://example.com';
        }

        // スキーム無しなら https:// を補う
        if (! preg_match('#^[a-z][a-z0-9+\-.]*:#i', $uri)) {
            $uri = 'https://'.ltrim($uri, '/');
        }

        // LINE 受付スキームのみ許可
        if (! preg_match('#^(https?|line)://#i', $uri) && ! str_starts_with(strtolower($uri), 'tel:') && ! str_starts_with(strtolower($uri), 'mailto:')) {
            return 'https://example.com';
        }

        return $uri;
    }

    /**
     * 入力寸法を LINE 仕様に丸め込む。
     *
     * 旧実装は "height < 1000 ならコンパクト" としていたが、レガシー DB の
     * デフォルト 1280x863 がコンパクト判定されてしまい、結果として
     * 「フルサイズの画像を半分の高さに潰す」事故を起こしていた。
     * 今は「width:height のアスペクト比が 2:1 (= 2.0) を超えるとき
     * のみコンパクト」と判定する。
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

        if ($width > 0 && $height > 0) {
            $ratio = $width / $height;
            // 2.0 を境にコンパクト/フルを判定。
            //   フル (2500/1686 = 1.483 / 1200/810 = 1.481) は ratio < 2
            //   コンパクト (2500/843 = 2.965 / 1200/405 = 2.963) は ratio > 2
            return $ratio > 2.0 ? [2500, 843] : [2500, 1686];
        }

        return [2500, 1686];
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
