<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediaFlow for Laravel — Documentation & Developer Guide</title>
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
            position: sticky;
            top: 0;
            z-index: 100;
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
            gap: 0.75rem;
            font-size: 0.875rem;
            align-items: center;
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
            font-weight: 600;
        }
    </style>
</head>
<body>
    <header class="mf-app-header">
        <a href="{{ route('mediaflow.docs') }}" class="mf-app-title">
            <span style="font-size: 1.4rem;">🌊</span>
            <span>MediaFlow</span>
            <span style="font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.5rem; border-radius: 999px; background: var(--mf-primary-light); color: var(--mf-primary); border: 1px solid rgba(79, 70, 229, 0.2);">v1.0 Docs</span>
        </a>
        <nav class="mf-nav-links">
            <a href="{{ route('mediaflow.docs') }}" class="mf-nav-link active">📖 Documentation</a>
            <a href="{{ route('mediaflow.manager') }}" class="mf-nav-link">Media Library</a>
            <a href="{{ route('mediaflow.settings.index') }}" class="mf-nav-link">Settings & Compression</a>
            <a href="{{ route('mediaflow.diagnostics.view') }}" class="mf-nav-link">Diagnostics</a>
            <a href="/" class="mf-nav-link" style="opacity: 0.75;">← Live Demo</a>
            @if(auth()->check())
                <span style="font-size: 0.8rem; padding: 0.25rem 0.6rem; border-radius: 6px; background: var(--mf-bg-hover); border: 1px solid var(--mf-border);">
                    👤 {{ auth()->user()->name }} (<code>{{ auth()->user()->role ?? 'user' }}</code>)
                </span>
            @endif
        </nav>
    </header>

    <div class="mf-docs-mobile-bar">
        <div style="font-weight: 600; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>📚</span> Table of Contents
        </div>
        <button type="button" id="mf-docs-toggle-sidebar" style="background: var(--mf-bg-hover); border: 1px solid var(--mf-border); border-radius: 6px; padding: 0.35rem 0.75rem; font-size: 0.8125rem; font-weight: 600; cursor: pointer;">
            Toggle Menu
        </button>
    </div>

    <div class="mf-docs-container">
        <!-- Sidebar Navigation -->
        <aside class="mf-docs-sidebar">
            <div class="mf-docs-search-box">
                <span class="mf-docs-search-icon">🔍</span>
                <input type="text" id="mf-docs-search-input" class="mf-docs-search-input" placeholder="Search guides, tags & APIs...">
            </div>

            <!-- Getting Started -->
            <div class="mf-docs-nav-group">
                <div class="mf-docs-nav-title">🚀 Getting Started</div>
                <a href="#quickstart" class="mf-docs-nav-link" style="color: var(--mf-primary); font-weight: 700;">⚡ Fresh Developer Quickstart</a>
                <a href="#overview" class="mf-docs-nav-link active">Overview & Architecture</a>
                <a href="#whats-included" class="mf-docs-nav-link">📦 What's Included Out-of-the-Box</a>
                <a href="#first-steps-settings" class="mf-docs-nav-link">⚙️ First Steps: Settings & .env Guide</a>
                <a href="#asset-setup" class="mf-docs-nav-link">🎨 CSS, JS & CSRF Asset Setup</a>
                <a href="#form-lifecycle" class="mf-docs-nav-link">📥 Form to Controller Lifecycle</a>
                <a href="#frontend-isolation" class="mf-docs-nav-link">🛡️ Frontend vs Admin Isolation</a>
                <a href="#fullscreen-copy" class="mf-docs-nav-link">👁️ Open in Tab & Copy URL</a>
                <a href="#table-prefix-collision" class="mf-docs-nav-link">🔒 Existing Media Table / Prefix Guide</a>
                <a href="#security-hardening" class="mf-docs-nav-link">🔐 Security: Route Names & Auth</a>
                <a href="#requirements" class="mf-docs-nav-link">System Requirements</a>
                <a href="#installation" class="mf-docs-nav-link">Installation Guide</a>
                <a href="#updating" class="mf-docs-nav-link">🔄 Updating Existing Projects</a>
                <a href="#configuration" class="mf-docs-nav-link">Configuration Reference</a>
            </div>

            <!-- Form Components -->
            <div class="mf-docs-nav-group">
                <div class="mf-docs-nav-title">🧩 Blade Components</div>
                <a href="#input-shortcut" class="mf-docs-nav-link">&lt;x-mediaflow-input&gt;</a>
                <a href="#side-admin-panel" class="mf-docs-nav-link">Side Admin Panels (:compact)</a>
                <a href="#model-binding" class="mf-docs-nav-link">Model Binding & Values</a>
                <a href="#component-uploader" class="mf-docs-nav-link">&lt;x-mediaflow::uploader&gt;</a>
                <a href="#component-manager" class="mf-docs-nav-link">&lt;x-mediaflow::manager&gt;</a>
                <a href="#theming-colors" class="mf-docs-nav-link">🎨 Theming & Light/Dark</a>
            </div>

            <!-- Theming & Colors -->
            <div class="mf-docs-nav-group">
                <div class="mf-docs-nav-title">🎨 Themes & Colors</div>
                <a href="#theming-colors" class="mf-docs-nav-link">Picker Colors & Accents</a>
                <a href="#folder-colors" class="mf-docs-nav-link">Media Folder Colors</a>
                <a href="#light-dark-mode" class="mf-docs-nav-link">Light / Dark Modes</a>
                <a href="#css-variables" class="mf-docs-nav-link">CSS Custom Tokens</a>
            </div>

            <!-- Developer API -->
            <div class="mf-docs-nav-group">
                <div class="mf-docs-nav-title">⚡ Developer API</div>
                <a href="#facade-api" class="mf-docs-nav-link">MediaFlow Facade</a>
                <a href="#model-methods" class="mf-docs-nav-link">Media Model Helpers</a>
                <a href="#eloquent-relation" class="mf-docs-nav-link">Eloquent Relationships</a>
                <a href="#optimization-api" class="mf-docs-nav-link">On-Demand Compression</a>
            </div>

            <!-- Settings & Compression -->
            <div class="mf-docs-nav-group">
                <div class="mf-docs-nav-title">⚙️ Settings & Compression</div>
                <a href="#settings-compression" class="mf-docs-nav-link">Settings Engine & Config</a>
                <a href="#compression-presets" class="mf-docs-nav-link">Quality & Format Presets</a>
                <a href="#compression-simulator" class="mf-docs-nav-link">Interactive Playground</a>
            </div>

            <!-- Access Control & Security -->
            <div class="mf-docs-nav-group">
                <div class="mf-docs-nav-title">🛡️ Access & Security</div>
                <a href="#guards-roles" class="mf-docs-nav-link">Guards & Roles</a>
                <a href="#spatie-gates" class="mf-docs-nav-link">Spatie Permissions & Gates</a>
                <a href="#closure-auth" class="mf-docs-nav-link">Closure Authorization</a>
                <a href="#user-scoping" class="mf-docs-nav-link">Per-User Scoping</a>
            </div>

            <!-- Storage & CDN -->
            <div class="mf-docs-nav-group">
                <div class="mf-docs-nav-title">☁️ Storage & Delivery</div>
                <a href="#cloud-providers" class="mf-docs-nav-link">10 Cloud & CDN Providers</a>
                <a href="#quick-3field-setup" class="mf-docs-nav-link">3-Field Quick Setup</a>
                <a href="#live-provider-testing" class="mf-docs-nav-link">Live Connection Probing</a>
                <a href="#cdn-delivery" class="mf-docs-nav-link">Custom CDN Domains</a>
                <a href="#signed-urls" class="mf-docs-nav-link">Signed Streaming URLs</a>
            </div>

            <!-- 3D Lifecycle Visualizer -->
            <div class="mf-docs-nav-group">
                <div class="mf-docs-nav-title">✨ 3D Engine & Lifecycle</div>
                <a href="#visualizer-lifecycle" class="mf-docs-nav-link">5-Stage Pipeline Engine</a>
                <a href="#upload-modal" class="mf-docs-nav-link">Live Modal Visualizer</a>
            </div>

            <!-- REST API & Health -->
            <div class="mf-docs-nav-group">
                <div class="mf-docs-nav-title">🔌 REST API & Diagnostics</div>
                <a href="#api-endpoints" class="mf-docs-nav-link">Endpoints Reference</a>
                <a href="#storage-probes" class="mf-docs-nav-link">Health Diagnostics Probes</a>
            </div>

            <!-- Operations -->
            <div class="mf-docs-nav-group">
                <div class="mf-docs-nav-title">🧰 Operations</div>
                <a href="#events-hooks" class="mf-docs-nav-link">Events &amp; Hooks</a>
                <a href="#artisan-commands" class="mf-docs-nav-link">Artisan Commands</a>
                <a href="#extending" class="mf-docs-nav-link">Custom Adapters</a>
                <a href="#security-checklist" class="mf-docs-nav-link">Security Checklist</a>
                <a href="#testing-troubleshooting" class="mf-docs-nav-link">Testing &amp; Troubleshooting</a>
            </div>

            <!-- Author & Credits -->
            <div class="mf-docs-nav-group">
                <div class="mf-docs-nav-title">👤 Author & Credits</div>
                <a href="#author-credits" class="mf-docs-nav-link">Anuj Dwivedi (Lead Architect)</a>
            </div>
        </aside>

        <!-- Documentation Content Body -->
        <main class="mf-docs-content">
            <!-- Hero Header -->
            <div class="mf-docs-hero">
                <div class="mf-docs-badge-row">
                    <span class="mf-docs-badge">✓ PHP 8.2 & 8.3 Ready</span>
                    <span class="mf-docs-badge">✓ Laravel 11.x, 12.x & 13.x</span>
                    <span class="mf-docs-badge">✓ Zero External JS/CSS CDNs</span>
                    <span class="mf-docs-badge">✓ 80 Automated Tests Passing</span>
                </div>
                <h1 class="mf-docs-title">MediaFlow for Laravel Documentation</h1>
                <p class="mf-docs-lead">
                    Universal media management infrastructure, provider-agnostic storage adapters, modern Blade shortcut input components, on-demand format conversion & compression, and hardware-accelerated 3D upload lifecycle visualization.
                </p>
            </div>

            <!-- SECTION 1: OVERVIEW -->
            <section id="overview" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>🌊</span> 1. Overview & Architecture</h2>
                <p class="mf-docs-p">
                    MediaFlow solves media handling in modern Laravel applications without coupling business logic to underlying storage providers. It provides a clean boundary between <strong>Physical Storage Persistence</strong> (Local disks, S3, Cloudflare R2, MinIO) and <strong>Delivery URL Resolution</strong> (Public disks, custom CDN edge endpoints, or time-limited signed streaming).
                </p>
                <div class="mf-docs-callout tip">
                    <div>💡</div>
                    <div>
                        <strong>Zero External Dependencies:</strong> All UI components, modal dialogs, and 3D upload animations are built using 100% self-contained vanilla CSS and JavaScript. No npm build steps, Tailwind, Alpine, or external CDNs required.
                    </div>
                </div>

                <!-- Reference Architecture Diagram -->
                <div class="mf-docs-diagram-card" style="margin: 1.5rem 0; background: var(--mf-bg-card); border: 1px solid var(--mf-border); border-radius: var(--mf-radius-lg); padding: 1.25rem; box-shadow: var(--mf-shadow-sm); overflow: hidden;">
                    <div style="font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--mf-primary); margin-bottom: 0.85rem; display: flex; align-items: center; justify-content: space-between;">
                        <span style="display: flex; align-items: center; gap: 0.5rem;"><span>📐</span> Reference System Architecture</span>
                        <a href="{{ asset('vendor/mediaflow/img/mediaflow_architecture.svg') }}" target="_blank" style="font-size: 0.75rem; color: var(--mf-text-muted); text-decoration: none; font-weight: 500;">Open full size ↗</a>
                    </div>
                    <div style="border-radius: var(--mf-radius-md); overflow: hidden; background: #ffffff; padding: 1rem; border: 1px solid rgba(0,0,0,0.06);">
                        <img src="{{ asset('vendor/mediaflow/img/mediaflow_architecture.svg') }}" alt="MediaFlow Architecture Diagram" style="width: 100%; height: auto; max-width: 100%; display: block; border-radius: 6px;" onerror="this.onerror=null; this.src='https://dwivedianuj9118.github.io/mediaflow-laravel/mediaflow_architecture.svg';">
                    </div>
                    <div style="font-size: 0.75rem; color: var(--mf-text-muted); margin-top: 0.65rem; text-align: right;">
                        Clean boundary: Physical Storage Adapters ⇄ Catalog State ⇄ Edge Delivery & Signed Streaming
                    </div>
                </div>

                <div class="mf-feature-card">
                    <div>
                        <div style="font-weight: 600; font-size: 0.95rem;">Experience MediaFlow in action</div>
                        <div style="color: var(--mf-text-muted); font-size: 0.8125rem; margin-top: 0.2rem;">Try upload dropzones, side admin drawers, and the media library.</div>
                    </div>
                    <a href="/" style="background: var(--mf-primary); color: #fff; padding: 0.45rem 1rem; border-radius: 6px; font-size: 0.8125rem; font-weight: 600; text-decoration: none;">Launch Live Demo →</a>
                </div>
            </section>

            <!-- SECTION: FRESH DEVELOPER QUICKSTART -->
            <section id="quickstart" class="mf-docs-section" style="border: 2px solid var(--mf-primary); border-radius: var(--mf-radius-lg); padding: 1.75rem; background: var(--mf-bg-card); box-shadow: var(--mf-shadow-md); margin-bottom: 2.5rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <span style="font-size: 1.75rem;">⚡</span>
                        <h2 style="font-size: 1.5rem; font-weight: 800; margin: 0; color: var(--mf-primary);">
                            Fresh Developer Step-by-Step Quickstart
                        </h2>
                    </div>
                    <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background: var(--mf-primary-light); color: var(--mf-primary); padding: 0.35rem 0.75rem; border-radius: 999px;">
                        Zero to Production in 5 Minutes
                    </span>
                </div>
                <p class="mf-docs-p" style="font-size: 0.95rem; line-height: 1.6;">
                    Welcome to MediaFlow! Whether you are a junior engineer or a senior architect, this comprehensive guide takes you step-by-step from clean installation to receiving files in your controller request, securing routes, avoiding table name collisions, and rendering responsive WebP images.
                </p>

                <!-- 1. WHAT'S INCLUDED OUT OF THE BOX -->
                <div id="whats-included" class="mf-docs-step-card">
                    <div class="mf-docs-step-header">
                        <h3 class="mf-docs-step-title">
                            <span>📦</span> Step 1: What's Included Out-of-the-Box
                        </h3>
                        <span class="mf-docs-step-badge">Batteries Included</span>
                    </div>
                    <p class="mf-docs-p">
                        Unlike traditional packages that require installing multiple third-party libraries, MediaFlow is 100% self-contained and pre-bundled:
                    </p>
                    <div class="mf-feature-grid" style="margin: 1.5rem 0; gap: 1.25rem;">
                        <div class="mf-feature-card" style="padding: 1.25rem;">
                            <div style="font-weight: 700; color: var(--mf-primary); margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.4rem;">
                                <span>☁️</span> Bundled S3 &amp; Cloudflare R2 Drivers
                            </div>
                            <div style="font-size: 0.85rem; color: var(--mf-text-muted); line-height: 1.55;">
                                <code>league/flysystem-aws-s3-v3</code> is bundled directly in <code>require</code>. Connect to AWS S3, Cloudflare R2, MinIO, or Wasabi with zero missing class errors.
                            </div>
                        </div>
                        <div class="mf-feature-card" style="padding: 1.25rem;">
                            <div style="font-weight: 700; color: #10b981; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.4rem;">
                                <span>⚡</span> Automated WebP Optimization Engine
                            </div>
                            <div style="font-size: 0.85rem; color: var(--mf-text-muted); line-height: 1.55;">
                                Uploaded JPEG, PNG, and GIF images are automatically converted to optimized WebP format with automated thumbnail variants and EXIF metadata stripping.
                            </div>
                        </div>
                        <div class="mf-feature-card" style="padding: 1.25rem;">
                            <div style="font-weight: 700; color: #a855f7; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.4rem;">
                                <span>✨</span> 3D Pipeline Lifecycle Visualizer
                            </div>
                            <div style="font-size: 0.85rem; color: var(--mf-text-muted); line-height: 1.55;">
                                Hardware-accelerated CSS 3D upload animation modal visualizing the 5 stages of file intake (Auth &rarr; Temp Buffer &rarr; Optimization &rarr; Storage &rarr; Edge CDN).
                            </div>
                        </div>
                        <div class="mf-feature-card" style="padding: 1.25rem;">
                            <div style="font-weight: 700; color: #06b6d4; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.4rem;">
                                <span>🍞</span> Non-Blocking Toast Notification System
                            </div>
                            <div style="font-size: 0.85rem; color: var(--mf-text-muted); line-height: 1.55;">
                                No annoying browser <code>alert()</code> popups! Modern, smooth, dismissible toasts report upload progress, relation warnings, and clipboard confirmations.
                            </div>
                        </div>
                        <div class="mf-feature-card" style="padding: 1.25rem;">
                            <div style="font-weight: 700; color: #f59e0b; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.4rem;">
                                <span>🛡️</span> Relation Integrity Delete Guards
                            </div>
                            <div style="font-size: 0.85rem; color: var(--mf-text-muted); line-height: 1.55;">
                                Prevents deleting media files that are still referenced by foreign keys in your host application tables with an informative toast warning.
                            </div>
                        </div>
                        <div class="mf-feature-card" style="padding: 1.25rem;">
                            <div style="font-weight: 700; color: #ec4899; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.4rem;">
                                <span>🧩</span> Drop-in Shortcut Blade Components
                            </div>
                            <div style="font-size: 0.85rem; color: var(--mf-text-muted); line-height: 1.55;">
                                One-line <code>&lt;x-mediaflow-input&gt;</code> provides drag-and-drop, instant upload, preview card, fullscreen view, URL copy, and library picker.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. INSTALLATION COMMANDS -->
                <div id="terminal-commands" class="mf-docs-step-card">
                    <div class="mf-docs-step-header">
                        <h3 class="mf-docs-step-title">
                            <span>📥</span> Step 2: Run the 4-Step Terminal Commands
                        </h3>
                        <span class="mf-docs-step-badge">Quick Install</span>
                    </div>
                    <p class="mf-docs-p">Open your terminal in your Laravel project root and execute:</p>
                    <div class="mf-code-container" style="margin-top: 1rem;">
                        <div class="mf-code-header">
                            <span class="mf-code-lang">Terminal (Bash / Zsh)</span>
                            <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                        </div>
                        <pre class="mf-code-pre"><code><span class="tok-com"># 1. Require the package via Composer</span>
composer require dwivedianuj9118/mediaflow-laravel

