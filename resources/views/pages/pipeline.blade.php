<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediaFlow — 3D Upload Lifecycle & Pipeline Visualizer</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('vendor/mediaflow/css/mediaflow.css') }}">
    <style>
        :root {
            --stage-primary: #6366f1;
            --stage-glow: rgba(99, 102, 241, 0.35);
            --stage-bg: rgba(255, 255, 255, 0.85);
            --stage-card: #ffffff;
            --stage-border: rgba(226, 232, 240, 0.8);
            --text-main: #0f172a;
            --text-sub: #64748b;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --stage-bg: rgba(15, 23, 42, 0.85);
                --stage-card: #131b2e;
                --stage-border: rgba(30, 41, 59, 0.8);
                --text-main: #f8fafc;
                --text-sub: #94a3b8;
            }
        }

        body {
            margin: 0;
            padding: 0;
            background: #090d16;
            color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            overflow-x: hidden;
        }

        .pipeline-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .pipeline-container {
            max-width: 1400px;
            margin: 1.5rem auto 3rem;
            padding: 0 1.5rem;
        }

        .hero-banner {
            text-align: center;
            margin-bottom: 2rem;
        }
        .hero-banner h1 {
            font-size: 2.25rem;
            font-weight: 800;
            margin: 0 0 0.5rem 0;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #a5b4fc 0%, #ffffff 50%, #6ee7b7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-banner p {
            font-size: 1.05rem;
            color: #94a3b8;
            max-width: 760px;
            margin: 0 auto;
        }

        /* 3D Visualizer Canvas & Controls */
        .visualizer-card {
            background: radial-gradient(circle at 50% 20%, rgba(30, 41, 59, 0.6) 0%, rgba(11, 15, 25, 0.95) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            position: relative;
            overflow: hidden;
        }

        .control-panel {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 1.5rem;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.06);
            color: #ffffff;
        }
        .btn-action:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-1px);
        }
        .btn-action.primary {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            border-color: #6366f1;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
        }
        .btn-action.primary:hover {
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.6);
            transform: translateY(-2px);
        }

        /* 3D Isometric Viewport */
        .stage-viewport {
            perspective: 1400px;
            perspective-origin: 50% 35%;
            min-height: 480px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
            user-select: none;
        }

        .stage-deck {
            position: relative;
            width: 100%;
            max-width: 1200px;
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1.5rem;
            transform-style: preserve-3d;
            transform: rotateX(20deg) rotateY(-6deg) rotateZ(1deg);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .stage-deck.flat-mode {
            transform: rotateX(0deg) rotateY(0deg) rotateZ(0deg) !important;
        }

        /* Station Pedestal */
        .pedestal {
            background: rgba(20, 28, 48, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            padding: 1.25rem 1rem;
            text-align: center;
            position: relative;
            transform-style: preserve-3d;
            transform: translateZ(0px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            backdrop-filter: blur(10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
            cursor: pointer;
        }

        .pedestal::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 18px;
            background: linear-gradient(135deg, transparent, rgba(99, 102, 241, 0.3), transparent);
            z-index: -1;
            opacity: 0.5;
            transition: opacity 0.3s;
        }

        .pedestal:hover, .pedestal.active {
            transform: translateZ(25px) scale(1.03);
            border-color: #6366f1;
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.3), 0 0 25px rgba(99, 102, 241, 0.2);
        }

        .pedestal.active::before {
            opacity: 1;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.6), rgba(16, 185, 129, 0.6));
        }

        .pedestal-icon {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            display: inline-block;
            transform: translateZ(15px);
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.4));
        }

        .pedestal-step {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #818cf8;
            margin-bottom: 0.25rem;
        }

        .pedestal-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.35rem;
        }

        .pedestal-desc {
            font-size: 0.75rem;
            color: #94a3b8;
            line-height: 1.35;
        }

        /* 3D Floating File Token */
        #file-token {
            position: absolute;
            width: 140px;
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(15, 23, 42, 0.95) 100%);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            padding: 0.75rem;
            box-shadow: 0 20px 35px rgba(0, 0, 0, 0.6), 0 0 20px rgba(99, 102, 241, 0.4);
            transform-style: preserve-3d;
            transform: translate3d(0, 0, 60px);
            transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 50;
            pointer-events: none;
            text-align: center;
        }

        #file-token .token-thumb {
            width: 100%;
            height: 60px;
            background: #1e293b;
            border-radius: 6px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            overflow: hidden;
            position: relative;
        }

        #file-token .token-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        #file-token .token-scanner {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: #10b981;
            box-shadow: 0 0 8px #10b981;
            opacity: 0;
        }

        #file-token.scanning .token-scanner {
            opacity: 1;
            animation: scanLine 1s infinite alternate ease-in-out;
        }

        @keyframes scanLine {
            0% { top: 0%; }
            100% { top: 95%; }
        }

        #file-token .token-name {
            font-size: 0.75rem;
            font-weight: 700;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.25rem;
        }

        #file-token .token-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.7rem;
            color: #94a3b8;
        }

        #file-token .token-badge {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.1rem 0.35rem;
            border-radius: 4px;
            background: #4f46e5;
            color: #ffffff;
        }

        /* Pulsing laser conduits between pedestals */
        .conduit-track {
            display: none !important;
        }

        .conduit-beam {
            display: none !important;
        }

        /* Deep Inspection Panel */
        .inspection-panel {
            margin-top: 1.5rem;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 1.25rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 900px) {
            .stage-deck {
                grid-template-columns: 1fr;
                transform: none !important;
            }
            .inspection-panel {
                grid-template-columns: 1fr;
            }
        }

        .code-snippet {
            background: #090d16;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 0.85rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.8rem;
            color: #38bdf8;
            overflow-x: auto;
            margin: 0;
        }

        .stage-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(99, 102, 241, 0.15);
            color: #818cf8;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }
    </style>
