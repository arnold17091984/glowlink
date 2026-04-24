@php
    $name = $name ?? 'ゲスト';
@endphp

<x-filament-widgets::widget>
    <div class="glow-hero">
        <div class="glow-hero-kanji">{{ $kanji }}</div>

        <div class="glow-hero-eyebrow">{{ $dateLabel }}</div>

        <h2 class="glow-hero-greeting">
            {{ $greeting }}、<span class="accent">{{ $name }}</span> さん
        </h2>

        <p class="glow-hero-date">
            本日の LINE 友だちエンゲージメントをご案内します。
        </p>

        <div class="glow-hero-meta">
            <div class="glow-hero-meta-item">
                <span class="glow-hero-meta-label">友だち総数</span>
                <span class="glow-hero-meta-value">{{ number_format($friendCount) }}</span>
                @if ($friendsThisMonth > 0)
                    <span class="glow-hero-meta-trend">今月 +{{ number_format($friendsThisMonth) }}</span>
                @endif
            </div>

            <div class="glow-hero-meta-item">
                <span class="glow-hero-meta-label">本日の配信</span>
                <span class="glow-hero-meta-value">{{ number_format($todayBroadcasts) }}</span>
                <span class="glow-hero-meta-trend">件 実行予定</span>
            </div>

            <div class="glow-hero-meta-item">
                <span class="glow-hero-meta-label">今後 7 日間</span>
                <span class="glow-hero-meta-value">{{ number_format($upcomingBroadcasts) }}</span>
                <span class="glow-hero-meta-trend">件 予約中</span>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