<span class="tok-com"># 2. Publish config and assets (creates config/mediaflow.php &amp; public/vendor/mediaflow)</span>
php artisan vendor:publish --tag=mediaflow-config
php artisan vendor:publish --tag=mediaflow-assets

<span class="tok-com"># 3. Run database migrations to create mediaflow_media &amp; mediaflow_folders tables</span>
php artisan migrate

<span class="tok-com"># 4. Create the public storage symlink for local disk file delivery</span>
php artisan storage:link</code></pre>
                    </div>
                </div>

                <!-- 3. FIRST STEPS: SETTINGS & .ENV -->
                <div id="first-steps-settings" class="mf-docs-step-card">
                    <div class="mf-docs-step-header">
                        <h3 class="mf-docs-step-title">
                            <span>⚙️</span> Step 3: Configure Settings &amp; .env Variables
                        </h3>
                        <span class="mf-docs-step-badge">Visual Config Dashboard</span>
                    </div>

                    <p class="mf-docs-p">
                        Immediately after running migrations, open your browser and navigate to the MediaFlow Settings dashboard:
                    </p>

                    <div class="mf-docs-callout tip" style="margin: 1.5rem 0;">
                        <div style="font-size: 1.3rem;">🧭</div>
                        <div>
                            <strong>Web Settings Dashboard:</strong> Visit <code>http://your-app.test/mediaflow/settings</code> (or your custom prefix) in your browser.
                        </div>
                    </div>

                    <!-- 3 Feature Highlight Cards with generous spacing & clean icons -->
                    <div class="mf-feature-grid" style="margin: 1.75rem 0 2rem; gap: 1.25rem;">
                        <div class="mf-feature-card" style="padding: 1.25rem;">
                            <div style="font-weight: 700; color: var(--mf-primary); margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.95rem;">
                                <span>☁️</span> Live Cloud Probing
                            </div>
                            <div style="font-size: 0.85rem; color: var(--mf-text-muted); line-height: 1.55;">
                                Click <em>"Test Connection"</em> next to Local, AWS S3, Cloudflare R2, or MinIO to confirm write and read bucket access before attempting uploads.
                            </div>
                        </div>

                        <div class="mf-feature-card" style="padding: 1.25rem;">
                            <div style="font-weight: 700; color: #10b981; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.95rem;">
                                <span>⚡</span> WebP Compression Quality
                            </div>
                            <div style="font-size: 0.85rem; color: var(--mf-text-muted); line-height: 1.55;">
                                Choose default conversion rate (Recommended: <strong>80% - 85%</strong> for optimal balance of 5x bandwidth savings and high visual fidelity).
                            </div>
                        </div>

                        <div class="mf-feature-card" style="padding: 1.25rem;">
                            <div style="font-weight: 700; color: #a855f7; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.95rem;">
                                <span>🎨</span> Theme &amp; Appearance
                            </div>
                            <div style="font-size: 0.85rem; color: var(--mf-text-muted); line-height: 1.55;">
                                Select brand accent colors (Indigo, Emerald, Violet, Amber, Rose, Cyan) or enter a custom hex code with automatic Light/Dark mode sync.
                            </div>
                        </div>
                    </div>

                    <!-- Distinct Sub-Section for .env Settings Reference -->
                    <div style="margin-top: 2.75rem; padding-top: 1.75rem; border-top: 1px dashed var(--mf-border);">
                        <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.5rem;">
                            <span style="font-size: 1.3rem;">📝</span>
                            <h4 class="mf-docs-h4" style="margin: 0; font-size: 1.15rem;">
                                Essential .env Settings Reference
                            </h4>
                        </div>
                        <p class="mf-docs-p" style="color: var(--mf-text-muted); font-size: 0.875rem; margin-bottom: 1.25rem;">
                            Copy and paste these essential variables into your root Laravel <code>.env</code> file:
                        </p>

                        <div class="mf-code-container" style="margin-top: 0.5rem;">
                            <div class="mf-code-header">
                                <span class="mf-code-lang">.env</span>
                                <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                            </div>
                            <pre class="mf-code-pre"><code><span class="tok-com"># ==============================================================================</span>
<span class="tok-com"># MediaFlow Core Configuration &amp; Security</span>
<span class="tok-com"># ==============================================================================</span>

<span class="tok-com"># 1. SECURITY: Custom Route Prefix (Obscure default /mediaflow from attackers)</span>
MEDIAFLOW_ROUTE_PREFIX=admin/media-vault

<span class="tok-com"># 2. SECURITY: Route Middleware Stack &amp; Auth Guard (Protect with authentication)</span>
MEDIAFLOW_MIDDLEWARE=web,auth
MEDIAFLOW_GUARD=web

<span class="tok-com"># 3. COLLISION PREVENTION: Database Table Prefix (Change to mf_ if media table exists)</span>
MEDIAFLOW_TABLE_PREFIX=mediaflow_

<span class="tok-com"># 4. STORAGE: Active default disk ('public', 's3', 'mediaflow_cloud', 'minio')</span>
MEDIAFLOW_DISK=public
MEDIAFLOW_MAX_FILE_SIZE_KB=20480

<span class="tok-com"># 5. CLOUDFLARE R2 / S3 CREDENTIALS (When MEDIAFLOW_DISK=mediaflow_cloud or s3)</span>
MEDIAFLOW_PROVIDER=cloudflare_r2
MEDIAFLOW_PROVIDER_ACCOUNT_ID=your-32-character-account-id
# (or CLOUDFLARE_ACCOUNT_ID=your-32-character-account-id)
MEDIAFLOW_PROVIDER_KEY=your-r2-access-key-id
MEDIAFLOW_PROVIDER_SECRET=your-r2-secret-access-key
MEDIAFLOW_PROVIDER_BUCKET=your-bucket-name
MEDIAFLOW_PROVIDER_PUBLIC_URL=https://pub-xxxx.r2.dev</code></pre>
                        </div>
                    </div>
                </div>

                <!-- 4. ASSET SETUP (CSS, JS & CSRF) -->
                <div id="asset-setup" class="mf-docs-step-card">
                    <div class="mf-docs-step-header">
                        <div class="mf-docs-step-badge">Step 4</div>
                        <h3 class="mf-docs-step-title"><span>🎨</span> Asset Directory &amp; Master Layout Inclusions</h3>
                    </div>
                    <p class="mf-docs-p">
                        When you ran <code>php artisan vendor:publish --tag=mediaflow-assets</code>, the production CSS and JavaScript files were copied into your application's <code>public/vendor/mediaflow/</code> directory:
                    </p>
                    <ul class="mf-docs-list" style="margin-bottom: 1.25rem;">
                        <li><code>public/vendor/mediaflow/css/mediaflow.css</code> &mdash; Full styling, responsive dropzones, modals, themes, and 3D visualizer styles.</li>
                        <li><code>public/vendor/mediaflow/js/mediaflow.js</code> &mdash; Self-contained vanilla JS engine, drag &amp; drop, toasts, clipboard copy, and explorer.</li>
                    </ul>
                    <p class="mf-docs-p">
                        Include them in your main Blade layout (e.g. <code>resources/views/layouts/app.blade.php</code>):
                    </p>
                    <div class="mf-code-container">
                        <div class="mf-code-header">
                            <span class="mf-code-lang">resources/views/layouts/app.blade.php</span>
                            <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                        </div>
                        <pre class="mf-code-pre"><code><span class="tok-tag">&lt;!DOCTYPE html&gt;</span>
<span class="tok-tag">&lt;html</span> <span class="tok-attr">lang</span>=<span class="tok-str">"en"</span><span class="tok-tag">&gt;</span>
<span class="tok-head">&lt;head&gt;</span>
    <span class="tok-tag">&lt;meta</span> <span class="tok-attr">charset</span>=<span class="tok-str">"UTF-8"</span><span class="tok-tag">&gt;</span>
    <span class="tok-tag">&lt;title&gt;</span>My Application<span class="tok-tag">&lt;/title&gt;</span>

    <span class="tok-com">&lt;!-- 1. MANDATORY: CSRF Token Meta Tag (Required for AJAX uploads &amp; picker) --&gt;</span>
    <span class="tok-tag">&lt;meta</span> <span class="tok-attr">name</span>=<span class="tok-str">"csrf-token"</span> <span class="tok-attr">content</span>=<span class="tok-str">"@{{ csrf_token() }}"</span><span class="tok-tag">&gt;</span>

    <span class="tok-com">&lt;!-- 2. MediaFlow Stylesheet (Choose Option A or Option B) --&gt;</span>
    <span class="tok-tag">&lt;link</span> <span class="tok-attr">rel</span>=<span class="tok-str">"stylesheet"</span> <span class="tok-attr">href</span>=<span class="tok-str">"@{{ asset('vendor/mediaflow/css/mediaflow.css') }}"</span><span class="tok-tag">&gt;</span>
    <span class="tok-com">{{-- Or Blade Directive: @@mediaflowStyles --}}</span>
<span class="tok-head">&lt;/head&gt;</span>
<span class="tok-body">&lt;body&gt;</span>
    <span class="tok-com">&lt;!-- Your Application Content --&gt;</span>
    @@yield('content')

    <span class="tok-com">&lt;!-- 3. MediaFlow JavaScript (Before closing body) --&gt;</span>
    <span class="tok-tag">&lt;script</span> <span class="tok-attr">src</span>=<span class="tok-str">"@{{ asset('vendor/mediaflow/js/mediaflow.js') }}"</span><span class="tok-tag">&gt;&lt;/script&gt;</span>
    <span class="tok-com">{{-- Or Blade Directive: @@mediaflowScripts --}}</span>
<span class="tok-body">&lt;/body&gt;</span>
<span class="tok-tag">&lt;/html&gt;</span></code></pre>
                    </div>
                </div>

                <!-- 5. FORM TO CONTROLLER LIFECYCLE -->
                <div id="form-lifecycle" class="mf-docs-step-card">
                    <div class="mf-docs-step-header">
                        <div class="mf-docs-step-badge">Step 5</div>
                        <h3 class="mf-docs-step-title"><span>📥</span> Form Request &rarr; Controller &rarr; Model Lifecycle</h3>
                    </div>
                    <p class="mf-docs-p" style="margin-bottom: 1.5rem;">
                        Here is exactly how MediaFlow works inside your standard HTML forms, what data is sent to your Controller in <code>$request</code>, how to validate and save it, and how to render the image in Blade:
                    </p>

                    <!-- STEP A: BLADE FORM -->
                    <div class="mf-sub-step-card">
                        <h4 class="mf-docs-h4" style="margin-top: 0; color: var(--mf-primary); display: flex; align-items: center; gap: 0.5rem;">
                            <span>A.</span> Place the Input Component in your Blade Form
                        </h4>
                        <div class="mf-code-container">
                            <div class="mf-code-header">
                                <span class="mf-code-lang">resources/views/articles/create.blade.php</span>
                                <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                            </div>
                            <pre class="mf-code-pre"><code><span class="tok-tag">&lt;form</span> <span class="tok-attr">method</span>=<span class="tok-str">"POST"</span> <span class="tok-attr">action</span>=<span class="tok-str">"@{{ route('articles.store') }}"</span><span class="tok-tag">&gt;</span>
    @@csrf

    <span class="tok-tag">&lt;div</span> <span class="tok-attr">class</span>=<span class="tok-str">"form-group"</span><span class="tok-tag">&gt;</span>
        <span class="tok-tag">&lt;label&gt;</span>Article Title<span class="tok-tag">&lt;/label&gt;</span>
        <span class="tok-tag">&lt;input</span> <span class="tok-attr">type</span>=<span class="tok-str">"text"</span> <span class="tok-attr">name</span>=<span class="tok-str">"title"</span> <span class="tok-attr">value</span>=<span class="tok-str">"@{{ old('title') }}"</span> <span class="tok-attr">required</span><span class="tok-tag">&gt;</span>
    <span class="tok-tag">&lt;/div&gt;</span>

    <span class="tok-com">@{{-- 1-Line Shortcut: Dropzone + Auto-Upload + Library Picker + Preview Card --}}</span>
    <span class="tok-tag">&lt;x-mediaflow-input</span> 
        <span class="tok-attr">name</span>=<span class="tok-str">"cover_media_id"</span> 
        <span class="tok-attr">label</span>=<span class="tok-str">"Featured Cover Image"</span>
        <span class="tok-attr">help</span>=<span class="tok-str">"Drop an image, browse your device, or select from your media library."</span>
        <span class="tok-attr">:required</span>=<span class="tok-str">"true"</span>
    <span class="tok-tag">/&gt;</span>

    <span class="tok-tag">&lt;button</span> <span class="tok-attr">type</span>=<span class="tok-str">"submit"</span><span class="tok-tag">&gt;</span>Publish Article<span class="tok-tag">&lt;/button&gt;</span>
<span class="tok-tag">&lt;/form&gt;</span></code></pre>
                        </div>
                    </div>

                    <!-- STEP B: WHAT CONTROLLER GETS -->
                    <div class="mf-sub-step-card">
                        <h4 class="mf-docs-h4" style="margin-top: 0; color: var(--mf-primary); display: flex; align-items: center; gap: 0.5rem;">
                            <span>B.</span> What You Receive in the Controller Request ($request)
                        </h4>
                        <div class="mf-docs-callout info" style="margin-bottom: 1rem;">
                            <div>💡</div>
                            <div>
                                <strong>Under the Hood:</strong> The <code>&lt;x-mediaflow-input name="cover_media_id" /&gt;</code> component automatically generates a hidden input:
                                <br><code>&lt;input type="hidden" id="mf-input-cover_media_id" name="cover_media_id" value="42"&gt;</code>
                                <br>When the user uploads a new image (or selects an existing image from the library modal), MediaFlow instantly uploads/optimizes it via AJAX and populates the hidden field with the <strong>Media database record ID</strong> (e.g. <code>42</code>).
                            </div>
                        </div>
                        <p class="mf-docs-p">
                            In your Laravel Controller, <code>$request-&gt;input('cover_media_id')</code> delivers the <strong>integer ID</strong> of the media item. You validate it with standard Laravel rules:
                        </p>
                        <div class="mf-code-container">
                            <div class="mf-code-header">
                                <span class="mf-code-lang">app/Http/Controllers/ArticleController.php</span>
                                <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                            </div>
                            <pre class="mf-code-pre"><code><span class="tok-kwd">namespace</span> App\Http\Controllers;

<span class="tok-kwd">use</span> App\Models\Article;
<span class="tok-kwd">use</span> Illuminate\Http\Request;

<span class="tok-kwd">class</span> <span class="tok-cls">ArticleController</span> <span class="tok-kwd">extends</span> <span class="tok-cls">Controller</span>
{
    <span class="tok-kwd">public function</span> <span class="tok-fn">store</span>(Request <span class="tok-var">$request</span>)
    {
        <span class="tok-com">// 1. Validate the incoming request</span>
        <span class="tok-var">$validated</span> = <span class="tok-var">$request</span>-&gt;<span class="tok-fn">validate</span>([
            <span class="tok-str">'title'</span> =&gt; <span class="tok-str">'required|string|max:255'</span>,
            <span class="tok-com">// 'cover_media_id' is an integer ID referencing the mediaflow table</span>
            <span class="tok-str">'cover_media_id'</span> =&gt; <span class="tok-str">'required|integer|exists:mediaflow_media,id'</span>,
        ]);

        <span class="tok-com">// 2. Create your record storing cover_media_id foreign key</span>
        <span class="tok-var">$article</span> = Article::<span class="tok-fn">create</span>([
            <span class="tok-str">'title'</span> =&gt; <span class="tok-var">$validated</span>[<span class="tok-str">'title'</span>],
            <span class="tok-str">'cover_media_id'</span> =&gt; <span class="tok-var">$validated</span>[<span class="tok-str">'cover_media_id'</span>],
        ]);

        <span class="tok-kwd">return</span> <span class="tok-fn">redirect</span>()-&gt;<span class="tok-fn">route</span>(<span class="tok-str">'articles.show'</span>, <span class="tok-var">$article</span>)
            -&gt;<span class="tok-fn">with</span>(<span class="tok-str">'success'</span>, <span class="tok-str">'Article published successfully!'</span>);
    }
}</code></pre>
                        </div>
                    </div>

                    <!-- STEP C: MODEL RELATIONSHIP -->
                    <div class="mf-sub-step-card">
                        <h4 class="mf-docs-h4" style="margin-top: 0; color: var(--mf-primary); display: flex; align-items: center; gap: 0.5rem;">
                            <span>C.</span> Define the Eloquent Relationship in your Model
                        </h4>
                        <p class="mf-docs-p">
                            In your <code>Article</code> model, import MediaFlow's <code>Media</code> model with an alias (e.g. <code>MediaflowMedia</code>) to ensure 100% coexistence with any existing application models:
                        </p>
                        <div class="mf-code-container">
                            <div class="mf-code-header">
                                <span class="mf-code-lang">app/Models/Article.php</span>
                                <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                            </div>
                            <pre class="mf-code-pre"><code><span class="tok-kwd">namespace</span> App\Models;

<span class="tok-kwd">use</span> Illuminate\Database\Eloquent\Model;
<span class="tok-kwd">use</span> MediaFlow\Laravel\Models\Media <span class="tok-kwd">as</span> MediaflowMedia; <span class="tok-com">// Prevents collision with App\Models\Media</span>

<span class="tok-kwd">class</span> <span class="tok-cls">Article</span> <span class="tok-kwd">extends</span> <span class="tok-cls">Model</span>
{
    <span class="tok-kwd">protected</span> <span class="tok-var">$fillable</span> = [<span class="tok-str">'title'</span>, <span class="tok-str">'cover_media_id'</span>];

    <span class="tok-com">/**
     * Relationship to the MediaFlow media record
     */</span>
    <span class="tok-kwd">public function</span> <span class="tok-fn">coverImage</span>()
    {
        <span class="tok-kwd">return</span> <span class="tok-var">$this</span>-&gt;<span class="tok-fn">belongsTo</span>(MediaflowMedia::<span class="tok-kwd">class</span>, <span class="tok-str">'cover_media_id'</span>);
    }
}</code></pre>
                        </div>
                    </div>

                    <!-- STEP D: DISPLAY IN BLADE -->
                    <div class="mf-sub-step-card">
                        <h4 class="mf-docs-h4" style="margin-top: 0; color: var(--mf-primary); display: flex; align-items: center; gap: 0.5rem;">
                            <span>D.</span> Display the Image in Blade Views
                        </h4>
                        <p class="mf-docs-p">Call <code>url()</code> for the full-resolution file, or <code>thumbnailUrl('thumb')</code> for the responsive WebP variant:</p>
                        <div class="mf-code-container">
                            <div class="mf-code-header">
                                <span class="mf-code-lang">resources/views/articles/show.blade.php</span>
                                <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                            </div>
                            <pre class="mf-code-pre"><code><span class="tok-tag">&lt;div</span> <span class="tok-attr">class</span>=<span class="tok-str">"article-header"</span><span class="tok-tag">&gt;</span>
    <span class="tok-tag">&lt;h1&gt;</span>@{{ $article-&gt;title }}<span class="tok-tag">&lt;/h1&gt;</span>

    @@if($article-&gt;coverImage)
        <span class="tok-com">@{{-- 1. Full Resolution Delivery or CDN URL --}}</span>
        <span class="tok-tag">&lt;img</span> <span class="tok-attr">src</span>=<span class="tok-str">"@{{ $article-&gt;coverImage-&gt;url() }}"</span> 
             <span class="tok-attr">alt</span>=<span class="tok-str">"@{{ $article-&gt;coverImage-&gt;original_name }}"</span>
             <span class="tok-attr">class</span>=<span class="tok-str">"article-hero"</span><span class="tok-tag">&gt;</span>

        <span class="tok-com">@{{-- 2. Responsive Auto-Optimized WebP Thumbnail --}}</span>
        <span class="tok-tag">&lt;img</span> <span class="tok-attr">src</span>=<span class="tok-str">"@{{ $article-&gt;coverImage-&gt;thumbnailUrl('thumb') }}"</span> 
             <span class="tok-attr">alt</span>=<span class="tok-str">"Thumbnail"</span> 
             <span class="tok-attr">class</span>=<span class="tok-str">"article-thumb"</span><span class="tok-tag">&gt;</span>
    @@endif
