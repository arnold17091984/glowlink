# フロントエンド技術評価レポート

## 現状サマリー

| 領域 | 現状 | 評価 |
|------|------|------|
| Tailwind カスタマイズ | プリセット依存・トークン未定義 | Low |
| Filament テーマ | primary色のみ hex 指定 | Medium |
| Alpine.js/Livewire 活用 | 基礎的な wire:poll のみ | Medium |
| リアルタイム | BROADCAST_DRIVER=null (未実装) | Critical |
| LINE Rich Preview | レイアウト選択UIはあるが描画プレビューなし | High |
| デザインシステム | 完全不在 | High |
| TypeScript/ESLint | 未導入 | Medium |
| 国際化 | filament-language-switch インストール済みだが未設定 | Medium |

## 技術負債・改善案

### [Critical]

**1. リアルタイム機能が完全未実装**
`config/broadcasting.php` の `BROADCAST_DRIVER` が `null` のまま。`BroadcastServiceProvider` は存在するが何も動いていない。チャット画面 (`messages.blade.php:131`) が `wire:poll.keep-alive` による5秒間隔ポーリングで動作しており、サーバー負荷・遅延の観点で許容不可。

**2. chat UI が全インラインスタイル**
`messages.blade.php` 全体 (279行) がすべて `style=""` の生インライン CSS。Tailwind クラスは一切使われておらず、ダークモード対応が構造上不可能。

### [High]

**3. Tailwind デザイントークン未定義**
`tailwind.config.js` はプリセット (`filament/support/tailwind.config.preset`) を `extend` せず上書きしている形。ブランドカラー `#21D59B` が `AdminPanelProvider.php:39` に直書きされており、CSS変数・Tailwindトークンとして定義されていない。`theme.extend` ブロックが空。

**4. LINE Rich Content プレビューが静的画像のみ**
`rich-menu-layout.blade.php` と `rich-message-layout.blade.php` は SVG サムネイル画像を選択するだけの実装。実際のボタン配置・テキスト・画像を反映したリアルタイムプレビューが存在しない。ユーザーが配信前にコンテンツを視覚確認できない致命的な UX 課題。

**5. Spatie MediaLibrary 統合が最小限**
`composer.json` に `filament/spatie-laravel-media-library-plugin` が存在するが、`config/media-library.php` での image conversion 定義 (LINE Rich Menu: 2500×1686px, Rich Message: 1040px幅) が確認できず、アスペクト比固定クロップが実装されていない可能性が高い。

### [Medium]

**6. filament-language-switch が未設定**
`composer.json` に `bezhansalleh/filament-language-switch` がインストール済みだが `AdminPanelProvider.php` に `->plugin(FilamentLanguageSwitchPlugin::make())` の記述がなく、UIに言語スイッチが表示されていない。

**7. Vite 設定に最適化要素なし**
`vite.config.js` は最小構成のみ。`build.rollupOptions` でのチャンク分割、`build.sourcemap` の環境別制御、`optimizeDeps` の事前バンドル指定がない。ESLint・Prettier・TypeScript は `package.json` に依存として存在しない。

**8. Dark mode 対応が不完全**
`AdminPanelProvider.php:43` に `->darkModeBrandLogo()` が設定されており Dark mode の意識はあるが、`messages.blade.php` のインラインスタイル (`background-color: #dcf8c6`, `#cfe7ff`) はダークモードで視認性が失われる。

**9. wire:navigate / wire:intersect 未活用**
Livewire 3 の SPA ナビゲーション (`wire:navigate`) が未導入。ページ遷移ごとに全画面リロードが発生している。

### [Low]

**10. PWA・プッシュ通知未対応**
モバイルで管理者が配信承認・友達対応を行うユースケースがあるが、manifest.json・service worker・Web Push 未実装。

**11. tailwind.config.js のタイポ**
`content` 配列の4番目のエントリが `.bladed.php` (誤字、正しくは `.blade.php`)。該当ビューの Tailwind クラスがパージされない。ファイル: `tailwind.config.js:9`

