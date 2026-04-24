<x-filament-panels::page.simple>
    <div class="glow-auth" x-data="{
        time: '{{ now()->timezone(config('app.timezone','Asia/Tokyo'))->format('H:i:s') }}',
        tick() {
            const n = new Date();
            const p = v => String(v).padStart(2,'0');
            this.time = p(n.getHours())+':'+p(n.getMinutes())+':'+p(n.getSeconds());
        }
    }" x-init="setInterval(() => tick(), 1000)">
        {{-- LEFT // brand column --}}
        <aside class="glow-auth-brand">
            <div class="glow-auth-brand-top">
                <div class="glow-auth-logo">
                    <span class="glow-auth-logo-dot"></span>
                    <span>glow<span style="color:var(--gl-signal);">link</span></span>
                </div>
                <div class="glow-auth-status">
                    <span>SYS STATUS</span>
                    <span class="glow-auth-status-badge">
                        <span class="glow-dot"></span>
                        <span>ONLINE</span>
                    </span>
                </div>
            </div>

            <div class="glow-auth-manifesto">
                <div class="glow-auth-eyebrow">LINE ENGAGEMENT CONSOLE</div>
                <h1 class="glow-auth-tagline">
                    Operate every LINE friend at mission-grade precision.<span class="glow-auth-cursor">_</span>
                </h1>
                <p class="glow-auth-lede">
                    Broadcast, coupon, referral — one cockpit, one dashboard. Built for brand operators who treat every message as a launch.
                </p>
            </div>

            <div class="glow-auth-ticker">
                <div class="glow-auth-ticker-row">
                    <span class="glow-auth-ticker-label">LOCAL</span>
                    <span class="glow-auth-ticker-value" x-text="time">{{ now()->format('H:i:s') }}</span>
                    <span class="glow-auth-ticker-unit">JST</span>
                </div>
                <div class="glow-auth-ticker-row">
                    <span class="glow-auth-ticker-label">BUILD</span>
                    <span class="glow-auth-ticker-value">1.0.0</span>
                    <span class="glow-auth-ticker-unit">MAIN</span>
                </div>
                <div class="glow-auth-ticker-row">
                    <span class="glow-auth-ticker-label">NODE</span>
                    <span class="glow-auth-ticker-value">NRT-01</span>
                    <span class="glow-auth-ticker-unit">JP</span>
                </div>
                <div class="glow-auth-ticker-row">
                    <span class="glow-auth-ticker-label">API</span>
                    <span class="glow-auth-ticker-value">LINE v2</span>
                    <span class="glow-auth-ticker-unit">READY</span>
                </div>
            </div>

            <div class="glow-auth-footprint">
                <pre class="glow-auth-ascii"> ┌──────────────────────────┐
 │  > ACCESS REQUIRED        │
 │  > CREDENTIAL CHECK       │
 │  > AUTHENTICATING...      │
 │  > AWAITING OPERATOR      │
 └──────────────────────────┘</pre>
            </div>
        </aside>

        {{-- RIGHT // form column --}}
        <section class="glow-auth-form">
            <div class="glow-auth-form-head">
                <div class="glow-auth-eyebrow">SECURE ACCESS // SSH/HTTPS</div>
                <h2 class="glow-auth-form-title">Sign in<span class="glow-auth-cursor">_</span></h2>
                <p class="glow-auth-form-sub">Operator credentials required to enter the console.</p>
            </div>

            <form wire:submit="authenticate" class="glow-auth-form-body">
                {{ $this->form }}

                <x-filament-panels::form.actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="$this->hasFullWidthFormActions()"
                />
            </form>

            <div class="glow-auth-form-foot">
                <span class="glow-dot"></span>
                <span>END-TO-END ENCRYPTED · SESSION 7200s</span>
            </div>
        </section>
    </div>
</x-filament-panels::page.simple>
