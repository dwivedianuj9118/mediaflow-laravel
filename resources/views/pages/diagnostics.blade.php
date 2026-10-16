<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediaFlow — Storage Diagnostics & Connection Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('vendor/mediaflow/css/mediaflow.css') }}">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: var(--mf-bg-main, #f8fafc);
            color: var(--mf-text-main, #0f172a);
            font-family: var(--mf-font-sans, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
        }
        .mf-app-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 2rem;
            background: var(--mf-bg-card);
            border-bottom: 1px solid var(--mf-border);
        }
        .mf-app-title {
            font-size: 1.15rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .mf-nav-links {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
        }
        .mf-nav-link {
            color: var(--mf-text-muted);
            text-decoration: none;
            padding: 0.35rem 0.65rem;
            border-radius: var(--mf-radius-sm);
            transition: var(--mf-transition);
        }
        .mf-nav-link:hover, .mf-nav-link.active {
            color: var(--mf-primary);
            background: var(--mf-primary-light);
        }
        .mf-page-body {
            padding: 2rem;
            max-width: 1000px;
            margin: 0 auto;
        }
        .mf-diag-card {
            background: var(--mf-bg-card);
            border: 1px solid var(--mf-border);
            border-radius: var(--mf-radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--mf-shadow-sm);
        }
        .mf-diag-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .mf-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .mf-stat-item {
            padding: 0.75rem;
            background: var(--mf-bg-main);
            border-radius: var(--mf-radius-sm);
            border: 1px solid var(--mf-border);
        }
        .mf-stat-label {
            font-size: 0.75rem;
            color: var(--mf-text-muted);
            text-transform: uppercase;
            font-weight: 600;
        }
        .mf-stat-val {
            font-size: 1.05rem;
            font-weight: 600;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <header class="mf-app-header">
        <div class="mf-app-title">
            <span style="font-size: 1.4rem;">🌊</span>
            <span>MediaFlow</span>
            <span style="font-size: 0.75rem; font-weight: 500; padding: 0.2rem 0.5rem; border-radius: 999px; background: var(--mf-primary-light); color: var(--mf-primary);">v1.0</span>
        </div>
        <nav class="mf-nav-links" style="align-items: center;">
            <a href="{{ route('mediaflow.docs') }}" class="mf-nav-link">📖 Documentation</a>
            <a href="{{ route('mediaflow.manager') }}" class="mf-nav-link">Media Library</a>
            <a href="{{ route('mediaflow.settings.index') }}" class="mf-nav-link">Settings & Compression</a>
            <a href="{{ route('mediaflow.diagnostics.view') }}" class="mf-nav-link active">Diagnostics & Storage</a>
            <a href="/" class="mf-nav-link" style="opacity: 0.7;">← Demo App</a>
            @if(auth()->check())
                <span style="font-size: 0.8rem; padding: 0.25rem 0.6rem; border-radius: 6px; background: var(--mf-bg-subtle); border: 1px solid var(--mf-border);">
                    👤 {{ auth()->user()->name }} (<code>{{ auth()->user()->role ?? 'user' }}</code>)
                </span>
                @if(\Illuminate\Support\Facades\Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}" style="display: inline; margin: 0;">
                        @csrf
                        <button type="submit" class="mf-nav-link" style="color: var(--mf-danger); background: none; border: 0; cursor: pointer; font: inherit;">Logout</button>
                    </form>
                @endif
            @elseif(\Illuminate\Support\Facades\Route::has('login'))
                <a href="{{ route('login') }}" class="mf-nav-link" style="color: var(--mf-primary);">🔑 Login</a>
            @endif
        </nav>
    </header>

    <main class="mf-page-body">
        {{-- Connection Test Card --}}
        <div class="mf-diag-card">
            <div class="mf-diag-title">
                <span>⚡</span> Storage Connection & Health Verification
            </div>
            <p style="font-size: 0.875rem; color: var(--mf-text-muted); margin-bottom: 1.25rem;">
                Runs a safe server-side probe against your configured storage disk to test write, read, and delete permissions without exposing credentials or secret tokens.
            </p>

            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.25rem;">
                <label style="font-size: 0.875rem; font-weight: 500;">Storage Disk:</label>
                <select id="disk-select" style="padding: 0.45rem 0.75rem; border-radius: var(--mf-radius-sm); border: 1px solid var(--mf-border); background: var(--mf-bg-card); color: var(--mf-text-main);">
                    <option value="{{ config('mediaflow.default_disk', 'public') }}">{{ config('mediaflow.default_disk', 'public') }} (Default)</option>
                    <option value="local">local</option>
                    <option value="s3">s3 (if configured)</option>
                </select>
                <button type="button" id="run-test-btn" class="mf-btn mf-btn-primary">
                    ▶ Run Connection Test
                </button>
            </div>

            <div id="test-result" style="display: none; padding: 1rem; border-radius: var(--mf-radius-sm); border: 1px solid var(--mf-border); font-size: 0.875rem;"></div>
        </div>

        {{-- System Environment Card --}}
        <div class="mf-diag-card">
            <div class="mf-diag-title">
                <span>🖥</span> Environment & Processing Capabilities
            </div>
            <div class="mf-grid-2">
                <div class="mf-stat-item">
                    <div class="mf-stat-label">PHP Version</div>
                    <div class="mf-stat-val">{{ PHP_VERSION }}</div>
                </div>
                <div class="mf-stat-item">
                    <div class="mf-stat-label">Laravel Version</div>
                    <div class="mf-stat-val">{{ app()->version() }}</div>
                </div>
                <div class="mf-stat-item">
                    <div class="mf-stat-label">GD Extension</div>
                    <div class="mf-stat-val" style="color: {{ extension_loaded('gd') ? 'var(--mf-success)' : 'var(--mf-danger)' }};">
                        {{ extension_loaded('gd') ? '✓ Available' : '✗ Missing' }}
                    </div>
                </div>
                <div class="mf-stat-item">
                    <div class="mf-stat-label">ImageMagick Extension</div>
                    <div class="mf-stat-val" style="color: {{ extension_loaded('imagick') ? 'var(--mf-success)' : 'var(--mf-muted)' }};">
                        {{ extension_loaded('imagick') ? '✓ Available' : 'Optional' }}
                    </div>
                </div>
                <div class="mf-stat-item">
                    <div class="mf-stat-label">Default Disk</div>
                    <div class="mf-stat-val">{{ config('mediaflow.default_disk', 'public') }}</div>
                </div>
                <div class="mf-stat-item">
                    <div class="mf-stat-label">Temporary Disk</div>
                    <div class="mf-stat-val">{{ config('mediaflow.temporary_disk', 'local') }}</div>
                </div>
                <div class="mf-stat-item">
                    <div class="mf-stat-label">Delivery Driver</div>
                    <div class="mf-stat-val">{{ config('mediaflow.delivery.driver', 'disk') }}</div>
                </div>
                <div class="mf-stat-item">
                    <div class="mf-stat-label">Max File Size Limit</div>
                    <div class="mf-stat-val">{{ round(config('mediaflow.max_file_size_kb', 20480) / 1024, 1) }} MB</div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const runBtn = document.getElementById('run-test-btn');
        const diskSelect = document.getElementById('disk-select');
        const resultDiv = document.getElementById('test-result');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        runBtn.addEventListener('click', () => {
            runBtn.disabled = true;
            runBtn.textContent = 'Testing...';
            resultDiv.style.display = 'block';
            resultDiv.style.background = 'var(--mf-bg-main)';
            resultDiv.textContent = 'Executing storage read/write probe...';

            fetch("{{ route('mediaflow.diagnostics.test') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ disk: diskSelect.value })
            })
            .then(res => res.json())
            .then(data => {
                runBtn.disabled = false;
                runBtn.textContent = '▶ Run Connection Test';

                if (data.success) {
                    resultDiv.style.background = 'var(--mf-success-light)';
                    resultDiv.style.borderColor = 'var(--mf-success)';
                    resultDiv.style.color = 'var(--mf-success)';
                    resultDiv.innerHTML = `<strong>✓ Connection Verified (${data.latency_ms}ms)</strong><br>${data.message}`;
                } else {
                    resultDiv.style.background = 'var(--mf-danger-light)';
                    resultDiv.style.borderColor = 'var(--mf-danger)';
                    resultDiv.style.color = 'var(--mf-danger)';
                    resultDiv.innerHTML = `<strong>✗ Connection Failed (${data.latency_ms}ms)</strong><br>${data.message}`;
                }
            })
            .catch(err => {
                runBtn.disabled = false;
                runBtn.textContent = '▶ Run Connection Test';
                resultDiv.style.background = 'var(--mf-danger-light)';
                resultDiv.textContent = 'Connection error: ' + err.message;
            });
        });
    </script>
</body>
</html>
