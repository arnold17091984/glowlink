# パフォーマンス・スケーラビリティ評価レポート

## Critical

### 1. Push per Friend — Multicast 未使用
`app/Actions/Broadcast/BroadcastMessageAction.php` の `execute()` が `Friend::all()` で全友達をメモリに読み込み、1件ずつ `LINEMessagingApi::pushMessage()` を同期呼び出ししている。友達10万人の場合、10万回の HTTP リクエストが直列実行される。LINE Messaging API の Multicast エンドポイントは1リクエストで最大500件送信できるのに対し、現状は1件/リクエストのため、理論上のスループット比は **1:500**。

同じパターンが `ScenarioDeliveriesJob` にも踏襲されており、シナリオ配信も同様に Push per Friend。

### 2. QUEUE_CONNECTION=sync — 配信がリクエストスレッドをブロック
`.env.example` の `QUEUE_CONNECTION=sync` が本番設定に流入している可能性が高い。Sync ドライバではジョブが HTTP リクエスト内で同期実行されるため、友達1,000人の配信で Filament 画面が 数十秒〜数分タイムアウトする。`config/media-library.php` の `queue_connection_name` も `env('QUEUE_CONNECTION', 'sync')` に依存しており、メディア変換も同期実行になっている。

### 3. Broadcast::all() — スケジューラの全件ロード
`app/Console/Commands/BroadcastCommand.php` の `handle()` が `Broadcast::all()` で全配信をメモリに展開後、`$broadcast->messageDelivery->message` をループ内で都度 lazy load している。ブロードキャスト件数が増えるほど N+1 + メモリ枯渇が複合する。

## High

### 4. 指数バックオフ・リトライ設定ゼロ
`app/Jobs/BroadcastingJob.php` と `ScenarioDeliveriesJob.php` に `public int $tries`、`public function backoff()`、`public function failed()` が一切定義されていない。LINE API が 429 Too Many Requests を返してもジョブはデフォルト設定 (tries=1) で即失敗し、`failed_jobs` テーブルに積まれるだけ。

### 5. Job Batching / Chunking 未実装
友達10万人規模では `Bus::batch()` と `chunk(500)` の組み合わせが必須だが、現状は単一ジョブが全件処理を試みる設計。Horizon も未導入のためジョブの優先度制御・失敗追跡・スループット監視が不可能。

### 6. キャッシュが file ドライバ
`CACHE_DRIVER=file` のため、友達セグメント結果・Rich Menu URL・LINE プロフィール情報が毎リクエスト DB or LINE API 問い合わせになる。マルチプロセス・マルチサーバー構成では file キャッシュはロック競合も引き起こす。

## Medium

### 7. message_deliveries テーブルのインデックス不足
`delivery_date`、`delivery_id`、`message_type` へのインデックスが存在しない。

### 8. Filament BroadcastResource の N+1
`BroadcastResource.php` のフォームが `$record->messageDelivery->message` を複数箇所で個別アクセス。テーブルリスト表示時に `with()` なしで各レコードの関連を個別ロードしている。

### 9. Spatie Media Library の同期変換
`config/media-library.php` の `queue_connection_name` が `sync` 依存。`queue_conversions_by_default = true` と設定されているが、Queue が sync のためコンバージョンは実質同期処理になる。

## Low

### 10. Observability の欠如
Sentry のみ統合済み。Laravel Pulse・Telescope・Horizon ダッシュボードなし。配信スループット・キュー深度・失敗率をリアルタイム観測する手段がない。

### 11. Read Replica・Connection Pooling 未設定
`config/database.php` に read/write 分離なし。大規模配信時の SELECT と INSERT が同一接続に集中する。

## 配信スループット試算

| 条件 | 現状 | 短期目標 (Redis+Multicast) | 中期目標 (Horizon+Batch+Multicast) |
|---|---|---|---|
| 友達1万人 | 約50〜200分 | 約2〜5分 | 約30秒〜2分 |
| 友達10万人 | **タイムアウト/不可** | 約20〜50分 | 約5〜15分 |
| 友達100万人 | **不可** | 不可 | 約1〜3時間 (専用worker必須) |

## スケール目標別対策

### 短期 (友達1万人対応)

1. `QUEUE_CONNECTION=redis` に変更、Redis インスタンス導入
2. `BroadcastMessageAction` を Multicast 化: `Friend::chunk(500)` で `user_id` を収集し `multicast()` を呼び出す新 Action (`MulticastBroadcastAction`) を作成
3. `BroadcastingJob` に `public int $tries = 3;` と `public function backoff(): array { return [10, 60, 300]; }` を追加
4. `media-library.php` の `queue_connection_name` を `redis` に統一

### 中期 (友達10万人対応)

1. **Laravel Horizon 導入**: `composer require laravel/horizon`、`config/horizon.php` で配信専用キュー (`broadcasts`, `scenarios`) を高 worker 数で設定
2. **Job Batching**: `BroadcastCommand` を `Bus::batch()` に移行
3. **Cache 戦略**: `CACHE_DRIVER=redis` に変更。友達セグメント結果を `Cache::remember('segment:all', 300, ...)` でキャッシュ
4. **インデックス追加 Migration**
5. **Filament N+1 修正**

### 長期 (友達100万人対応)

1. Read Replica 導入
2. 専用配信ワーカー: Horizon の `broadcasts` キューを独立サーバーで処理
3. 友達テーブルのシャーディング検討
4. ProxySQL / RDS Proxy

## Horizon + Reverb + Pulse 導入提案

```bash
composer require laravel/horizon laravel/pulse laravel/reverb
php artisan horizon:install
```

`config/horizon.php`:

```php
'environments' => [
    'production' => [
        'supervisor-broadcasts' => [
            'queue' => ['broadcasts', 'scenarios'],
            'processes' => 10,
            'tries' => 3,
            'timeout' => 3600,
        ],
        'supervisor-default' => [
            'queue' => ['default'],
            'processes' => 3,
        ],
    ],
]
```

## 最優先アクション

1. `QUEUE_CONNECTION=redis` に変更し非同期化 (1h)
2. `BroadcastMessageAction` を Multicast + chunk(500) に書き換え (半日)
3. `BroadcastingJob` / `ScenarioDeliveriesJob` に `tries` と `backoff()` を追加 (30min)
4. `message_deliveries` と `broadcasts` テーブルに複合インデックスを追加 (1h)
5. Laravel Horizon 導入とキューワーカー設定 (1日)

現状の設計では友達1万人超の配信は実用的に動作しない。Multicast 化と Redis Queue 移行の2点だけで、スループットは理論上 **500倍** 改善される。