<span class="tok-tag">&lt;/div&gt;</span></code></pre>
                        </div>
                    </div>
                </div>

                <!-- 6. FRONTEND PICKER VS ADMIN ISOLATION -->
                <div id="frontend-isolation" class="mf-docs-step-card">
                    <div class="mf-docs-step-header">
                        <div class="mf-docs-step-badge">Step 6</div>
                        <h3 class="mf-docs-step-title"><span>🛡️</span> Frontend Media Picker vs Admin Isolation</h3>
                    </div>
                    <p class="mf-docs-p">
                        In many web applications, frontend users (e.g., users uploading avatars, guest authors submitting articles, or customers uploading receipts) should <strong>NEVER</strong> see administrative images, invoices, or other users' confidential uploads. MediaFlow gives you two powerful options:
                    </p>

                    <div class="mf-feature-grid" style="margin: 1.25rem 0;">
                        <div class="mf-feature-card">
                            <div style="font-weight: 700; color: var(--mf-text-main); margin-bottom: 0.5rem; font-size: 0.95rem;">🔒 Option A: Upload-Only Mode (:upload-only="true")</div>
                            <div style="font-size: 0.85rem; color: var(--mf-text-muted); line-height: 1.55;">
                                Completely hides the <em>"Browse Media Library"</em> button. Users can only upload files via drag-and-drop or local file browsing.
                            </div>
                            <div class="mf-code-container" style="margin-top: 0.75rem;">
                                <pre class="mf-code-pre"><code><span class="tok-tag">&lt;x-mediaflow-input</span> 
    <span class="tok-attr">name</span>=<span class="tok-str">"avatar"</span> 
    <span class="tok-attr">label</span>=<span class="tok-str">"Profile Photo"</span> 
    <span class="tok-attr">:upload-only</span>=<span class="tok-str">"true"</span> 
<span class="tok-tag">/&gt;</span></code></pre>
                            </div>
                        </div>

                        <div class="mf-feature-card">
                            <div style="font-weight: 700; color: var(--mf-text-main); margin-bottom: 0.5rem; font-size: 0.95rem;">📁 Option B: Scoped / Isolated Picker (:frontend="true")</div>
                            <div style="font-size: 0.85rem; color: var(--mf-text-muted); line-height: 1.55;">
                                Locks the media library modal strictly to a specific folder or collection. Hides all root admin folders and locks breadcrumbs so users cannot navigate to admin directories.
                            </div>
                            <div class="mf-code-container" style="margin-top: 0.75rem;">
                                <pre class="mf-code-pre"><code><span class="tok-tag">&lt;x-mediaflow-input</span> 
    <span class="tok-attr">name</span>=<span class="tok-str">"avatar"</span> 
    <span class="tok-attr">label</span>=<span class="tok-str">"Profile Photo"</span> 
    <span class="tok-attr">:frontend</span>=<span class="tok-str">"true"</span> 
    <span class="tok-attr">folder</span>=<span class="tok-str">"user-avatars"</span> 
    <span class="tok-attr">collection</span>=<span class="tok-str">"avatars"</span> 
<span class="tok-tag">/&gt;</span></code></pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 7. FULLSCREEN VIEW & COPY URL OPTIONS -->
                <div id="fullscreen-copy" class="mf-docs-step-card">
                    <div class="mf-docs-step-header">
                        <div class="mf-docs-step-badge">Step 7</div>
                        <h3 class="mf-docs-step-title"><span>👁️</span> Open Image in New Tab (Fullscreen) &amp; Copy URL</h3>
                    </div>
                    <p class="mf-docs-p">
                        MediaFlow provides instant fullscreen image inspection and 1-click clipboard URL copying across both the input component and the media manager explorer:
                    </p>
                    <ul class="mf-docs-list" style="margin: 1.25rem 0;">
                        <li>
                            <strong>Input Component Preview Card:</strong> Once an image is uploaded or chosen, two quick action buttons appear alongside <em>Replace</em> and <em>Delete</em>:
                            <ul style="margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.5rem;">
                                <li><code>👁️ Open</code> &mdash; Opens the full-resolution image directly in a new browser tab (<code>_blank</code>) for full-screen inspection.</li>
                                <li><code>📋 Copy URL</code> &mdash; Copies the exact public or CDN delivery URL to the user's clipboard and triggers an instant success toast notification.</li>
                            </ul>
                        </li>
                        <li>
                            <strong>Media Manager Explorer &amp; Inspector:</strong> Every image card has hover quick-action buttons (<code>👁️</code> and <code>📋</code>), and the Inspector sidebar features prominent <em>"Open in New Tab"</em> and <em>"Copy Image URL"</em> buttons.
                        </li>
                    </ul>
                </div>

                <!-- 8. COLLISION-PROOF EXISTING TABLE PREFIX -->
                <div id="table-prefix-collision" class="mf-docs-step-card">
                    <div class="mf-docs-step-header">
                        <div class="mf-docs-step-badge">Step 8</div>
                        <h3 class="mf-docs-step-title"><span>🔒</span> Existing Media Table &amp; Spatie Collision-Proof Guide</h3>
                    </div>
                    <p class="mf-docs-p">
                        Does your Laravel project already have an existing <code>media</code> database table or an <code>App\Models\Media</code> model (for example from <strong>Spatie MediaLibrary</strong> or a legacy custom system)?
                    </p>
                    <div class="mf-docs-callout tip" style="margin: 1.25rem 0;">
                        <div>🛡️</div>
                        <div>
                            <strong>Zero Conflict Guarantee:</strong> MediaFlow models reside strictly under <code>MediaFlow\Laravel\Models\Media</code>. By default, MediaFlow uses the table prefix <code>mediaflow_</code> (<code>mediaflow_media</code>, <code>mediaflow_folders</code>, etc.), completely avoiding any collision with existing tables!
                        </div>
                    </div>
                    <p class="mf-docs-p">To shorten or customize the table prefix, set it in your <code>.env</code> file:</p>
                    <div class="mf-code-container">
                        <div class="mf-code-header">
                            <span class="mf-code-lang">.env</span>
                            <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                        </div>
                        <pre class="mf-code-pre"><code><span class="tok-com"># Prefix all MediaFlow tables with 'mf_' (creates mf_media, mf_folders, mf_variants)</span>
MEDIAFLOW_TABLE_PREFIX=mf_</code></pre>
                    </div>
                </div>

                <!-- 9. SECURITY: ROUTE NAMES & AUTH -->
                <div id="security-hardening" class="mf-docs-step-card">
                    <div class="mf-docs-step-header">
                        <div class="mf-docs-step-badge">Step 9</div>
                        <h3 class="mf-docs-step-title"><span>🔐</span> Security Hardening: Obscuring Route Names &amp; Adding Auth</h3>
                    </div>
                    <p class="mf-docs-p">
                        For production security, you should customize the route prefix so bots and attackers cannot probe the default <code>/mediaflow</code> URL, and attach authentication middleware:
                    </p>
                    <div class="mf-code-container" style="margin: 1.25rem 0;">
                        <div class="mf-code-header">
                            <span class="mf-code-lang">.env</span>
                            <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                        </div>
                        <pre class="mf-code-pre"><code><span class="tok-com"># 1. Change route prefix (e.g. your company's private path)</span>
MEDIAFLOW_ROUTE_PREFIX=cms/internal-media-vault

<span class="tok-com"># 2. Add authentication middleware (e.g. web, auth, or role checks)</span>
MEDIAFLOW_MIDDLEWARE=web,auth

<span class="tok-com"># 3. Specify custom auth guard if using a separate admin guard</span>
MEDIAFLOW_GUARD=admin</code></pre>
                    </div>
                    <p class="mf-docs-p">
                        All Blade components (such as <code>&lt;x-mediaflow-input&gt;</code>) automatically resolve their upload and picker routes dynamically using Laravel's named route system (<code>route('mediaflow.upload')</code>), so changing <code>MEDIAFLOW_ROUTE_PREFIX</code> requires <strong>zero changes</strong> to your frontend Blade code!
                    </p>
                </div>
            </section>

            <!-- SECTION 2: REQUIREMENTS -->
            <section id="requirements" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>📋</span> 2. System Requirements</h2>
                <div class="mf-docs-table-wrapper">
                    <table class="mf-docs-table">
                        <thead>
                            <tr>
                                <th>Component</th>
                                <th>Requirement</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>PHP</strong></td>
                                <td><code>^8.2 | ^8.3 | ^8.4 | ^8.5</code></td>
                                <td>Tested across PHP 8.2, 8.3, and 8.4 with OPcache</td>
                            </tr>
                            <tr>
                                <td><strong>Laravel Framework</strong></td>
                                <td><code>^11.0 | ^12.0 | ^13.0</code></td>
                                <td>Standard Laravel package auto-discovery enabled</td>
                            </tr>
                            <tr>
                                <td><strong>Database</strong></td>
                                <td>SQLite 3.35+, MySQL 8.0+, or PostgreSQL 14+</td>
                                <td>Stores media catalog, folder trees, variants & settings</td>
                            </tr>
                            <tr>
                                <td><strong>PHP Extensions</strong></td>
                                <td><code>fileinfo</code>, <code>gd</code> or <code>imagick</code>, <code>exif</code></td>
                                <td>MIME sniffing, EXIF stripping, WebP thumbnails & compression</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- SECTION 3: INSTALLATION -->
            <section id="installation" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>⚡</span> 3. Step-by-Step Installation</h2>
                <p class="mf-docs-p">Follow these simple steps to install MediaFlow in your Laravel application:</p>

                <h3 class="mf-docs-h3">Step 1: Install via Composer</h3>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">Terminal</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code>composer require dwivedianuj9118/mediaflow-laravel</code></pre>
                </div>

                <h3 class="mf-docs-h3">Step 2: Publish Package Config, Migrations & Assets</h3>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">Terminal</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-com"># Publish configuration file (config/mediaflow.php)</span>
php artisan vendor:publish --tag=mediaflow-config

<span class="tok-com"># Publish database migrations</span>
php artisan vendor:publish --tag=mediaflow-migrations

<span class="tok-com"># Publish CSS and JS assets to public/vendor/mediaflow</span>
php artisan vendor:publish --tag=mediaflow-assets</code></pre>
                </div>

                <h3 class="mf-docs-h3">Step 3: Run Database Migrations</h3>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">Terminal</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code>php artisan migrate</code></pre>
                </div>

                <h3 class="mf-docs-h3">Step 4: Create Public Storage Symlink</h3>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">Terminal</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code>php artisan storage:link</code></pre>
                </div>

                <div id="updating" style="margin-top: 2.5rem; padding-top: 1rem; border-top: 1px dashed var(--mf-border);">
                    <h3 class="mf-docs-h3" style="color: var(--mf-primary); display: flex; align-items: center; gap: 0.5rem;">
                        <span>🔄</span> How to Update an Existing Project to the Latest Version
                    </h3>
                    <p class="mf-docs-p">
                        When a new version or commit of MediaFlow is released, update any existing Laravel project with this single 1-line command:
                    </p>

                    <div class="mf-code-container">
                        <div class="mf-code-header">
                            <span class="mf-code-lang">Terminal (1-Line Update)</span>
                            <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                        </div>
                        <pre class="mf-code-pre"><code>composer update dwivedianuj9118/mediaflow-laravel &amp;&amp; php artisan vendor:publish --tag=mediaflow-assets --force &amp;&amp; php artisan optimize:clear</code></pre>
                    </div>

                    <div class="mf-feature-grid" style="margin-top: 1.25rem;">
                        <div class="mf-feature-card">
                            <div style="font-weight: 700; color: var(--mf-text-main); margin-bottom: 0.35rem;">1. Composer Update</div>
                            <div style="font-size: 0.85rem; color: var(--mf-text-muted); line-height: 1.5;">
                                <code>composer update dwivedianuj9118/mediaflow-laravel</code> downloads the newest release or git commits into your vendor folder.
                            </div>
                        </div>
                        <div class="mf-feature-card">
                            <div style="font-weight: 700; color: var(--mf-text-main); margin-bottom: 0.35rem;">2. Overwrite Published Assets (--force)</div>
                            <div style="font-size: 0.85rem; color: var(--mf-text-muted); line-height: 1.5;">
                                <code>php artisan vendor:publish --tag=mediaflow-assets --force</code> updates JavaScript (toasts, uploaders) and CSS in your <code>public/vendor/mediaflow</code> directory.
                            </div>
                        </div>
                        <div class="mf-feature-card">
                            <div style="font-weight: 700; color: var(--mf-text-main); margin-bottom: 0.35rem;">3. Clear Caches</div>
                            <div style="font-size: 0.85rem; color: var(--mf-text-muted); line-height: 1.5;">
                                <code>php artisan optimize:clear</code> clears stale configuration, view, and route caches so all new features take effect immediately.
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 4: CONFIGURATION -->
            <section id="configuration" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>⚙️</span> 4. Configuration & Environment Variables</h2>
                <p class="mf-docs-p">
                    MediaFlow is configured via <code>config/mediaflow.php</code> or environment variables in your <code>.env</code> file:
                </p>

                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">.env</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-com"># Route prefix for media manager and upload endpoints (default: mediaflow)</span>
MEDIAFLOW_ROUTE_PREFIX=mediaflow
<span class="tok-com"># Comma-separated middleware stack for all MediaFlow routes (default: web)</span>
MEDIAFLOW_MIDDLEWARE=web

<span class="tok-com"># Access control (authentication is ON by default — only disable for local dev)</span>
MEDIAFLOW_AUTH_ENABLED=true
MEDIAFLOW_GUARD=web                  <span class="tok-com"># MEDIAFLOW_AUTH_GUARD is accepted as an alias</span>
MEDIAFLOW_ROLES=admin,editor
MEDIAFLOW_PERMISSIONS=manage-media
MEDIAFLOW_GATE=
MEDIAFLOW_CUSTOM_MIDDLEWARE=
MEDIAFLOW_AUTH_ALLOW_UI_OVERRIDE=false <span class="tok-com"># let the Settings page switch auth off / replace roles</span>
MEDIAFLOW_USER_SCOPED=false          <span class="tok-com"># users only see their own media &amp; folders</span>

<span class="tok-com"># Storage</span>
MEDIAFLOW_DISK=public                <span class="tok-com"># disk for public uploads</span>
MEDIAFLOW_PRIVATE_DISK=local         <span class="tok-com"># disk for visibility=private uploads (recommended)</span>
MEDIAFLOW_TEMP_DISK=local

<span class="tok-com"># Upload limits</span>
MEDIAFLOW_MAX_FILE_SIZE_KB=20480     <span class="tok-com"># 20 MB</span>
MEDIAFLOW_MAX_IMAGE_MEGAPIXELS=50    <span class="tok-com"># decompression-bomb guard</span>

<span class="tok-com"># Images (only the GD driver ships today)</span>
MEDIAFLOW_IMAGE_DRIVER=gd

<span class="tok-com"># Delivery: 'disk' (storage URL), 'cdn' (MEDIAFLOW_CDN_URL prefix) or 'signed' (always expiring URLs)</span>
MEDIAFLOW_DELIVERY_DRIVER=disk
MEDIAFLOW_CDN_URL=https://cdn.example.com
MEDIAFLOW_SIGNED_EXPIRY=30           <span class="tok-com"># minutes</span>

<span class="tok-com"># Database Table Prefix &amp; Custom Table Names (Collision-Proof)</span>
MEDIAFLOW_TABLE_PREFIX=mediaflow_
<span class="tok-com"># MEDIAFLOW_TABLE_MEDIA=mediaflow_media</span>
<span class="tok-com"># MEDIAFLOW_TABLE_FOLDERS=mediaflow_folders</span>
<span class="tok-com"># MEDIAFLOW_TABLE_VARIANTS=mediaflow_variants</span>
<span class="tok-com"># MEDIAFLOW_TABLE_UPLOAD_SESSIONS=mediaflow_upload_sessions</span>
<span class="tok-com"># MEDIAFLOW_TABLE_SETTINGS=mediaflow_settings</span>

<span class="tok-com"># UI</span>
MEDIAFLOW_SHOW_HEADER=true
MEDIAFLOW_DEV_NAV=false</code></pre>
                </div>

                <h3 class="mf-docs-h3">Coexisting with Existing Media Models, Spatie &amp; Legacy Tables</h3>
                <p class="mf-docs-p">
                    If your Laravel application already has an existing <code>media</code> database table or an <code>App\Models\Media</code> model (for example, from <strong>Spatie MediaLibrary</strong> or an existing custom media implementation), MediaFlow operates completely collision-free:
                </p>
                <ul class="mf-docs-p" style="padding-left: 1.25rem; line-height: 1.8;">
                    <li><strong>Fully Isolated Namespace:</strong> MediaFlow models reside strictly under <code>MediaFlow\Laravel\Models\Media</code>. They will never collide with your application's <code>App\Models\Media</code>. You can alias the model in your controllers or models:
                        <div class="mf-code-container" style="margin: 0.5rem 0;">
                            <pre class="mf-code-pre"><code><span class="tok-kwd">use</span> MediaFlow\Laravel\Models\Media <span class="tok-kwd">as</span> MediaFlowItem;
<span class="tok-kwd">use</span> MediaFlow\Laravel\Facades\MediaFlow;

