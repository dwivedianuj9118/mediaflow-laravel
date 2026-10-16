@php
    $settingsManager = app(\MediaFlow\Laravel\Support\MediaFlowSettingsManager::class);
    $themeAttr = $settingsManager->getThemeAttributes();
    $cssVars = $settingsManager->getCssVariables();
    $showHeader = (bool) config('mediaflow.ui.show_header', true) && !request()->boolean('clean') && !request()->boolean('embed');
@endphp
<!DOCTYPE html>
<html lang="en" {!! $themeAttr !!}>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediaFlow — Media Library</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('vendor/mediaflow/css/mediaflow.css') }}">
    <style>
        @if($cssVars)
        :root {
            {!! $cssVars !!}
        }
        @endif
        body {
            margin: 0;
            padding: 0;
            background: var(--mf-bg-main, #0b0f19);
            color: var(--mf-text-main, #f1f5f9);
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
            text-decoration: none;
            color: inherit;
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
            padding: 1.5rem 2rem;
            max-width: 1440px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    @if($showHeader)
    <header class="mf-app-header">
        <a href="{{ route('mediaflow.manager') }}" class="mf-app-title">
            <span style="font-size: 1.4rem;">🌊</span>
            <span>Media Library</span>
        </a>
        <nav class="mf-nav-links" style="align-items: center;">
            <a href="{{ route('mediaflow.docs') }}" class="mf-nav-link">📖 Documentation</a>
            <a href="{{ route('mediaflow.manager') }}" class="mf-nav-link active">Media Library</a>
            <a href="{{ route('mediaflow.settings.index') }}" class="mf-nav-link">Settings & Compression</a>
            <a href="{{ route('mediaflow.diagnostics.view') }}" class="mf-nav-link">Diagnostics & Storage</a>
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
    @endif

    <main class="mf-page-body" style="{{ !$showHeader ? 'padding: 0; max-width: 100%;' : '' }}">
        @if(!request()->boolean('no_uploader'))
        <div style="margin-bottom: 1.25rem;">
            <x-mediaflow::uploader />
        </div>
        @endif

        <x-mediaflow::manager mode="standalone" />
    </main>

    <script src="{{ asset('vendor/mediaflow/js/mediaflow.js') }}"></script>
</body>
</html>
