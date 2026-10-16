<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediaFlow — Media Settings & Configuration</title>
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
            gap: 0.75rem;
        }
        .mf-nav-link {
            color: var(--mf-text-muted);
            text-decoration: none;
            padding: 0.35rem 0.65rem;
            border-radius: var(--mf-radius-sm);
            transition: var(--mf-transition);
            font-size: 0.875rem;
        }
        .mf-nav-link:hover, .mf-nav-link.active {
            color: var(--mf-primary);
            background: var(--mf-primary-light);
        }
        .mf-page-body {
            padding: 2rem;
            max-width: 960px;
            margin: 0 auto;
        }
        .mf-settings-card {
            background: var(--mf-bg-card);
            border: 1px solid var(--mf-border);
            border-radius: var(--mf-radius-lg);
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--mf-shadow-sm);
        }
        .mf-card-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.35rem;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .mf-card-desc {
            font-size: 0.85rem;
            color: var(--mf-text-muted);
            margin-bottom: 1.25rem;
        }
        .mf-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }
        .mf-field {
            margin-bottom: 1.1rem;
        }
        .mf-field-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.8125rem;
            font-weight: 600;
            margin-bottom: 0.35rem;
        }
        .mf-input-control {
            width: 100%;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            border: 1px solid var(--mf-border);
            border-radius: var(--mf-radius-sm);
            background: var(--mf-bg-main);
            color: var(--mf-text-main);
            outline: none;
            box-sizing: border-box;
            transition: var(--mf-transition);
        }
        .mf-input-control:focus {
            border-color: var(--mf-border-focus);
            background: var(--mf-bg-card);
        }
        .mf-switch-label {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.875rem;
            cursor: pointer;
            user-select: none;
        }
        .mf-sticky-bar {
            position: sticky;
            bottom: 1.5rem;
            background: var(--mf-bg-card);
            border: 1px solid var(--mf-border);
            border-radius: var(--mf-radius-lg);
            padding: 1rem 1.5rem;
            box-shadow: var(--mf-shadow-lg);
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 2rem;
            z-index: 50;
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
            <a href="{{ route('mediaflow.settings.index') }}" class="mf-nav-link active">Settings & Compression</a>
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

    <main class="mf-page-body">
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem;">Media Configuration</h1>
            <p style="color: var(--mf-text-muted); font-size: 0.95rem; margin: 0;">Configure compression pipelines, image format conversions, storage quotas, and design theme inheritance.</p>
        </div>

        <form id="mediaflow-settings-form">
            @csrf

            {{-- 1. Image Compression & Format Conversion --}}
            <div class="mf-settings-card">
                <div class="mf-card-header">
                    <span>⚡</span> Image Compression & Optimization
                </div>
                <div class="mf-card-desc">Control automatic compression quality, target web formats, and EXIF stripping.</div>

                <div class="mf-form-grid">
                    <div class="mf-field">
                        <label class="mf-field-label">Default Format</label>
                        <select name="images.default_format" class="mf-input-control">
                            <option value="webp" {{ ($settings['images']['default_format'] ?? 'webp') === 'webp' ? 'selected' : '' }}>WebP (Recommended - Smallest)</option>
                            <option value="jpeg" {{ ($settings['images']['default_format'] ?? '') === 'jpeg' ? 'selected' : '' }}>JPEG (Standard Compatibility)</option>
                            <option value="png" {{ ($settings['images']['default_format'] ?? '') === 'png' ? 'selected' : '' }}>PNG (Lossless Graphics)</option>
                            <option value="keep" {{ ($settings['images']['default_format'] ?? '') === 'keep' ? 'selected' : '' }}>Keep Original Format</option>
                        </select>
                    </div>

                    <div class="mf-field">
                        <div class="mf-field-label">
                            <span>Default Compression Quality</span>
                            <span id="quality-display" style="color: var(--mf-primary);">{{ $settings['images']['default_quality'] ?? 82 }}%</span>
                        </div>
                        <input type="range" min="30" max="95" name="images.default_quality" id="quality-slider" value="{{ $settings['images']['default_quality'] ?? 82 }}" class="mf-range-slider" style="margin-top: 0.6rem;">
                    </div>
                </div>

                <div class="mf-form-grid" style="margin-top: 0.5rem;">
                    <div class="mf-field">
                        <label class="mf-field-label">Max Allowed Width (px)</label>
                        <input type="number" name="images.max_width" class="mf-input-control" value="{{ $settings['images']['max_width'] ?? 2560 }}">
                    </div>
                    <div class="mf-field">
                        <label class="mf-field-label">Max Allowed Height (px)</label>
                        <input type="number" name="images.max_height" class="mf-input-control" value="{{ $settings['images']['max_height'] ?? 2560 }}">
                    </div>
                </div>

                <div style="display: flex; gap: 2rem; margin-top: 1rem;">
                    <label class="mf-switch-label">
                        <input type="checkbox" name="images.auto_optimize" value="1" {{ !empty($settings['images']['auto_optimize']) ? 'checked' : '' }}>
                        <span>Auto-optimize images on initial upload</span>
                    </label>
                    <label class="mf-switch-label">
                        <input type="checkbox" name="images.strip_exif" value="1" {{ !empty($settings['images']['strip_exif']) ? 'checked' : '' }}>
                        <span>Strip EXIF metadata (GPS / Privacy)</span>
                    </label>
                </div>
            </div>

            {{-- 2. Storage & Upload Quotas --}}
            <div class="mf-settings-card">
                <div class="mf-card-header">
                    <span>📦</span> Storage Disks & Upload Limits
                </div>
                <div class="mf-card-desc">Define disk drivers and server-side file thresholds.</div>

                <div class="mf-form-grid">
                    <div class="mf-field">
                        <label class="mf-field-label">Default Storage Disk</label>
                        <select name="storage.default_disk" class="mf-input-control">
                            <option value="public" {{ ($settings['storage']['default_disk'] ?? 'public') === 'public' ? 'selected' : '' }}>public (Local Public Storage)</option>
                            <option value="local" {{ ($settings['storage']['default_disk'] ?? '') === 'local' ? 'selected' : '' }}>local (Private Local Disk)</option>
                            <option value="s3" {{ ($settings['storage']['default_disk'] ?? '') === 's3' ? 'selected' : '' }}>s3 (AWS S3 / S3 Compatible)</option>
                        </select>
                    </div>

                    <div class="mf-field">
                        <label class="mf-field-label">Max File Size Limit (KB)</label>
                        <input type="number" name="upload.max_file_size_kb" class="mf-input-control" value="{{ $settings['upload']['max_file_size_kb'] ?? 20480 }}">
                        <span style="font-size: 0.75rem; color: var(--mf-text-muted);">Current: {{ round(($settings['upload']['max_file_size_kb'] ?? 20480) / 1024, 1) }} MB</span>
                    </div>
                </div>
            </div>

            {{-- 3. Cloud Storage & CDN Provider Presets (10 Providers) --}}
            <div class="mf-settings-card" id="card-cloud-providers">
                <div class="mf-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span>☁️</span> Cloud Storage & CDN Providers (10 Providers)
                    </div>
                    <span id="provider-badge-pill" style="font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.65rem; border-radius: 999px; background: var(--mf-primary-light); color: var(--mf-primary); border: 1px solid rgba(79, 70, 229, 0.2);">
                        {{ $providers[$settings['provider']['name'] ?? 'local']['badge'] ?? 'Zero-Config' }}
                    </span>
                </div>
                <div class="mf-card-desc">
                    Connect directly to your cloud storage or CDN provider. Just select your provider and enter your <strong>Key</strong>, <strong>Secret</strong>, <strong>Bucket</strong>, and <strong>Public URL</strong>.
                </div>

                <!-- 10 Provider Pills Selector -->
                <div class="mf-provider-pills-grid">
                    @foreach($providers as $pId => $p)
                        <div class="mf-provider-pill {{ ($settings['provider']['name'] ?? 'local') === $pId ? 'active' : '' }}" 
                             data-provider="{{ $pId }}"
                             data-name="{{ $p['name'] }}"
                             data-badge="{{ $p['badge'] }}"
                             data-key-label="{{ $p['key_label'] }}"
                             data-secret-label="{{ $p['secret_label'] }}"
                             data-account-id-label="{{ $p['account_id_label'] ?? '' }}"
                             data-bucket-label="{{ $p['bucket_label'] }}"
                             data-region-label="{{ $p['region_label'] }}"
                             data-public-hint="{{ $p['public_url_hint'] }}"
                             data-endpoint="{{ $p['endpoint_template'] }}"
                             data-default-region="{{ $p['default_region'] }}">
                            <span style="font-size: 1.3rem;">{{ $p['icon'] }}</span>
                            <div style="min-width: 0;">
                                <div style="font-weight: 600; font-size: 0.8125rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $p['name'] }}</div>
                                <div style="font-size: 0.7rem; color: var(--mf-text-muted);">{{ $p['badge'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mf-field" style="margin-bottom: 1rem;">
                    <label class="mf-field-label">Active Provider Selection</label>
                    <select name="provider.name" id="provider-name-select" class="mf-input-control">
                        <option value="local" {{ ($settings['provider']['name'] ?? 'local') === 'local' ? 'selected' : '' }}>🏠 Local Public Disk (No Cloud Config)</option>
                        @foreach($providers as $pId => $p)
                            <option value="{{ $pId }}" {{ ($settings['provider']['name'] ?? '') === $pId ? 'selected' : '' }}>
                                {{ $p['icon'] }} {{ $p['name'] }} ({{ $p['badge'] }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="provider-details-card" style="background: var(--mf-bg-main); border: 1px solid var(--mf-border); border-radius: var(--mf-radius-md); padding: 1.25rem; margin-bottom: 0.5rem;">
                    <div id="provider-description-text" style="font-size: 0.85rem; color: var(--mf-text-muted); margin-bottom: 1rem; line-height: 1.5;">
                        {{ $providers[$settings['provider']['name'] ?? 'cloudflare_r2']['description'] ?? 'Select a cloud provider above.' }}
                    </div>

                    <div class="mf-form-grid">
                        <div class="mf-field">
                            <label class="mf-field-label" id="provider-key-label">
                                {{ $providers[$settings['provider']['name'] ?? 'cloudflare_r2']['key_label'] ?? 'Access Key ID / Account ID' }}
                            </label>
                            <input type="text" name="provider.key" id="provider-key-input" class="mf-input-control" value="{{ $settings['provider']['key'] ?? '' }}" placeholder="Enter key or account ID...">
                        </div>

                        <div class="mf-field">
                            <label class="mf-field-label" id="provider-secret-label">
                                {{ $providers[$settings['provider']['name'] ?? 'cloudflare_r2']['secret_label'] ?? 'Secret Access Key / Token' }}
                            </label>
                            <div style="position: relative;">
                                <input type="password" name="provider.secret" id="provider-secret-input" class="mf-input-control" value="{{ $settings['provider']['secret'] ?? '' }}" placeholder="Enter secret key or token...">
                                <button type="button" id="btn-toggle-secret-visibility" style="position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--mf-text-muted); font-size: 0.85rem;">👁️</button>
                            </div>
                        </div>
                    </div>

                    {{-- Dedicated Account ID field (for Cloudflare R2, Azure, etc.) --}}
                    <div class="mf-form-grid" id="provider-account-id-row" style="margin-top: 0.75rem; {{ ($settings['provider']['name'] ?? 'cloudflare_r2') === 'cloudflare_r2' ? '' : 'display: none;' }}">
                        <div class="mf-field" style="grid-column: 1 / -1;">
                            <label class="mf-field-label" id="provider-account-id-label">
                                {{ $providers[$settings['provider']['name'] ?? 'cloudflare_r2']['account_id_label'] ?? 'Cloudflare Account ID (32-character hex from Overview)' }}
                            </label>
                            <input type="text" name="provider.account_id" id="provider-account-id-input" class="mf-input-control" value="{{ $settings['provider']['account_id'] ?? '' }}" placeholder="e.g. 0123456789abcdef0123456789abcdef">
                            <span id="provider-account-id-hint" style="font-size: 0.75rem; color: var(--mf-text-muted);">
                                Found on your Cloudflare Dashboard overview / R2 page (used to build the endpoint URL: <code>https://&lt;account_id&gt;.r2.cloudflarestorage.com</code>).
                            </span>
                        </div>
                    </div>

                    <div class="mf-form-grid" style="margin-top: 0.75rem;">
                        <div class="mf-field">
                            <label class="mf-field-label" id="provider-bucket-label">
                                {{ $providers[$settings['provider']['name'] ?? 'cloudflare_r2']['bucket_label'] ?? 'Bucket / Container Name' }}
                            </label>
                            <input type="text" name="provider.bucket" id="provider-bucket-input" class="mf-input-control" value="{{ $settings['provider']['bucket'] ?? '' }}" placeholder="e.g. my-app-media">
                        </div>

                        <div class="mf-field">
                            <label class="mf-field-label">Public Delivery URL / Custom CDN Domain</label>
                            <input type="url" name="provider.public_url" id="provider-public-url-input" class="mf-input-control" value="{{ $settings['provider']['public_url'] ?? '' }}" placeholder="https://pub-xxx.r2.dev or https://cdn.example.com">
                            <span id="provider-public-hint" style="font-size: 0.75rem; color: var(--mf-text-muted);">
                                {{ $providers[$settings['provider']['name'] ?? 'cloudflare_r2']['public_url_hint'] ?? '' }}
                            </span>
                        </div>
                    </div>

                    <div class="mf-form-grid" style="margin-top: 0.75rem;">
                        <div class="mf-field">
                            <label class="mf-field-label" id="provider-region-label">Region / Datacenter (Optional)</label>
                            <input type="text" name="provider.region" id="provider-region-input" class="mf-input-control" value="{{ $settings['provider']['region'] ?? '' }}" placeholder="e.g. auto, us-east-1, eu2, nyc3">
                        </div>

                        <div class="mf-field">
                            <label class="mf-field-label">Endpoint Template / Custom Host (Optional)</label>
                            <input type="text" name="provider.endpoint" id="provider-endpoint-input" class="mf-input-control" value="{{ $settings['provider']['endpoint'] ?? '' }}" placeholder="Auto-generated from provider preset">
                        </div>
                    </div>

                    <!-- Connection Test & Status Area -->
                    <div style="display: flex; gap: 0.75rem; align-items: center; margin-top: 1.25rem; flex-wrap: wrap;">
                        <button type="button" id="btn-test-provider" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; font-size: 0.825rem; font-weight: 600; border-radius: var(--mf-radius-md); border: 1px solid var(--mf-border); background: var(--mf-bg-card); cursor: pointer; color: var(--mf-text-main);">
                            <span>⚡</span> Test Provider Connection
                        </button>
                        <button type="button" id="btn-copy-env-snippet" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem; font-size: 0.825rem; font-weight: 600; border-radius: var(--mf-radius-md); border: 1px solid var(--mf-border); background: var(--mf-bg-card); cursor: pointer; color: var(--mf-text-main);">
                            <span>📋</span> Copy .env Block
                        </button>
                        <div id="provider-test-status" style="font-size: 0.825rem; font-weight: 600;"></div>
                    </div>

                    <!-- Copyable .env Block Preview -->
                    <div id="env-preview-box" style="margin-top: 1rem; display: none;">
                        <div style="font-size: 0.75rem; font-weight: 600; color: var(--mf-text-muted); margin-bottom: 0.35rem; text-transform: uppercase;">Generated .env configuration</div>
                        <pre id="env-preview-code" style="background: #0d1117; color: #c9d1d9; padding: 0.85rem; border-radius: 6px; font-size: 0.8rem; overflow-x: auto; margin: 0; border: 1px solid #30363d;"></pre>
                    </div>
                </div>
            </div>

            {{-- 4. Theme & System Design Inheritance --}}
            <div class="mf-settings-card">
                <div class="mf-card-header">
                    <span>🎨</span> Appearance, Light / Dark Modes & Theme Inheritance
                </div>
                <div class="mf-card-desc">Configure light/dark modes, primary brand colors, folder icon colors, and seamless design tokens.</div>

                <div class="mf-form-grid">
                    <div class="mf-field">
                        <label class="mf-field-label">Color Mode (Light / Dark)</label>
                        <select name="ui.mode" id="theme-mode-select" class="mf-input-control">
                            <option value="system" {{ ($settings['ui']['mode'] ?? 'system') === 'system' ? 'selected' : '' }}>🌓 System Inherited (Auto-match parent app / OS)</option>
                            <option value="light" {{ ($settings['ui']['mode'] ?? '') === 'light' ? 'selected' : '' }}>☀️ Force Light Mode (Crisp white / subtle gray)</option>
                            <option value="dark" {{ ($settings['ui']['mode'] ?? '') === 'dark' ? 'selected' : '' }}>🌙 Force Dark Mode (Sleek dark slate / deep navy)</option>
                        </select>
                        <span style="font-size: 0.75rem; color: var(--mf-text-muted);">Controls media manager, picker modal, and upload drawers.</span>
                    </div>

                    <div class="mf-field">
                        <label class="mf-field-label">Theme Color Preset (Picker & Accents)</label>
                        <select name="ui.theme" id="theme-preset-select" class="mf-input-control">
                            <option value="system" {{ ($settings['ui']['theme'] ?? 'system') === 'system' ? 'selected' : '' }}>System Inherited (Auto-match parent CSS tokens)</option>
                            <option value="indigo" {{ ($settings['ui']['theme'] ?? '') === 'indigo' ? 'selected' : '' }}>Indigo Modern (#4f46e5)</option>
                            <option value="emerald" {{ ($settings['ui']['theme'] ?? '') === 'emerald' ? 'selected' : '' }}>Emerald Fresh (#10b981)</option>
                            <option value="violet" {{ ($settings['ui']['theme'] ?? '') === 'violet' ? 'selected' : '' }}>Violet Royal (#8b5cf6)</option>
                            <option value="sky" {{ ($settings['ui']['theme'] ?? '') === 'sky' ? 'selected' : '' }}>Sky Ocean (#0284c7)</option>
                            <option value="amber" {{ ($settings['ui']['theme'] ?? '') === 'amber' ? 'selected' : '' }}>Amber Warm (#f59e0b)</option>
                            <option value="rose" {{ ($settings['ui']['theme'] ?? '') === 'rose' ? 'selected' : '' }}>Rose Vibrant (#f43f5e)</option>
                            <option value="slate" {{ ($settings['ui']['theme'] ?? '') === 'slate' ? 'selected' : '' }}>Slate Neutral (#334155)</option>
                            <option value="custom" {{ ($settings['ui']['theme'] ?? '') === 'custom' ? 'selected' : '' }}>Custom Primary Accent</option>
                        </select>
                        <span style="font-size: 0.75rem; color: var(--mf-text-muted);">Sets focus rings, selection borders, badges, and primary action buttons.</span>
                    </div>
                </div>

                <div class="mf-form-grid" style="margin-top: 1rem;">
                    <div class="mf-field">
                        <label class="mf-field-label">Custom Primary Accent Color</label>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="color" name="ui.custom_primary" id="color-picker" value="{{ $settings['ui']['custom_primary'] ?? '#4f46e5' }}" style="height: 38px; width: 48px; border: 1px solid var(--mf-border); border-radius: 6px; padding: 2px; cursor: pointer; background: var(--mf-bg-main);">
                            <input type="text" id="color-hex-text" class="mf-input-control" value="{{ $settings['ui']['custom_primary'] ?? '#4f46e5' }}">
                        </div>
                        <span style="font-size: 0.75rem; color: var(--mf-text-muted);">Active when Theme Preset is set to "Custom Primary Accent".</span>
                    </div>

                    <div class="mf-field">
                        <label class="mf-field-label">Media Folder Color (📁 Folders)</label>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="color" name="ui.folder_color" id="folder-color-picker" value="{{ $settings['ui']['folder_color'] ?? '#f59e0b' }}" style="height: 38px; width: 48px; border: 1px solid var(--mf-border); border-radius: 6px; padding: 2px; cursor: pointer; background: var(--mf-bg-main);">
                            <input type="text" id="folder-color-text" class="mf-input-control" value="{{ $settings['ui']['folder_color'] ?? '#f59e0b' }}" placeholder="#f59e0b">
                            <select id="folder-preset-select" class="mf-input-control" style="max-width: 170px;">
                                <option value="">Quick Preset...</option>
                                <option value="#f59e0b">Amber Gold (Classic)</option>
                                <option value="primary">Match Theme Accent</option>
                                <option value="#3b82f6">Ocean Blue</option>
                                <option value="#10b981">Emerald Green</option>
                                <option value="#8b5cf6">Violet Purple</option>
                                <option value="#f43f5e">Rose Pink</option>
                            </select>
                        </div>
                        <span style="font-size: 0.75rem; color: var(--mf-text-muted);">Controls folder icon badges in Explorer and Media Picker.</span>
                    </div>
                </div>

                {{-- Interactive Live Component Preview --}}
                <div style="margin-top: 1.25rem; padding: 1.25rem; border-radius: var(--mf-radius-md); border: 1px solid var(--mf-border); background: var(--mf-bg-main);" id="theme-live-preview-box">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <span style="font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--mf-text-muted);">
                            👁️ Live Real-time Component Preview
                        </span>
                        <span id="preview-mode-tag" style="font-size: 0.72rem; padding: 0.15rem 0.5rem; border-radius: 999px; background: var(--mf-bg-card); border: 1px solid var(--mf-border); color: var(--mf-text-muted);">
                            Mode: {{ ucfirst($settings['ui']['mode'] ?? 'System') }}
                        </span>
                    </div>
                    <div style="display: flex; gap: 1.25rem; flex-wrap: wrap; align-items: center;">
                        <!-- Folder Item Preview -->
                        <div style="border: 1px solid var(--mf-border); border-radius: var(--mf-radius-md); background: var(--mf-bg-card); padding: 0.65rem 1rem; display: flex; align-items: center; gap: 0.75rem; box-shadow: var(--mf-shadow-sm);">
                            <div id="preview-folder-icon" style="width: 38px; height: 38px; border-radius: 6px; background: rgba(245, 158, 11, 0.14); display: flex; align-items: center; justify-content: center;">
                                <svg id="preview-folder-svg" style="width: 24px; height: 24px; fill: #f59e0b;" viewBox="0 0 24 24">
                                    <path d="M19.5 21a3 3 0 0 0 3-3v-4.5a3 3 0 0 0-3-3h-1.5V9a3 3 0 0 0-3-3h-3.379a3 3 0 0 1-2.121-.879L8.379 3.879A3 3 0 0 0 6.257 3H4.5A3 3 0 0 0 1.5 6v12a3 3 0 0 0 3 3h15Z" opacity="0.35"/>
                                    <path d="M2 9.5A2.5 2.5 0 0 1 4.5 7h15A2.5 2.5 0 0 1 22 9.5v8a2.5 2.5 0 0 1-2.5 2.5h-15A2.5 2.5 0 0 1 2 17.5v-8Z"/>
                                </svg>
                            </div>
                            <div>
                                <div style="font-size: 0.8125rem; font-weight: 600; color: var(--mf-text-main);">Documents & Media</div>
                                <div style="font-size: 0.7rem; color: var(--mf-text-muted);">14 items</div>
                            </div>
                        </div>

                        <!-- Media Picker Card Preview -->
                        <div id="preview-picker-card" style="border: 2px solid var(--mf-primary); border-radius: var(--mf-radius-md); background: var(--mf-bg-card); padding: 0.5rem; text-align: center; width: 110px; box-shadow: var(--mf-shadow-sm);">
                            <div style="height: 50px; border-radius: 4px; background: var(--mf-bg-hover); display: flex; align-items: center; justify-content: center; margin-bottom: 0.35rem;">
                                <span style="font-size: 1.5rem;">🖼️</span>
                            </div>
                            <div style="font-size: 0.72rem; font-weight: 600; color: var(--mf-text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">hero-banner.webp</div>
                            <div id="preview-picker-badge" style="font-size: 0.65rem; color: var(--mf-primary); font-weight: 600;">✓ Selected</div>
                        </div>

                        <!-- Action Buttons Preview -->
                        <div style="display: flex; flex-direction: column; gap: 0.45rem;">
                            <button type="button" class="mf-btn mf-btn-primary" id="preview-primary-btn" style="padding: 0.45rem 1rem; font-size: 0.8125rem;">
                                <span>⚡</span> Select in Media Picker
                            </button>
                            <span id="preview-badge-pill" style="font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 999px; background: var(--mf-primary-light); color: var(--mf-primary); text-align: center;">
                                Accent: Indigo Modern
                            </span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 2rem; margin-top: 1rem;">
                    <label class="mf-switch-label">
                        <input type="checkbox" name="ui.inherit_system" value="1" {{ !empty($settings['ui']['inherit_system']) ? 'checked' : '' }}>
                        <span>Inherit font-family, background, and dark mode tokens from host HTML</span>
                    </label>
                    <label class="mf-switch-label">
                        <input type="checkbox" name="ui.animations" value="1" {{ !empty($settings['ui']['animations']) ? 'checked' : '' }}>
                        <span>Enable smooth micro-animations & transitions</span>
                    </label>
                </div>
            </div>

            {{-- 4. Access Control, Auth Guard & Roles --}}
            <div class="mf-settings-card">
                <div class="mf-card-header">
                    <span>🛡️</span> Access Control, Auth Guards & Role Permissions
                </div>
                <div class="mf-card-desc">Restrict MediaFlow access by authentication status, custom auth guards, role names, permissions, or custom middleware.</div>

                <div class="mf-form-grid">
                    <div class="mf-field">
                        <label class="mf-field-label">Authentication Guard</label>
                        <input type="text" name="auth.guard" class="mf-input-control" placeholder="web, admin, api, etc. (leave empty for default)" value="{{ $settings['auth']['guard'] ?? '' }}">
                        <span style="font-size: 0.75rem; color: var(--mf-text-muted);">Leave empty to use Laravel's default guard, or specify 'web', 'admin', 'sanctum', etc.</span>
                    </div>

                    <div class="mf-field">
                        <label class="mf-field-label">Allowed Roles (comma-separated)</label>
                        @php
                            $rolesVal = is_array($settings['auth']['roles'] ?? '') ? implode(', ', $settings['auth']['roles']) : ($settings['auth']['roles'] ?? '');
                        @endphp
                        <input type="text" name="auth.roles" class="mf-input-control" placeholder="e.g. admin, media-manager, editor" value="{{ $rolesVal }}">
                        <span style="font-size: 0.75rem; color: var(--mf-text-muted);">Compatible with Spatie hasRole/hasAnyRole, model attributes ($user->role), or custom role methods.</span>
                    </div>
                </div>

                <div class="mf-form-grid" style="margin-top: 1rem;">
                    <div class="mf-field">
                        <label class="mf-field-label">Allowed Permissions (comma-separated)</label>
                        @php
                            $permsVal = is_array($settings['auth']['permissions'] ?? '') ? implode(', ', $settings['auth']['permissions']) : ($settings['auth']['permissions'] ?? '');
                        @endphp
                        <input type="text" name="auth.permissions" class="mf-input-control" placeholder="e.g. manage-media, upload-files" value="{{ $permsVal }}">
                        <span style="font-size: 0.75rem; color: var(--mf-text-muted);">Checked via $user->can() or $user->hasPermissionTo().</span>
                    </div>

                    <div class="mf-field">
                        <label class="mf-field-label">Gate Name (optional)</label>
                        <input type="text" name="auth.gate" class="mf-input-control" placeholder="e.g. viewMediaFlow or manage-media" value="{{ $settings['auth']['gate'] ?? '' }}">
                        <span style="font-size: 0.75rem; color: var(--mf-text-muted);">Laravel Gate checked via Gate::allows('gate-name').</span>
                    </div>
                </div>

                <div style="margin-top: 1rem;">
                    <label class="mf-switch-label">
                        <input type="checkbox" name="auth.enabled" value="1" {{ !empty($settings['auth']['enabled']) ? 'checked' : '' }}>
                        <span>Enforce Authentication for all MediaFlow management endpoints</span>
                    </label>
                    @if(!config('mediaflow.auth.allow_ui_override', false))
                        <div style="font-size: 0.75rem; color: var(--mf-text-muted); margin-top: 0.4rem;">
                            Values set in <code>config/mediaflow.php</code> / <code>.env</code> are a security floor: settings saved here can add restrictions but cannot switch off authentication or clear config roles. Set <code>MEDIAFLOW_AUTH_ALLOW_UI_OVERRIDE=true</code> to let this page fully control access.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Sticky Action Bar --}}
            <div class="mf-sticky-bar">
                <button type="button" id="reset-defaults-btn" class="mf-btn mf-btn-danger">
                    ↺ Reset to Defaults
                </button>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span id="save-msg" style="font-size: 0.875rem; font-weight: 500;"></span>
                    <button type="submit" id="save-settings-btn" class="mf-btn mf-btn-primary" style="padding: 0.6rem 1.4rem;">
                        Save Configuration
                    </button>
                </div>
            </div>
        </form>
    </main>

    <script>
        const slider = document.getElementById('quality-slider');
        const display = document.getElementById('quality-display');
        slider.addEventListener('input', () => {
            display.textContent = slider.value + '%';
        });

        const colorPicker = document.getElementById('color-picker');
        const colorText = document.getElementById('color-hex-text');
        colorPicker.addEventListener('input', () => {
            colorText.value = colorPicker.value;
            updateLivePreview();
        });
        colorText.addEventListener('input', () => {
            colorPicker.value = colorText.value;
            updateLivePreview();
        });

        // -------------------------------------------------------------
        // Theme & Live Real-time Component Preview Logic
        // -------------------------------------------------------------
        const themeModeSelect = document.getElementById('theme-mode-select');
        const themePresetSelect = document.getElementById('theme-preset-select');
        const folderColorPicker = document.getElementById('folder-color-picker');
        const folderColorText = document.getElementById('folder-color-text');
        const folderPresetSelect = document.getElementById('folder-preset-select');
        const previewBox = document.getElementById('theme-live-preview-box');
        const previewModeTag = document.getElementById('preview-mode-tag');
        const previewFolderIcon = document.getElementById('preview-folder-icon');
        const previewFolderSvg = document.getElementById('preview-folder-svg');
        const previewPickerCard = document.getElementById('preview-picker-card');
        const previewPickerBadge = document.getElementById('preview-picker-badge');
        const previewPrimaryBtn = document.getElementById('preview-primary-btn');
        const previewBadgePill = document.getElementById('preview-badge-pill');

        const themeColors = {
            indigo: '#4f46e5',
            emerald: '#10b981',
            violet: '#8b5cf6',
            sky: '#0284c7',
            amber: '#f59e0b',
            rose: '#f43f5e',
            slate: '#334155',
            system: '#4f46e5',
        };

        function updateLivePreview() {
            const mode = themeModeSelect?.value || 'system';
            const theme = themePresetSelect?.value || 'indigo';
            let primaryHex = theme === 'custom' ? (colorPicker.value || '#4f46e5') : (themeColors[theme] || '#4f46e5');

            let folderHex = folderColorPicker?.value || '#f59e0b';
            if (folderColorText && folderColorText.value === 'primary') {
                folderHex = primaryHex;
            }

            if (previewModeTag) {
                previewModeTag.textContent = 'Mode: ' + (mode === 'dark' ? '🌙 Dark' : (mode === 'light' ? '☀️ Light' : '🌓 System'));
            }

            if (previewBox) {
                if (mode === 'dark') {
                    previewBox.style.background = '#0b0f19';
                    previewBox.style.borderColor = '#1e293b';
                    previewBox.style.color = '#f1f5f9';
                } else if (mode === 'light') {
                    previewBox.style.background = '#f8fafc';
                    previewBox.style.borderColor = '#e2e8f0';
                    previewBox.style.color = '#0f172a';
                } else {
                    previewBox.style.background = 'var(--mf-bg-main)';
                    previewBox.style.borderColor = 'var(--mf-border)';
                    previewBox.style.color = 'var(--mf-text-main)';
                }
            }

            if (previewFolderSvg) previewFolderSvg.style.fill = folderHex;
            if (previewFolderIcon) {
                previewFolderIcon.style.background = folderHex.startsWith('#') ? folderHex + '24' : 'rgba(245, 158, 11, 0.14)';
            }

            if (previewPickerCard) {
                previewPickerCard.style.borderColor = primaryHex;
                previewPickerCard.style.boxShadow = `0 0 0 1px ${primaryHex}33`;
            }
            if (previewPickerBadge) previewPickerBadge.style.color = primaryHex;
            if (previewPrimaryBtn) {
                previewPrimaryBtn.style.background = primaryHex;
                previewPrimaryBtn.style.borderColor = primaryHex;
            }
            if (previewBadgePill) {
                previewBadgePill.style.background = primaryHex.startsWith('#') ? primaryHex + '22' : 'var(--mf-primary-light)';
                previewBadgePill.style.color = primaryHex;
                previewBadgePill.textContent = 'Accent: ' + (theme === 'custom' ? primaryHex : theme.toUpperCase());
            }
        }

        [themeModeSelect, themePresetSelect].forEach(sel => {
            if (sel) sel.addEventListener('change', updateLivePreview);
        });

        if (folderColorPicker && folderColorText) {
            folderColorPicker.addEventListener('input', () => {
                folderColorText.value = folderColorPicker.value;
                updateLivePreview();
            });
            folderColorText.addEventListener('input', () => {
                if (folderColorText.value.startsWith('#')) {
                    folderColorPicker.value = folderColorText.value;
                }
                updateLivePreview();
            });
        }

        if (folderPresetSelect) {
            folderPresetSelect.addEventListener('change', (e) => {
                if (e.target.value) {
                    if (e.target.value === 'primary') {
                        folderColorText.value = 'primary';
                    } else {
                        folderColorPicker.value = e.target.value;
                        folderColorText.value = e.target.value;
                    }
                    updateLivePreview();
                }
            });
        }

        // Initialize preview on load
        updateLivePreview();

        const form = document.getElementById('mediaflow-settings-form');
        const saveMsg = document.getElementById('save-msg');
        const saveBtn = document.getElementById('save-settings-btn');
        const resetBtn = document.getElementById('reset-defaults-btn');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';
            saveMsg.style.color = 'var(--mf-text-muted)';
            saveMsg.textContent = '';

            const formData = new FormData(form);
            const payload = {};
            formData.forEach((value, key) => {
                if (key !== '_token') {
                    payload[key] = value;
                }
            });

            // Convert checkboxes safely
            payload['images.auto_optimize'] = form.querySelector('[name="images.auto_optimize"]')?.checked ? 1 : 0;
            payload['images.strip_exif'] = form.querySelector('[name="images.strip_exif"]')?.checked ? 1 : 0;
            payload['ui.inherit_system'] = form.querySelector('[name="ui.inherit_system"]')?.checked ? 1 : 0;
            payload['ui.animations'] = form.querySelector('[name="ui.animations"]')?.checked ? 1 : 0;
            payload['auth.enabled'] = form.querySelector('[name="auth.enabled"]')?.checked ? 1 : 0;

            fetch("{{ route('mediaflow.settings.update') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Configuration';
                if (data.success) {
                    saveMsg.style.color = 'var(--mf-success)';
                    saveMsg.textContent = '✓ Settings saved successfully!';
                    setTimeout(() => saveMsg.textContent = '', 3000);
                } else {
                    saveMsg.style.color = 'var(--mf-danger)';
                    saveMsg.textContent = 'Error saving settings.';
                }
            })
            .catch(err => {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Configuration';
                saveMsg.style.color = 'var(--mf-danger)';
                saveMsg.textContent = 'Error: ' + err.message;
            });
        });

        resetBtn.addEventListener('click', () => {
            if (confirm('Reset all media settings back to system defaults?')) {
                fetch("{{ route('mediaflow.settings.reset') }}", {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (window.MediaFlow && window.MediaFlow.toast) {
                            window.MediaFlow.toast('Settings have been reset to system defaults.', 'success', 'Reset Complete');
                        }
                        setTimeout(() => {
                            window.location.reload();
                        }, 700);
                    }
                });
            }
        });

        // -------------------------------------------------------------
        // Cloud Provider Presets Interaction Logic
        // -------------------------------------------------------------
        const providerSelect = document.getElementById('provider-name-select');
        const providerPills = document.querySelectorAll('.mf-provider-pill');
        const providerBadge = document.getElementById('provider-badge-pill');
        const providerKeyLabel = document.getElementById('provider-key-label');
        const providerSecretLabel = document.getElementById('provider-secret-label');
        const providerAccountIdRow = document.getElementById('provider-account-id-row');
        const providerAccountIdLabel = document.getElementById('provider-account-id-label');
        const providerAccountIdInput = document.getElementById('provider-account-id-input');
        const providerBucketLabel = document.getElementById('provider-bucket-label');
        const providerRegionLabel = document.getElementById('provider-region-label');
        const providerPublicHint = document.getElementById('provider-public-hint');
        const providerEndpointInput = document.getElementById('provider-endpoint-input');
        const providerRegionInput = document.getElementById('provider-region-input');
        const providerKeyInput = document.getElementById('provider-key-input');
        const providerSecretInput = document.getElementById('provider-secret-input');
        const providerBucketInput = document.getElementById('provider-bucket-input');
        const providerPublicUrlInput = document.getElementById('provider-public-url-input');
        const providerDescText = document.getElementById('provider-description-text');
        const btnTestProvider = document.getElementById('btn-test-provider');
        const testStatus = document.getElementById('provider-test-status');
        const btnCopyEnv = document.getElementById('btn-copy-env-snippet');
        const envPreviewBox = document.getElementById('env-preview-box');
        const envPreviewCode = document.getElementById('env-preview-code');
        const btnToggleSecret = document.getElementById('btn-toggle-secret-visibility');

        function selectProvider(providerId) {
            providerSelect.value = providerId;
            providerPills.forEach(pill => {
                if (pill.dataset.provider === providerId) {
                    pill.classList.add('active');
                    providerBadge.textContent = pill.dataset.badge || 'Cloud';
                    providerKeyLabel.textContent = pill.dataset.keyLabel || 'Access Key ID';
                    providerSecretLabel.textContent = pill.dataset.secretLabel || 'Secret Access Key';
                    providerBucketLabel.textContent = pill.dataset.bucketLabel || 'Bucket Name';

                    if (providerId === 'cloudflare_r2') {
                        if (providerAccountIdRow) providerAccountIdRow.style.display = 'grid';
                        if (providerAccountIdLabel) providerAccountIdLabel.textContent = 'Cloudflare Account ID (32-character hex from Overview)';
                        if (providerRegionLabel) providerRegionLabel.textContent = 'Region (default: auto)';
                        if (!providerRegionInput.value) providerRegionInput.placeholder = 'auto';
                        if (providerEndpointInput) providerEndpointInput.placeholder = 'https://<account_id>.r2.cloudflarestorage.com';
                    } else if (pill.dataset.accountIdLabel) {
                        if (providerAccountIdRow) providerAccountIdRow.style.display = 'grid';
                        if (providerAccountIdLabel) providerAccountIdLabel.textContent = pill.dataset.accountIdLabel;
                        if (providerRegionLabel) providerRegionLabel.textContent = (pill.dataset.regionLabel || 'Region') + ' (Optional)';
                    } else {
                        if (providerAccountIdRow) providerAccountIdRow.style.display = 'none';
                        if (providerRegionLabel) providerRegionLabel.textContent = (pill.dataset.regionLabel || 'Region') + ' (Optional)';
                        if (!providerRegionInput.value) providerRegionInput.placeholder = pill.dataset.defaultRegion || 'auto';
                        if (pill.dataset.endpoint && providerEndpointInput) providerEndpointInput.placeholder = pill.dataset.endpoint;
                    }

                    if (providerPublicHint) providerPublicHint.textContent = 'Hint: ' + (pill.dataset.publicHint || '');
                } else {
                    pill.classList.remove('active');
                }
            });
            updateEnvPreview();
        }

        providerPills.forEach(pill => {
            pill.addEventListener('click', () => {
                selectProvider(pill.dataset.provider);
            });
        });

        providerSelect.addEventListener('change', (e) => {
            selectProvider(e.target.value);
        });

        // Secret visibility toggle
        if (btnToggleSecret && providerSecretInput) {
            btnToggleSecret.addEventListener('click', () => {
                if (providerSecretInput.type === 'password') {
                    providerSecretInput.type = 'text';
                    btnToggleSecret.textContent = '🔒';
                } else {
                    providerSecretInput.type = 'password';
                    btnToggleSecret.textContent = '👁️';
                }
            });
        }

        function updateEnvPreview() {
            const provider = providerSelect.value;
            const key = providerKeyInput.value.trim() || 'your_key';
            const secret = providerSecretInput.value.trim() ? '••••••••••••••••' : 'your_secret';
            const accountId = providerAccountIdInput ? providerAccountIdInput.value.trim() : '';
            const bucket = providerBucketInput.value.trim() || 'my-bucket';
            const publicUrl = providerPublicUrlInput.value.trim() || 'https://cdn.example.com';
            const region = providerRegionInput.value.trim() || 'auto';

            const snippetLines = [
                `MEDIAFLOW_PROVIDER=${provider}`,
                `MEDIAFLOW_PROVIDER_KEY=${key}`,
                `MEDIAFLOW_PROVIDER_SECRET=${secret}`,
            ];

            if (provider === 'cloudflare_r2' || accountId) {
                const actId = accountId || 'your_32_char_account_id';
                snippetLines.push(`MEDIAFLOW_PROVIDER_ACCOUNT_ID=${actId}`);
                snippetLines.push(`CLOUDFLARE_ACCOUNT_ID=${actId}`);
            }

            snippetLines.push(`MEDIAFLOW_PROVIDER_BUCKET=${bucket}`);
            snippetLines.push(`MEDIAFLOW_PROVIDER_PUBLIC_URL=${publicUrl}`);
            snippetLines.push(`MEDIAFLOW_PROVIDER_REGION=${region}`);
            if (provider === 'cloudflare_r2' && accountId) {
                snippetLines.push(`MEDIAFLOW_PROVIDER_ENDPOINT=https://${accountId}.r2.cloudflarestorage.com`);
            } else if (providerEndpointInput.value.trim()) {
                snippetLines.push(`MEDIAFLOW_PROVIDER_ENDPOINT=${providerEndpointInput.value.trim()}`);
            }
            snippetLines.push(`MEDIAFLOW_DISK=mediaflow_cloud`);
            snippetLines.push(`MEDIAFLOW_DELIVERY_DRIVER=cdn`);
            snippetLines.push(`MEDIAFLOW_CDN_URL=${publicUrl}`);

            envPreviewCode.textContent = snippetLines.join('\n');
            envPreviewBox.style.display = 'block';
        }

        [providerKeyInput, providerSecretInput, providerAccountIdInput, providerBucketInput, providerPublicUrlInput, providerRegionInput, providerEndpointInput].forEach(inp => {
            if (inp) inp.addEventListener('input', updateEnvPreview);
        });

        // Test Provider Connection
        if (btnTestProvider) {
            btnTestProvider.addEventListener('click', () => {
                btnTestProvider.disabled = true;
                btnTestProvider.innerHTML = '<span>⏳</span> Testing Connection...';
                testStatus.style.color = 'var(--mf-text-muted)';
                testStatus.textContent = 'Sending storage probe...';

                const params = {
                    name: providerSelect.value,
                    key: providerKeyInput.value.trim(),
                    secret: providerSecretInput.value.trim(),
                    account_id: providerAccountIdInput ? providerAccountIdInput.value.trim() : '',
                    bucket: providerBucketInput.value.trim(),
                    public_url: providerPublicUrlInput.value.trim(),
                    region: providerRegionInput.value.trim(),
                    endpoint: providerEndpointInput.value.trim(),
                };

                fetch("{{ route('mediaflow.settings.test-provider') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(params)
                })
                .then(res => res.json())
                .then(data => {
                    btnTestProvider.disabled = false;
                    btnTestProvider.innerHTML = '<span>⚡</span> Test Provider Connection';
                    if (data.success) {
                        testStatus.style.color = 'var(--mf-success)';
                        testStatus.textContent = data.message + ` (${data.latency_ms} ms)`;
                    } else {
                        testStatus.style.color = 'var(--mf-danger)';
                        testStatus.textContent = '✕ ' + data.message;
                    }
                    if (data.env_snippet) {
                        envPreviewCode.textContent = data.env_snippet;
                        envPreviewBox.style.display = 'block';
                    }
                })
                .catch(err => {
                    btnTestProvider.disabled = false;
                    btnTestProvider.innerHTML = '<span>⚡</span> Test Provider Connection';
                    testStatus.style.color = 'var(--mf-danger)';
                    testStatus.textContent = '✕ Connection error: ' + err.message;
                });
            });
        }

        // Copy .env snippet
        if (btnCopyEnv) {
            btnCopyEnv.addEventListener('click', () => {
                const text = envPreviewCode.textContent || '';
                navigator.clipboard.writeText(text).then(() => {
                    const originalText = btnCopyEnv.innerHTML;
                    btnCopyEnv.innerHTML = '<span>✓ Copied!</span>';
                    setTimeout(() => btnCopyEnv.innerHTML = originalText, 2000);
                });
            });
        }
    </script>
</body>
</html>