<span class="tok-com">// Your existing App\Models\Media remains completely untouched!</span></code></pre>
                        </div>
                    </li>
                    <li><strong>Independent Tables &amp; Configurable Prefix:</strong> By default, MediaFlow uses dedicated tables prefixed with <code>mediaflow_</code> (<code>mediaflow_media</code>, <code>mediaflow_folders</code>, <code>mediaflow_variants</code>, <code>mediaflow_upload_sessions</code>, <code>mediaflow_settings</code>).</li>
                    <li><strong>Custom Prefix via .env:</strong> If you wish to shorten or customize the prefix, simply set <code>MEDIAFLOW_TABLE_PREFIX=mf_</code> in your <code>.env</code> file before running migrations.</li>
                    <li><strong>Individual Table Overrides:</strong> Each table name can also be individually overridden in <code>.env</code> (e.g. <code>MEDIAFLOW_TABLE_MEDIA=custom_media_library</code>).</li>
                </ul>

                <h4 class="mf-docs-h4" style="font-weight: 700; margin-top: 1rem; color: var(--mf-text-main);">Defining Model Relationships with MediaFlow</h4>
                <p class="mf-docs-p">To link an uploaded MediaFlow image to any existing Eloquent model (e.g., <code>Post</code>, <code>Article</code>, <code>User</code>):</p>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">app/Models/Article.php</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-kwd">namespace</span> App\Models;

<span class="tok-kwd">use</span> Illuminate\Database\Eloquent\Model;
<span class="tok-kwd">use</span> MediaFlow\Laravel\Models\Media <span class="tok-kwd">as</span> MediaFlowItem;

<span class="tok-kwd">class</span> <span class="tok-cls">Article</span> <span class="tok-kwd">extends</span> <span class="tok-cls">Model</span>
{
    <span class="tok-kwd">protected</span> <span class="tok-var">$fillable</span> = [<span class="tok-str">'title'</span>, <span class="tok-str">'content'</span>, <span class="tok-str">'cover_media_id'</span>];

    <span class="tok-com">/**
     * Featured cover image managed via MediaFlow
     */</span>
    <span class="tok-kwd">public function</span> <span class="tok-fn">coverImage</span>()
    {
        <span class="tok-kwd">return</span> <span class="tok-var">$this</span>-&gt;<span class="tok-fn">belongsTo</span>(MediaFlowItem::<span class="tok-kwd">class</span>, <span class="tok-str">'cover_media_id'</span>);
    }
}</code></pre>
                </div>
            
                <h3 class="mf-docs-h3">Upload Rules Enforced on the Server</h3>
                <ul class="mf-docs-p" style="padding-left: 1.25rem; line-height: 1.8;">
                    <li><code>allowed_mimes</code> is checked against the MIME type <em>detected from the file contents</em>, never the browser-supplied type. Wildcards such as <code>image/*</code> are supported.</li>
                    <li>The stored file extension is derived from the detected MIME type, so <code>invoice.html</code> containing plain text is stored as <code>.txt</code>. <code>blocked_extensions</code> is an extra deny-list on top.</li>
                    <li>SVGs containing scripts, event handlers, <code>&lt;foreignObject&gt;</code>, external <code>href</code>s or entities are rejected while <code>sanitize_svg</code> is <code>true</code>.</li>
                    <li>JPEG/PNG/WebP files carrying EXIF/XMP metadata are re-encoded to remove GPS and camera data (<code>images.strip_exif</code>). JPEG orientation is applied first so photos are not rotated.</li>
                    <li>The max file size saved on the Settings page overrides <code>max_file_size_kb</code>; a per-call <code>max_kb</code> rule overrides both.</li>
                </ul>
                <h3 class="mf-docs-h3">Thumbnail Presets</h3>
                <p class="mf-docs-p">Every raster image gets one variant per preset in <code>images.presets</code>. Add, remove or resize presets to fit your layouts:</p>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">config/mediaflow.php</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-str">'presets'</span> =&gt; [
    <span class="tok-str">'thumb'</span>  =&gt; [<span class="tok-str">'width'</span> =&gt; <span class="tok-num">200</span>,  <span class="tok-str">'height'</span> =&gt; <span class="tok-num">200</span>,  <span class="tok-str">'fit'</span> =&gt; <span class="tok-str">'crop'</span>,    <span class="tok-str">'format'</span> =&gt; <span class="tok-str">'webp'</span>, <span class="tok-str">'quality'</span> =&gt; <span class="tok-num">80</span>],
    <span class="tok-str">'medium'</span> =&gt; [<span class="tok-str">'width'</span> =&gt; <span class="tok-num">800</span>,  <span class="tok-str">'height'</span> =&gt; <span class="tok-num">800</span>,  <span class="tok-str">'fit'</span> =&gt; <span class="tok-str">'contain'</span>, <span class="tok-str">'format'</span> =&gt; <span class="tok-str">'webp'</span>, <span class="tok-str">'quality'</span> =&gt; <span class="tok-num">85</span>],
    <span class="tok-str">'og'</span>     =&gt; [<span class="tok-str">'width'</span> =&gt; <span class="tok-num">1200</span>, <span class="tok-str">'height'</span> =&gt; <span class="tok-num">630</span>,  <span class="tok-str">'fit'</span> =&gt; <span class="tok-str">'crop'</span>,    <span class="tok-str">'format'</span> =&gt; <span class="tok-str">'jpeg'</span>, <span class="tok-str">'quality'</span> =&gt; <span class="tok-num">85</span>],
],</code></pre>
                </div>
                <div class="mf-docs-callout info">
                    <div>ℹ️</div>
                    <div><strong>Not implemented yet:</strong> <code>images.queue_processing</code>, <code>images.driver = imagick</code> and <code>scope.tenant_column</code> are reserved keys. Variants are generated synchronously with GD during the upload request; bind your own <code>MediaFlow\Laravel\Contracts\ImageProcessor</code> to use another engine.</div>
                </div>
            </section>

            <!-- SECTION 5: INPUT SHORTCUT COMPONENT -->
            <section id="input-shortcut" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>📥</span> 5. Shortcut Input Tag: &lt;x-mediaflow-input&gt;</h2>
                <p class="mf-docs-p">
                    The shortcut input component creates a drop-in file selector and upload box for any Blade form. It includes drag-and-drop, native file browsing, instant 3D upload animation trigger, an existing library picker modal, and a hidden input binding the media ID to standard <code>POST</code> form requests.
                </p>

                <h3 class="mf-docs-h3">Basic Usage</h3>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">Blade View</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-tag">&lt;form</span> <span class="tok-attr">method</span>=<span class="tok-str">"POST"</span> <span class="tok-attr">action</span>=<span class="tok-str">"/articles"</span><span class="tok-tag">&gt;</span>
    @csrf

    <span class="tok-tag">&lt;div</span> <span class="tok-attr">class</span>=<span class="tok-str">"form-group"</span><span class="tok-tag">&gt;</span>
        <span class="tok-tag">&lt;label&gt;</span>Article Title<span class="tok-tag">&lt;/label&gt;</span>
        <span class="tok-tag">&lt;input</span> <span class="tok-attr">type</span>=<span class="tok-str">"text"</span> <span class="tok-attr">name</span>=<span class="tok-str">"title"</span> <span class="tok-attr">class</span>=<span class="tok-str">"form-control"</span> <span class="tok-attr">required</span><span class="tok-tag">&gt;</span>
    <span class="tok-tag">&lt;/div&gt;</span>

    <span class="tok-com">@{{-- MediaFlow Drop-in Input Box --}}</span>
    <span class="tok-tag">&lt;x-mediaflow-input</span> 
        <span class="tok-attr">name</span>=<span class="tok-str">"cover_media_id"</span> 
        <span class="tok-attr">label</span>=<span class="tok-str">"Featured Cover Image"</span>
        <span class="tok-attr">help</span>=<span class="tok-str">"Drag & drop an image or select from your media library."</span>
        <span class="tok-attr">:required</span>=<span class="tok-str">"true"</span>
    <span class="tok-tag">/&gt;</span>

    <span class="tok-tag">&lt;button</span> <span class="tok-attr">type</span>=<span class="tok-str">"submit"</span><span class="tok-tag">&gt;</span>Save Article<span class="tok-tag">&lt;/button&gt;</span>
<span class="tok-tag">&lt;/form&gt;</span></code></pre>
                </div>

                <div class="mf-docs-callout info">
                    <div>💡</div>
                    <div>
                        <strong>Tag Names:</strong> Both <code>&lt;x-mediaflow-input ... /&gt;</code> and <code>&lt;x-mediaflow::input ... /&gt;</code> are registered and can be used interchangeably.
                    </div>
                </div>

                <h3 class="mf-docs-h3">All Supported Component Attributes</h3>
                <div class="mf-docs-table-wrapper">
                    <table class="mf-docs-table">
                        <thead>
                            <tr>
                                <th>Prop</th>
                                <th>Type</th>
                                <th>Default</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>name</code></td>
                                <td><code>string</code></td>
                                <td><code>'media_id'</code></td>
                                <td>The form field name submitted in the POST request (carries media ID integer).</td>
                            </tr>
                            <tr>
                                <td><code>label</code></td>
                                <td><code>string|null</code></td>
                                <td><code>null</code></td>
                                <td>Field label text rendered above the dropzone with optional required asterisk.</td>
                            </tr>
                            <tr>
                                <td><code>value</code></td>
                                <td><code>mixed</code></td>
                                <td><code>null</code></td>
                                <td>Initial selection. Accepts a <code>Media</code> model instance, media ID integer, or URL string.</td>
                            </tr>
                            <tr>
                                <td><code>compact</code></td>
                                <td><code>bool</code></td>
                                <td><code>false</code></td>
                                <td>Optimized layout for sidebars, off-canvas drawers, and narrow admin panels (≤ 340px).</td>
                            </tr>
                            <tr>
                                <td><code>picker</code></td>
                                <td><code>bool</code></td>
                                <td><code>true</code></td>
                                <td>Displays the "🗂 Browse Library" modal button to choose existing files without re-uploading.</td>
                            </tr>
                            <tr>
                                <td><code>type</code></td>
                                <td><code>string</code></td>
                                <td><code>'image'</code></td>
                                <td>Target media type preset: <code>'image'</code>, <code>'video'</code>, <code>'doc'</code> / <code>'pdf'</code>, <code>'audio'</code>, or <code>'any'</code> / <code>'*'</code>. Automatically configures appropriate file accept rules, dropzone icon, helper subtext, and filters picker media.</td>
                            </tr>
                            <tr>
                                <td><code>accept</code></td>
                                <td><code>string|null</code></td>
                                <td><code>null</code> (auto)</td>
                                <td>Custom MIME type or extension whitelist (e.g. <code>accept=".pdf,.docx"</code> or <code>accept="video/*"</code>). Enforced on file selection, drag-and-drop, and in the picker modal.</td>
                            </tr>
                            <tr>
                                <td><code>upload-only</code></td>
                                <td><code>bool</code></td>
                                <td><code>false</code></td>
                                <td>When set to <code>true</code> (or <code>:browse="false"</code> / <code>:hide-browse="true"</code>), hides the "Browse Media Library" button so users can only upload files.</td>
                            </tr>
                            <tr>
                                <td><code>frontend</code></td>
                                <td><code>bool</code></td>
                                <td><code>false</code></td>
                                <td>When set to <code>true</code> (or <code>:isolated="true"</code>), locks the media picker strictly to the designated folder or collection, shielding admin root folders.</td>
                            </tr>
                            <tr>
                                <td><code>folder</code></td>
                                <td><code>string|null</code></td>
                                <td><code>null</code></td>
                                <td>Target folder slug or UUID for files uploaded through this input box.</td>
                            </tr>
                            <tr>
                                <td><code>collection</code></td>
                                <td><code>string</code></td>
                                <td><code>'default'</code></td>
                                <td>Target media collection classification name.</td>
                            </tr>
                            <tr>
                                <td><code>required</code></td>
                                <td><code>bool</code></td>
                                <td><code>false</code></td>
                                <td>Renders required indicator asterisk and adds form validation attributes.</td>
                            </tr>
                            <tr>
                                <td><code>help</code></td>
                                <td><code>string|null</code></td>
                                <td><code>null</code></td>
                                <td>Subtle helper text rendered beneath the dropzone.</td>
                            </tr>
                            <tr>
                                <td><code>pipeline</code> / <code>visualizer</code></td>
                                <td><code>bool</code></td>
                                <td><code>false</code></td>
                                <td>When set to <code>true</code>, dropping or choosing a file triggers the full-screen interactive 3D 5-station upload lifecycle visualizer modal.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 class="mf-docs-h3">Dynamic File Types: Videos, Documents, Audio &amp; Any File</h3>
                <p class="mf-docs-p">
                    MediaFlow is not just for images &mdash; you can allow any media or document type by specifying <code>type</code> or custom <code>accept</code> extensions:
                </p>

                <div class="mf-code-container" style="margin-top: 1rem;">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">Blade View Examples</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-com">@{{-- 1. Video Input (MP4, WebM, OGG, QuickTime) --}}</span>
<span class="tok-tag">&lt;x-mediaflow-input</span> 
    <span class="tok-attr">name</span>=<span class="tok-str">"trailer_video_id"</span> 
    <span class="tok-attr">label</span>=<span class="tok-str">"Feature Trailer Video"</span> 
    <span class="tok-attr">type</span>=<span class="tok-str">"video"</span> 
    <span class="tok-attr">folder</span>=<span class="tok-str">"videos"</span> 
<span class="tok-tag">/&gt;</span>

<span class="tok-com">@{{-- 2. PDF & Office Documents (PDF, Word, Excel, CSV) --}}</span>
<span class="tok-tag">&lt;x-mediaflow-input</span> 
    <span class="tok-attr">name</span>=<span class="tok-str">"contract_file_id"</span> 
    <span class="tok-attr">label</span>=<span class="tok-str">"Signed Agreement or Resume"</span> 
    <span class="tok-attr">type</span>=<span class="tok-str">"docs"</span> 
    <span class="tok-attr">accept</span>=<span class="tok-str">".pdf,.doc,.docx"</span> 
    <span class="tok-attr">folder</span>=<span class="tok-str">"documents"</span> 
<span class="tok-tag">/&gt;</span>

<span class="tok-com">@{{-- 3. Audio / Podcast File (MP3, WAV, OGG) --}}</span>
<span class="tok-tag">&lt;x-mediaflow-input</span> 
    <span class="tok-attr">name</span>=<span class="tok-str">"podcast_track_id"</span> 
    <span class="tok-attr">label</span>=<span class="tok-str">"Episode Audio Track"</span> 
    <span class="tok-attr">type</span>=<span class="tok-str">"audio"</span> 
    <span class="tok-attr">folder</span>=<span class="tok-str">"podcasts"</span> 
<span class="tok-tag">/&gt;</span>

<span class="tok-com">@{{-- 4. Any Allowed File Type --}}</span>
<span class="tok-tag">&lt;x-mediaflow-input</span> 
    <span class="tok-attr">name</span>=<span class="tok-str">"attachment_id"</span> 
    <span class="tok-attr">label</span>=<span class="tok-str">"Ticket Attachment"</span> 
    <span class="tok-attr">type</span>=<span class="tok-str">"any"</span> 
<span class="tok-tag">/&gt;</span></code></pre>
                </div>
            </section>

            <!-- SECTION 6: SIDE ADMIN PANEL COMPATIBILITY -->
            <section id="side-admin-panel" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>📐</span> 6. Side Admin Panel & Drawer Compatibility</h2>
                <p class="mf-docs-p">
                    Administrative side panels and slide-over drawers (typically 280px to 340px wide) often suffer from horizontal clipping, oversized icons, and modal clipping caused by parent <code>overflow: hidden</code> or CSS stacking contexts. MediaFlow provides native solutions:
                </p>

                <h3 class="mf-docs-h3">Compact Mode (:compact="true")</h3>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">Admin Drawer Blade View</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-tag">&lt;div</span> <span class="tok-attr">class</span>=<span class="tok-str">"admin-sidebar-drawer"</span> <span class="tok-attr">style</span>=<span class="tok-str">"width: 320px;"</span><span class="tok-tag">&gt;</span>
    <span class="tok-tag">&lt;h3&gt;</span>Quick Post Editor<span class="tok-tag">&lt;/h3&gt;</span>

    <span class="tok-com">@{{-- Compact layout fits perfectly without horizontal overflow --}}</span>
    <span class="tok-tag">&lt;x-mediaflow-input</span> 
        <span class="tok-attr">name</span>=<span class="tok-str">"sidebar_thumbnail_id"</span> 
        <span class="tok-attr">label</span>=<span class="tok-str">"Sidebar Thumbnail"</span>
        <span class="tok-attr">:compact</span>=<span class="tok-str">"true"</span>
        <span class="tok-attr">help</span>=<span class="tok-str">"Scaled for narrow panels (280px-340px)."</span>
    <span class="tok-tag">/&gt;</span>
<span class="tok-tag">&lt;/div&gt;</span></code></pre>
                </div>

                <div class="mf-docs-callout tip">
                    <div>🛡️</div>
                    <div>
                        <strong>Stacking Context & Modal Isolation:</strong> MediaFlow automatically attaches modal backdrops (both the 3D upload animation and the Media Picker) directly to <code>document.body</code> with <code>z-index: 9999999 !important;</code>. This prevents sidebar CSS rules like <code>transform</code>, <code>filter</code>, or <code>overflow: hidden</code> from clipping dialogs.
                    </div>
                </div>
            </section>

            <!-- SECTION 7: MODEL BINDING -->
            <section id="model-binding" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>🔗</span> 7. Model Binding & Pre-populated Values</h2>
                <p class="mf-docs-p">
                    When editing existing resources, pass the relation or integer ID to <code>:value</code>. MediaFlow will automatically render the preview card with the file thumbnail and replace button:
                </p>

                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">Blade Edit View</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-tag">&lt;x-mediaflow-input</span> 
    <span class="tok-attr">name</span>=<span class="tok-str">"cover_media_id"</span> 
    <span class="tok-attr">label</span>=<span class="tok-str">"Cover Image"</span>
    <span class="tok-attr">:value</span>=<span class="tok-str">"$post-&gt;coverMedia ?? old('cover_media_id')"</span> 
<span class="tok-tag">/&gt;</span></code></pre>
                </div>

                <h3 class="mf-docs-h3">Saving in Controller</h3>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">PHP Controller</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-kw">public function</span> <span class="tok-fn">store</span>(Request <span class="tok-var">$request</span>)
{
    <span class="tok-var">$validated</span> = <span class="tok-var">$request</span>-&gt;<span class="tok-fn">validate</span>([
        <span class="tok-str">'title'</span> =&gt; <span class="tok-str">'required|string|max:255'</span>,
        <span class="tok-str">'cover_media_id'</span> =&gt; <span class="tok-str">'required|exists:mediaflow_media,id'</span>,
    ]);

    <span class="tok-var">$post</span> = Post::<span class="tok-fn">create</span>(<span class="tok-var">$validated</span>);

    <span class="tok-kw">return</span> <span class="tok-fn">redirect</span>()-&gt;<span class="tok-fn">route</span>(<span class="tok-str">'posts.show'</span>, <span class="tok-var">$post</span>);
}</code></pre>
                </div>
            </section>

            <!-- SECTION 8: STANDALONE UPLOADER -->
            <section id="component-uploader" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>📤</span> 8. Standalone Uploader: &lt;x-mediaflow::uploader&gt;</h2>
                <p class="mf-docs-p">
                    For multi-file drops, mass document uploads, or dedicated batch upload cards, use the standalone uploader component:
                </p>

                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">Blade View</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-tag">&lt;x-mediaflow::uploader</span>
    <span class="tok-attr">folder</span>=<span class="tok-str">"gallery/products"</span>
    <span class="tok-attr">collection</span>=<span class="tok-str">"product_shots"</span>
    <span class="tok-attr">accept</span>=<span class="tok-str">"image/*,application/pdf"</span>
    <span class="tok-attr">:multiple</span>=<span class="tok-str">"true"</span>
    <span class="tok-attr">:animated</span>=<span class="tok-str">"true"</span>
<span class="tok-tag">/&gt;</span></code></pre>
                </div>
            </section>

            <!-- SECTION 9: EXPLORER & PICKER COMPONENT -->
            <section id="component-manager" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>🗂️</span> 9. Explorer & Media Manager: &lt;x-mediaflow::manager&gt;</h2>
                <p class="mf-docs-p">
                    MediaFlow includes a desktop-class explorer interface featuring folder tree navigation, search, file type filters, file metadata inspector, and selection modes:
                </p>

                <h3 class="mf-docs-h3">Standalone Gallery Component (&lt;x-mediaflow-manager&gt;)</h3>
                <p class="mf-docs-p">
                    To embed only the media manager/gallery inside any custom admin view, dashboard card, or modal without any outer navigation wrappers:
                </p>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">Blade View</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-com">{{-- 1-Line Shortcut for pure gallery widget in any view --}}</span>
<span class="tok-tag">&lt;x-mediaflow-manager</span> <span class="tok-tag">/&gt;</span></code></pre>
                </div>

                <h3 class="mf-docs-h3">Asset Directives for Master Layouts</h3>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">Blade Layout</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-tag">&lt;head&gt;</span>
    <span class="tok-kw">@@mediaflowStyles</span>
<span class="tok-tag">&lt;/head&gt;</span>
<span class="tok-tag">&lt;body&gt;</span>
    <span class="tok-kw">@@yield</span>(<span class="tok-str">'content'</span>)

    <span class="tok-kw">@@mediaflowScripts</span>
<span class="tok-tag">&lt;/body&gt;</span></code></pre>
                </div>

                <h3 class="mf-docs-h3">Clean & Headerless Built-In Route Modes</h3>
                <p class="mf-docs-p">
                    By default, the route <code>/mediaflow/manager</code> is completely clean (dev navigation links to documentation and pipeline are hidden). You can also run it completely headerless for iframe modals:
                </p>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">URL Options</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-com"># 1. Completely Headerless (for iframes or embedded modals)</span>
/mediaflow/manager?clean=1

<span class="tok-com"># 2. Pure Grid Mode (hides top upload dropzone, shows only folders & files)</span>
/mediaflow/manager?no_uploader=1</code></pre>
                </div>

                <h3 class="mf-docs-h3">Modal Picker Mode with JavaScript Events</h3>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">JavaScript</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code>document.<span class="tok-fn">addEventListener</span>(<span class="tok-str">'mediaflow:selected'</span>, (<span class="tok-var">e</span>) =&gt; {
    <span class="tok-kw">const</span> media = <span class="tok-var">e</span>.detail;
    console.<span class="tok-fn">log</span>(<span class="tok-str">'Selected media ID:'</span>, media.id);
    console.<span class="tok-fn">log</span>(<span class="tok-str">'Full Delivery URL:'</span>, media.url);
    console.<span class="tok-fn">log</span>(<span class="tok-str">'Thumb WebP URL:'</span>, media.thumbnail);
});</code></pre>
                </div>
            </section>

            <!-- SECTION 9B: THEMING, FOLDER COLORS & LIGHT/DARK MODES -->
            <section id="theming-colors" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>🎨</span> 9b. Theming, Media Folder Colors & Light / Dark Modes</h2>
                <p class="mf-docs-p">
                    MediaFlow is built to seamlessly adapt to any host design system, Laravel Breeze/Jetstream/Filament dashboard, or custom CSS theme. You can customize the media picker accents, folder icon badges, and force light or dark modes in <strong>4 different ways</strong>:
                </p>

                <h3 class="mf-docs-h3">Option 1: Global Admin Dashboard Settings</h3>
                <p class="mf-docs-p">
                    Visit <code>/mediaflow/settings</code> (<strong>Appearance & System Design Inheritance</strong>) to select your preset color, custom hex code, folder color, and Light/Dark mode with real-time live preview. These settings are stored in your database and applied automatically across all views.
                </p>

                <h3 id="folder-colors" class="mf-docs-h3">Option 2: Blade Component Props</h3>
                <p class="mf-docs-p">
                    Customize individual input components or media managers directly via Blade props:
                </p>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">Blade View</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-com">{{-- 1. Custom Emerald Theme in Force Dark Mode with Ocean Blue Folders --}}</span>
<span class="tok-tag">&lt;x-mediaflow-input</span>
    <span class="tok-attr">name</span>=<span class="tok-str">"cover_image"</span>
    <span class="tok-attr">theme</span>=<span class="tok-str">"emerald"</span>
    <span class="tok-attr">mode</span>=<span class="tok-str">"dark"</span>
    <span class="tok-attr">folder-color</span>=<span class="tok-str">"#3b82f6"</span>
<span class="tok-tag">/&gt;</span>

<span class="tok-com">{{-- 2. Clean Light Mode with Violet Accents and Warm Amber Folders --}}</span>
<span class="tok-tag">&lt;x-mediaflow-input</span>
    <span class="tok-attr">name</span>=<span class="tok-str">"avatar_id"</span>
    <span class="tok-attr">theme</span>=<span class="tok-str">"violet"</span>
    <span class="tok-attr">mode</span>=<span class="tok-str">"light"</span>
    <span class="tok-attr">folder-color</span>=<span class="tok-str">"#f59e0b"</span>
<span class="tok-tag">/&gt;</span>

<span class="tok-com">{{-- 3. Standalone Media Explorer with Dark Mode & Custom Brand Hex --}}</span>
<span class="tok-tag">&lt;x-mediaflow::manager</span>
    <span class="tok-attr">theme</span>=<span class="tok-str">"custom"</span>
    <span class="tok-attr">color-mode</span>=<span class="tok-str">"dark"</span>
    <span class="tok-attr">folder-color</span>=<span class="tok-str">"#10b981"</span>
<span class="tok-tag">/&gt;</span></code></pre>
                </div>

                <h3 id="light-dark-mode" class="mf-docs-h3">Option 3: Automatic Host App Class & Attribute Inheritance</h3>
                <p class="mf-docs-p">
                    MediaFlow automatically detects your host application's dark mode without any configuration:
                </p>
                <ul class="mf-docs-list">
                    <li><strong>Tailwind CSS Dark Mode:</strong> If your <code>&lt;html&gt;</code> or <code>&lt;body&gt;</code> has <code>class="dark"</code>, MediaFlow instantly switches to dark mode.</li>
                    <li><strong>Custom Data Attributes:</strong> Supports <code>data-theme="dark"</code> (DaisyUI / theme switchers), <code>data-mf-mode="dark"</code>, or <code>data-mf-mode="light"</code>.</li>
                    <li><strong>OS / Device Preference:</strong> Automatically respects <code>@media (prefers-color-scheme: dark)</code> when in System mode.</li>
                </ul>

                <h3 id="css-variables" class="mf-docs-h3">Option 4: CSS Custom Variables (Design Tokens)</h3>
                <p class="mf-docs-p">
                    Override MediaFlow's CSS variables anywhere in your app stylesheet:
                </p>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">CSS</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-tag">:root</span> {
    <span class="tok-com">/* Primary Accent Color (Picker borders, buttons, badges) */</span>
    <span class="tok-prop">--mf-primary</span>: <span class="tok-str">#8b5cf6</span>;
    <span class="tok-prop">--mf-primary-hover</span>: <span class="tok-str">#7c3aed</span>;
    <span class="tok-prop">--mf-primary-light</span>: <span class="tok-str">rgba(139, 92, 246, 0.12)</span>;

    <span class="tok-com">/* Media Folder Icon & Background Colors */</span>
    <span class="tok-prop">--mf-folder-color</span>: <span class="tok-str">#f59e0b</span>; <span class="tok-com">/* Amber Gold */</span>
    <span class="tok-prop">--mf-folder-bg</span>: <span class="tok-str">rgba(245, 158, 11, 0.14)</span>;
}

