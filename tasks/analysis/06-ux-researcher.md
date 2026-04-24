# UXリサーチ分析レポート

## 1. ペルソナ定義 (4体)

**P1 — マーケティング担当者 (Maya, 28歳)**
- 目標: ブロードキャスト・クーポンを週次で回し、開封率・利用率をレポート
- ペインポイント: メッセージ作成とブロードキャスト設定が別画面に分断され、何度も行き来。効果測定画面が存在しない

**P2 — 店舗運営者 (Kenji, 42歳)**
- 目標: クーポン発行・リダムを現場スタッフが迷わず実行できる状態にする
- ペインポイント: Advance Settings に必須項目(抽選、上限数)が埋まっており、初見では設定漏れが発生。クーポンにビジュアルプレビューなし

**P3 — カスタマーサポート担当者 (Yuki, 35歳)**
- 目標: 友達の問い合わせに即応し、フラグ・ポイント調整・履歴確認を1画面で完結
- ペインポイント: FriendResourceに検索フィルターが皆無 (`->filters([])`)。メッセージ送信アクションが存在しない

**P4 — システム管理者 (Taro, 38歳)**
- 目標: Rich Menuのデプロイ、権限管理、配信失敗の監視
- ペインポイント: Role-Based UIが未実装（全ロールが同一パネルを見る）。配信失敗の通知・アラート機構が不在

## 2. 主要タスク別ユーザージャーニーマップ

### (A) ブロードキャスト作成→配信→効果測定

| ステップ | 現状 | 理想 |
|---|---|---|
| 1 | Messaging > Messages でメッセージを事前作成 | ブロードキャスト作成フォーム内でインライン作成 |
| 2 | Messaging > Broadcast で新規作成を開く | 同一フォームで続行 |
| 3 | message_type を Select で選択 | メッセージ種別はサムネイルカードから選択 |
| 4 | message_id を別 Select で選択 | 選択と同時にプレビューが右ペインに自動表示 |
| 5 | send_to を "all" か FlagEnum値で選択 | セグメントビルダーで対象者数をリアルタイム表示 |
| 6 | is_send_now Radio → start_date Picker | 「今すぐ」「スケジュール」をタブUIで切り替え |
| 7 | Repeat + Every の2段階Select | 繰り返しパターンをビジュアルカレンダーで確認 |
| 8 | 保存・送信 → 結果確認不可 | 送信後に activities ページへ自動遷移、開封率・クリック率を表示 |

現状ステップ: **8ステップ (実質10+操作)**
理想ステップ: **4ステップ (単一ウィザード)**

### (B) クーポン発行→配布→リダム

現状: 基本情報 → Advance Settingsを展開 → coupon_type・is_lottery・win_rate・is_limited・no_of_users を設定 → 保存 → **配布手段なし(メッセージ内にクーポンを紐付ける経路が画面上から不明)** → リダムは FriendCoupons RelationManager で手動編集

現状: **6+画面遷移、配布経路不明**
理想: **3ステップ (設定→配布先選択→送信)**

### (C) Rich Menu作成→切替→テスト

現状: RichMenuSet 作成 → "Rich Menu List" ボタンで子画面へ → RichMenu 作成(画像アップロード → layout選択 → actionsリピーター) → ToggleColumnで is_active を切替

現状: **7ステップ (2階層のResource間を往復)**
理想: **3ステップ (ウィザード: レイアウト選択→画像配置→アクション割当)**

### (D) Friend検索→セグメント→個別対応

現状: テキスト検索なし(グローバル検索のみ) → フィルターなし → "Manage points" スライドオーバーでポイント調整のみ → **メッセージ送信不可** → AwardPointsLogs タブで履歴確認

## 3. 摩擦点 Top 15

| # | 摩擦点 | 深刻度 | 影響ペルソナ |
|---|---|---|---|
| 1 | **効果測定画面の不在** — Broadcast activities は activity log のラッパー、開封率・クリック率・コンバージョン表示なし | 致命的 | P1, P4 |
| 2 | **メッセージ事前作成の強制** — Broadcast/AutoResponse を作る前に必ず Message を作成 | 高 | P1, P2 |
| 3 | **フィルター・検索の全面的欠如** — 全 Resource で `->filters([])` が空 | 高 | P1, P3 |
| 4 | **クーポン配布経路が画面上から不明** | 高 | P1, P2 |
| 5 | **Rich Menu の2層構造ナビゲーション** — Set → Menu → Action の3階層 | 高 | P4 |
| 6 | **send_to のセグメント粒度が粗すぎる** — "All" か FlagEnum (4値) のみ | 高 | P1 |
| 7 | **Advance Settings の折り畳み** — 必須設定が隠れ、設定漏れを誘発 | 中 | P2 |
| 8 | **RichMenu actions が画像より先に無効化** — 操作できない理由のフィードバックなし | 中 | P4 |
| 9 | **FlagEnum の意味が文脈に不一致** — "Unresolved/Requires Action" は送信セグメント語彙ではない | 中 | P1, P3 |
| 10 | **ダッシュボードが AccountWidget のみ** — KPI ウィジェット皆無 | 中 | P1, P2 |
| 11 | **メッセージプレビューが部分的** — RichVideo・RichCard 選択時にプレビューなし | 中 | P1 |
| 12 | **Repeat + Every の2段階 Select** — ONCE選択後も Every が表示され続ける | 低-中 | P1 |
| 13 | **個別メッセージ送信アクションの不在** | 低-中 | P3 |
| 14 | **Role-Based UIの未実装** | 低-中 | P4 |
| 15 | **通知・アラートの不在** — 配信失敗・クーポン残数・新規友達急増 | 低 | P1, P4 |