</head>
<body>
    <header class="pipeline-page-header">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <span style="font-size: 1.5rem;">🌊</span>
            <span style="font-weight: 700; font-size: 1.15rem;">MediaFlow 3D Pipeline Visualizer</span>
        </div>
        <nav style="display: flex; gap: 0.75rem; align-items: center;">
            <a href="/" class="btn-action">← Demo Showcase</a>
            <a href="{{ route('mediaflow.docs') }}" class="btn-action">📖 Documentation</a>
            <a href="{{ route('mediaflow.manager') }}" class="btn-action">Media Library</a>
            <a href="{{ route('mediaflow.settings') }}" class="btn-action">Settings</a>
            <a href="{{ route('mediaflow.diagnostics.view') }}" class="btn-action">Diagnostics</a>
        </nav>
    </header>

    <div class="pipeline-container">
        <div class="hero-banner">
            <h1>3D Upload Lifecycle & Engine Visualizer</h1>
            <p>Experience how MediaFlow picks raw files from disk, stages them in quarantine buffers, rewrites collision-proof UUIDs, optimizes raster bytes into WebP, and resolves edge CDN delivery.</p>
        </div>

        <div class="visualizer-card" id="visualizer-root">
            <div class="control-panel">
                <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                    <button type="button" id="btn-play" class="btn-action primary">
                        ▶ Run 3D Pipeline Animation
                    </button>
                    <label class="btn-action" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); border-color: #10b981; cursor: pointer;">
                        <span>📤</span> Pick Image & Watch 3D Upload Modal
                        <input type="file" id="pipeline-live-upload-input" accept="image/*" style="display: none;">
                    </label>
                    <button type="button" id="btn-reset" class="btn-action">
                        ↺ Reset
                    </button>
                    <button type="button" id="btn-toggle-3d" class="btn-action">
                        📐 Toggle 3D / Flat View
                    </button>
                </div>

                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <span class="stage-pill" id="current-stage-pill">
                        Ready to Start
                    </span>
                    <label style="font-size: 0.8rem; color: #94a3b8; display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                        <input type="checkbox" id="auto-tilt-chk" checked>
                        <span>Interactive 3D Mouse Tilt</span>
                    </label>
                </div>
            </div>

            <!-- Viewport with 3D Perspective -->
            <div class="stage-viewport" id="stage-viewport">
                <!-- Glowing Conduit Path -->
                <div class="conduit-track">
                    <div class="conduit-beam" id="conduit-beam"></div>
                </div>

                <!-- 3D Platform Deck -->
                <div class="stage-deck" id="stage-deck">
                    <!-- Station 1 -->
                    <div class="pedestal active" data-step="1" id="ped-1">
                        <div class="pedestal-icon">🖥️</div>
                        <div class="pedestal-step">Step 1</div>
                        <div class="pedestal-title">Disk Pick</div>
                        <div class="pedestal-desc">Raw user file picked from local device storage</div>
                    </div>

                    <!-- Station 2 -->
                    <div class="pedestal" data-step="2" id="ped-2">
                        <div class="pedestal-icon">📥</div>
                        <div class="pedestal-step">Step 2</div>
                        <div class="pedestal-title">Temp Buffer</div>
                        <div class="pedestal-desc">Chunk staging in <code>mediaflow-temp</code> & EXIF scrub</div>
                    </div>

                    <!-- Station 3 -->
                    <div class="pedestal" data-step="3" id="ped-3">
                        <div class="pedestal-icon">⚡</div>
                        <div class="pedestal-step">Step 3</div>
                        <div class="pedestal-title">Engine & Rename</div>
                        <div class="pedestal-desc">UUID hashing, WebP conversion & byte compression</div>
                    </div>

                    <!-- Station 4 -->
                    <div class="pedestal" data-step="4" id="ped-4">
                        <div class="pedestal-icon">🗄️</div>
                        <div class="pedestal-step">Step 4</div>
                        <div class="pedestal-title">Target Storage</div>
                        <div class="pedestal-desc">SHA-256 integrity check & variant generation</div>
                    </div>

                    <!-- Station 5 -->
                    <div class="pedestal" data-step="5" id="ped-5">
                        <div class="pedestal-icon">🌐</div>
                        <div class="pedestal-step">Step 5</div>
                        <div class="pedestal-title">Delivery CDN</div>
                        <div class="pedestal-desc">Edge CDN routing or HMAC expiring signed URLs</div>
                    </div>
                </div>

                <!-- Animated 3D File Token -->
                <div id="file-token">
                    <div class="token-thumb" id="token-thumb">
                        <span id="token-thumb-icon">🖼️</span>
                        <div class="token-scanner"></div>
                    </div>
                    <div class="token-name" id="token-name">raw_photo_IMG9821.png</div>
                    <div class="token-meta">
                        <span id="token-size">3.8 MB</span>
                        <span class="token-badge" id="token-badge">PNG</span>
                    </div>
                </div>
            </div>

            <!-- Deep Inspection Panel -->
            <div class="inspection-panel">
                <div>
                    <h3 style="font-size: 0.95rem; margin: 0 0 0.5rem 0; color: #a5b4fc;" id="inspector-heading">
                        Stage 1: User File Selection
                    </h3>
                    <p style="font-size: 0.85rem; color: #94a3b8; margin: 0 0 0.75rem 0;" id="inspector-desc">
                        The client initiates the upload via drag & drop or file dialog. MediaFlow captures the file, enforces client-side MIME checks, and prepares chunked streaming to prevent server RAM exhaustion.
                    </p>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <span class="stage-pill" id="stat-savings" style="display: none; background: rgba(16, 185, 129, 0.2); color: #34d399; border-color: rgba(16, 185, 129, 0.4);">
                            ⚡ -96.7% Size Savings
                        </span>
                        <span class="stage-pill" id="stat-uuid" style="display: none; background: rgba(14, 165, 233, 0.2); color: #38bdf8; border-color: rgba(14, 165, 233, 0.4);">
                            🔑 Collision-Proof UUID
                        </span>
                    </div>
                </div>

                <div>
                    <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 0.4rem;">
                        Backend Implementation Logic
                    </div>
                    <pre class="code-snippet" id="inspector-code">