<span class="tok-com">/* Host Dark Mode Tokens */</span>
<span class="tok-tag">html.dark</span> {
    <span class="tok-prop">--mf-bg-main</span>: <span class="tok-str">#0b0f19</span>;
    <span class="tok-prop">--mf-bg-card</span>: <span class="tok-str">#131b2e</span>;
    <span class="tok-prop">--mf-border</span>: <span class="tok-str">#1e293b</span>;
    <span class="tok-prop">--mf-text-main</span>: <span class="tok-str">#f1f5f9</span>;
}</code></pre>
                </div>
            </section>

            <!-- SECTION 10: PHP DEVELOPER API -->
            <section id="facade-api" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>⚡</span> 10. PHP Developer API & MediaFlow Facade</h2>
                <p class="mf-docs-p">
                    Interact directly with the MediaFlow engine in controllers, background queued jobs, or console commands:
                </p>

                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">PHP</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-kw">use</span> MediaFlow\Laravel\Facades\MediaFlow;

<span class="tok-com">// 1. Upload a file from HTTP Request</span>
<span class="tok-var">$media</span> = MediaFlow::<span class="tok-fn">upload</span>(<span class="tok-var">$request</span>-&gt;<span class="tok-fn">file</span>(<span class="tok-str">'photo'</span>), [
    <span class="tok-str">'disk'</span> =&gt; <span class="tok-str">'public'</span>,
    <span class="tok-str">'folder'</span> =&gt; <span class="tok-str">'avatars'</span>,
    <span class="tok-str">'collection'</span> =&gt; <span class="tok-str">'profiles'</span>,
    <span class="tok-str">'thumbnails'</span> =&gt; <span class="tok-kw">true</span>,
]);

<span class="tok-com">// 2. Create hierarchical folder</span>
<span class="tok-var">$folder</span> = MediaFlow::<span class="tok-fn">createFolder</span>(<span class="tok-str">'2026 Campaigns'</span>, <span class="tok-var">$parentFolderId</span> = <span class="tok-kw">null</span>);

<span class="tok-com">// 3. Move media to folder</span>
MediaFlow::<span class="tok-fn">move</span>(<span class="tok-var">$media</span>, <span class="tok-var">$folder</span>-&gt;id);

<span class="tok-com">// 4. Rename media display name</span>
MediaFlow::<span class="tok-fn">rename</span>(<span class="tok-var">$media</span>, <span class="tok-str">'hero-banner.webp'</span>);

<span class="tok-com">// 5. Delete file, physical disk objects & thumbnail variants</span>
MediaFlow::<span class="tok-fn">delete</span>(<span class="tok-var">$media</span>);</code></pre>
                </div>
            </section>

            <!-- SECTION 11: MODEL HELPERS & ELOQUENT -->
            <section id="model-methods" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>📦</span> 11. Media Model Helpers & Eloquent Relations</h2>
                
                <h3 id="eloquent-relation" class="mf-docs-h3">Defining Relationships in Your Models</h3>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">app/Models/Post.php</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-kw">namespace</span> App\Models;

<span class="tok-kw">use</span> Illuminate\Database\Eloquent\Model;
<span class="tok-kw">use</span> Illuminate\Database\Eloquent\Relations\BelongsTo;
<span class="tok-kw">use</span> MediaFlow\Laravel\Models\Media;

<span class="tok-kw">class</span> Post <span class="tok-kw">extends</span> Model
{
    <span class="tok-kw">protected</span> <span class="tok-var">$fillable</span> = [<span class="tok-str">'title'</span>, <span class="tok-str">'cover_media_id'</span>];

    <span class="tok-kw">public function</span> <span class="tok-fn">coverMedia</span>(): BelongsTo
    {
        <span class="tok-kw">return</span> <span class="tok-var">$this</span>-&gt;<span class="tok-fn">belongsTo</span>(Media::<span class="tok-kw">class</span>, <span class="tok-str">'cover_media_id'</span>);
    }
}</code></pre>
                </div>

                <h3 class="mf-docs-h3">Accessing Media URLs and Thumbnail Presets</h3>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">Blade View</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-com">@{{-- Full resolved delivery URL (Public disk or CDN) --}}</span>
<span class="tok-tag">&lt;img</span> <span class="tok-attr">src</span>=<span class="tok-str">"@{{ $post-&gt;coverMedia-&gt;url() }}"</span> <span class="tok-attr">alt</span>=<span class="tok-str">"@{{ $post-&gt;title }}"</span><span class="tok-tag">&gt;</span>

<span class="tok-com">@{{-- WebP Thumbnail presets: 'thumb' (200x200), 'medium' (800x800), 'large' (1600x1600) --}}</span>
<span class="tok-tag">&lt;img</span> <span class="tok-attr">src</span>=<span class="tok-str">"@{{ $post-&gt;coverMedia-&gt;thumbnailUrl('thumb') }}"</span> <span class="tok-attr">loading</span>=<span class="tok-str">"lazy"</span><span class="tok-tag">&gt;</span>
<span class="tok-tag">&lt;img</span> <span class="tok-attr">src</span>=<span class="tok-str">"@{{ $post-&gt;coverMedia-&gt;thumbnailUrl('medium') }}"</span><span class="tok-tag">&gt;</span>

<span class="tok-com">@{{-- Human formatted size --}}</span>
<span class="tok-tag">&lt;span&gt;</span>File size: @{{ $post-&gt;coverMedia-&gt;humanSize() }}<span class="tok-tag">&lt;/span&gt;</span></code></pre>
                </div>
            </section>

            <!-- SECTION 12: ON-DEMAND COMPRESSION -->
            <section id="optimization-api" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>🗜️</span> 12. On-Demand Image Optimization & Conversion</h2>
                <p class="mf-docs-p">
                    Convert any stored raster image (JPEG, PNG, WebP, GIF) to <strong>WebP</strong>, <strong>JPEG</strong> or <strong>PNG</strong> with a custom quality and optional max dimensions. Either replace the original in place or create an optimized copy. SVGs cannot be optimized. Transparent areas become white when converting to JPEG.
                </p>

                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">PHP</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-var">$result</span> = MediaFlow::<span class="tok-fn">optimize</span>(<span class="tok-var">$media</span>, [
    <span class="tok-str">'format'</span>  =&gt; <span class="tok-str">'webp'</span>,    <span class="tok-com">// 'webp', 'jpeg' (or 'jpg'), 'png'</span>
    <span class="tok-str">'quality'</span> =&gt; <span class="tok-num">80</span>,        <span class="tok-com">// 30 - 100 (HTTP endpoint limits)</span>
    <span class="tok-str">'width'</span>   =&gt; <span class="tok-num">1600</span>,      <span class="tok-com">// optional max width, aspect ratio kept</span>
    <span class="tok-str">'height'</span>  =&gt; <span class="tok-num">1600</span>,      <span class="tok-com">// optional max height</span>
    <span class="tok-str">'action'</span>  =&gt; <span class="tok-str">'copy'</span>,    <span class="tok-com">// 'replace' (default) overwrites, 'copy' creates a new Media record</span>
]);

