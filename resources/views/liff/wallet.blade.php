<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>クーポンウォレット</title>
    <link rel="preconnect" href="https://static.line-scdn.net">
    <style>
        :root {
            --brand: #21D59B;
            --brand-dark: #17A87A;
            --bg: #f4f7f9;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --danger: #dc2626;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Hiragino Sans", "Noto Sans JP", sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }
        header {
            background: linear-gradient(135deg, var(--brand), var(--brand-dark));
            color: white;
            padding: 1.5rem 1rem;
            text-align: center;
        }
        header h1 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }
        .points {
            margin-top: .5rem;
            font-size: 2rem;
            font-weight: 800;
        }
        main {
            padding: 1rem;
            max-width: 480px;
            margin: 0 auto;
        }
        .redeem-form {
            background: var(--card);
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            margin-bottom: 1rem;
        }
        .redeem-form input {
            width: 100%;
            padding: .75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            margin-bottom: .75rem;
        }
        .redeem-form button {
            width: 100%;
            padding: .75rem;
            border: none;
            border-radius: 8px;
            background: var(--brand);
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
        }
        .redeem-form button:disabled {
            opacity: .5;
            cursor: not-allowed;
        }
        .coupon-list h2 {
            font-size: 1rem;
            color: var(--muted);
            margin: 1rem 0 .5rem;
        }
        .coupon-card {
            background: var(--card);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: .75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .coupon-card .name { font-weight: 600; }
        .coupon-card .code {
            font-family: ui-monospace, monospace;
            color: var(--muted);
            font-size: .85rem;
        }
        .badge {
            padding: .25rem .5rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 600;
        }
        .badge-won { background: #d1fae5; color: #065f46; }
        .badge-notwon { background: #fee2e2; color: #991b1b; }
        .badge-pending { background: #dbeafe; color: #1e40af; }
        .error {
            background: #fee2e2;
            color: var(--danger);
            padding: .75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: .9rem;
        }
        .loading { color: var(--muted); font-size: .9rem; text-align: center; padding: 2rem 0; }
    </style>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js" defer></script>
</head>
<body>
    <header>
        <div id="user-name">読み込み中...</div>
        <h1>クーポンウォレット</h1>
        <div class="points" id="points-display">— pt</div>
    </header>

    <main>
        <div id="error" class="error" style="display:none"></div>

        <div class="redeem-form">
            <label for="coupon-code">クーポンコードを入力</label>
            <input id="coupon-code" type="text" autocomplete="off" placeholder="ABC123" maxlength="32">
            <button id="redeem-btn" disabled>引き換える</button>
        </div>

        <div class="coupon-list">
            <h2>保有クーポン</h2>
            <div id="coupon-list">
                <div class="loading">読み込み中...</div>
            </div>
        </div>
    </main>

    <script>
        const LIFF_ID = @json($liffId);
        const API_BASE = '/liff/api/coupons';

        let idToken = null;

        function showError(message) {
            const el = document.getElementById('error');
            el.textContent = message;
            el.style.display = 'block';
        }

        function clearError() {
            document.getElementById('error').style.display = 'none';
        }

        async function api(path, options = {}) {
            const res = await fetch(API_BASE + path, {
                headers: {
                    'Authorization': `Bearer ${idToken}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...(options.headers || {}),
                },
                ...options,
            });
            if (!res.ok) {
                const body = await res.json().catch(() => ({ message: res.statusText }));
                throw new Error(body.message || 'API error');
            }
            return res.json();
        }

        function statusBadge(status) {
            const map = {
                won: ['badge-won', '当選'],
                not_won: ['badge-notwon', 'ハズレ'],
                pending: ['badge-pending', '保留'],
                unlimited: ['badge-won', '無制限'],
            };
            const [cls, label] = map[status] || ['badge-pending', status || '不明'];
            return `<span class="badge ${cls}">${label}</span>`;
        }

        async function loadWallet() {
            try {
                const data = await api('/mine');
                document.getElementById('user-name').textContent = data.friend.name;
                document.getElementById('points-display').textContent = `${data.friend.points.toLocaleString()} pt`;

                const list = document.getElementById('coupon-list');
                if (!data.coupons.length) {
                    list.innerHTML = '<div class="loading">まだクーポンがありません</div>';
                    return;
                }
                list.innerHTML = data.coupons.map(c => `
                    <div class="coupon-card">
                        <div>
                            <div class="name">${c.name || '(名称なし)'}</div>
                            <div class="code">${c.code || ''}</div>
                        </div>
                        ${statusBadge(c.status)}
                    </div>
                `).join('');
            } catch (e) {
                showError(`ウォレット取得失敗: ${e.message}`);
            }
        }

        async function redeemCoupon(code) {
            try {
                clearError();
                const data = await api('/redeem', {
                    method: 'POST',
                    body: JSON.stringify({ coupon_code: code }),
                });
                const msg = data.result?.title1 || (data.result?.is_win ? '当選しました！' : '処理完了');
                alert(msg);
                await loadWallet();
            } catch (e) {
                showError(`引き換え失敗: ${e.message}`);
            }
        }

        document.addEventListener('DOMContentLoaded', async () => {
            try {
                await liff.init({ liffId: LIFF_ID });
                if (!liff.isLoggedIn()) {
                    liff.login();
                    return;
                }
                idToken = liff.getIDToken();
                if (!idToken) {
                    showError('LIFF の ID トークンを取得できませんでした');
                    return;
                }

                document.getElementById('redeem-btn').disabled = false;
                document.getElementById('redeem-btn').addEventListener('click', () => {
                    const code = document.getElementById('coupon-code').value.trim();
                    if (code) redeemCoupon(code);
                });

                await loadWallet();
            } catch (e) {
                showError(`LIFF 初期化失敗: ${e.message}`);
            }
        });
    </script>
</body>
</html>