## 4. UX改善施策

### Quick Win (1-2週間)

**QW-1: FriendResource にフィルター追加** (30分)
- SelectFilter: `mark` (FlagEnum)
- RangeFilter: `points`
- DateFilter: `created_at`

**QW-2: BroadcastResource table に `start_date` ソートとステータスバッジ追加**
配信予定日でソートできないため運用が困難。TextColumn に `->sortable()` と `->badge()` を追加。

**QW-3: Advance Settings を常時展開に変更**
`->collapsible()` を削除。抽選・上限数は業務上必須。

**QW-4: RichMenu actions 無効化時にヘルプテキスト追加**
`->helperText('画像をアップロードするとアクションを設定できます')` を Repeater に追加。

**QW-5: メッセージプレビューに RichVideo・RichCard 対応追加**
BroadcastResource::getPreview() に2ケース追加。

### 中期施策 (1-2ヶ月)

**M-1: ブロードキャスト作成をマルチステップウィザード化**
Step 1: メッセージ選択 or インライン作成 → Step 2: 配信対象セグメントビルダー → Step 3: スケジュール → Step 4: 確認・送信

**M-2: ダッシュボードKPIウィジェット実装**

**M-3: クーポン配布フロー整備**
CouponResource の View ページに "配布" タブを追加し、ブロードキャストまたは個別送信へのショートカット。リダム率 (used/issued) をリアルタイム表示。

**M-4: FriendResource に "Send Message" スライドオーバー追加**
`Tables\Actions\Action::make('sendMessage')` で既存メッセージを選択して即時送信。

**M-5: Rich Menu ステップ編集UIをインライン化**
RichMenuSet の Edit ページ内に RichMenu 一覧をインライン RelationManager として表示。

### 長期施策 (3ヶ月+)

**L-1: 効果測定レイヤーの実装**
Broadcast activities を LINEのWebhook(open/click)と結合し、開封率・ユニーククリック率・コンバージョン率を集計。

**L-2: Role-Based UI (Spatie Permission 連携)**

**L-3: セグメントビルダー**
FlagEnum依存のsend_toを廃止し、クエリビルダーUIで友達属性・行動履歴・ポイント残高による動的セグメントを生成。

**L-4: イベントドリブン通知**
配信失敗・クーポン残数閾値・友達数急増を Observer/Event で通知ペイロードに変換。

## 5. 毎日開くべきダッシュボード設計

### レイアウト: 3カラム、上部サマリー + 下部詳細

**Row 1 — 今日の健全性 (StatsOverview 4枚)**
- 友達数 | 総数 / 昨日比 (delta%)
- 本日配信数 | 送信済みブロードキャスト数
- クーポン利用率 | 今月 used/issued %
- リファーラル成約 | 今月の紹介経由新規友達数

**Row 2 — トレンドグラフ (2カラム)**
- 左: 友達数推移 (30日折れ線)
- 右: ブロードキャスト開封率推移 (要LINE Webhook連携)

**Row 3 — アクションが必要なアイテム (TableWidget)**
- 配信失敗したブロードキャスト一覧
- 有効期限3日以内のクーポン一覧
- mark = REQUIRES_ACTION の友達一覧

**Row 4 — 今後のスケジュール (TableWidget)**
- 今後7日間の配信予定ブロードキャスト

## 優先順位マトリクス

| 施策 | 実装コスト | UX改善インパクト | 推奨順位 |
|---|---|---|---|
| フィルター追加 (QW-1~2) | 低 | 高 | 1位 |
| Advance Settings 常時展開 | 低 | 中 | 2位 |
| ダッシュボードKPI | 中 | 最高 | 3位 |
| ブロードキャストウィザード | 高 | 最高 | 4位 |
| クーポン配布フロー | 中 | 高 | 5位 |
| Role-Based UI | 高 | 高 | 6位 |

**核心的な診断**: 現状の「実用性が最悪」という認識の根本原因は2点に集約される。
1. 目標到達に必要なステップ数が多い (メッセージ・ブロードキャスト・Rich Menu Setの3層構造を行き来させる設計)
2. 作業した結果が見えない (効果測定・ダッシュボード・アラートの不在)

Quick Win 5件を2週間で実施するだけでも体感品質は大幅に向上。