<span class="tok-com">// Metrics are returned at the top level of the array</span>
<span class="tok-var">$result</span>[<span class="tok-str">'savings_percent'</span>]; <span class="tok-com">// e.g. 94.6</span>
<span class="tok-var">$result</span>[<span class="tok-str">'size_before'</span>], <span class="tok-var">$result</span>[<span class="tok-str">'size_after'</span>], <span class="tok-var">$result</span>[<span class="tok-str">'width'</span>], <span class="tok-var">$result</span>[<span class="tok-str">'height'</span>], <span class="tok-var">$result</span>[<span class="tok-str">'mime'</span>];
<span class="tok-var">$optimizedMedia</span> = <span class="tok-var">$result</span>[<span class="tok-str">'media'</span>]; <span class="tok-com">// updated or newly created Media model</span></code></pre>
                </div>

                <div class="mf-feature-card">
                    <div>
                        <div style="font-weight: 600; font-size: 0.95rem;">Configure default compression presets</div>
                        <div style="color: var(--mf-text-muted); font-size: 0.8125rem; margin-top: 0.2rem;">Adjust global WebP/JPEG quality sliders and theme options.</div>
                    </div>
                    <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
                        <a href="{{ route('mediaflow.settings.index') }}" style="background: var(--mf-primary); color: #fff; padding: 0.45rem 1rem; border-radius: 6px; font-size: 0.8125rem; font-weight: 600; text-decoration: none;">Open Settings Page →</a>
                        <a href="#settings-compression" style="background: var(--mf-bg-hover); color: var(--mf-text-main); border: 1px solid var(--mf-border); padding: 0.45rem 1rem; border-radius: 6px; font-size: 0.8125rem; font-weight: 600; text-decoration: none;">Explore Simulator ↓</a>
                    </div>
                </div>
            </section>

            <!-- SECTION 13: SETTINGS & COMPRESSION DASHBOARD -->
            <section id="settings-compression" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>⚙️</span> 13. Settings, Image Compression & Dynamic Theming</h2>
                <p class="mf-docs-p">
                    MediaFlow features an enterprise-grade <strong>Runtime Settings Engine</strong> (<code>MediaFlowSettingsManager</code>) that decouples configuration from code deployments. Through the built-in Settings Dashboard (<code>/mediaflow/settings</code>) or REST API, administrators can adjust image compression parameters, configure default formats (WebP, AVIF, JPEG), set max dimensions, toggle EXIF stripping, adjust cloud credentials, and switch theme accents live with instant database persistence.
                </p>

                <div class="mf-feature-card" style="margin-bottom: 2rem;">
                    <div>
                        <div style="font-weight: 600; font-size: 0.95rem;">Built-in Settings Dashboard Route</div>
                        <div style="color: var(--mf-text-muted); font-size: 0.8125rem; margin-top: 0.2rem;">
                            Included out-of-the-box in the Laravel package at <code>/mediaflow/settings</code> with CSRF protection, credential testing, and instant preview.
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <span style="font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.65rem; border-radius: 999px; background: var(--mf-primary-light); color: var(--mf-primary); border: 1px solid rgba(79, 70, 229, 0.2);">Route: /mediaflow/settings</span>
                        <span style="font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.65rem; border-radius: 999px; background: var(--mf-success-light); color: var(--mf-success); border: 1px solid var(--mf-success);">Live DB & Cache Sync</span>
                    </div>
                </div>

                <h3 id="compression-presets" class="mf-docs-h3"><span>🗜️</span> Global Compression & Format Presets</h3>
                <p class="mf-docs-p">
                    Images uploaded through <code>&lt;x-mediaflow-input&gt;</code>, the standalone uploader, or the Media Library explorer can be automatically compressed and reformatted according to your presets:
                </p>

                <div style="overflow-x: auto; margin-bottom: 1.5rem;">
                    <table class="mf-docs-table">
                        <thead>
                            <tr>
                                <th>Target Format</th>
                                <th>Default Quality</th>
                                <th>Avg. Savings</th>
                                <th>Key Benefits & Best Use Case</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span style="color: var(--mf-success); font-weight: 700;">WebP</span> (Recommended)</td>
                                <td><code>82%</code> (30 - 98%)</td>
                                <td><strong>85% - 94%</strong></td>
                                <td>Universal browser support, transparent alpha channel, ultra-small file sizes. Best for web apps.</td>
                            </tr>
                            <tr>
                                <td><span style="color: var(--mf-primary); font-weight: 700;">AVIF</span> (Next-Gen)</td>
                                <td><code>75%</code> (30 - 90%)</td>
                                <td><strong>90% - 96%</strong></td>
                                <td>State-of-the-art compression algorithm based on AV1 video codec. Unbeatable fidelity-to-byte ratio.</td>
                            </tr>
                            <tr>
                                <td><span style="color: var(--mf-warning); font-weight: 700;">JPEG</span></td>
                                <td><code>85%</code> (30 - 100%)</td>
                                <td><strong>60% - 80%</strong></td>
                                <td>Standard backwards compatibility with legacy client readers and embedded desktop software.</td>
                            </tr>
                            <tr>
                                <td><span style="color: var(--mf-text-main); font-weight: 700;">PNG</span></td>
                                <td><code>Lossless</code></td>
                                <td><strong>20% - 50%</strong></td>
                                <td>Sharp pixel-perfect lines, vector-converted graphics, diagrams, and technical schematics.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mf-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <div style="background: var(--mf-bg-card); border: 1px solid var(--mf-border); border-radius: var(--mf-radius-md); padding: 1.25rem;">
                        <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.4rem; color: var(--mf-text-main);">
                            <span>📐</span> Automatic Dimension Downscaling
                        </div>
                        <div style="font-size: 0.85rem; color: var(--mf-text-muted); line-height: 1.5;">
                            Limits maximum dimensions (e.g. <code>max_width: 2560</code>, <code>max_height: 2560</code>). High-res 48MP/8K phone camera shots are proportionally scaled down before disk storage, cutting file weight while maintaining retina sharpness.
                        </div>
                    </div>
                    <div style="background: var(--mf-bg-card); border: 1px solid var(--mf-border); border-radius: var(--mf-radius-md); padding: 1.25rem;">
                        <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.4rem; color: var(--mf-text-main);">
                            <span>🛡️</span> EXIF & GPS Metadata Stripping
                        </div>
                        <div style="font-size: 0.85rem; color: var(--mf-text-muted); line-height: 1.5;">
                            When enabled (<code>strip_exif: true</code>), geolocation latitude/longitude coordinates, camera make, lens model, and personal tracking headers are sanitized from the file header, safeguarding user privacy and trimming 15–40 KB.
                        </div>
                    </div>
                </div>

                <h3 id="compression-simulator" class="mf-docs-h3"><span>🎮</span> Interactive Settings & Compression Simulator</h3>
                <p class="mf-docs-p">
                    Test the compression engine and appearance inheritance in real time. Adjust the target format, quality slider, and theme color swatches below to see how the parameters interact and generate reactive configuration code:
                </p>

                <!-- Interactive Simulator Component -->
                <div id="mf-settings-simulator" class="mf-sim-container">
                    <div class="mf-sim-header">
                        <div class="mf-sim-title">
                            <span>⚡</span> MediaFlow Engine Simulator & Live Playground
                        </div>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <span id="mf-sim-status-pill" style="font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.65rem; border-radius: 999px; background: var(--mf-primary-light); color: var(--mf-primary);">
                                Live Interactive
                            </span>
                        </div>
                    </div>

                    <div class="mf-sim-grid">
                        <!-- Left Control Column -->
                        <div class="mf-sim-panel">
                            <div>
                                <label style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.4rem; color: var(--mf-text-main);">
                                    Target Output Format
                                </label>
                                <div class="mf-sim-pills" id="mf-sim-format-pills">
                                    <button type="button" class="mf-sim-pill active" data-format="webp">WebP (Recommended)</button>
                                    <button type="button" class="mf-sim-pill" data-format="avif">AVIF (Next-Gen)</button>
                                    <button type="button" class="mf-sim-pill" data-format="jpeg">JPEG</button>
                                    <button type="button" class="mf-sim-pill" data-format="png">PNG</button>
                                </div>
                            </div>

                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                    <label style="font-size: 0.8125rem; font-weight: 600; color: var(--mf-text-main);">
                                        Compression Quality Level
                                    </label>
                                    <span id="mf-sim-quality-val" style="font-size: 0.9rem; font-weight: 700; color: var(--mf-primary);">82%</span>
                                </div>
                                <input type="range" min="30" max="98" value="82" id="mf-sim-quality-slider" class="mf-range-slider">
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--mf-text-muted); margin-top: 0.25rem;">
                                    <span>30% (Aggressive)</span>
                                    <span>80-85% (Optimal)</span>
                                    <span>98% (Near-Lossless)</span>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                <div>
                                    <label style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--mf-text-main);">Max Width (px)</label>
                                    <input type="number" id="mf-sim-max-w" value="2560" style="width: 100%; padding: 0.45rem 0.65rem; border: 1px solid var(--mf-border); border-radius: 6px; background: var(--mf-bg-main); color: var(--mf-text-main); font-size: 0.85rem; outline: none; box-sizing: border-box;">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--mf-text-main);">Max Height (px)</label>
                                    <input type="number" id="mf-sim-max-h" value="2560" style="width: 100%; padding: 0.45rem 0.65rem; border: 1px solid var(--mf-border); border-radius: 6px; background: var(--mf-bg-main); color: var(--mf-text-main); font-size: 0.85rem; outline: none; box-sizing: border-box;">
                                </div>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer; color: var(--mf-text-main);">
                                    <input type="checkbox" id="mf-sim-strip-exif" checked style="accent-color: var(--mf-primary); cursor: pointer;">
                                    <span>Strip EXIF camera & GPS metadata</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer; color: var(--mf-text-main);">
                                    <input type="checkbox" id="mf-sim-auto-optimize" checked style="accent-color: var(--mf-primary); cursor: pointer;">
                                    <span>Auto-optimize on initial file upload</span>
                                </label>
                            </div>

                            <!-- Live Theming Accent Test -->
                            <div style="padding-top: 0.75rem; border-top: 1px solid var(--mf-border);">
                                <label style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.45rem; color: var(--mf-text-main);">
                                    🎨 Test Accent Theme Swatches (Updates Docs Live)
                                </label>
                                <div class="mf-sim-theme-swatches" id="mf-sim-theme-swatches">
                                    <button type="button" class="mf-sim-swatch active" data-theme="indigo" data-color="#4f46e5" style="background: #4f46e5;" title="Indigo Modern"></button>
                                    <button type="button" class="mf-sim-swatch" data-theme="emerald" data-color="#10b981" style="background: #10b981;" title="Emerald Fresh"></button>
                                    <button type="button" class="mf-sim-swatch" data-theme="violet" data-color="#8b5cf6" style="background: #8b5cf6;" title="Violet Royal"></button>
                                    <button type="button" class="mf-sim-swatch" data-theme="sky" data-color="#0284c7" style="background: #0284c7;" title="Sky Ocean"></button>
                                    <button type="button" class="mf-sim-swatch" data-theme="amber" data-color="#f59e0b" style="background: #f59e0b;" title="Amber Warm"></button>
                                    <button type="button" class="mf-sim-swatch" data-theme="rose" data-color="#f43f5e" style="background: #f43f5e;" title="Rose Vibrant"></button>
                                    <button type="button" class="mf-sim-swatch" data-theme="slate" data-color="#334155" style="background: #334155;" title="Slate Neutral"></button>
                                </div>
                            </div>

                            <!-- Mode Switcher -->
                            <div>
                                <label style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.45rem; color: var(--mf-text-main);">
                                    Color Mode
                                </label>
                                <div class="mf-sim-pills" id="mf-sim-mode-pills">
                                    <button type="button" class="mf-sim-pill active" data-mode="system">🌓 System</button>
                                    <button type="button" class="mf-sim-pill" data-mode="light">☀️ Light</button>
                                    <button type="button" class="mf-sim-pill" data-mode="dark">🌙 Dark</button>
                                </div>
                            </div>
                        </div>

                        <!-- Right Output / Preview Column -->
                        <div class="mf-sim-panel">
                            <!-- Metrics Box -->
                            <div class="mf-sim-metrics-box">
                                <div>
                                    <div class="mf-sim-metric-val">3.42 MB</div>
                                    <div class="mf-sim-metric-lbl">Raw Image</div>
                                </div>
                                <div>
                                    <div class="mf-sim-metric-val" id="mf-sim-opt-size" style="color: var(--mf-primary);">226 KB</div>
                                    <div class="mf-sim-metric-lbl" id="mf-sim-opt-format">WebP (82%)</div>
                                </div>
                                <div>
                                    <div class="mf-sim-metric-val"><span id="mf-sim-opt-savings" class="mf-sim-badge-saved">-93.4%</span></div>
                                    <div class="mf-sim-metric-lbl">Bandwidth Saved</div>
                                </div>
                            </div>

                            <!-- Visual Comparison Card -->
                            <div style="background: var(--mf-bg-main); border: 1px solid var(--mf-border); border-radius: var(--mf-radius-md); padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8125rem;">
                                    <span style="font-weight: 600; color: var(--mf-text-main);">Optimization Pipeline Status</span>
                                    <span id="mf-sim-pipeline-badge" style="color: var(--mf-success); font-weight: 600;">✓ Optimized & Ready</span>
                                </div>
                                <div style="height: 8px; background: var(--mf-bg-hover); border-radius: 999px; overflow: hidden; position: relative;">
                                    <div id="mf-sim-bar" style="height: 100%; width: 6.6%; background: var(--mf-success); border-radius: 999px; transition: width 0.3s ease;"></div>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--mf-text-muted);">
                                    <span>Payload: <strong id="mf-sim-ratio-text" style="color: var(--mf-text-main);">6.6% of original</strong></span>
                                    <span id="mf-sim-exif-status">EXIF: Stripped (Sanitized)</span>
                                </div>
                            </div>

                            <!-- Generated Reactive PHP Facade & Config Code -->
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                    <span style="font-size: 0.8125rem; font-weight: 600; color: var(--mf-text-main);">Reactive Code Generator</span>
                                    <span style="font-size: 0.75rem; color: var(--mf-text-muted);">Auto-updates live</span>
                                </div>
                                <div class="mf-code-container" style="margin: 0;">
                                    <div class="mf-code-header">
                                        <span class="mf-code-lang" id="mf-sim-code-lang">PHP (Config & Facade)</span>
                                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                                    </div>
                                    <pre class="mf-code-pre"><code id="mf-sim-code-output">// config/mediaflow.php
'images' => [
    'default_format' => 'webp',
    'default_quality' => 82,
    'max_width' => 2560,
    'max_height' => 2560,
    'auto_optimize' => true,
    'strip_exif' => true,
],

// PHP Facade On-Demand Execution
$result = MediaFlow::optimize(
    $media,
    format: 'webp',
    quality: 82,
    replaceOriginal: false
);
// Metrics: 226 KB (-93.4% reduction)</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="mf-docs-h3"><span>📡</span> REST API Settings Endpoints</h3>
                <p class="mf-docs-p">
                    For headless frontends, SPAs, or custom admin control panels, settings can be retrieved and updated programmatically:
                </p>

                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">JavaScript (Fetch API)</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-com">// Update compression presets and theme via JSON</span>
<span class="tok-kw">const</span> response = <span class="tok-kw">await</span> <span class="tok-fn">fetch</span>(<span class="tok-str">'/mediaflow/settings'</span>, {
    method: <span class="tok-str">'POST'</span>,
    headers: {
        <span class="tok-str">'Content-Type'</span>: <span class="tok-str">'application/json'</span>,
        <span class="tok-str">'X-CSRF-TOKEN'</span>: document.<span class="tok-fn">querySelector</span>(<span class="tok-str">'meta[name="csrf-token"]'</span>).content,
        <span class="tok-str">'Accept'</span>: <span class="tok-str">'application/json'</span>
    },
    body: JSON.<span class="tok-fn">stringify</span>({
        <span class="tok-str">'images.default_format'</span>: <span class="tok-str">'webp'</span>,
        <span class="tok-str">'images.default_quality'</span>: <span class="tok-num">85</span>,
        <span class="tok-str">'images.max_width'</span>: <span class="tok-num">2560</span>,
        <span class="tok-str">'images.strip_exif'</span>: <span class="tok-kw">true</span>,
        <span class="tok-str">'ui.theme'</span>: <span class="tok-str">'indigo'</span>,
        <span class="tok-str">'ui.mode'</span>: <span class="tok-str">'system'</span>
    })
});

<span class="tok-kw">const</span> data = <span class="tok-kw">await</span> response.<span class="tok-fn">json</span>();
console.<span class="tok-fn">log</span>(data.settings);</code></pre>
                </div>
            </section>

            <!-- SECTION 13: ACCESS CONTROL, GUARDS & ROLES -->
            <section id="guards-roles" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>🛡️</span> 14. Access Control, Guards & Roles</h2>
                <p class="mf-docs-p">
                    Protect media operations using Laravel's native authentication guards, Spatie permissions, Gates, or custom middleware:
                </p>

                <h3 class="mf-docs-h3">Configuring Authentication</h3>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">config/mediaflow.php</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-str">'auth'</span> =&gt; [
    <span class="tok-com">// Require login on all management routes (default: true)</span>
    <span class="tok-str">'enabled'</span> =&gt; env(<span class="tok-str">'MEDIAFLOW_AUTH_ENABLED'</span>, <span class="tok-kw">true</span>),

    <span class="tok-com">// Guard: 'web', 'admin', 'sanctum', ... or null for the default guard</span>
    <span class="tok-str">'guard'</span> =&gt; env(<span class="tok-str">'MEDIAFLOW_GUARD'</span>, env(<span class="tok-str">'MEDIAFLOW_AUTH_GUARD'</span>, <span class="tok-kw">null</span>)),

    <span class="tok-com">// Any one of these roles grants access (Spatie hasAnyRole/hasRole, isRole(),</span>
    <span class="tok-com">// $user-&gt;role / role_name / type / user_type, or a roles relation)</span>
    <span class="tok-str">'roles'</span> =&gt; [<span class="tok-str">'admin'</span>, <span class="tok-str">'editor'</span>],

    <span class="tok-com">// Any one of these permissions grants access (hasPermissionTo / hasPermission / can)</span>
    <span class="tok-str">'permissions'</span> =&gt; [<span class="tok-str">'manage-media'</span>],

    <span class="tok-com">// Optional Gate ability checked with Gate::forUser($user)-&gt;allows()</span>
    <span class="tok-str">'gate'</span> =&gt; env(<span class="tok-str">'MEDIAFLOW_GATE'</span>),

    <span class="tok-com">// Extra middleware appended to every MediaFlow route, e.g. ['verified', 'throttle:60,1']</span>
    <span class="tok-str">'custom_middleware'</span> =&gt; [],

    <span class="tok-com">// false (default): config/.env is a floor the Settings page cannot weaken</span>
    <span class="tok-str">'allow_ui_override'</span> =&gt; env(<span class="tok-str">'MEDIAFLOW_AUTH_ALLOW_UI_OVERRIDE'</span>, <span class="tok-kw">false</span>),
],</code></pre>
                </div>
                <div class="mf-docs-callout warning">
                    <div>⚠️</div>
                    <div><strong>Authentication is on by default.</strong> Guests get <code>401</code> (JSON) or are redirected to your <code>login</code> route. For a local demo without login set <code>MEDIAFLOW_AUTH_ENABLED=false</code> — never in production, because anyone could then upload, delete media and read or change your storage settings. The public documentation page, the CSRF-token endpoint and signed delivery URLs are always reachable.</div>
                </div>

                <h3 id="spatie-gates" class="mf-docs-h3">Spatie Permissions, Gates &amp; Route Middleware Parameters</h3>
                <p class="mf-docs-p">The middleware is also registered as <code>mediaflow.auth</code>, so you can protect your own routes with the same rules. Parameters override the config for that route:</p>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">routes/web.php</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code>Route::<span class="tok-fn">middleware</span>(<span class="tok-str">'mediaflow.auth:guard=admin,roles=admin|editor'</span>)-&gt;<span class="tok-fn">group</span>(<span class="tok-kw">function</span> () {
    <span class="tok-com">// ...your media-related routes</span>
});

<span class="tok-com">// Gate example (AppServiceProvider::boot) + MEDIAFLOW_GATE=manage-media</span>
Gate::<span class="tok-fn">define</span>(<span class="tok-str">'manage-media'</span>, <span class="tok-kw">fn</span> (<span class="tok-var">$user</span>) =&gt; <span class="tok-var">$user</span>-&gt;is_admin);</code></pre>
                </div>

                <h3 class="mf-docs-h3">Settings Page vs. Config</h3>
                <p class="mf-docs-p">Access rules saved on <code>/mediaflow/settings</code> are stored in the database. By default they can only <em>add</em> restrictions: a saved "auth disabled" never overrides <code>MEDIAFLOW_AUTH_ENABLED=true</code>, and config roles/permissions/gate/guard win over saved ones. Set <code>MEDIAFLOW_AUTH_ALLOW_UI_OVERRIDE=true</code> if you want the Settings page to be the single source of truth.</p>

                <h3 id="user-scoping" class="mf-docs-h3">Per-User Media Scoping</h3>
                <p class="mf-docs-p">Set <code>MEDIAFLOW_USER_SCOPED=true</code> (or <code>scope.user_scoped</code>) to give every user a private library. A global Eloquent scope (<code>MediaFlow\Laravel\Models\Scopes\OwnerScope</code>) limits <code>Media</code> and <code>MediaFolder</code> queries to rows whose <code>owner_id</code> matches the logged-in user, so listing, moving, optimizing and deleting other users' files returns <code>404</code>. Console commands, queued jobs and signed delivery URLs are not filtered. Bypass it explicitly with <code>Media::withoutGlobalScope(OwnerScope::class)</code>.</p>
                <div class="mf-docs-callout info">
                    <div>ℹ️</div>
                    <div>When you accept a media ID from a form (for example <code>cover_media_id</code>), validate that the user may use it. With scoping on, <code>Media::findOrFail($id)</code> already enforces ownership; the plain <code>exists:mediaflow_media,id</code> rule does not.</div>
                </div>

                <h3 id="closure-auth" class="mf-docs-h3">Custom Closure Authorization</h3>
                <p class="mf-docs-p">Register a closure in <code>AppServiceProvider</code>. It runs after the authentication check and receives <code>($user, $request)</code>:</p>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">app/Providers/AppServiceProvider.php</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-kw">use</span> MediaFlow\Laravel\Facades\MediaFlow;

