@props([
    'name',
    'label' => null,
    'value' => null,
    'accept' => null,
    'type' => null,
    'types' => null,
    'folder' => null,
    'collection' => 'default',
    'visibility' => 'public',
    'help' => null,
    'required' => false,
    'compact' => false,
    'picker' => true,
    'browse' => true,
    'hideBrowse' => false,
    'uploadUrl' => null,
    'theme' => null,
    'mode' => null,
    'folderColor' => null,
    'pipeline' => null,
    'visualizer' => null,
    'frontend' => false,
    'isolated' => false,
    'uploadOnly' => false,
    'pickerScope' => null,
    'copyUrl' => true,
    'openTab' => true,
])

@php
    $resolvedUploadUrl = $uploadUrl ?: route('mediaflow.upload', [], false);
    $mediaManagerUrl = route('mediaflow.manager', [], false);
    $configShowPipeline = (bool) config('mediaflow.ui.show_pipeline', true);
    $showPipeline = $pipeline !== null
        ? filter_var($pipeline, FILTER_VALIDATE_BOOLEAN)
        : ($visualizer !== null ? filter_var($visualizer, FILTER_VALIDATE_BOOLEAN) : $configShowPipeline);
    $isIsolated = (bool) ($isolated || $frontend);
    $browseExplicitlyDisabled = $browse === false || $browse === 'false' || (bool) $hideBrowse;
    $canBrowsePicker = (bool) ($picker && $picker !== 'false' && $picker !== 'upload-only' && !$uploadOnly && !$browseExplicitlyDisabled);
    $effectiveScope = $pickerScope ?: ($frontend ? 'frontend' : 'all');

    // Dynamic file types and accept resolution
    $effectiveType = strtolower(trim((string) ($type ?: $types)));
    $maxSizeKb = (int) config('mediaflow.max_file_size_kb', 20480);
    $humanMax = round($maxSizeKb / 1024) . 'MB';

    if ($effectiveType === 'video') {
        $resolvedAccept = $accept ?: 'video/*,video/mp4,video/webm,video/ogg,video/quicktime';
        $defaultSubtext = "MP4, WebM, OGG video up to {$humanMax}";
        $dropIcon = '🎬';
    } elseif (in_array($effectiveType, ['doc', 'docs', 'document', 'documents', 'pdf'], true)) {
        $resolvedAccept = $accept ?: '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,application/pdf';
        $defaultSubtext = "PDF, Word, Excel, CSV, text up to {$humanMax}";
        $dropIcon = '📄';
    } elseif ($effectiveType === 'audio') {
        $resolvedAccept = $accept ?: 'audio/*,audio/mpeg,audio/wav,audio/ogg,audio/mp3';
        $defaultSubtext = "MP3, WAV, OGG audio up to {$humanMax}";
        $dropIcon = '🎵';
    } elseif (in_array($effectiveType, ['any', 'all', '*'], true) || $accept === '*') {
        $resolvedAccept = $accept ?: '*';
        $defaultSubtext = "Any file up to {$humanMax}";
        $dropIcon = '📁';
    } elseif ($accept && (str_contains($accept, 'video') || str_contains($accept, '.mp4'))) {
        $resolvedAccept = $accept;
        $effectiveType = 'video';
        $defaultSubtext = "Video files up to {$humanMax}";
        $dropIcon = '🎬';
    } elseif ($accept && (str_contains($accept, 'pdf') || str_contains($accept, '.doc') || str_contains($accept, 'document'))) {
        $resolvedAccept = $accept;
        $effectiveType = 'document';
        $defaultSubtext = "Documents up to {$humanMax}";
        $dropIcon = '📄';
    } elseif ($accept && (str_contains($accept, 'audio') || str_contains($accept, '.mp3'))) {
        $resolvedAccept = $accept;
        $effectiveType = 'audio';
        $defaultSubtext = "Audio files up to {$humanMax}";
        $dropIcon = '🎵';
    } else {
        $resolvedAccept = $accept ?: 'image/*,image/jpeg,image/png,image/webp,image/gif,image/svg+xml';
        $effectiveType = 'image';
        $defaultSubtext = "JPG, PNG, WebP, GIF images up to {$humanMax}";
        $dropIcon = '🖼️';
    }

    // Resolve initial media if passed as Model, ID, or URL
    $initialMedia = null;
    $initialId = null;
    $initialUrl = null;
    $initialName = null;
    $initialSize = null;

    if (!empty($value)) {
        if ($value instanceof \MediaFlow\Laravel\Models\Media) {
            $initialMedia = $value;
            $initialId = $value->id;
            $initialUrl = $value->thumbnailUrl('thumb') ?: $value->url();
            $initialName = $value->original_name;
            $initialSize = $value->humanSize();
        } elseif (is_numeric($value)) {
            $initialMedia = \MediaFlow\Laravel\Models\Media::find($value);
            if ($initialMedia) {
                $initialId = $initialMedia->id;
                $initialUrl = $initialMedia->thumbnailUrl('thumb') ?: $initialMedia->url();
                $initialName = $initialMedia->original_name;
                $initialSize = $initialMedia->humanSize();
            } else {
                $initialId = $value;
            }
        } elseif (is_string($value)) {
            $initialUrl = $value;
            $initialName = basename(parse_url($value, PHP_URL_PATH) ?: $value);
            $initialId = $value;
        }
    }
@endphp

@mediaflowStyles
@mediaflowScripts