## 採用推奨 Filamentプラグイン

| パッケージ名 | 用途 | 学習コスト |
|---|---|---|
| `bezhansalleh/filament-shield` | RBAC・権限管理 | 中 |
| `awcodes/filament-curator` | メディアライブラリUI。LINE画像のトリミング | 低 |
| `awcodes/filament-tiptap-editor` | リッチテキスト入力 | 低 |
| `pxlrbt/filament-excel` | 友達リスト・配信レポートの Excel エクスポート | 低 |
| `saade/filament-fullcalendar` | 配信スケジュール・シナリオ配信のカレンダーUI | 中 |
| `ryangjchandler/filament-feature-flags` | テナントごとの機能フラグ管理 | 低 |

## デザインシステム構築ロードマップ

### Phase 1 — デザイントークン定義 (1-2週)

```js
// tailwind.config.js
theme: {
  extend: {
    colors: {
      brand: { DEFAULT: '#21D59B', dark: '#17A87A', light: '#E8FBF5' },
      chat: { incoming: '#dcf8c6', outgoing: '#cfe7ff' },
    },
    fontFamily: { sans: ['Noto Sans JP', 'sans-serif'] },
  }
}
```

### Phase 2 — カスタムコンポーネント整備 (2-3週)
- `resources/views/components/chat/` — chat bubble、media preview
- `resources/views/components/line/` — LINE メッセージバブル専用
- 各コンポーネントに `dark:` バリアントを付与

### Phase 3 — パターンライブラリ整備 (3-4週)
- 配信ステータスバッジ・タイムライン・友達カードのパターン統一
- `php artisan make:filament-page StyleGuide` でカタログページを作成

## リアルタイムダッシュボード実装提案

### アーキテクチャ

```
Laravel Reverb (WebSocket サーバー、self-hosted)
  └─ Laravel Echo (クライアント JS)
       └─ Livewire 3 Echo integration
            ├─ BroadcastResource: 配信ステータスライブ更新
            ├─ IndividualTalk: チャット即時受信 (wire:poll 廃止)
            └─ Dashboard Widget: 友達増減・開封率リアルタイム
```

### 実装ステップ

1. `config/broadcasting.php` の `default` を `reverb` に変更し `.env` に Reverb 認証情報を追加
2. `package.json` に `laravel-echo` と `pusher-js` を追加、`resources/js/app.js` で Echo 初期化
3. `app/Events/MessageReceived.php` を `ShouldBroadcast` で実装し `private-talk.{userId}` チャンネルで送信
4. `Messages.php` の Livewire コンポーネントで `#[On('echo-private:talk.{userId},MessageReceived')]` アトリビュートを使い `wire:poll` を完全置換
5. Filament Dashboard の Stats Widget を Echo ベースに移行

## LINE Rich Content リアルタイムプレビュー実装方針

**方針: Alpine.js + CSS Grid による純粋フロントエンド描画**

`resources/views/components/line/rich-menu-preview.blade.php` を新設し、Livewire の `$wire.$entangle` でフォームの各セルのアクション・ラベル・画像を双方向バインド。CSS Grid でレイアウト (1〜7) を `grid-template-areas` で切り替え、各セルに画像・テキストオーバーレイを重ねることで LINE 公式アプリと近い見た目を再現。

実装コスト: 中 (約3〜4日)。外部ライブラリ追加不要。

## 優先対応順序

1. `tailwind.config.js` の `.bladed.php` タイポ修正 (即時、5分)
2. `filament-language-switch` の PanelProvider への追加 (1時間)
3. chat UI のインラインスタイルを Tailwind クラス化 + dark mode 対応 (2日)
4. Laravel Reverb 導入で `wire:poll` を排除 (2日)
5. LINE Rich Content プレビューコンポーネント実装 (3-4日)
6. Tailwind デザイントークン定義 (1日)
7. Spatie MediaLibrary conversion の LINE 仕様定義 (半日)
8. `filament-shield` によるRBAC実装 (2-3日)