<span class="tok-kw">public function</span> <span class="tok-fn">boot</span>(): void
{
    <span class="tok-com">// Receives the resolved user (null for guests) and the current request</span>
    MediaFlow::<span class="tok-fn">authorize</span>(<span class="tok-kw">function</span> (<span class="tok-var">$user</span>, <span class="tok-var">$request</span>) {
        <span class="tok-kw">return</span> <span class="tok-var">$user</span>?->is_admin || <span class="tok-var">$user</span>?->can(<span class="tok-str">'access-media'</span>);
    });
}</code></pre>
                </div>
            </section>

            <!-- SECTION 14: CLOUD STORAGE & CDN -->
            <section id="cloud-providers" class="mf-docs-section">
                <span id="local-s3" style="display:none;"></span>
                <h2 class="mf-docs-h2"><span>☁️</span> 15. 10 Cloud Storage & CDN Provider Presets</h2>
                <p class="mf-docs-p">
                    MediaFlow includes built-in driver auto-configuration for <strong>10 major cloud storage and CDN providers</strong>. You never need to write manual S3 endpoint calculations or path-style configurations: MediaFlow dynamically bootstraps the <code>mediaflow_cloud</code> disk at runtime based on your selected preset.
                </p>

                <div class="mf-docs-callout info">
                    <div>⚡</div>
                    <div>All 10 presets use Laravel's S3 driver (<code>league/flysystem-aws-s3-v3</code>), which is <strong>bundled directly inside MediaFlow</strong> as a standard dependency. Cloudflare R2, AWS S3, MinIO, Wasabi, Backblaze B2, and DigitalOcean Spaces work immediately out-of-the-box with zero extra installation commands!</div>
                </div>

                <h3 id="quick-3field-setup" class="mf-docs-h3">3-Field Quick Setup: Key, Secret & Public URL</h3>
                <p class="mf-docs-p">
                    Just provide your credentials and bucket: MediaFlow handles regional endpoints, virtual-host vs. path-style routing, and public delivery formatting automatically:
                </p>

                <div class="mf-docs-table-wrapper">
                    <table class="mf-docs-table">
                        <thead>
                            <tr>
                                <th>Provider</th>
                                <th>Protocol / Driver</th>
                                <th>Default Region</th>
                                <th>Key Field</th>
                                <th>Secret Field</th>
                                <th>Bucket Field</th>
                                <th>Recommended Public URL / CDN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>⚡ Cloudflare R2</strong></td>
                                <td>S3 API (Zero Egress)</td>
                                <td><code>auto</code></td>
                                <td>R2 Access Key ID</td>
                                <td>R2 Secret Access Key</td>
                                <td>R2 Bucket Name</td>
                                <td><code>https://cdn.yourdomain.com</code></td>
                            </tr>
                            <tr>
                                <td><strong>📦 AWS S3</strong></td>
                                <td>AWS S3 + CloudFront</td>
                                <td><code>us-east-1</code></td>
                                <td>AWS Access Key ID</td>
                                <td>AWS Secret Key</td>
                                <td>S3 Bucket Name</td>
                                <td><code>https://xxxx.cloudfront.net</code></td>
                            </tr>
                            <tr>
                                <td><strong>🌐 Contabo</strong></td>
                                <td>Path-Style S3</td>
                                <td><code>eu2</code></td>
                                <td>Contabo Access Key</td>
                                <td>Contabo Secret Key</td>
                                <td>Contabo Bucket Name</td>
                                <td><code>https://eu2.contabostorage.com/{bucket}</code></td>
                            </tr>
                            <tr>
                                <td><strong>🌊 DigitalOcean Spaces</strong></td>
                                <td>Spaces Edge CDN</td>
                                <td><code>nyc3</code></td>
                                <td>Spaces Access Key</td>
                                <td>Spaces Secret Key</td>
                                <td>Space Name</td>
                                <td><code>https://{bucket}.nyc3.cdn.digitaloceanspaces.com</code></td>
                            </tr>
                            <tr>
                                <td><strong>🔥 Wasabi</strong></td>
                                <td>Hot Cloud S3</td>
                                <td><code>us-east-1</code></td>
                                <td>Wasabi Access Key</td>
                                <td>Wasabi Secret Key</td>
                                <td>Wasabi Bucket Name</td>
                                <td><code>https://s3.us-east-1.wasabisys.com/{bucket}</code></td>
                            </tr>
                            <tr>
                                <td><strong>💾 Backblaze B2</strong></td>
                                <td>B2 S3-Compatible</td>
                                <td><code>us-west-004</code></td>
                                <td>keyID</td>
                                <td>applicationKey</td>
                                <td>B2 Bucket Name</td>
                                <td><code>https://f000.backblazeb2.com/file/{bucket}</code></td>
                            </tr>
                            <tr>
                                <td><strong>☁️ Google Cloud Storage</strong></td>
                                <td>GCS S3 Interop</td>
                                <td><code>auto</code></td>
                                <td>HMAC Access ID</td>
                                <td>HMAC Secret Key</td>
                                <td>GCS Bucket Name</td>
                                <td><code>https://storage.googleapis.com/{bucket}</code></td>
                            </tr>
                            <tr>
                                <td><strong>🔷 Azure Blob Storage</strong></td>
                                <td>Azure Blob API</td>
                                <td><code>auto</code></td>
                                <td>Storage Account Name</td>
                                <td>Account Access Key</td>
                                <td>Container Name</td>
                                <td><code>https://{account}.blob.core.windows.net/{container}</code></td>
                            </tr>
                            <tr>
                                <td><strong>🎨 Cloudinary</strong></td>
                                <td>Media CDN</td>
                                <td><code>global</code></td>
                                <td>Cloudinary API Key</td>
                                <td>Cloudinary API Secret</td>
                                <td>Cloud Name</td>
                                <td><code>https://res.cloudinary.com/{cloud_name}/image/upload</code></td>
                            </tr>
                            <tr>
                                <td><strong>🦙 MinIO</strong></td>
                                <td>Self-Hosted S3</td>
                                <td><code>us-east-1</code></td>
                                <td>Root User / Key</td>
                                <td>Root Pass / Secret</td>
                                <td>MinIO Bucket Name</td>
                                <td><code>http://localhost:9000/{bucket}</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 id="live-provider-testing" class="mf-docs-h3">Live Connection Probing (Zero Risk)</h3>
                <p class="mf-docs-p">
                    MediaFlow includes an active credential probe directly inside the Settings page (<code>/mediaflow/settings</code>) and via REST endpoint:
                </p>
                <div class="mf-docs-callout">
                    <strong>How the probe works:</strong> MediaFlow instantiates an isolated ephemeral memory disk, uploads a tiny test file (<code>.mediaflow-probe-[uuid].tmp</code>), immediately reads and verifies it, deletes the file, and returns the measured millisecond round-trip latency. All secret credentials are encrypted in transmission and scrubbed/redacted from error outputs.
                </div>

                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">JavaScript / REST API Probe</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-kw">const</span> res = <span class="tok-kw">await</span> <span class="tok-fn">fetch</span>(<span class="tok-str">'/mediaflow/settings/test-provider'</span>, {
    method: <span class="tok-str">'POST'</span>,
    headers: {
        <span class="tok-str">'Content-Type'</span>: <span class="tok-str">'application/json'</span>,
        <span class="tok-str">'X-CSRF-TOKEN'</span>: document.<span class="tok-fn">querySelector</span>(<span class="tok-str">'meta[name="csrf-token"]'</span>).content
    },
    body: JSON.<span class="tok-fn">stringify</span>({
        provider: <span class="tok-str">'cloudflare_r2'</span>,
        account_id: <span class="tok-str">'your_32_character_account_id'</span>,
        key: <span class="tok-str">'your_r2_access_key_id'</span>,
        secret: <span class="tok-str">'your_r2_secret_access_key'</span>,
        bucket: <span class="tok-str">'my-production-bucket'</span>,
        endpoint: <span class="tok-str">'https://&lt;account_id&gt;.r2.cloudflarestorage.com'</span>,
        public_url: <span class="tok-str">'https://cdn.example.com'</span>
    })
});

<span class="tok-kw">const</span> result = <span class="tok-kw">await</span> res.<span class="tok-fn">json</span>();
console.<span class="tok-fn">log</span>(result); <span class="tok-com">// { success: true, latency_ms: 142.5, message: "✓ Connected!" }</span></code></pre>
                </div>

                <h3 class="mf-docs-h3">Copy-Paste .env Configurations</h3>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">.env (Pick Any Provider)</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-com"># Set Default Disk to MediaFlow Cloud</span>
MEDIAFLOW_DISK=mediaflow_cloud

<span class="tok-com"># Example 1: Cloudflare R2 (Recommended for zero egress fees)</span>
MEDIAFLOW_PROVIDER=cloudflare_r2
MEDIAFLOW_PROVIDER_ACCOUNT_ID=your_32_character_account_id
# (or CLOUDFLARE_ACCOUNT_ID=your_32_character_account_id)
MEDIAFLOW_PROVIDER_KEY=your_r2_access_key_id
MEDIAFLOW_PROVIDER_SECRET=your_r2_secret_access_key
MEDIAFLOW_PROVIDER_BUCKET=my-r2-bucket
MEDIAFLOW_PROVIDER_PUBLIC_URL=https://cdn.example.com
MEDIAFLOW_PROVIDER_ENDPOINT=https://&lt;account_id&gt;.r2.cloudflarestorage.com

<span class="tok-com"># Example 2: Contabo Object Storage (European High-Capacity S3)</span>
# MEDIAFLOW_PROVIDER=contabo
# MEDIAFLOW_PROVIDER_KEY=your_contabo_key
# MEDIAFLOW_PROVIDER_SECRET=your_contabo_secret
# MEDIAFLOW_PROVIDER_BUCKET=my-contabo-bucket
# MEDIAFLOW_PROVIDER_PUBLIC_URL=https://eu2.contabostorage.com/my-contabo-bucket
# MEDIAFLOW_PROVIDER_REGION=eu2

<span class="tok-com"># Example 3: MinIO Local / Docker S3</span>
# MEDIAFLOW_PROVIDER=minio
# MEDIAFLOW_PROVIDER_KEY=minioadmin
# MEDIAFLOW_PROVIDER_SECRET=minioadmin
# MEDIAFLOW_PROVIDER_BUCKET=my-minio-bucket
# MEDIAFLOW_PROVIDER_ENDPOINT=http://localhost:9000
# MEDIAFLOW_PROVIDER_PUBLIC_URL=http://localhost:9000/my-minio-bucket</code></pre>
                </div>

                <h3 id="cdn-delivery" class="mf-docs-h3">Custom CDN Domain Resolver</h3>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">.env</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code>MEDIAFLOW_DELIVERY_DRIVER=cdn
MEDIAFLOW_CDN_URL=https://media-cdn.yourdomain.com</code></pre>
                </div>

                <h3 id="signed-urls" class="mf-docs-h3">Time-Limited Signed Streaming URLs</h3>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">PHP</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-com">// Upload as private (goes to MEDIAFLOW_PRIVATE_DISK when set)</span>
<span class="tok-var">$media</span> = MediaFlow::<span class="tok-fn">upload</span>(<span class="tok-var">$file</span>, [<span class="tok-str">'visibility'</span> =&gt; <span class="tok-str">'private'</span>]);

<span class="tok-com">// Expiring URL: native S3 pre-signed URL, or the signed /mediaflow/delivery/{id} stream for local disks</span>
<span class="tok-var">$privateUrl</span> = <span class="tok-var">$media</span>-&gt;<span class="tok-fn">temporaryUrl</span>(<span class="tok-num">30</span>); <span class="tok-com">// minutes</span>

<span class="tok-com">// url() and thumbnailUrl() return signed URLs automatically for private media</span>
<span class="tok-var">$thumb</span> = <span class="tok-var">$media</span>-&gt;<span class="tok-fn">thumbnailUrl</span>(<span class="tok-str">'thumb'</span>); <span class="tok-com">// .../delivery/12?variant=thumb&amp;expires=...&amp;signature=...</span></code></pre>
                </div>
                <div class="mf-docs-callout warning">
                    <div>⚠️</div>
                    <div>Private files stored on the <code>public</code> disk are still reachable through the <code>storage:link</code> symlink by anyone who learns the file path. Set <code>MEDIAFLOW_PRIVATE_DISK=local</code> (or a private bucket) so private uploads never land in a public directory. The delivery stream serves only images/video/audio inline; everything else (including SVG and PDF) is sent as a download with a sandboxing CSP.</div>
                </div>
            </section>

            <!-- SECTION 15: 3D LIFECYCLE VISUALIZER -->
            <section id="visualizer-lifecycle" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>✨</span> 16. 3D Upload Lifecycle & Pipeline Visualizer</h2>
                <p class="mf-docs-p">
                    MediaFlow features a real-time hardware-accelerated 3D isometric simulation of the entire upload process across 5 operational stations:
                </p>

                <div class="mf-docs-table-wrapper">
                    <table class="mf-docs-table">
                        <thead>
                            <tr>
                                <th>Station</th>
                                <th>Stage Name</th>
                                <th>Operational Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>1</strong></td>
                                <td>Client File Pick & Buffer</td>
                                <td>Acquires raw byte stream from OS file picker or drag & drop event.</td>
                            </tr>
                            <tr>
                                <td><strong>2</strong></td>
                                <td>Quarantine Temp Staging</td>
                                <td>Places bytes in quarantined disk buffer, validates real MIME header & size limits.</td>
                            </tr>
                            <tr>
                                <td><strong>3</strong></td>
                                <td>UUID Renaming & Catalog</td>
                                <td>Generates collision-proof UUID v4 hash and creates catalog database entry.</td>
                            </tr>
                            <tr>
                                <td><strong>4</strong></td>
                                <td>GD / Imagick Variant Processing</td>
                                <td>Strips EXIF metadata, extracts dimensions, and generates WebP thumbnails.</td>
                            </tr>
                            <tr>
                                <td><strong>5</strong></td>
                                <td>Storage Commit & CDN Edge</td>
                                <td>Syncs files to final storage (Local/S3) and resolves edge CDN delivery URLs.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 5-Stage Lifecycle Diagram Illustration -->
                <div class="mf-docs-diagram-card" style="margin: 1.5rem 0; background: var(--mf-bg-card); border: 1px solid var(--mf-border); border-radius: var(--mf-radius-lg); padding: 1.25rem; box-shadow: var(--mf-shadow-sm); overflow: hidden;">
                    <div style="font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--mf-primary); margin-bottom: 0.85rem; display: flex; align-items: center; justify-content: space-between;">
                        <span style="display: flex; align-items: center; gap: 0.5rem;"><span>⚡</span> 5-Stage Processing Pipeline Flow</span>
                        <a href="{{ asset('vendor/mediaflow/img/mediaflow_upload_lifecycle.svg') }}" target="_blank" style="font-size: 0.75rem; color: var(--mf-text-muted); text-decoration: none; font-weight: 500;">Open full size ↗</a>
                    </div>
                    <div style="border-radius: var(--mf-radius-md); overflow: hidden; background: #ffffff; padding: 1rem; border: 1px solid rgba(0,0,0,0.06);">
                        <img src="{{ asset('vendor/mediaflow/img/mediaflow_upload_lifecycle.svg') }}" alt="MediaFlow 5-Stage Upload Lifecycle" style="width: 100%; height: auto; max-width: 100%; display: block; border-radius: 6px;" onerror="this.onerror=null; this.src='https://dwivedianuj9118.github.io/mediaflow-laravel/mediaflow_upload_lifecycle.svg';">
                    </div>
                    <div style="font-size: 0.75rem; color: var(--mf-text-muted); margin-top: 0.65rem; text-align: right;">
                        Quarantine staging ➔ Validation ➔ GD/Imagick WebP variants ➔ Storage commit ➔ Delivery URL
                    </div>
                </div>

                <div class="mf-feature-card">
                    <div>
                        <div style="font-weight: 600; font-size: 0.95rem;">Experience the 3D Engine in 360° Perspective</div>
                        <div style="color: var(--mf-text-muted); font-size: 0.8125rem; margin-top: 0.2rem;">Watch laser conduits, mouse parallax tilt, and live upload animation.</div>
                    </div>
                    <a href="{{ route('mediaflow.pipeline') }}" style="background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%); color: #fff; padding: 0.45rem 1rem; border-radius: 6px; font-size: 0.8125rem; font-weight: 600; text-decoration: none;">Launch 3D Visualizer →</a>
                </div>
            <div id="upload-modal"></div>
            </section>

            <!-- SECTION 16: REST API ENDPOINTS -->
            <section id="api-endpoints" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>🔌</span> 17. REST API Endpoints Reference</h2>
                <p class="mf-docs-p">All routes use the <code>MEDIAFLOW_ROUTE_PREFIX</code> (default <code>mediaflow</code>), the <code>web</code> middleware (session + CSRF) and the access-control middleware. Send <code>X-CSRF-TOKEN</code> and <code>Accept: application/json</code> from JavaScript. Route names are prefixed with <code>mediaflow.</code> (e.g. <code>route('mediaflow.upload')</code>).</p>
                <div class="mf-docs-table-wrapper">
                    <table class="mf-docs-table">
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>Endpoint</th>
                                <th>Parameters</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>POST</code></td>
                                <td><code>/mediaflow/upload</code></td>
                                <td><code>file</code>, <code>folder</code> (ID, name or path like <code>blog/covers</code>), <code>folder_id</code>, <code>collection</code>, <code>visibility</code> (public|private), <code>thumbnails</code> (bool)</td>
                                <td>Uploads a file and generates thumbnail variants. Returns <code>media</code>.</td>
                            </tr>
                            <tr>
                                <td><code>GET</code></td>
                                <td><code>/mediaflow/status/{id}</code></td>
                                <td>—</td>
                                <td>Upload status (<code>status</code>, <code>is_ready</code>, <code>error_message</code>, <code>url</code>).</td>
                            </tr>
                            <tr>
                                <td><code>GET</code></td>
                                <td><code>/mediaflow/manager</code></td>
                                <td><code>folder_id</code>, <code>search</code>, <code>type</code> (image|video|document), <code>collection</code>, <code>sort</code> (created_at|original_name|size_bytes), <code>order</code>, <code>page</code></td>
                                <td>HTML page, or JSON (<code>folders</code>, <code>media</code>, <code>breadcrumbs</code>, <code>pagination</code>) when <code>Accept: application/json</code> / AJAX.</td>
                            </tr>
                            <tr>
                                <td><code>GET</code></td>
                                <td><code>/mediaflow/media/{id}</code></td>
                                <td>—</td>
                                <td>Single media item with variants.</td>
                            </tr>
                            <tr>
                                <td><code>PATCH</code></td>
                                <td><code>/mediaflow/media/{id}</code></td>
                                <td><code>name</code>, <code>folder_id</code></td>
                                <td>Rename (display name only) and/or move a media item.</td>
                            </tr>
                            <tr>
                                <td><code>DELETE</code></td>
                                <td><code>/mediaflow/media/{id}</code></td>
                                <td>—</td>
                                <td>Deletes the record (soft delete) and removes the original and variant files from storage.</td>
                            </tr>
                            <tr>
                                <td><code>POST</code></td>
                                <td><code>/mediaflow/media/{id}/optimize</code></td>
                                <td><code>format</code> (webp|jpeg|jpg|png), <code>quality</code> (30-100), <code>width</code>, <code>height</code>, <code>action</code> (replace|copy)</td>
                                <td>Re-encodes/resizes a raster image and returns savings metrics.</td>
                            </tr>
                            <tr>
                                <td><code>POST</code></td>
                                <td><code>/mediaflow/media/bulk</code></td>
                                <td><code>action</code> (delete|move), <code>ids[]</code> (max 500), <code>target_folder_id</code></td>
                                <td>Bulk delete or move.</td>
                            </tr>
                            <tr>
                                <td><code>GET</code></td>
                                <td><code>/mediaflow/folders</code></td>
                                <td><code>parent_id</code></td>
                                <td>Lists child folders with media/children counts.</td>
                            </tr>
                            <tr>
                                <td><code>POST</code></td>
                                <td><code>/mediaflow/folders</code></td>
                                <td><code>name</code>, <code>parent_id</code></td>
                                <td>Creates a folder (names are unique per parent).</td>
                            </tr>
                            <tr>
                                <td><code>PATCH</code></td>
                                <td><code>/mediaflow/folders/{id}</code></td>
                                <td><code>name</code>, <code>parent_id</code></td>
                                <td>Renames or moves a folder; descendant paths are updated. Moving into its own subtree is rejected.</td>
                            </tr>
                            <tr>
                                <td><code>DELETE</code></td>
                                <td><code>/mediaflow/folders/{id}</code></td>
                                <td>—</td>
                                <td>Deletes a folder; its files and subfolders move up to the parent.</td>
                            </tr>
                            <tr>
                                <td><code>GET</code></td>
                                <td><code>/mediaflow/settings</code></td>
                                <td>—</td>
                                <td>Settings page, or JSON settings (credentials masked) + provider list.</td>
                            </tr>
                            <tr>
                                <td><code>POST</code></td>
                                <td><code>/mediaflow/settings</code></td>
                                <td>Whitelisted keys such as <code>images.default_quality</code>, <code>ui.theme</code>, <code>ui.folder_color</code>, <code>auth.roles</code>, <code>provider.*</code></td>
                                <td>Saves settings. Unknown keys are ignored; invalid values return <code>422</code>. Sending the masked secret keeps the stored one.</td>
                            </tr>
                            <tr>
                                <td><code>POST</code></td>
                                <td><code>/mediaflow/settings/reset</code></td>
                                <td>—</td>
                                <td>Deletes all saved settings (back to config defaults).</td>
                            </tr>
                            <tr>
                                <td><code>POST</code></td>
                                <td><code>/mediaflow/settings/test-provider</code></td>
                                <td><code>name</code>, <code>key</code>, <code>secret</code>, <code>bucket</code>, <code>region</code>, <code>endpoint</code>, <code>public_url</code></td>
                                <td>Write/read/delete probe against a cloud bucket. Metadata/link-local endpoints are refused.</td>
                            </tr>
                            <tr>
                                <td><code>GET</code></td>
                                <td><code>/mediaflow/settings/providers</code></td>
                                <td>—</td>
                                <td>Metadata for the 10 provider presets.</td>
                            </tr>
                            <tr>
                                <td><code>GET</code></td>
                                <td><code>/mediaflow/diagnostics/info</code></td>
                                <td>—</td>
                                <td>PHP/Laravel versions, GD/Imagick support and active storage config.</td>
                            </tr>
                            <tr>
                                <td><code>POST</code></td>
                                <td><code>/mediaflow/diagnostics/test</code></td>
                                <td><code>disk</code> (must exist in filesystems.php)</td>
                                <td>Write/read/delete latency probe on a disk.</td>
                            </tr>
                            <tr>
                                <td><code>GET</code></td>
                                <td><code>/mediaflow/csrf-token</code></td>
                                <td>—</td>
                                <td>Fresh CSRF token for long-lived pages (public).</td>
                            </tr>
                            <tr>
                                <td><code>GET</code></td>
                                <td><code>/mediaflow/delivery/{media}</code></td>
                                <td><code>variant</code>, <code>expires</code>, <code>signature</code></td>
                                <td>Signed streaming of private files (public, signature required).</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- SECTION 17: DIAGNOSTICS & HEALTH PROBES -->
            <section id="storage-probes" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>🛠️</span> 18. Health Diagnostics & Storage Latency Probes</h2>
                <p class="mf-docs-p">
                    MediaFlow includes an automated storage health probe that writes a temporary probe file, verifies byte contents, checks URL resolution, and records exact round-trip latency in milliseconds without exposing credentials:
                </p>

                <div class="mf-feature-card">
                    <div>
                        <div style="font-weight: 600; font-size: 0.95rem;">Test storage disk and inspect environment</div>
                        <div style="color: var(--mf-text-muted); font-size: 0.8125rem; margin-top: 0.2rem;">Verify PHP extensions, directory permissions, and disk latency.</div>
                    </div>
                    <a href="{{ route('mediaflow.diagnostics.view') }}" style="background: var(--mf-primary); color: #fff; padding: 0.45rem 1rem; border-radius: 6px; font-size: 0.8125rem; font-weight: 600; text-decoration: none;">Run Storage Health Probe →</a>
                </div>
            </section>

            <!-- SECTION 18: AUTHOR & CREDITS -->
            <!-- SECTION: EVENTS & HOOKS -->
            <section id="events-hooks" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>📣</span> Events, Hooks &amp; JavaScript Events</h2>
                <p class="mf-docs-p">React to the media lifecycle from your own listeners (e.g. update a CDN cache, notify users, attach media to a model):</p>
                <div class="mf-docs-table-wrapper">
                    <table class="mf-docs-table">
                        <thead><tr><th>Event</th><th>Payload</th><th>Fired when</th></tr></thead>
                        <tbody>
                            <tr><td><code>MediaFlow\Laravel\Events\MediaReady</code></td><td><code>$event-&gt;media</code></td><td>Original and variants are stored and the status is <code>ready</code>.</td></tr>
                            <tr><td><code>MediaFlow\Laravel\Events\MediaFailed</code></td><td><code>$event-&gt;media</code>, <code>$event-&gt;errorMessage</code></td><td>The upload pipeline failed (partially written files are cleaned up).</td></tr>
                            <tr><td><code>MediaFlow\Laravel\Events\MediaDeleted</code></td><td><code>$event-&gt;media</code></td><td>A media item was deleted via the API or <code>MediaFlow::delete()</code>.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">app/Providers/AppServiceProvider.php</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-kw">use</span> Illuminate\Support\Facades\Event;
