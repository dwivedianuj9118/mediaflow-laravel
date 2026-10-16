@props([
    'name' => 'media_id',
    'accept' => 'image/*',
    'multiple' => false,
    'animated' => true,
    'folder' => null,
    'collection' => 'default',
    'visibility' => 'public',
    'uploadUrl' => null,
])

@php
    $resolvedUploadUrl = $uploadUrl ?: route('mediaflow.upload', [], false);
@endphp

@mediaflowStyles
@mediaflowScripts

<div class="mf-container mf-uploader"
     data-csrf-token="{{ csrf_token() }}"
     data-upload-url="{{ $resolvedUploadUrl }}"
     data-input-name="{{ $name }}"
     data-multiple="{{ $multiple ? 'true' : 'false' }}"
     data-animated="{{ $animated ? 'true' : 'false' }}"
     data-folder="{{ $folder }}"
     data-collection="{{ $collection }}"
     data-visibility="{{ $visibility }}">

    {{-- Dropzone Area --}}
    <div class="mf-dropzone" tabindex="0" role="button" aria-label="Drop files here or click to browse">
        <svg class="mf-dropzone-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
        </svg>
        <div class="mf-dropzone-title">Drag & drop files here, or <span style="color: var(--mf-primary); text-decoration: underline;">browse</span></div>
        <div class="mf-dropzone-desc">Supports JPG, PNG, WebP, GIF, PDF, MP4 up to 20MB</div>
        <input type="file"
               class="mf-file-input"
               accept="{{ $accept }}"
               {{ $multiple ? 'multiple' : '' }}
               tabindex="-1">
    </div>

    {{-- Real Backend Stage Journey / Progress --}}
    <div class="mf-progress-card" style="display: none;">
        <div style="display: flex; justify-content: space-between; font-size: 0.8125rem;">
            <span class="mf-progress-text" style="font-weight: 600;">Uploading...</span>
            <span class="mf-progress-percent"></span>
        </div>
        <div class="mf-progress-bar-wrap">
            <div class="mf-progress-bar"></div>
        </div>
        <div class="mf-stages-row">
            <div class="mf-stage-pill stage-select">1. Select</div>
            <div class="mf-stage-pill stage-upload">2. Upload</div>
            <div class="mf-stage-pill stage-validate">3. Validate & Store</div>
            <div class="mf-stage-pill stage-ready">4. Ready</div>
        </div>
    </div>

    {{-- Uploaded Previews --}}
    <div class="mf-preview-list"></div>

    {{-- Hidden Form Inputs with Media IDs --}}
    <div class="mf-hidden-inputs"></div>
</div>
