<x-filament-widgets::widget>
    <div class="glow-hero"
         x-data="{
             time: @js($isoTime),
             date: @js($isoDate),
             tick() {
                 const now = new Date();
                 const pad = n => String(n).padStart(2, '0');
                 this.time = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
             }
         }"
         x-init="setInterval(() => tick(), 1000)">

        <div class="glow-hero-header">
            <div class="glow-hero-status">
                <span class="glow-hero-status-dot"></span>
                <span>SYSTEM ONLINE</span>
                <span style="color:var(--gl-text-faint); margin-left:.5rem;">// {{ $shiftTag }}</span>
            </div>
            <div class="glow-hero-clock">
                <span>{{ $isoDate }}</span>
                <span style="color:var(--gl-text-faint); margin:0 .5rem;">·</span>
                <span x-text="time">{{ $isoTime }}</span>
                <span style="color:var(--gl-text-faint); margin-left:.5rem;">{{ $timezone }}</span>
            </div>
        </div>

        <div class="glow-hero-body">
            <div class="glow-hero-op">OPERATOR // {{ strtoupper($operator) }}</div>

            <h2 class="glow-hero-greeting">
                {{ $greeting }}, <span class="operator">{{ $operator }}</span><span class="glow-blink"></span>
            </h2>

            <div class="glow-hero-date">
                LINE engagement console ready for input.
            </div>
        </div>

        <div class="glow-hero-grid">
            <div class="glow-hero-cell">
                <div class="glow-hero-cell-label">
                    <span class="glow-hero-cell-dot"></span>
                    FRIENDS // TOTAL
                </div>
                <div class="glow-hero-cell-value">{{ number_format($friendCount) }}</div>
                @if ($friendsThisMonth > 0)
                    <div class="glow-hero-cell-trend">+{{ number_format($friendsThisMonth) }} MTD</div>
                @else
                    <div class="glow-hero-cell-unit">no new friends MTD</div>
                @endif
            </div>

            <div class="glow-hero-cell">
                <div class="glow-hero-cell-label">
                    <span class="glow-hero-cell-dot {{ $broadcastsToday === 0 ? 'inactive' : '' }}"></span>
                    BROADCASTS // TODAY
                </div>
                <div class="glow-hero-cell-value">{{ number_format($broadcastsToday) }}</div>
                <div class="glow-hero-cell-unit">scheduled</div>
            </div>

            <div class="glow-hero-cell">
                <div class="glow-hero-cell-label">
                    <span class="glow-hero-cell-dot {{ $couponsRedeemedToday === 0 ? 'inactive' : '' }}"></span>
                    COUPONS // REDEEMED
                </div>
                <div class="glow-hero-cell-value">{{ number_format($couponsRedeemedToday) }}</div>
                <div class="glow-hero-cell-unit">last 24h</div>
            </div>

            <div class="glow-hero-cell">
                <div class="glow-hero-cell-label">
                    <span class="glow-hero-cell-dot {{ $queueDepth === 0 ? 'inactive' : '' }}"></span>
                    QUEUE // DEPTH
                </div>
                <div class="glow-hero-cell-value">{{ number_format($queueDepth) }}</div>
                <div class="glow-hero-cell-unit">jobs pending</div>
            </div>
        </div>

        <div style="display:flex; gap:1.25rem; padding:.75rem 1.5rem; border-top:1px solid var(--gl-hairline); background:var(--gl-canvas); font-family:var(--gl-mono); font-size:.65rem; letter-spacing:.12em; text-transform:uppercase; color:var(--gl-text-faint);">
            <span style="display:inline-flex; align-items:center; gap:.35rem;">
                <span class="glow-dot {{ $lineApiOk ? '' : 'dim' }}"></span>
                LINE API <span style="color:{{ $lineApiOk ? 'var(--gl-signal)' : 'var(--gl-pulse-red)' }};">{{ $lineApiOk ? 'CONNECTED' : 'NO TOKEN' }}</span>
            </span>
            <span style="display:inline-flex; align-items:center; gap:.35rem;">
                <span class="glow-dot {{ $dbOk ? '' : 'dim' }}"></span>
                MYSQL <span style="color:{{ $dbOk ? 'var(--gl-signal)' : 'var(--gl-pulse-red)' }};">{{ $dbOk ? 'ONLINE' : 'DOWN' }}</span>
            </span>
            <span style="display:inline-flex; align-items:center; gap:.35rem;">
                <span class="glow-dot"></span>
                REDIS <span style="color:var(--gl-signal);">ONLINE</span>
            </span>
            <span style="margin-left:auto; color:var(--gl-text-faint);">sess. {{ substr(session()->getId(), 0, 8) }}</span>
        </div>
    </div>
</x-filament-widgets::widget>