<span class="tok-kw">use</span> MediaFlow\Laravel\Events\MediaReady;

Event::<span class="tok-fn">listen</span>(MediaReady::<span class="tok-kw">class</span>, <span class="tok-kw">function</span> (MediaReady <span class="tok-var">$event</span>) {
    logger()-&gt;<span class="tok-fn">info</span>(<span class="tok-str">'Media ready'</span>, [<span class="tok-str">'id'</span> =&gt; <span class="tok-var">$event</span>-&gt;media-&gt;id]);
});</code></pre>
                </div>
                <p class="mf-docs-p">The browser components dispatch DOM events you can listen to:</p>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">JavaScript</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-com">// Fired on the component (bubbles) and on document after each successful upload</span>
document.<span class="tok-fn">addEventListener</span>(<span class="tok-str">'mediaflow:uploaded'</span>, (e) =&gt; console.<span class="tok-fn">log</span>(e.detail.media));

<span class="tok-com">// Fired by the manager when the user opens another folder</span>
document.<span class="tok-fn">addEventListener</span>(<span class="tok-str">'mediaflow:folder-changed'</span>, (e) =&gt; console.<span class="tok-fn">log</span>(e.detail.folderId, e.detail.folderName));</code></pre>
                </div>
            </section>

            <!-- SECTION: ARTISAN COMMANDS -->
            <section id="artisan-commands" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>🧰</span> Artisan Commands &amp; Scheduling</h2>
                <div class="mf-docs-table-wrapper">
                    <table class="mf-docs-table">
                        <thead><tr><th>Command</th><th>Purpose</th></tr></thead>
                        <tbody>
                            <tr><td><code>php artisan mediaflow:clean-temp [--dry-run]</code></td><td>Deletes expired temporary upload sessions and their quarantine files.</td></tr>
                            <tr><td><code>php artisan mediaflow:verify-storage [--disk=public]</code></td><td>Checks that every media record still has its file in storage; exits non-zero when files are missing (useful in CI or monitoring).</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">routes/console.php</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-kw">use</span> Illuminate\Support\Facades\Schedule;

Schedule::<span class="tok-fn">command</span>(<span class="tok-str">'mediaflow:clean-temp'</span>)-&gt;<span class="tok-fn">hourly</span>();
Schedule::<span class="tok-fn">command</span>(<span class="tok-str">'mediaflow:verify-storage'</span>)-&gt;<span class="tok-fn">weekly</span>();</code></pre>
                </div>
                <p class="mf-docs-p">Publish tags: <code>mediaflow-config</code>, <code>mediaflow-migrations</code>, <code>mediaflow-assets</code> and <code>mediaflow-views</code> (to customise the Blade views in <code>resources/views/vendor/mediaflow</code>). Re-run <code>php artisan vendor:publish --tag=mediaflow-assets --force</code> after every package update so the published CSS/JS match the PHP code.</p>
            </section>

            <!-- SECTION: EXTENDING -->
            <section id="extending" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>🔧</span> Extending: Custom Storage Adapters &amp; Image Processors</h2>
                <p class="mf-docs-p">Disks with driver <code>s3</code> use <code>S3StorageAdapter</code>; every other driver uses <code>LocalStorageAdapter</code> (which works with any Flysystem disk). Register your own implementation of <code>MediaFlow\Laravel\Contracts\StorageAdapter</code> for a disk, or swap the image engine by binding <code>ImageProcessor</code>:</p>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">app/Providers/AppServiceProvider.php</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code><span class="tok-kw">use</span> MediaFlow\Laravel\Contracts\ImageProcessor;
<span class="tok-kw">use</span> MediaFlow\Laravel\Storage\StorageAdapterRegistry;

<span class="tok-kw">public function</span> <span class="tok-fn">register</span>(): void
{
    <span class="tok-var">$this</span>-&gt;app-&gt;<span class="tok-fn">singleton</span>(ImageProcessor::<span class="tok-kw">class</span>, \App\Media\ImagickProcessor::<span class="tok-kw">class</span>);
}

<span class="tok-kw">public function</span> <span class="tok-fn">boot</span>(StorageAdapterRegistry <span class="tok-var">$registry</span>): void
{
    <span class="tok-var">$registry</span>-&gt;<span class="tok-fn">registerAdapter</span>(<span class="tok-str">'gcs'</span>, <span class="tok-kw">new</span> \App\Media\GcsStorageAdapter(<span class="tok-str">'gcs'</span>));
}</code></pre>
                </div>
                <p class="mf-docs-p">Also useful: <code>MediaFlow::disk($name)</code> returns the adapter, <code>MediaFlow::delivery()</code> the URL resolver, and <code>MediaFlow::testStorageConnection($disk)</code> runs the probe from code.</p>
            </section>

            <!-- SECTION: SECURITY CHECKLIST -->
            <section id="security-checklist" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>🔐</span> Production &amp; Security Checklist</h2>
                <ul class="mf-docs-p" style="padding-left: 1.25rem; line-height: 1.9;">
                    <li>Keep <code>MEDIAFLOW_AUTH_ENABLED=true</code> and restrict access with roles, permissions, a Gate or <code>MediaFlow::authorize()</code>. The Settings and Diagnostics pages can change storage credentials, so limit them to administrators.</li>
                    <li>Turn on <code>MEDIAFLOW_USER_SCOPED=true</code> if non-admin users share one library, and validate submitted media IDs against the current user.</li>
                    <li>Store private uploads on a non-public disk (<code>MEDIAFLOW_PRIVATE_DISK</code>).</li>
                    <li>Add rate limiting, e.g. <code>MEDIAFLOW_CUSTOM_MIDDLEWARE=throttle:60</code>.</li>
                    <li>Keep <code>allowed_mimes</code> as narrow as your app needs; leave <code>sanitize_svg</code> on, or remove <code>image/svg+xml</code> if you do not need SVG.</li>
                    <li>Prefer putting cloud credentials in <code>.env</code> (<code>MEDIAFLOW_PROVIDER_*</code>). Credentials saved from the Settings page are stored in the <code>mediaflow_settings</code> table; they are never sent back to the browser, but anyone with database access can read them.</li>
                    <li>Configure trusted hosts/proxies in your app so generated URLs use your real domain.</li>
                    <li>Schedule <code>mediaflow:clean-temp</code> and monitor <code>mediaflow:verify-storage</code>.</li>
                    <li>Run <code>php artisan config:cache</code> (or <code>php artisan optimize</code>) after installing or updating MediaFlow, and re-publish assets with <code>--force</code>.</li>
                    <li>Raise PHP <code>upload_max_filesize</code>/<code>post_max_size</code> (and your web server body limit) to at least <code>MEDIAFLOW_MAX_FILE_SIZE_KB</code>.</li>
                </ul>
            </section>

            <!-- SECTION: TESTING & TROUBLESHOOTING -->
            <section id="testing-troubleshooting" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>🧪</span> Testing &amp; Troubleshooting</h2>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">Terminal</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code>composer install
vendor/bin/phpunit                <span class="tok-com"># package test suite (Orchestra Testbench, in-memory SQLite)</span></code></pre>
                </div>
                <p class="mf-docs-p">In your own feature tests, fake storage and act as an allowed user:</p>
                <div class="mf-code-container">
                    <div class="mf-code-header">
                        <span class="mf-code-lang">tests/Feature/MediaUploadTest.php</span>
                        <button type="button" class="mf-code-copy-btn"><span>📋 Copy</span></button>
                    </div>
                    <pre class="mf-code-pre"><code>Storage::<span class="tok-fn">fake</span>(<span class="tok-str">'public'</span>);
<span class="tok-var">$this</span>-&gt;<span class="tok-fn">actingAs</span>(<span class="tok-var">$admin</span>)
     -&gt;<span class="tok-fn">postJson</span>(<span class="tok-fn">route</span>(<span class="tok-str">'mediaflow.upload'</span>), [<span class="tok-str">'file'</span> =&gt; UploadedFile::<span class="tok-fn">fake</span>()-&gt;<span class="tok-fn">image</span>(<span class="tok-str">'a.jpg'</span>)])
     -&gt;<span class="tok-fn">assertOk</span>();</code></pre>
                </div>
                <div class="mf-docs-table-wrapper">
                    <table class="mf-docs-table">
                        <thead><tr><th>Symptom</th><th>Fix</th></tr></thead>
                        <tbody>
                            <tr><td><code>401 Unauthenticated</code> / redirect to login</td><td>Log in, or for a local demo set <code>MEDIAFLOW_AUTH_ENABLED=false</code>.</td></tr>
                            <tr><td><code>419 CSRF token mismatch</code></td><td>Include <code>&lt;meta name="csrf-token" content="@{{ csrf_token() }}"&gt;</code> in your layout; the components also read <code>data-csrf-token</code> and can refresh via <code>/mediaflow/csrf-token</code>.</td></tr>
                            <tr><td>Images 404 on the public disk</td><td>Run <code>php artisan storage:link</code> and check <code>APP_URL</code>.</td></tr>
                            <tr><td>No thumbnails generated</td><td>Install/enable the PHP <code>gd</code> extension with WebP support (see Diagnostics page).</td></tr>
                            <tr><td>"MIME type [...] is not allowed"</td><td>Add the detected type to <code>allowed_mimes</code> (e.g. <code>image/*</code>).</td></tr>
                            <tr><td><code>413</code> or empty upload</td><td>Increase <code>upload_max_filesize</code>, <code>post_max_size</code> and the web server body size limit.</td></tr>
                            <tr><td>Cloud provider test says "Cloud storage needs the S3 driver"</td><td>The S3 driver (<code>league/flysystem-aws-s3-v3</code>) is required: it is installed by default with MediaFlow. If missing in a legacy install, run <code>composer require league/flysystem-aws-s3-v3 "^3.0"</code>.</td></tr>
                            <tr><td>Uploads accept unexpected types / no thumbnails after deploying</td><td>Your config cache predates MediaFlow. Run <code>php artisan config:cache</code> (or <code>optimize</code>) after every install or update; MediaFlow falls back to its safe defaults and logs a warning meanwhile.</td></tr>
                            <tr><td>Old UI behaviour after updating</td><td>Re-publish assets: <code>php artisan vendor:publish --tag=mediaflow-assets --force</code>.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="author-credits" class="mf-docs-section">
                <h2 class="mf-docs-h2"><span>👤</span> 19. Author & Lead Architect</h2>
                <p class="mf-docs-p">
                    MediaFlow for Laravel was architected and built by <strong>Anuj Dwivedi</strong> to deliver a universal, high-performance, and vendor-neutral media infrastructure ecosystem for modern Laravel applications.
                </p>

                <div class="mf-feature-card" style="align-items: center; background: linear-gradient(135deg, rgba(79, 70, 229, 0.05) 0%, rgba(6, 182, 212, 0.05) 100%); border: 1px solid var(--mf-border);">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #fff; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);">
                            👨‍💻
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 1.1rem; color: var(--mf-text-main);">Anuj Dwivedi</div>
                            <div style="color: var(--mf-text-muted); font-size: 0.85rem;">Creator & Lead Architect &bull; Full-Stack Software Engineer</div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
                        <a href="https://github.com/dwivedianuj9118" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 0.45rem; background: #0f172a; color: #ffffff; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.8125rem; font-weight: 600; text-decoration: none; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
                            <span>GitHub Profile</span>
                        </a>
                        <a href="https://www.linkedin.com/in/dwivedianuj9118/" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 0.45rem; background: #0077b5; color: #ffffff; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.8125rem; font-weight: 600; text-decoration: none; box-shadow: 0 2px 6px rgba(0,119,181,0.25);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                            <span>LinkedIn Profile</span>
                        </a>
                        <a href="mailto:dwivedianuj9118@gmail.com" style="display: inline-flex; align-items: center; gap: 0.45rem; background: var(--mf-bg-hover); color: var(--mf-text-main); border: 1px solid var(--mf-border); padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.8125rem; font-weight: 600; text-decoration: none;">
                            <span>✉️ Contact</span>
                        </a>
                        <a href="https://anujdwivedi.online" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 0.45rem; background: var(--mf-bg-hover); color: var(--mf-text-main); border: 1px solid var(--mf-border); padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.8125rem; font-weight: 600; text-decoration: none;">
                            <span>🌐 Portfolio</span>
                        </a>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/mediaflow/js/mediaflow.js') }}"></script>
</body>
</html>