<div {{ $attributes->merge(['class' => 'mf-container mf-input-field ' . ($compact ? 'mf-input-compact' : '')]) }}
     data-csrf-token="{{ csrf_token() }}"
     @if($theme) data-mf-theme="{{ $theme }}" @endif
     @if($mode) data-mf-mode="{{ $mode }}" @endif
     @if($folderColor) data-mf-folder="{{ $folderColor }}" style="--mf-folder-color: {{ $folderColor }};" @endif
     @if($showPipeline) data-pipeline="true" @endif
     data-upload-url="{{ $resolvedUploadUrl }}"
     data-manager-url="{{ $mediaManagerUrl }}"
     data-input-name="{{ $name }}"
     data-folder="{{ $folder }}"
     data-collection="{{ $collection }}"
     data-visibility="{{ $visibility }}"
     data-isolated="{{ $isIsolated ? 'true' : 'false' }}"
     data-picker-scope="{{ $effectiveScope }}"
     data-accept="{{ $resolvedAccept }}"
     data-type="{{ $effectiveType }}"
     data-current-url="{{ $initialUrl }}"
     data-has-value="{{ !empty($initialId) ? 'true' : 'false' }}">

    @if($label)
        <label class="mf-input-label" for="mf-input-{{ $name }}">
            <span>{{ $label }}</span>
            @if($required)
                <span class="mf-required-star">*</span>
            @endif
        </label>
    @endif

    {{-- Hidden Form Input that carries the media ID/value for standard POST forms --}}
    <input type="hidden"
           id="mf-input-{{ $name }}"
           name="{{ $name }}"
           class="mf-input-hidden-value"
           value="{{ $initialId }}"
           {{ $required ? 'required' : '' }}>

    {{-- Dropzone Area (shown when no media is chosen) --}}
    <div class="mf-input-dropzone"
         tabindex="0"
         role="button"
         aria-label="Upload {{ $label ?: $name }}"
         style="{{ !empty($initialId) ? 'display: none;' : '' }}">

        <div class="mf-input-dropzone-inner">
            <svg class="mf-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            <div class="mf-input-text-wrap">
                <div class="mf-input-title">
                    <span>Drop file here, or </span>
                    <span class="mf-input-browse-link">browse</span>
                </div>
                <div class="mf-input-subtext">{{ $defaultSubtext }}</div>
            </div>
        </div>

        @if($canBrowsePicker)
            <div class="mf-input-actions-bar">
                <button type="button" class="mf-input-picker-btn">
                    <span>🗂</span> {{ $frontend ? 'Browse Your Media' : 'Browse Media Library' }}
                </button>
            </div>
        @endif

        <input type="file"
               class="mf-file-input"
               accept="{{ $resolvedAccept }}"
               tabindex="-1">
    </div>

    {{-- Selected Media Preview Card (shown when an image or file is uploaded/selected) --}}
    <div class="mf-input-preview-card" style="{{ empty($initialId) ? 'display: none;' : '' }}">
        <div class="mf-input-thumb-box">
            @if(!empty($initialUrl))
                @php
                    $isImg = $initialMedia ? $initialMedia->isImage() : (bool) preg_match('/\.(jpeg|jpg|png|webp|gif|svg)($|\?)/i', $initialUrl);
                    $ext = strtolower(pathinfo(parse_url($initialUrl, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
                @endphp
                @if($isImg)
                    <img src="{{ $initialUrl }}" alt="{{ $initialName ?: 'Preview' }}" class="mf-input-preview-img">
                @elseif(in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv']))
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(239, 68, 68, 0.12); color: #ef4444; border-radius: 6px;">
                        <span style="font-size: 1.5rem;">📄</span>
                        <span style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase;">{{ $ext ?: 'DOC' }}</span>
                    </div>
                @elseif(in_array($ext, ['mp4', 'webm', 'ogg', 'mov']))
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(168, 85, 247, 0.12); color: #a855f7; border-radius: 6px;">
                        <span style="font-size: 1.5rem;">🎬</span>
                        <span style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase;">{{ $ext ?: 'VIDEO' }}</span>
                    </div>
                @elseif(in_array($ext, ['mp3', 'wav', 'ogg', 'm4a']))
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(6, 182, 212, 0.12); color: #06b6d4; border-radius: 6px;">
                        <span style="font-size: 1.5rem;">🎵</span>
                        <span style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase;">{{ $ext ?: 'AUDIO' }}</span>
                    </div>
                @else
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(148, 163, 184, 0.12); color: #94a3b8; border-radius: 6px;">
                        <span style="font-size: 1.5rem;">📁</span>
                        <span style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase;">{{ $ext ?: 'FILE' }}</span>
                    </div>
                @endif
            @else
                <span class="mf-input-preview-icon">{{ $dropIcon }}</span>
            @endif
        </div>
        <div class="mf-input-meta-box">
            <div class="mf-input-meta-name">{{ $initialName ?: 'Selected Media' }}</div>
            <div class="mf-input-meta-sub">{{ $initialSize ?: 'Ready to submit' }}</div>
        </div>
        <div class="mf-input-item-actions">
            @if($openTab)
                <button type="button" class="mf-input-view-btn" title="Open in new tab (Full screen)" style="{{ empty($initialUrl) ? 'display: none;' : '' }}">
                    <span>👁️</span> Open
                </button>
            @endif
            @if($copyUrl)
                <button type="button" class="mf-input-copy-btn" title="Copy image URL" style="{{ empty($initialUrl) ? 'display: none;' : '' }}">
                    <span>📋</span> Copy URL
                </button>
            @endif
            <button type="button" class="mf-input-replace-btn" title="Replace file">
                <span>🔄</span> Replace
            </button>
            <button type="button" class="mf-input-remove-btn" title="Remove file">
                <span>🗑️</span>
            </button>
        </div>
    </div>

    @if($help)
        <div class="mf-input-help-text">{{ $help }}</div>
    @endif

    @if(isset($errors) && $errors->has($name))
        <div class="mf-input-error-text">{{ $errors->first($name) }}</div>
    @endif
</div>
