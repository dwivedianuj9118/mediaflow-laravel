@props([
    'mode' => 'standalone',
    'name' => 'selected_media_id',
    'multiple' => false,
    'collection' => null,
    'theme' => null,
    'colorMode' => null,
    'folderColor' => null,
    'folder' => null,
    'folderId' => null,
    'pipeline' => null,
    'visualizer' => null,
])

@php
    $configShowPipeline = (bool) config('mediaflow.ui.show_pipeline', true);
    $showPipeline = $pipeline !== null
        ? filter_var($pipeline, FILTER_VALIDATE_BOOLEAN)
        : ($visualizer !== null ? filter_var($visualizer, FILTER_VALIDATE_BOOLEAN) : $configShowPipeline);
@endphp

@mediaflowStyles
@mediaflowScripts

<div {{ $attributes->merge(['class' => 'mf-container mf-manager-wrapper']) }}
     data-csrf-token="{{ csrf_token() }}"
     @if($theme) data-mf-theme="{{ $theme }}" @endif
     @if($colorMode) data-mf-mode="{{ $colorMode }}" @endif
     @if($folderColor) data-mf-folder="{{ $folderColor }}" style="--mf-folder-color: {{ $folderColor }};" @endif
     @if($folderId ?: $folder) data-folder-id="{{ $folderId ?: $folder }}" @endif
     @if($showPipeline) data-pipeline="true" @endif
     data-api-url="{{ route('mediaflow.manager', [], false) }}"
     data-upload-url="{{ route('mediaflow.upload', [], false) }}"
     data-folders-url="{{ route('mediaflow.folders.index', [], false) }}"
     data-bulk-url="{{ route('mediaflow.media.bulk', [], false) }}"
     data-mode="{{ $mode }}"
     data-multiple="{{ $multiple ? 'true' : 'false' }}"
     data-input-name="{{ $name }}">

    {{-- Top Toolbar --}}
    <div class="mf-toolbar">
        <div class="mf-breadcrumbs"></div>

        <div class="mf-toolbar-actions">
            {{-- Search box --}}
            <div class="mf-search-box">
                <svg class="mf-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" class="mf-search-input" placeholder="Search media...">
            </div>

            {{-- New folder button --}}
            <button type="button" class="mf-btn mf-new-folder-btn">
                <span>📁</span> New Folder
            </button>

            {{-- Upload Files Button directly into active folder --}}
            <button type="button" class="mf-btn mf-btn-primary mf-manager-upload-btn" title="Upload files to this directory" style="cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem;">
                <span>📤</span>
                <span class="mf-manager-upload-label">Upload</span>
            </button>
            <input type="file" class="mf-manager-file-input" multiple style="display: none;">

            @if($mode === 'picker')
                <button type="button" class="mf-btn mf-btn-primary mf-picker-select-btn" disabled>
                    ✓ Select Media
                </button>
            @endif
        </div>
    </div>

    {{-- Body: Sidebar + Main Grid + Inspector --}}
    <div class="mf-manager-body">
        {{-- Sidebar --}}
        <div class="mf-sidebar">
            <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--mf-text-subtle); margin-bottom: 0.5rem; letter-spacing: 0.05em;">
                Library
            </div>
            <div class="mf-tree-item mf-active" data-type="all" style="cursor: pointer;">
                <span>🗂</span> All Files
            </div>
            <div class="mf-tree-item" data-type="image" style="cursor: pointer;">
                <span>🖼</span> Images
            </div>
            <div class="mf-tree-item" data-type="document" style="cursor: pointer;">
                <span>📄</span> Documents
            </div>
            <div class="mf-tree-item" data-type="video" style="cursor: pointer;">
                <span>🎥</span> Videos
            </div>
        </div>

        {{-- Center Content --}}
        <div class="mf-content-area" tabindex="0">
            <div class="mf-drop-overlay" style="display: none;">
                <div class="mf-drop-overlay-box">
                    <span style="font-size: 2.75rem;">📥</span>
                    <div class="mf-drop-overlay-text" style="font-weight: 700; margin-top: 0.5rem;">Drop files to upload here</div>
                </div>
            </div>

            {{-- Bulk Actions Bar --}}
            <div class="mf-bulk-bar" style="display: none;">
                <div class="mf-bulk-bar-left">
                    <label class="mf-bulk-select-all-wrap" title="Select or deselect all items">
                        <input type="checkbox" class="mf-bulk-select-all-checkbox">
                        <span class="mf-bulk-bar-label">
                            <span class="mf-bulk-count">0</span> selected
                        </span>
                    </label>
                    <span class="mf-bulk-bar-size"></span>
                </div>
                <div class="mf-bulk-bar-actions">
                    <button type="button" class="mf-btn mf-btn-sm mf-bulk-select-all-btn">Select All</button>
                    <button type="button" class="mf-btn mf-btn-sm mf-btn-danger mf-bulk-delete-btn" title="Delete selected items">
                        <svg style="width: 13px; height: 13px; margin-right: 4px;" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span>Delete Selected</span>
                    </button>
                    <button type="button" class="mf-btn mf-btn-sm mf-bulk-clear-btn" title="Clear selection">✕ Clear</button>
                </div>
            </div>

            <div class="mf-grid"></div>
        </div>

        {{-- Inspector Panel --}}
        <div class="mf-inspector">
            <div style="text-align: center; color: var(--mf-text-muted); margin-top: 2rem;">
                Select an item to view details
            </div>
        </div>
    </div>
</div>
