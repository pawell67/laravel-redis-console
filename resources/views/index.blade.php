<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Redis Console</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0f1117;
            --bg-secondary: #161920;
            --bg-tertiary: #1c1f2b;
            --bg-input: #232736;
            --border: #2a2e3d;
            --border-focus: #6366f1;
            --text-primary: #e2e8f0;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent: #6366f1;
            --accent-hover: #818cf8;
            --success: #22c55e;
            --error: #ef4444;
            --warning: #f59e0b;
            --surface: rgba(99, 102, 241, 0.08);
            --red-glow: rgba(239, 68, 68, 0.15);
            --green-dim: #4ade80;
            --radius: 10px;
            --shadow: 0 4px 24px rgba(0,0,0,0.3);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* ---- LAYOUT ---- */
        .app {
            display: grid;
            grid-template-columns: 320px 1fr;
            grid-template-rows: auto 1fr;
            height: 100vh;
        }

        /* ---- HEADER ---- */
        .header {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 24px;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
        }
        .header-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 16px;
        }
        .header-brand svg { color: var(--accent); }
        .header-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 999px;
            background: var(--surface);
            color: var(--accent);
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .conn-select {
            background: var(--bg-input);
            color: var(--text-primary);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 13px;
            font-family: 'JetBrains Mono', monospace;
            outline: none;
            cursor: pointer;
        }
        .conn-select:focus { border-color: var(--border-focus); }
        .status-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 6px var(--success);
        }
        .header-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
        }

        /* ---- SIDEBAR ---- */
        .sidebar {
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .sidebar-header {
            padding: 16px;
            border-bottom: 1px solid var(--border);
        }
        .sidebar-header h3 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            margin-bottom: 10px;
        }
        .search-box {
            position: relative;
        }
        .search-box input {
            width: 100%;
            background: var(--bg-input);
            color: var(--text-primary);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 8px 10px 8px 32px;
            font-size: 13px;
            font-family: 'JetBrains Mono', monospace;
            outline: none;
        }
        .search-box input:focus { border-color: var(--border-focus); }
        .search-box svg {
            position: absolute;
            left: 9px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            width: 14px; height: 14px;
        }
        .key-actions {
            display: flex;
            gap: 6px;
            margin-top: 8px;
        }
        .btn-sm {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 4px;
            border: 1px solid var(--border);
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-sm:hover {
            border-color: var(--accent);
            color: var(--text-primary);
        }
        .btn-sm.active {
            background: var(--surface);
            border-color: var(--accent);
            color: var(--accent);
        }

        .key-list {
            flex: 1;
            overflow-y: auto;
            padding: 4px 0;
        }
        .key-list::-webkit-scrollbar { width: 5px; }
        .key-list::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

        .key-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 16px;
            cursor: pointer;
            transition: background 0.12s;
            font-size: 13px;
            font-family: 'JetBrains Mono', monospace;
        }
        .key-item:hover { background: var(--bg-tertiary); }
        .key-item.selected { background: var(--surface); }
        .key-type {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 1px 5px;
            border-radius: 3px;
            letter-spacing: 0.04em;
            flex-shrink: 0;
        }
        .key-type.string  { background: #1e3a5f; color: #60a5fa; }
        .key-type.list    { background: #1a3d2e; color: #4ade80; }
        .key-type.set     { background: #3b1f4e; color: #c084fc; }
        .key-type.zset    { background: #4a2c17; color: #fb923c; }
        .key-type.hash    { background: #3b3017; color: #fbbf24; }
        .key-type.stream  { background: #173b3b; color: #2dd4bf; }
        .key-type.none    { background: #2a2a2a; color: #888; }
        .key-name {
            word-break: break-all;
            color: var(--text-secondary);
        }
        .key-ttl {
            margin-left: auto;
            font-size: 10px;
            color: var(--text-muted);
            flex-shrink: 0;
        }
        .sidebar-footer {
            padding: 10px 16px;
            border-top: 1px solid var(--border);
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            justify-content: space-between;
        }
        .load-more {
            font-size: 11px;
            color: var(--accent);
            cursor: pointer;
            border: none;
            background: none;
            font-weight: 600;
        }
        .load-more:hover { color: var(--accent-hover); }

        /* ---- MAIN ---- */
        .main {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Command bar */
        .command-bar {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
            background: var(--bg-secondary);
        }
        .command-input-wrap {
            display: flex;
            align-items: center;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            transition: border-color 0.15s;
        }
        .command-input-wrap:focus-within { border-color: var(--border-focus); }
        .command-prompt {
            padding: 0 12px;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            font-size: 14px;
            color: var(--accent);
            user-select: none;
        }
        .command-input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: var(--text-primary);
            font-family: 'JetBrains Mono', monospace;
            font-size: 14px;
            padding: 12px 0;
        }
        .command-input::placeholder { color: var(--text-muted); }
        .command-run {
            padding: 8px 20px;
            margin: 4px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 7px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.15s;
        }
        .command-run:hover { background: var(--accent-hover); }

        /* Output */
        .output-area {
            flex: 1;
            overflow-y: auto;
            padding: 16px 24px;
        }
        .output-area::-webkit-scrollbar { width: 6px; }
        .output-area::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

        .output-entry {
            margin-bottom: 16px;
        }
        .output-cmd {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .output-cmd span { color: var(--accent); font-weight: 600; }
        .output-result {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            line-height: 1.6;
            padding: 12px 16px;
            background: var(--bg-tertiary);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            white-space: pre-wrap;
            word-break: break-all;
            color: var(--green-dim);
        }
        .output-result.error {
            color: var(--error);
            background: var(--red-glow);
            border-color: rgba(239,68,68,0.25);
        }
        .output-result.warning {
            color: var(--warning);
        }
        .output-timestamp {
            font-size: 10px;
            color: var(--text-muted);
            margin-top: 4px;
        }
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-muted);
            gap: 12px;
        }
        .empty-state svg { opacity: 0.3; }
        .empty-state p { font-size: 14px; }
        .empty-state code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            padding: 2px 6px;
            background: var(--bg-tertiary);
            border-radius: 4px;
            color: var(--text-secondary);
        }

        /* History dropdown */
        .history-wrap { position: relative; }
        .history-list {
            display: none;
            position: absolute;
            bottom: calc(100% + 8px);
            left: 0; right: 0;
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
        }
        .history-list.show { display: block; }
        .history-item {
            padding: 8px 14px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            cursor: pointer;
            color: var(--text-secondary);
            transition: background 0.1s;
        }
        .history-item:hover {
            background: var(--surface);
            color: var(--text-primary);
        }

        /* Tabs */
        .tab-bar {
            display: flex;
            gap: 0;
            border-bottom: 1px solid var(--border);
            background: var(--bg-secondary);
            padding: 0 24px;
        }
        .tab {
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.15s;
        }
        .tab:hover { color: var(--text-secondary); }
        .tab.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }

        /* Info panel */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
            padding: 16px 24px;
        }
        .info-card {
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 14px 16px;
        }
        .info-card-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            margin-bottom: 6px;
        }
        .info-card-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            word-break: break-all;
        }
        .info-card-sub {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Loading spinner */
        .spinner {
            width: 14px; height: 14px;
            border: 2px solid var(--border);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            display: inline-block;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Responsive */
        @media (max-width: 900px) {
            .app { grid-template-columns: 1fr; }
            .sidebar { display: none; }
        }
    </style>
</head>
<body>
    <div class="app">
        <!-- Header -->
        <header class="header">
            <div class="header-brand">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                Redis Console
                <span class="header-badge">v1.0</span>
            </div>
            <div class="header-actions">
                <label class="header-label">Connection</label>
                <select class="conn-select" id="connection">
                    @foreach($connections as $conn)
                        @if(!in_array($conn, ['client', 'options']))
                            <option value="{{ $conn }}" {{ $conn === config('redis-console.connection', 'default') ? 'selected' : '' }}>
                                {{ $conn }}
                            </option>
                        @endif
                    @endforeach
                </select>
                <label class="header-label">DB</label>
                <select class="conn-select" id="db-index">
                    @for($i = 0; $i <= $maxDb; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
                <div class="status-dot" id="status-dot" title="Connected"></div>
            </div>
        </header>

        <!-- Sidebar: Key Browser -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Key Browser</h3>
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" id="key-pattern" placeholder="Pattern (e.g. cache:*)" value="*">
                </div>
                <div class="key-actions">
                    <button class="btn-sm active" onclick="scanKeys()" id="btn-scan">Scan</button>
                    <button class="btn-sm" onclick="refreshKeys()" id="btn-refresh">Refresh</button>
                </div>
            </div>
            <div class="key-list" id="key-list">
                <div class="empty-state" style="padding:40px 20px">
                    <p style="font-size:12px">Click <strong>Scan</strong> to browse keys</p>
                </div>
            </div>
            <div class="sidebar-footer">
                <span id="key-count">0 keys</span>
                <button class="load-more" id="load-more" style="display:none" onclick="scanMore()">Load more →</button>
            </div>
        </aside>

        <!-- Main Panel -->
        <div class="main">
            <!-- Tabs -->
            <div class="tab-bar">
                <div class="tab active" data-tab="cli" onclick="switchTab('cli')">CLI</div>
                <div class="tab" data-tab="info" onclick="switchTab('info')">Server Info</div>
            </div>

            <!-- CLI Tab -->
            <div id="tab-cli" style="display:flex;flex-direction:column;flex:1;overflow:hidden">
                <div class="output-area" id="output-area">
                    <div class="empty-state" id="empty-state">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>
                        <p>Enter a Redis command to get started</p>
                        <p>Try <code>PING</code>, <code>INFO server</code>, or <code>KEYS *</code></p>
                    </div>
                </div>
                <div class="command-bar history-wrap">
                    <div class="history-list" id="history-list"></div>
                    <div class="command-input-wrap">
                        <span class="command-prompt">›</span>
                        <input type="text" class="command-input" id="command-input"
                               placeholder="Enter Redis command..."
                               autocomplete="off"
                               autofocus>
                        <button class="command-run" onclick="runCommand()" id="run-btn">Run</button>
                    </div>
                </div>
            </div>

            <!-- Info Tab -->
            <div id="tab-info" style="display:none;flex:1;overflow-y:auto">
                <div class="info-grid" id="info-grid">
                    <div class="empty-state" style="grid-column:1/-1;padding:60px">
                        <div class="spinner"></div>
                        <p>Loading server info...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    const baseUrl = '{{ url(config("redis-console.path", "redis-console")) }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // State
    let history = JSON.parse(localStorage.getItem('redis-console-history') || '[]');
    let historyIndex = -1;
    let scanCursor = '0';
    let allKeys = [];

    // ---- CLI ----
    const input = document.getElementById('command-input');
    const outputArea = document.getElementById('output-area');
    const emptyState = document.getElementById('empty-state');

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            runCommand();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            navigateHistory(-1);
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            navigateHistory(1);
        }
    });

    function navigateHistory(dir) {
        if (history.length === 0) return;
        historyIndex = Math.max(-1, Math.min(history.length - 1, historyIndex + dir));
        input.value = historyIndex >= 0 ? history[historyIndex] : '';
    }

    async function runCommand() {
        const cmd = input.value.trim();
        if (!cmd) return;

        // Push to history
        if (history[0] !== cmd) {
            history.unshift(cmd);
            if (history.length > 50) history.pop();
            localStorage.setItem('redis-console-history', JSON.stringify(history));
        }
        historyIndex = -1;
        input.value = '';

        emptyState?.remove();

        const entry = document.createElement('div');
        entry.className = 'output-entry';
        entry.innerHTML = `
            <div class="output-cmd"><span>›</span> ${escapeHtml(cmd)}</div>
            <div class="output-result"><span class="spinner"></span> Running...</div>
        `;
        outputArea.appendChild(entry);
        outputArea.scrollTop = outputArea.scrollHeight;

        const resultEl = entry.querySelector('.output-result');

        try {
            const conn = document.getElementById('connection').value;
            const db = document.getElementById('db-index').value;
            const resp = await fetch(`${baseUrl}/execute`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ command: cmd, connection: conn, db: db }),
            });

            const data = await resp.json();

            if (data.error) {
                resultEl.className = 'output-result error';
                resultEl.textContent = `(error) ${data.error}`;
            } else {
                resultEl.className = 'output-result';
                resultEl.textContent = formatOutput(data.result, data.type);
                if (data.dangerous) {
                    resultEl.className = 'output-result warning';
                }
            }
        } catch (err) {
            resultEl.className = 'output-result error';
            resultEl.textContent = `(network error) ${err.message}`;
        }

        const ts = document.createElement('div');
        ts.className = 'output-timestamp';
        ts.textContent = new Date().toLocaleTimeString();
        entry.appendChild(ts);

        outputArea.scrollTop = outputArea.scrollHeight;
    }

    function formatOutput(result, type) {
        if (result === null || result === '(nil)') return '(nil)';
        if (Array.isArray(result)) {
            if (result.length === 0) return '(empty array)';
            return result.map((item, i) => `${i + 1}) ${typeof item === 'object' ? JSON.stringify(item) : item}`).join('\n');
        }
        if (typeof result === 'object') return JSON.stringify(result, null, 2);
        return String(result);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ---- Key Browser ----
    async function scanKeys() {
        scanCursor = '0';
        allKeys = [];
        await scanMore();
    }

    function refreshKeys() {
        scanKeys();
    }

    async function scanMore() {
        const pattern = document.getElementById('key-pattern').value || '*';
        const conn = document.getElementById('connection').value;
        const db = document.getElementById('db-index').value;

        try {
            const resp = await fetch(`${baseUrl}/keys?pattern=${encodeURIComponent(pattern)}&cursor=${scanCursor}&connection=${conn}&db=${db}&count=100`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await resp.json();

            if (data.error) {
                console.error(data.error);
                return;
            }

            allKeys = allKeys.concat(data.keys);
            scanCursor = data.cursor;
            renderKeys();

            const loadMore = document.getElementById('load-more');
            loadMore.style.display = data.done ? 'none' : 'inline';
        } catch (err) {
            console.error(err);
        }
    }

    function renderKeys() {
        const list = document.getElementById('key-list');
        document.getElementById('key-count').textContent = `${allKeys.length} key${allKeys.length !== 1 ? 's' : ''}`;

        if (allKeys.length === 0) {
            list.innerHTML = '<div class="empty-state" style="padding:40px 20px"><p style="font-size:12px">No keys found</p></div>';
            return;
        }

        list.innerHTML = allKeys.map((k) => `
            <div class="key-item" onclick="inspectKey('${escapeHtml(k.key)}')" title="${escapeHtml(k.key)}">
                <span class="key-type ${k.type}">${k.type}</span>
                <span class="key-name">${escapeHtml(k.key)}</span>
                <span class="key-ttl">${k.ttl === -1 ? '∞' : k.ttl + 's'}</span>
            </div>
        `).join('');
    }

    async function inspectKey(key) {
        const conn = document.getElementById('connection').value;
        const db = document.getElementById('db-index').value;

        input.value = `GET ${key}`;
        input.focus();

        emptyState?.remove();

        const entry = document.createElement('div');
        entry.className = 'output-entry';
        entry.innerHTML = `
            <div class="output-cmd"><span>›</span> INSPECT ${escapeHtml(key)}</div>
            <div class="output-result"><span class="spinner"></span> Loading...</div>
        `;
        outputArea.appendChild(entry);
        outputArea.scrollTop = outputArea.scrollHeight;

        const resultEl = entry.querySelector('.output-result');

        try {
            const resp = await fetch(`${baseUrl}/inspect?key=${encodeURIComponent(key)}&connection=${conn}&db=${db}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await resp.json();

            if (data.error) {
                resultEl.className = 'output-result error';
                resultEl.textContent = `(error) ${data.error}`;
            } else {
                const meta = [];
                meta.push(`Type: ${data.type}`);
                if (data.encoding) meta.push(`Encoding: ${data.encoding}`);
                if (data.ttl === -1) {
                    meta.push('TTL: ∞ (no expiry)');
                } else if (data.ttl === -2) {
                    meta.push('TTL: key does not exist');
                } else {
                    meta.push(`TTL: ${Number(data.ttl).toLocaleString()}s`);
                    if (data.expires_at) meta.push(`Expires at: ${data.expires_at}`);
                }

                const valueStr = formatOutput(data.value, typeof data.value);
                resultEl.innerHTML =
                    `<span style="color:var(--text-muted);font-size:11px;display:block;margin-bottom:6px">${meta.join('  ·  ')}</span>` +
                    escapeHtml(valueStr);
            }
        } catch (err) {
            resultEl.className = 'output-result error';
            resultEl.textContent = `(network error) ${err.message}`;
        }

        const ts = document.createElement('div');
        ts.className = 'output-timestamp';
        ts.textContent = new Date().toLocaleTimeString();
        entry.appendChild(ts);

        outputArea.scrollTop = outputArea.scrollHeight;
    }

    // ---- Tabs ----
    function switchTab(tab) {
        document.querySelectorAll('.tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
        document.getElementById('tab-cli').style.display = tab === 'cli' ? 'flex' : 'none';
        document.getElementById('tab-info').style.display = tab === 'info' ? 'flex' : 'none';

        if (tab === 'info') loadInfo();
    }

    async function loadInfo() {
        const conn = document.getElementById('connection').value;
        const db = document.getElementById('db-index').value;
        const grid = document.getElementById('info-grid');

        try {
            const resp = await fetch(`${baseUrl}/info?connection=${conn}&db=${db}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await resp.json();

            if (data.error) {
                grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1"><p style="color:var(--error)">${escapeHtml(data.error)}</p></div>`;
                return;
            }

            // Parse INFO string
            const info = typeof data.info === 'string' ? parseInfoString(data.info) : data.info;
            const cards = [];

            const add = (title, value, sub = '') => {
                cards.push(`<div class="info-card"><div class="info-card-title">${title}</div><div class="info-card-value">${value}</div>${sub ? `<div class="info-card-sub">${sub}</div>` : ''}</div>`);
            };

            if (info.redis_version) add('Redis Version', info.redis_version);
            if (info.uptime_in_days !== undefined) add('Uptime', `${info.uptime_in_days}d`, `${info.uptime_in_seconds || 0}s total`);
            if (info.connected_clients) add('Connected Clients', info.connected_clients);
            if (info.used_memory_human) add('Memory Used', info.used_memory_human, info.used_memory_peak_human ? `Peak: ${info.used_memory_peak_human}` : '');
            if (info.total_commands_processed) add('Commands Processed', Number(info.total_commands_processed).toLocaleString());
            if (info.keyspace_hits !== undefined) {
                const hits = Number(info.keyspace_hits);
                const misses = Number(info.keyspace_misses || 0);
                const ratio = hits + misses > 0 ? ((hits / (hits + misses)) * 100).toFixed(1) : '0';
                add('Hit Rate', `${ratio}%`, `${hits.toLocaleString()} hits / ${misses.toLocaleString()} misses`);
            }
            if (info.role) add('Role', info.role);
            if (info.os) add('OS', info.os);

            // DB info — only match db0..db15, skip distribution stats
            for (const [k, v] of Object.entries(info)) {
                if (/^db\d+$/.test(k)) {
                    const dbNum = k.replace('db', '');
                    const parsed = parseDbInfo(v);
                    add(`Database ${dbNum}`, `${parsed.keys} keys`, parsed.sub);
                }
            }

            grid.innerHTML = cards.join('');
        } catch (err) {
            grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1"><p style="color:var(--error)">${err.message}</p></div>`;
        }
    }

    function parseInfoString(str) {
        const result = {};
        str.split('\n').forEach(line => {
            line = line.trim();
            if (!line || line.startsWith('#')) return;
            const [key, ...val] = line.split(':');
            if (key) result[key] = val.join(':');
        });
        return result;
    }

    function parseDbInfo(str) {
        // Parse "keys=112,expires=110,avg_ttl=51546948,subexpiry=0"
        const parts = {};
        str.split(',').forEach(p => {
            const [k, v] = p.split('=');
            if (k) parts[k.trim()] = v ? v.trim() : '';
        });

        const subs = [];
        if (parts.expires !== undefined) subs.push(`${Number(parts.expires).toLocaleString()} expires`);
        if (parts.avg_ttl !== undefined) {
            const ttlSec = Math.round(Number(parts.avg_ttl) / 1000);
            subs.push(`avg TTL ${ttlSec.toLocaleString()}s`);
        }

        return {
            keys: Number(parts.keys || 0).toLocaleString(),
            sub: subs.join(' · '),
        };
    }

    // Key pattern enter
    document.getElementById('key-pattern').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') scanKeys();
    });
    </script>
</body>
</html>