// Client selection
$file = $request->file('file');
$validationService->validate($file, ['image/jpeg', 'image/png', 'image/webp']);
                    </pre>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/mediaflow/js/mediaflow.js') }}"></script>
    <script>
        const root = document.getElementById('visualizer-root');
        const viewport = document.getElementById('stage-viewport');
        const deck = document.getElementById('stage-deck');
        const token = document.getElementById('file-token');
        const conduit = document.getElementById('conduit-beam');
        const btnPlay = document.getElementById('btn-play');
        const btnReset = document.getElementById('btn-reset');
        const btnToggle3D = document.getElementById('btn-toggle-3d');
        const autoTiltChk = document.getElementById('auto-tilt-chk');
        const currentStagePill = document.getElementById('current-stage-pill');

        const tokenName = document.getElementById('token-name');
        const tokenSize = document.getElementById('token-size');
        const tokenBadge = document.getElementById('token-badge');
        const tokenThumbIcon = document.getElementById('token-thumb-icon');
        const statSavings = document.getElementById('stat-savings');
        const statUuid = document.getElementById('stat-uuid');

        const inspectorHeading = document.getElementById('inspector-heading');
        const inspectorDesc = document.getElementById('inspector-desc');
        const inspectorCode = document.getElementById('inspector-code');

        let currentStep = 1;
        let isAnimating = false;
        let animationTimer = null;

        const stageData = {
            1: {
                title: "Stage 1: User File Selection & Local Pick",
                desc: "User selects a raw image (raw_photo_IMG9821.png - 3.8 MB). MediaFlow performs pre-flight client validation on mime types and file limits before any chunks leave the browser.",
                code: `// Stage 1: Client selection\n$file = $request->file('file');\n$validationService->validate($file, config('mediaflow.allowed_mimes'));`,
                name: "raw_photo_IMG9821.png",
                size: "3.8 MB",
                badge: "PNG",
                icon: "🖼️",
                conduitWidth: "0%",
                savingsVisible: false,
                uuidVisible: false,
            },
            2: {
                title: "Stage 2: Temporary Disk Staging (mediaflow-temp)",
                desc: "The file streams into temporary isolation (mediaflow-temp/). MediaFlow inspects magic bytes with finfo, neutralizes executable payloads, and strips sensitive EXIF GPS metadata.",
                code: `// Stage 2: Temp Buffer & Sanitization\n$tempPath = $tempManager->storeChunk($chunk, $uploadId);\n$sanitizer->stripExif($tempPath);\n$mime = finfo_file($finfo, $tempPath);`,
                name: "temp_chunk_stage_9821.tmp",
                size: "3.8 MB",
                badge: "BUFFER",
                icon: "📥",
                conduitWidth: "25%",
                savingsVisible: false,
                uuidVisible: false,
            },
            3: {
                title: "Stage 3: Engine Optimization & UUID Naming Rewrite",
                desc: "The GD/Imagick engine executes format conversion into WebP and optimizes compression quality (e.g. 82%). The unsafe original filename is rewritten to a collision-proof UUID.",
                code: `// Stage 3: UUID Generation & Optimization\n$uuid = (string) Str::uuid();\n$newName = "{$uuid}.webp";\n$metrics = $imageProcessor->optimizeAndConvert($tempPath, $dest, [\n    'format' => 'webp',\n    'quality' => 82\n]); // File size drops from 3.8 MB -> 126 KB!`,
                name: "e49a10c7-3b28.webp",
                size: "126 KB",
                badge: "WEBP",
                icon: "⚡",
                conduitWidth: "50%",
                savingsVisible: true,
                uuidVisible: true,
            },
            4: {
                title: "Stage 4: Storage Persistence & Variant Generation",
                desc: "The optimized asset is streamed to permanent storage (Local public disk or AWS S3). SHA-256 checksum is calculated and WebP thumbnail variants (thumb: 200px, medium: 800px, large: 1600px) are generated.",
                code: `// Stage 4: Storage & Variants\n$storageKey = "media/2026/10/{$uuid}.webp";\n$adapter->writeStream($storageKey, fopen($dest, 'r'));\n$media = Media::create([...]);\n$variantGenerator->createWebpVariants($media);`,
                name: "media/2026/10/e49a10c7.webp",
                size: "126 KB",
                badge: "PERSISTED",
                icon: "🗄️",
                conduitWidth: "75%",
                savingsVisible: true,
                uuidVisible: true,
            },
            5: {
                title: "Stage 5: Decoupled Delivery URL & CDN Edge Resolution",
                desc: "The asset is finalized and ready for consumption. The Delivery URL Resolver resolves public disk URLs, custom CloudFront/Bunny CDN edge URLs, or generates time-limited HMAC signed URLs.",
                code: `// Stage 5: Delivery URL Resolution\n$url = MediaFlow::delivery()->url($media);\n// https://cdn.example.com/media/2026/10/e49a10c7.webp\n// Or signed: URL::temporarySignedRoute('mediaflow.delivery.stream', ...);`,
                name: "cdn.example.com/...e49a10c7.webp",
                size: "126 KB",
                badge: "READY",
                icon: "🌐",
                conduitWidth: "100%",
                savingsVisible: true,
                uuidVisible: true,
            }
        };

        function positionTokenAtStep(step) {
            const ped = document.getElementById(`ped-${step}`);
            if (!ped) return;

            // Highlight active pedestal
            document.querySelectorAll('.pedestal').forEach(p => p.classList.remove('active'));
            ped.classList.add('active');

            const pedRect = ped.getBoundingClientRect();
            const viewRect = viewport.getBoundingClientRect();

            // Calculate offset relative to viewport
            const left = pedRect.left - viewRect.left + (pedRect.width / 2) - 70;
            const top = pedRect.top - viewRect.top - 60;

            token.style.left = `${left}px`;
            token.style.top = `${top}px`;

            // Update content based on step
            const data = stageData[step];
            tokenName.textContent = data.name;
            tokenSize.textContent = data.size;
            tokenBadge.textContent = data.badge;
            tokenThumbIcon.textContent = data.icon;

            if (step === 2) {
                token.classList.add('scanning');
            } else {
                token.classList.remove('scanning');
            }

            if (step >= 3) {
                tokenBadge.style.background = '#10b981';
            } else {
                tokenBadge.style.background = '#4f46e5';
            }

            conduit.style.width = data.conduitWidth;
            currentStagePill.textContent = `Stage ${step}/5: ${data.title.split(':')[0]}`;

            // Update inspector details
            inspectorHeading.textContent = data.title;
            inspectorDesc.textContent = data.desc;
            inspectorCode.textContent = data.code;

            statSavings.style.display = data.savingsVisible ? 'inline-flex' : 'none';
            statUuid.style.display = data.uuidVisible ? 'inline-flex' : 'none';
        }

        // Initialize at Step 1
        setTimeout(() => positionTokenAtStep(1), 200);
        window.addEventListener('resize', () => positionTokenAtStep(currentStep));

        // Click on pedestals to jump
        document.querySelectorAll('.pedestal').forEach(ped => {
            ped.addEventListener('click', () => {
                if (isAnimating) stopAnimation();
                currentStep = parseInt(ped.getAttribute('data-step'));
                positionTokenAtStep(currentStep);
            });
        });

        // 3D Mouse Parallax
        root.addEventListener('mousemove', (e) => {
            if (!autoTiltChk.checked || deck.classList.contains('flat-mode')) return;

            const rect = root.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;

            const rotX = 20 - (y * 18);
            const rotY = -6 + (x * 22);

            deck.style.transform = `rotateX(${rotX}deg) rotateY(${rotY}deg) rotateZ(1deg)`;
        });

        root.addEventListener('mouseleave', () => {
            if (!deck.classList.contains('flat-mode')) {
                deck.style.transform = `rotateX(20deg) rotateY(-6deg) rotateZ(1deg)`;
            }
        });

        // Toggle 3D / Flat View
        btnToggle3D.addEventListener('click', () => {
            deck.classList.toggle('flat-mode');
            const isFlat = deck.classList.contains('flat-mode');
            btnToggle3D.textContent = isFlat ? '🧊 Switch to 3D Isometric View' : '📐 Switch to Flat View';
            positionTokenAtStep(currentStep);
        });

        // Play Animation Flow
        function runAnimation() {
            isAnimating = true;
            btnPlay.disabled = true;
            btnPlay.textContent = '⏳ Processing Pipeline...';

            currentStep = 1;
            positionTokenAtStep(1);

            animationTimer = setInterval(() => {
                currentStep++;
                if (currentStep > 5) {
                    stopAnimation();
                    currentStagePill.textContent = '✓ Pipeline Completed Successfully!';
                    return;
                }
                positionTokenAtStep(currentStep);
            }, 1600);
        }

        function stopAnimation() {
            clearInterval(animationTimer);
            isAnimating = false;
            btnPlay.disabled = false;
            btnPlay.textContent = '▶ Replay 3D Pipeline Animation';
        }

        btnPlay.addEventListener('click', runAnimation);
        btnReset.addEventListener('click', () => {
            stopAnimation();
            currentStep = 1;
            positionTokenAtStep(1);
        });

        // Live Image Pick & 3D Modal Trigger
        const liveInput = document.getElementById('pipeline-live-upload-input');
        if (liveInput) {
            liveInput.addEventListener('change', () => {
                if (liveInput.files && liveInput.files[0]) {
                    const file = liveInput.files[0];
                    if (window.MediaFlow && typeof window.MediaFlow.openPipelineModal === 'function') {
                        window.MediaFlow.openPipelineModal(file, {
                            uploadUrl: '{{ route("mediaflow.upload", [], false) }}',
                            csrfToken: '{{ csrf_token() }}'
                        });
                    } else if (typeof showToast === 'function') {
                        showToast('Visualizer engine is initializing...', 'info');
                    }
                    liveInput.value = '';
                }
            });
        }
    </script>
</body>
</html>
