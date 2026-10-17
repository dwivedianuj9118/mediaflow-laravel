/**
 * MediaFlow Vanilla Client — Zero-dependency, accessible, reactive
 */
(function () {
    'use strict';

    function resolveMediaUrl(url) {
        if (!url || typeof url !== 'string') return '';
        try {
            if (url.startsWith('http://') || url.startsWith('https://')) {
                const parsed = new URL(url);
                if ((parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1') && parsed.pathname.startsWith('/storage/')) {
                    return window.location.origin + parsed.pathname + parsed.search;
                }
            }
        } catch (e) {}
        return url;
    }

    function resolveEndpointUrl(url) {
        if (!url || typeof url !== 'string') return '';
        try {
            if (url.startsWith('/')) {
                return url;
            }
            if (url.startsWith('http://') || url.startsWith('https://')) {
                const parsed = new URL(url);
                const loc = window.location;
                const isLocalHost = (parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1') &&
                                    (loc.hostname === 'localhost' || loc.hostname === '127.0.0.1');
                if (parsed.hostname === loc.hostname || isLocalHost) {
                    return loc.origin + parsed.pathname + parsed.search;
                }
            }
        } catch (e) {}
        return url;
    }

    // Escapes text for safe use in HTML content AND quoted attributes.
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Makes a URL safe to drop into src/href attributes and inline handler strings:
    // blocks javascript:/data:text URLs and percent-encodes quote/space/markup characters.
    function safeUrl(url) {
        if (url === null || url === undefined) return '';
        const str = String(url).trim();
        if (/^\s*((javascript|vbscript):|data:(?!image\/(png|jpe?g|gif|webp);))/i.test(str)) return '';
        return str.replace(/["'<>`\\\s]/g, (c) => encodeURIComponent(c));
    }

    function formatBytes(bytes) {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function isFileAccepted(file, acceptRule) {
        if (!file) return false;
        if (!acceptRule || acceptRule === '*' || acceptRule === '*/*') return true;
        const tokens = acceptRule.split(',').map(t => t.trim().toLowerCase());
        const fileName = (file.name || '').toLowerCase();
        const fileMime = (file.type || '').toLowerCase();
        const fileExt = fileName.includes('.') ? '.' + fileName.split('.').pop() : '';

        for (let token of tokens) {
            if (token === '*' || token === '*/*') return true;
            if (token.startsWith('.')) {
                if (fileExt === token) return true;
            } else if (token.endsWith('/*')) {
                const prefix = token.slice(0, -1);
                if (fileMime.startsWith(prefix)) return true;
            } else if (token === fileMime) {
                return true;
            }
        }
        return false;
    }

    function getRawCsrfToken(preferredToken, container) {
        if (preferredToken && typeof preferredToken === 'string' && preferredToken.trim().length > 0) {
            const trimmed = preferredToken.trim();
            if (!trimmed.startsWith('eyJ')) {
                return trimmed;
            }
        }

        // 1. Container attribute (data-csrf-token)
        if (container && container.dataset && container.dataset.csrfToken) {
            const tok = container.dataset.csrfToken.trim();
            if (tok && !tok.startsWith('eyJ')) return tok;
        }

        // 2. <meta name="csrf-token">
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.getAttribute('content')) {
            const tok = meta.getAttribute('content').trim();
            if (tok && !tok.startsWith('eyJ')) return tok;
        }

        // 3. Form input _token
        if (container) {
            const formToken = container.closest('form')?.querySelector('input[name="_token"]')?.value;
            if (formToken && formToken.trim() && !formToken.startsWith('eyJ')) return formToken.trim();
        }
        const anyToken = document.querySelector('input[name="_token"]')?.value;
        if (anyToken && anyToken.trim() && !anyToken.startsWith('eyJ')) return anyToken.trim();

        // 4. Global runtime token
        if (window.MediaFlow && window.MediaFlow.csrfToken) return window.MediaFlow.csrfToken;
        if (window.Laravel && window.Laravel.csrfToken) return window.Laravel.csrfToken;

        return '';
    }

    const getCsrfToken = getRawCsrfToken;

    function getXsrfCookie() {
        const match = document.cookie.match(/(^|;)\s*XSRF-TOKEN\s*=\s*([^;]+)/);
        if (match) {
            return decodeURIComponent(match[2]);
        }
        return '';
    }

    function applyCsrfHeaders(xhr, preferredToken, container, formData) {
        xhr.withCredentials = true;
        const rawToken = getRawCsrfToken(preferredToken, container);
        if (rawToken) {
            xhr.setRequestHeader('X-CSRF-TOKEN', rawToken);
            if (formData && !formData.has('_token')) {
                formData.append('_token', rawToken);
            }
        } else {
            const xsrf = getXsrfCookie();
            if (xsrf) {
                // Laravel decrypts X-XSRF-TOKEN cookie only if raw token not present
                xhr.setRequestHeader('X-XSRF-TOKEN', xsrf);
            }
        }
    }

    function fetchFreshCsrfToken(callback) {
        fetch('/mediaflow/csrf-token', {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.csrf_token) {
                const fresh = data.csrf_token;
                window.MediaFlow.csrfToken = fresh;
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) meta.setAttribute('content', fresh);
                document.querySelectorAll('.mf-container').forEach(c => c.dataset.csrfToken = fresh);
                document.querySelectorAll('input[name="_token"]').forEach(i => i.value = fresh);
                if (typeof callback === 'function') callback(fresh);
            } else if (typeof callback === 'function') {
                callback(null);
            }
        })
        .catch(() => {
            if (typeof callback === 'function') callback(null);
        });
    }

    function showToast(message, type, title, duration) {
        if (!message) return;
        type = type || 'info';
        duration = duration || (type === 'warning' ? 6500 : 4500);

        let container = document.getElementById('mf-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'mf-toast-container';
            container.className = 'mf-toast-container';
            document.body.appendChild(container);
        }

        const icons = {
            success: '✓',
            warning: '⚠️',
            error: '✕',
            info: 'ℹ️'
        };

        const defaultTitles = {
            success: 'Success',
            warning: 'Warning',
            error: 'Error',
            info: 'Notice'
        };

        const toast = document.createElement('div');
        toast.className = `mf-toast mf-toast-${type}`;

        const iconChar = icons[type] || 'ℹ️';
        const displayTitle = title !== null && title !== undefined ? title : (defaultTitles[type] || '');

        toast.innerHTML = `
            <div class="mf-toast-icon">${iconChar}</div>
            <div class="mf-toast-content">
                ${displayTitle ? `<div class="mf-toast-title">${escapeHtml(displayTitle)}</div>` : ''}
                <div class="mf-toast-msg">${escapeHtml(message)}</div>
            </div>
            <button type="button" class="mf-toast-close" aria-label="Close notification">&times;</button>
        `;

        container.appendChild(toast);

        const removeToast = () => {
            toast.classList.add('mf-toast-exit');
            setTimeout(() => {
                toast.remove();
                if (container && container.children.length === 0) {
                    container.remove();
                }
            }, 260);
        };

        toast.querySelector('.mf-toast-close')?.addEventListener('click', removeToast);

        let timer = setTimeout(removeToast, duration);
        toast.addEventListener('mouseenter', () => clearTimeout(timer));
        toast.addEventListener('mouseleave', () => {
            timer = setTimeout(removeToast, 2000);
        });
    }

    function copyTextToClipboard(text, successToastMsg) {
        if (!text) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                showToast(successToastMsg || 'Copied to clipboard!', 'success', 'Copied');
            }).catch(() => {
                fallbackCopyText(text, successToastMsg);
            });
        } else {
            fallbackCopyText(text, successToastMsg);
        }
    }

    function fallbackCopyText(text, successToastMsg) {
        try {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            const success = document.execCommand('copy');
            document.body.removeChild(textarea);
            if (success) {
                showToast(successToastMsg || 'Copied to clipboard!', 'success', 'Copied');
            } else {
                showToast('Please copy the URL manually: ' + text, 'info', 'Copy URL');
            }
        } catch (e) {
            showToast('Unable to access clipboard.', 'error', 'Error');
        }
    }

    window.MediaFlow = {
        toast: showToast,
        showToast: showToast,
        copyToClipboard: copyTextToClipboard,
        initUploader: function (container) {
            const dropzone = container.querySelector('.mf-dropzone');
            const fileInput = container.querySelector('.mf-file-input');
            const progressCard = container.querySelector('.mf-progress-card');
            const progressBar = container.querySelector('.mf-progress-bar');
            const progressText = container.querySelector('.mf-progress-text');
            const previewList = container.querySelector('.mf-preview-list');
            const hiddenInputsWrap = container.querySelector('.mf-hidden-inputs');

            const uploadUrl = resolveEndpointUrl(container.dataset.uploadUrl || '/mediaflow/upload');
            const folder = container.dataset.folder || '';
            const collection = container.dataset.collection || 'default';
            const inputName = container.dataset.inputName || 'media_id';
            const isMultiple = container.dataset.multiple === 'true';
            const csrfToken = getRawCsrfToken(null, container);

            if (!dropzone || !fileInput) return;

            // Synchronize with companion media manager active folder
            document.addEventListener('mediaflow:folder-changed', (e) => {
                if (e.detail) {
                    const newFolder = e.detail.folderId !== null && e.detail.folderId !== undefined && e.detail.folderId !== '' && e.detail.folderId !== 'null' ? String(e.detail.folderId) : '';
                    container.dataset.folder = newFolder;
                    container.dataset.folderId = newFolder;
                    const folderDesc = e.detail.folderName ? `to: 📁 ${e.detail.folderName}` : 'to: 🗂 All Media';
                    const descEl = container.querySelector('.mf-dropzone-desc');
                    if (descEl) {
                        descEl.innerHTML = `Uploading ${escapeHtml(folderDesc)} &bull; Supports JPG, PNG, WebP, GIF, PDF up to 20MB`;
                    }
                }
            });

            // Click to browse
            dropzone.addEventListener('click', () => fileInput.click());
            dropzone.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    fileInput.click();
                }
            });

            // Drag & drop handlers
            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    dropzone.classList.add('mf-dragover');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    dropzone.classList.remove('mf-dragover');
                });
            });

            dropzone.addEventListener('drop', (e) => {
                if (e.dataTransfer?.files?.length) {
                    handleFiles(e.dataTransfer.files);
                }
            });

            fileInput.addEventListener('change', () => {
                if (fileInput.files?.length) {
                    handleFiles(fileInput.files);
                }
            });

            function getActiveFolder() {
                const dfId = container.dataset.folderId;
                if (dfId !== undefined && dfId !== '' && dfId !== 'null' && dfId !== 'undefined') return dfId;
                const df = container.dataset.folder;
                if (df !== undefined && df !== '' && df !== 'null' && df !== 'undefined') return df;
                return (folder && folder !== 'null' && folder !== 'undefined') ? folder : '';
            }

            function handleFiles(files) {
                const fileList = Array.from(files);
                if (!isMultiple && fileList.length > 1) {
                    showToast('Only single file upload is permitted.', 'warning', 'Limit Reached');
                    return;
                }

                fileList.forEach(file => {
                    const usePipelineVisualizer = container.dataset.pipelineVisualizer !== 'false';
                    const activeFolder = getActiveFolder();
                    if (usePipelineVisualizer && typeof window.MediaFlow.openPipelineModal === 'function') {
                        window.MediaFlow.openPipelineModal(file, {
                            container: container,
                            uploadUrl: uploadUrl,
                            folder: activeFolder,
                            folder_id: activeFolder,
                            collection: collection,
                            csrfToken: getRawCsrfToken(csrfToken, container),
                            onComplete: function (media) {
                                addUploadedMedia(media);
                            },
                            onError: function (err) {
                                handleError(err);
                            }
                        });
                    } else {
                        uploadFile(file);
                    }
                });
            }

            function uploadFile(file, isRetry) {
                if (progressCard) progressCard.style.display = 'block';
                updateProgress(0, 'Connecting...');

                const activeFolder = getActiveFolder();
                const formData = new FormData();
                formData.append('file', file);
                if (activeFolder) {
                    formData.append('folder', activeFolder);
                    formData.append('folder_id', activeFolder);
                }
                if (collection) formData.append('collection', collection);

                const xhr = new XMLHttpRequest();
                xhr.open('POST', uploadUrl, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                applyCsrfHeaders(xhr, csrfToken, container, formData);

                // Browser upload progress
                xhr.upload.onprogress = function (e) {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        updateProgress(percent, `Uploading ${percent}%...`);
                        setStageActive(percent < 100 ? 'stage-upload' : 'stage-validate');
                    }
                };

                xhr.onload = function () {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            const res = JSON.parse(xhr.responseText);
                            if (res.success && res.media) {
                                setStageActive('stage-ready');
                                updateProgress(100, 'Upload Complete');
                                addUploadedMedia(res.media);
                                setTimeout(() => {
                                    if (progressCard) progressCard.style.display = 'none';
                                }, 1200);
                            } else {
                                handleError(res.error || 'Upload error');
                            }
                        } catch (e) {
                            handleError('Invalid server response');
                        }
                    } else if (xhr.status === 419 && !isRetry) {
                        // Automatically fetch fresh CSRF token and retry upload once
                        fetchFreshCsrfToken((fresh) => {
                            if (fresh) {
                                uploadFile(file, true);
                            } else {
                                handleError('Session expired or CSRF token mismatch (419). Please refresh the page.');
                            }
                        });
                    } else {
                        let errMsg = 'Upload failed';
                        if (xhr.status === 419) {
                            errMsg = 'Session expired or CSRF token mismatch (419). Please refresh the page.';
                        } else {
                            try {
                                const errRes = JSON.parse(xhr.responseText);
                                if (errRes.error) errMsg = errRes.error;
                            } catch (e) {}
                        }
                        handleError(errMsg);
                    }
                };

                xhr.onerror = function () {
                    handleError('Network transport failure');
                };

                xhr.send(formData);
            }

            function updateProgress(percent, text) {
                if (progressBar) progressBar.style.width = percent + '%';
                if (progressText) progressText.textContent = text;
            }

            function setStageActive(stageClass) {
                container.querySelectorAll('.mf-stage-pill').forEach(el => el.classList.remove('mf-active'));
                const stageEl = container.querySelector('.' + stageClass);
                if (stageEl) stageEl.classList.add('mf-active');
            }

            function handleError(msg) {
                if (progressText) progressText.textContent = 'Failed: ' + msg;
                if (progressBar) progressBar.style.background = 'var(--mf-danger)';
            }

            function addUploadedMedia(media) {
                // If single mode, clear existing inputs
                if (!isMultiple) {
                    hiddenInputsWrap.innerHTML = '';
                    previewList.innerHTML = '';
                }

                // Add hidden input with media ID
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = isMultiple ? `${inputName}[]` : inputName;
                hidden.value = media.id;
                hiddenInputsWrap.appendChild(hidden);

                // Add preview card
                const item = document.createElement('div');
                item.className = 'mf-preview-item';
                item.innerHTML = `
                    <img src="${safeUrl(resolveMediaUrl(media.thumbnail_url || media.url))}" alt="${escapeHtml(media.original_name)}" class="mf-preview-thumb" onerror="this.onerror=null; this.src='${safeUrl(resolveMediaUrl(media.url))}';">
                    <div class="mf-preview-meta">
                        <div class="mf-preview-name">${escapeHtml(media.original_name)}</div>
                        <div class="mf-preview-sub">${escapeHtml(media.human_size)} • Ready</div>
                    </div>
                    <button type="button" class="mf-btn mf-btn-danger mf-remove-btn" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">✕ Remove</button>
                `;

                item.querySelector('.mf-remove-btn').addEventListener('click', () => {
                    item.remove();
                    hidden.remove();
                });

                previewList.appendChild(item);

                // Emit event on container and globally on document
                container.dispatchEvent(new CustomEvent('mediaflow:uploaded', {
                    bubbles: true,
                    detail: { media: media }
                }));
                document.dispatchEvent(new CustomEvent('mediaflow:uploaded', {
                    detail: { media: media }
                }));
            }
        },

        initManager: function (container) {
            const apiUrl = resolveEndpointUrl(container.dataset.apiUrl || '/mediaflow/manager');
            const uploadUrl = resolveEndpointUrl(container.dataset.uploadUrl || '/mediaflow/upload');
            const foldersUrl = resolveEndpointUrl(container.dataset.foldersUrl || '/mediaflow/folders');
            const bulkUrl = resolveEndpointUrl(container.dataset.bulkUrl || (apiUrl.replace(/\/manager\/?$/, '') + '/media/bulk'));
            const isPicker = container.dataset.mode === 'picker';
            const isMultiple = container.dataset.multiple === 'true';
            const inputName = container.dataset.inputName || 'selected_media_id';
            const csrfToken = getRawCsrfToken(null, container);

            let currentFolderId = null;
            let currentFolderName = '';
            let currentSearch = '';
            let currentType = 'all';
            let selectedItems = new Map();
            let currentMediaList = [];
            let lastSelectedIndex = -1;
            let activeInspectorItem = null;

            // Check URL query parameters or container dataset for initial folder
            try {
                const urlParams = new URLSearchParams(window.location.search);
                const initialFolderParam = urlParams.get('folder_id') || urlParams.get('folder');
                if (initialFolderParam && initialFolderParam !== 'null' && initialFolderParam !== 'undefined') {
                    currentFolderId = isNaN(parseInt(initialFolderParam, 10)) ? initialFolderParam : parseInt(initialFolderParam, 10);
                } else if (container.dataset.folderId) {
                    const df = container.dataset.folderId;
                    currentFolderId = (df && df !== 'null' && df !== 'undefined') ? (isNaN(parseInt(df, 10)) ? df : parseInt(df, 10)) : null;
                }
            } catch (e) {}

            function syncUrlFolder(folderId) {
                if (window.history && window.history.replaceState) {
                    try {
                        const newUrl = new URL(window.location.href);
                        if (folderId !== null && folderId !== undefined && folderId !== '' && folderId !== 'null' && folderId !== 'undefined') {
                            newUrl.searchParams.set('folder_id', folderId);
                        } else {
                            newUrl.searchParams.delete('folder_id');
                            newUrl.searchParams.delete('folder');
                        }
                        window.history.replaceState({ folderId: folderId }, '', newUrl.toString());
                    } catch (e) {}
                }
            }

            window.addEventListener('popstate', () => {
                try {
                    const params = new URLSearchParams(window.location.search);
                    const f = params.get('folder_id') || params.get('folder');
                    currentFolderId = (f && f !== 'null' && f !== 'undefined') ? (isNaN(parseInt(f, 10)) ? f : parseInt(f, 10)) : null;
                    clearSelection();
                    loadData();
                } catch (e) {}
            });

            const gridContainer = container.querySelector('.mf-grid');
            const breadcrumbsContainer = container.querySelector('.mf-breadcrumbs');
            const searchInput = container.querySelector('.mf-search-input');
            const inspector = container.querySelector('.mf-inspector');
            const newFolderBtn = container.querySelector('.mf-new-folder-btn');
            const folderTreeList = container.querySelector('.mf-folder-tree');
            const pickerSelectBtn = container.querySelector('.mf-picker-select-btn');
            const managerFileInput = container.querySelector('.mf-manager-file-input');
            const managerUploadBtn = container.querySelector('.mf-manager-upload-btn');
            const managerUploadLabel = container.querySelector('.mf-manager-upload-label');
            const contentArea = container.querySelector('.mf-content-area');
            const dropOverlay = container.querySelector('.mf-drop-overlay');
            const dropOverlayText = container.querySelector('.mf-drop-overlay-text');

            let bulkBar = container.querySelector('.mf-bulk-bar');
            if (!bulkBar && contentArea && gridContainer) {
                bulkBar = document.createElement('div');
                bulkBar.className = 'mf-bulk-bar';
                bulkBar.style.display = 'none';
                bulkBar.innerHTML = `
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
                `;
                contentArea.insertBefore(bulkBar, gridContainer);
            }

            if (bulkBar) {
                const bulkCheckbox = bulkBar.querySelector('.mf-bulk-select-all-checkbox');
                const bulkSelectAllBtn = bulkBar.querySelector('.mf-bulk-select-all-btn');
                const bulkDeleteBtn = bulkBar.querySelector('.mf-bulk-delete-btn');
                const bulkClearBtn = bulkBar.querySelector('.mf-bulk-clear-btn');

                if (bulkCheckbox) {
                    bulkCheckbox.addEventListener('change', () => {
                        if (bulkCheckbox.checked) {
                            selectAllMedia();
                        } else {
                            clearSelection();
                        }
                    });
                }

                if (bulkSelectAllBtn) {
                    bulkSelectAllBtn.addEventListener('click', () => {
                        if (currentMediaList.length > 0 && selectedItems.size === currentMediaList.length) {
                            clearSelection();
                        } else {
                            selectAllMedia();
                        }
                    });
                }

                if (bulkDeleteBtn) {
                    bulkDeleteBtn.addEventListener('click', () => {
                        bulkDeleteMedia(Array.from(selectedItems.keys()));
                    });
                }

                if (bulkClearBtn) {
                    bulkClearBtn.addEventListener('click', () => {
                        clearSelection();
                    });
                }
            }

            function loadData(callback) {
                let url = `${apiUrl}?ajax=1`;
                if (currentFolderId !== null && currentFolderId !== undefined && currentFolderId !== '' && currentFolderId !== 'null') {
                    url += `&folder_id=${encodeURIComponent(currentFolderId)}`;
                }
                if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
                if (currentType && currentType !== 'all') url += `&type=${currentType}`;

                fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            renderBreadcrumbs(data.breadcrumbs || []);
                            renderContent(data.folders || [], data.media || []);
                            if (typeof callback === 'function') callback(data);
                        }
                    })
                    .catch(err => console.error('MediaFlow error loading data:', err));
            }

            function renderBreadcrumbs(breadcrumbs) {
                if (!breadcrumbsContainer) return;
                breadcrumbsContainer.innerHTML = '';

                if (breadcrumbs && breadcrumbs.length > 0) {
                    currentFolderName = breadcrumbs[breadcrumbs.length - 1].name;
                } else {
                    currentFolderName = '';
                }

                if (managerUploadLabel) {
                    managerUploadLabel.textContent = currentFolderName ? `Upload to ${currentFolderName}` : 'Upload';
                }

                // Sync current active folder to URL query parameter
                syncUrlFolder(currentFolderId);

                // Notify uploader and other components on the page
                document.dispatchEvent(new CustomEvent('mediaflow:folder-changed', {
                    detail: {
                        folderId: currentFolderId,
                        folderName: currentFolderName
                    }
                }));

                // Root item
                const root = document.createElement('span');
                root.className = 'mf-breadcrumb-item' + (currentFolderId === null ? ' mf-active' : '');
                root.textContent = 'All Media';
                root.addEventListener('click', () => {
                    currentFolderId = null;
                    clearSelection();
                    loadData();
                });
                breadcrumbsContainer.appendChild(root);

                breadcrumbs.forEach((b, idx) => {
                    const sep = document.createElement('span');
                    sep.textContent = ' / ';
                    sep.style.color = 'var(--mf-text-subtle)';
                    breadcrumbsContainer.appendChild(sep);

                    const crumb = document.createElement('span');
                    crumb.className = 'mf-breadcrumb-item' + (idx === breadcrumbs.length - 1 ? ' mf-active' : '');
                    crumb.textContent = b.name;
                    crumb.addEventListener('click', () => {
                        currentFolderId = b.id;
                        clearSelection();
                        loadData();
                    });
                    breadcrumbsContainer.appendChild(crumb);
                });

                if (currentFolderId !== null && breadcrumbs && breadcrumbs.length > 0) {
                    const activeCrumb = breadcrumbs[breadcrumbs.length - 1];
                    const delFolderBtn = document.createElement('button');
                    delFolderBtn.type = 'button';
                    delFolderBtn.className = 'mf-btn';
                    delFolderBtn.style.cssText = 'color: #ef4444; border-color: rgba(239, 68, 68, 0.4); padding: 0.15rem 0.55rem; font-size: 0.75rem; margin-left: 0.85rem; display: inline-flex; align-items: center; gap: 0.3rem; cursor: pointer;';
                    delFolderBtn.innerHTML = '<span>🗑️</span> Delete Folder';
                    delFolderBtn.title = `Delete folder "${escapeHtml(activeCrumb.name)}"`;
                    delFolderBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        deleteFolder(activeCrumb.id, activeCrumb.name);
                    });
                    breadcrumbsContainer.appendChild(delFolderBtn);
                }
            }

            function deleteFolder(fId, fName) {
                if (!confirm(`Are you sure you want to delete folder "${fName}"?\nAny files inside will be safely moved to the parent directory.`)) {
                    return;
                }

                const tok = getRawCsrfToken(csrfToken, container);
                const headers = {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                };
                if (tok) headers['X-CSRF-TOKEN'] = tok;
                else {
                    const xsrf = getXsrfCookie();
                    if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;
                }

                fetch(`${foldersUrl}/${fId}`, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: headers
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || `Folder "${fName}" deleted successfully.`, 'success', 'Folder Deleted');
                        if (String(currentFolderId) === String(fId)) {
                            currentFolderId = null;
                        }
                        loadData();
                    } else {
                        showToast(data.error || 'Failed to delete folder.', 'error', 'Folder Error');
                    }
                })
                .catch(err => {
                    showToast('Failed to delete folder: ' + err.message, 'error', 'Folder Error');
                });
            }

            // Toolbar File Upload Button Handler
            if (managerFileInput) {
                if (managerUploadBtn) {
                    managerUploadBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        managerFileInput.click();
                    });
                }
                managerFileInput.addEventListener('change', () => {
                    if (managerFileInput.files && managerFileInput.files.length) {
                        handleManagerUpload(Array.from(managerFileInput.files));
                        managerFileInput.value = '';
                    }
                });
            }

            // Sidebar Library Type Filters (All Files, Images, Documents, Videos)
            const sidebarTreeItems = container.querySelectorAll('.mf-sidebar .mf-tree-item');
            sidebarTreeItems.forEach(item => {
                item.addEventListener('click', () => {
                    sidebarTreeItems.forEach(i => i.classList.remove('mf-active'));
                    item.classList.add('mf-active');

                    const type = item.getAttribute('data-type') ||
                        (item.textContent.toLowerCase().includes('image') ? 'image' :
                         item.textContent.toLowerCase().includes('document') ? 'document' :
                         item.textContent.toLowerCase().includes('video') ? 'video' : 'all');

                    currentType = type;
                    clearSelection();
                    loadData();
                });
            });

            // Drag & Drop on Manager Content Area
            if (contentArea) {
                ['dragenter', 'dragover'].forEach(name => {
                    contentArea.addEventListener(name, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        if (dropOverlay) {
                            dropOverlay.style.display = 'flex';
                            if (dropOverlayText) {
                                dropOverlayText.textContent = currentFolderName ? `Drop files to upload into 📁 ${currentFolderName}` : 'Drop files to upload into 🗂 All Media';
                            }
                        }
                    });
                });

                ['dragleave', 'drop'].forEach(name => {
                    contentArea.addEventListener(name, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        if (e.type === 'drop') {
                            if (dropOverlay) dropOverlay.style.display = 'none';
                            if (e.dataTransfer?.files?.length) {
                                handleManagerUpload(Array.from(e.dataTransfer.files));
                            }
                        } else if (e.target === dropOverlay || !contentArea.contains(e.relatedTarget)) {
                            if (dropOverlay) dropOverlay.style.display = 'none';
                        }
                    });
                });
            }

            const useManagerPipeline = container.dataset.pipeline === 'true' || container.dataset.visualizer === 'true';

            function handleManagerUpload(files) {
                if (!files || !files.length) return;
                if (useManagerPipeline && typeof window.MediaFlow.openPipelineModal === 'function') {
                    files.forEach(file => {
                        window.MediaFlow.openPipelineModal(file, {
                            container: container,
                            uploadUrl: uploadUrl,
                            folder: currentFolderId !== null && currentFolderId !== undefined ? String(currentFolderId) : '',
                            folder_id: currentFolderId,
                            csrfToken: getRawCsrfToken(csrfToken, container),
                            onComplete: function () {
                                loadData();
                            }
                        });
                    });
                } else {
                    uploadDirectFiles(files);
                }
            }

            function uploadDirectFiles(files) {
                if (!files || !files.length) return;

                const origLabel = managerUploadLabel ? managerUploadLabel.textContent : 'Upload';
                if (managerUploadBtn) {
                    managerUploadBtn.disabled = true;
                    managerUploadBtn.style.opacity = '0.75';
                    managerUploadBtn.style.pointerEvents = 'none';
                }

                let completed = 0;
                let failed = 0;
                let lastError = null;

                const updateButtonStatus = () => {
                    if (managerUploadLabel) {
                        managerUploadLabel.textContent = `Uploading ${completed + failed + 1}/${files.length}...`;
                    }
                };

                const uploadNext = (index) => {
                    if (index >= files.length) {
                        if (managerUploadBtn) {
                            managerUploadBtn.disabled = false;
                            managerUploadBtn.style.opacity = '';
                            managerUploadBtn.style.pointerEvents = '';
                        }
                        if (managerUploadLabel) {
                            managerUploadLabel.textContent = origLabel;
                        }
                        loadData();
                        if (failed > 0) {
                            showToast(`${completed} uploaded successfully, ${failed} failed: ${lastError || 'Upload error'}`, 'warning', 'Upload Notice');
                        } else if (completed > 0) {
                            showToast(`Successfully uploaded ${completed} ${completed === 1 ? 'file' : 'files'}.`, 'success', 'Uploaded');
                        }
                        return;
                    }

                    updateButtonStatus();
                    const file = files[index];
                    uploadSingle(file, false, (err) => {
                        if (err) {
                            failed++;
                            lastError = err;
                        } else {
                            completed++;
                        }
                        uploadNext(index + 1);
                    });
                };

                const uploadSingle = (file, isRetry, cb) => {
                    const formData = new FormData();
                    formData.append('file', file);
                    if (currentFolderId !== null && currentFolderId !== undefined && currentFolderId !== '' && currentFolderId !== 'null' && currentFolderId !== 'undefined') {
                        formData.append('folder', String(currentFolderId));
                        formData.append('folder_id', String(currentFolderId));
                    }
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', uploadUrl, true);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    applyCsrfHeaders(xhr, csrfToken, container, formData);

                    xhr.onload = function () {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            try {
                                const res = JSON.parse(xhr.responseText);
                                if (res.success && res.media) {
                                    document.dispatchEvent(new CustomEvent('mediaflow:uploaded', {
                                        detail: { media: res.media }
                                    }));
                                    cb(null, res.media);
                                } else {
                                    cb(res.error || 'Server error');
                                }
                            } catch (e) {
                                cb('Invalid server response');
                            }
                        } else if (xhr.status === 419 && !isRetry) {
                            fetchFreshCsrfToken((fresh) => {
                                if (fresh) uploadSingle(file, true, cb);
                                else cb('Session expired or CSRF mismatch (419)');
                            });
                        } else {
                            let msg = `Upload failed (${xhr.status})`;
                            try {
                                const err = JSON.parse(xhr.responseText);
                                if (err.error) msg = err.error;
                            } catch (e) {}
                            cb(msg);
                        }
                    };

                    xhr.onerror = function () {
                        cb('Network error while uploading');
                    };

                    xhr.send(formData);
                };

                uploadNext(0);
            }

            function renderContent(folders, mediaItems) {
                currentMediaList = mediaItems || [];
                if (!gridContainer) return;
                gridContainer.innerHTML = '';

                // Render Folders only when viewing all files and not searching
                if (currentType === 'all' && !currentSearch) {
                    folders.forEach(folder => {
                        const card = document.createElement('div');
                        card.className = 'mf-card';
                        card.innerHTML = `
                            <div class="mf-card-thumb-wrap mf-folder-thumb" style="background: var(--mf-folder-bg, rgba(245, 158, 11, 0.14)); color: var(--mf-folder-color, #f59e0b); display: flex; align-items: center; justify-content: center; position: relative;">
                                <button type="button" class="mf-folder-delete-btn" title="Delete folder ${escapeHtml(folder.name)}" aria-label="Delete folder" style="position: absolute; top: 8px; right: 8px; width: 28px; height: 28px; border-radius: 6px; border: 1px solid rgba(239, 68, 68, 0.35); background: rgba(239, 68, 68, 0.15); color: #ef4444; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; z-index: 2;">
                                    <svg style="width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 2;" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                                <svg style="width: 44px; height: 44px; fill: var(--mf-folder-color, #f59e0b); filter: drop-shadow(0 2px 4px rgba(0,0,0,0.08));" viewBox="0 0 24 24">
                                    <path d="M19.5 21a3 3 0 0 0 3-3v-4.5a3 3 0 0 0-3-3h-1.5V9a3 3 0 0 0-3-3h-3.379a3 3 0 0 1-2.121-.879L8.379 3.879A3 3 0 0 0 6.257 3H4.5A3 3 0 0 0 1.5 6v12a3 3 0 0 0 3 3h15Z" opacity="0.35"/>
                                    <path d="M2 9.5A2.5 2.5 0 0 1 4.5 7h15A2.5 2.5 0 0 1 22 9.5v8a2.5 2.5 0 0 1-2.5 2.5h-15A2.5 2.5 0 0 1 2 17.5v-8Z"/>
                                </svg>
                            </div>
                            <div class="mf-card-info">
                                <div class="mf-card-name">${escapeHtml(folder.name)}</div>
                                <div class="mf-card-meta">${folder.media_count || 0} items</div>
                            </div>
                        `;
                        const delFolderBtn = card.querySelector('.mf-folder-delete-btn');
                        if (delFolderBtn) {
                            delFolderBtn.addEventListener('click', (e) => {
                                e.stopPropagation();
                                e.preventDefault();
                                deleteFolder(folder.id, folder.name);
                            });
                        }
                        card.addEventListener('click', () => {
                            currentFolderId = folder.id;
                            clearSelection();
                            loadData();
                        });
                        gridContainer.appendChild(card);
                    });
                }

            // Render Media
            const hasFolders = currentType === 'all' && !currentSearch && folders.length > 0;
            if (mediaItems.length === 0 && !hasFolders) {
                const typeLabel = currentType === 'image' ? 'images' :
                                  currentType === 'video' ? 'videos' :
                                  currentType === 'document' ? 'documents' : 'media items';
                gridContainer.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem 1rem; color: var(--mf-text-muted);">
                        <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📂</div>
                        <div style="font-weight: 600;">No ${escapeHtml(typeLabel)} found</div>
                        <div style="font-size: 0.875rem;">Drop files here or click Upload to get started.</div>
                    </div>
                `;
                updateSelectionUI();
                return;
            }

                mediaItems.forEach((media, index) => {
                    const card = document.createElement('div');
                    const isSelected = selectedItems.has(media.id);
                    card.className = 'mf-card' + (isSelected ? ' mf-selected' : '');
                    card.dataset.mediaId = media.id;
                    card.dataset.index = index;

                    const preview = safeUrl(resolveMediaUrl(media.thumbnail_url || media.url));
                    const fullUrl = safeUrl(resolveMediaUrl(media.url));

                    card.innerHTML = `
                        <div class="mf-card-select-wrap" title="Select item">
                            <input type="checkbox" class="mf-card-checkbox" ${isSelected ? 'checked' : ''} aria-label="Select ${escapeHtml(media.name)}">
                        </div>
                        <div class="mf-card-quick-actions">
                            <button type="button" class="mf-card-action-btn mf-card-view-btn" data-url="${fullUrl}" title="Open in new tab (Fullscreen)">👁️</button>
                            <button type="button" class="mf-card-action-btn mf-card-copy-btn" data-url="${fullUrl}" title="Copy image URL">📋</button>
                        </div>
                        <div class="mf-card-thumb-wrap">
                            ${media.is_image ? `<img src="${preview}" alt="${escapeHtml(media.name)}" class="mf-card-thumb" loading="lazy" onerror="this.onerror=null; if('${fullUrl}' && this.src !== '${fullUrl}') { this.src='${fullUrl}'; } else { this.parentElement.innerHTML='<span style=\\'font-size: 2rem;\\'>🖼️</span>'; }">` : `<span style="font-size: 2rem;">📄</span>`}
                        </div>
                        <div class="mf-card-info">
                            <div class="mf-card-name" title="${escapeHtml(media.name)}">${escapeHtml(media.name)}</div>
                            <div class="mf-card-meta">${media.human_size}</div>
                        </div>
                    `;

                    // Handle card view and copy actions
                    card.querySelector('.mf-card-view-btn')?.addEventListener('click', (e) => {
                        e.stopPropagation();
                        if (fullUrl) window.open(fullUrl, '_blank');
                    });

                    card.querySelector('.mf-card-copy-btn')?.addEventListener('click', (e) => {
                        e.stopPropagation();
                        if (fullUrl) copyTextToClipboard(fullUrl, 'Image URL copied to clipboard!');
                    });

                    // Handle clicking the select checkbox wrap
                    const selectWrap = card.querySelector('.mf-card-select-wrap');
                    if (selectWrap) {
                        selectWrap.addEventListener('click', (e) => {
                            e.stopPropagation();
                            toggleMediaSelection(media, card, index);
                        });
                    }

                    // Handle clicking anywhere on the card
                    card.addEventListener('click', (e) => {
                        if (e.target.closest('.mf-card-select-wrap') || e.target.closest('.mf-card-quick-actions')) return;

                        if (isMultiple) {
                            toggleMediaSelection(media, card, index);
                        } else if (e.shiftKey) {
                            handleRangeSelect(index);
                        } else if (e.metaKey || e.ctrlKey) {
                            toggleMediaSelection(media, card, index);
                        } else {
                            selectSingleMedia(media, card, index);
                        }
                    });

                    gridContainer.appendChild(card);
                });

                updateSelectionUI();
            }

            function toggleMediaSelection(media, card, index) {
                if (selectedItems.has(media.id)) {
                    selectedItems.delete(media.id);
                } else {
                    selectedItems.set(media.id, media);
                }
                lastSelectedIndex = typeof index === 'number' ? index : lastSelectedIndex;
                updateSelectionUI();
            }

            function selectSingleMedia(media, card, index) {
                selectedItems.clear();
                selectedItems.set(media.id, media);
                lastSelectedIndex = typeof index === 'number' ? index : -1;
                updateSelectionUI();
            }

            function handleRangeSelect(targetIndex) {
                if (lastSelectedIndex === -1 || lastSelectedIndex >= currentMediaList.length) {
                    if (currentMediaList[targetIndex]) {
                        selectedItems.set(currentMediaList[targetIndex].id, currentMediaList[targetIndex]);
                        lastSelectedIndex = targetIndex;
                    }
                } else {
                    const start = Math.min(lastSelectedIndex, targetIndex);
                    const end = Math.max(lastSelectedIndex, targetIndex);
                    for (let i = start; i <= end; i++) {
                        const m = currentMediaList[i];
                        if (m) selectedItems.set(m.id, m);
                    }
                    lastSelectedIndex = targetIndex;
                }
                updateSelectionUI();
            }

            function selectAllMedia() {
                currentMediaList.forEach((m, idx) => {
                    selectedItems.set(m.id, m);
                });
                if (currentMediaList.length > 0) lastSelectedIndex = currentMediaList.length - 1;
                updateSelectionUI();
            }

            function clearSelection() {
                selectedItems.clear();
                lastSelectedIndex = -1;
                updateSelectionUI();
            }

            function updateSelectionUI() {
                container.querySelectorAll('.mf-card[data-media-id]').forEach(card => {
                    const id = Number(card.dataset.mediaId);
                    const isSelected = selectedItems.has(id);
                    card.classList.toggle('mf-selected', isSelected);
                    const cb = card.querySelector('.mf-card-checkbox');
                    if (cb) cb.checked = isSelected;
                });

                let totalBytes = 0;
                selectedItems.forEach(item => {
                    totalBytes += (Number(item.size_bytes) || 0);
                });
                const count = selectedItems.size;

                if (bulkBar) {
                    if (count === 0) {
                        bulkBar.style.display = 'none';
                    } else {
                        bulkBar.style.display = 'flex';
                        const countEl = bulkBar.querySelector('.mf-bulk-count');
                        if (countEl) countEl.textContent = count;
                        const sizeEl = bulkBar.querySelector('.mf-bulk-bar-size');
                        if (sizeEl) sizeEl.textContent = totalBytes > 0 ? `(${formatBytes(totalBytes)})` : '';

                        const cb = bulkBar.querySelector('.mf-bulk-select-all-checkbox');
                        if (cb) {
                            if (currentMediaList.length > 0 && count === currentMediaList.length) {
                                cb.checked = true;
                                cb.indeterminate = false;
                            } else if (count > 0 && count < currentMediaList.length) {
                                cb.checked = false;
                                cb.indeterminate = true;
                            } else {
                                cb.checked = false;
                                cb.indeterminate = false;
                            }
                        }

                        const selectAllBtn = bulkBar.querySelector('.mf-bulk-select-all-btn');
                        if (selectAllBtn) {
                            selectAllBtn.textContent = (currentMediaList.length > 0 && count === currentMediaList.length)
                                ? 'Deselect All'
                                : `Select All (${currentMediaList.length})`;
                        }

                        const delBtn = bulkBar.querySelector('.mf-bulk-delete-btn span');
                        if (delBtn) {
                            delBtn.textContent = count > 1 ? `Delete Selected (${count})` : 'Delete Selected';
                        }
                    }
                }

                if (pickerSelectBtn) {
                    pickerSelectBtn.disabled = count === 0;
                    if (count > 1) {
                        pickerSelectBtn.textContent = `✓ Select Media (${count})`;
                    } else {
                        pickerSelectBtn.textContent = '✓ Select Media';
                    }
                }

                if (!inspector) return;
                if (count === 0) {
                    inspector.innerHTML = `<div style="text-align: center; color: var(--mf-text-muted); margin-top: 2rem;">Select an item to view details</div>`;
                } else if (count === 1) {
                    const singleMedia = selectedItems.values().next().value;
                    renderInspector(singleMedia);
                } else {
                    renderBulkInspector(selectedItems, totalBytes);
                }
            }

            function renderBulkInspector(selectedMap, totalBytes) {
                if (!inspector) return;
                const count = selectedMap.size;
                const items = Array.from(selectedMap.values());

                let miniThumbsHtml = '<div class="mf-inspector-bulk-thumbs">';
                items.slice(0, 12).forEach(item => {
                    const thumbUrl = safeUrl(resolveMediaUrl(item.thumbnail_url || item.url));
                    miniThumbsHtml += `
                        <div class="mf-inspector-mini-thumb" title="${escapeHtml(item.name)}">
                            ${item.is_image ? `<img src="${thumbUrl}" alt="${escapeHtml(item.name)}" onerror="this.parentElement.innerHTML='📄';">` : '<span>📄</span>'}
                        </div>
                    `;
                });
                if (count > 12) {
                    miniThumbsHtml += `
                        <div class="mf-inspector-mini-thumb" style="background: var(--mf-bg-subtle); color: var(--mf-text-muted); font-size: 0.75rem; font-weight: 700;">
                            +${count - 12}
                        </div>
                    `;
                }
                miniThumbsHtml += '</div>';

                inspector.innerHTML = `
                    <div style="font-size: 2.5rem; text-align: center; padding: 0.75rem 0;">📦</div>
                    <div class="mf-inspector-title" style="text-align: center; font-size: 1.05rem;">${count} Items Selected</div>
                    <div class="mf-inspector-row">
                        <span class="mf-inspector-label">Selected Files</span>
                        <span class="mf-inspector-val">${count}</span>
                    </div>
                    <div class="mf-inspector-row">
                        <span class="mf-inspector-label">Combined Size</span>
                        <span class="mf-inspector-val">${formatBytes(totalBytes)}</span>
                    </div>
                    ${miniThumbsHtml}
                    <div style="margin-top: 1.25rem; display: flex; flex-direction: column; gap: 0.5rem;">
                        <button type="button" class="mf-btn mf-btn-danger mf-inspector-bulk-delete-btn" style="display: flex; align-items: center; justify-content: center; gap: 0.4rem;">
                            <svg style="width: 14px; height: 14px;" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span>Delete Selected (${count})</span>
                        </button>
                        <button type="button" class="mf-btn mf-inspector-bulk-clear-btn">✕ Clear Selection</button>
                    </div>
                `;

                inspector.querySelector('.mf-inspector-bulk-delete-btn')?.addEventListener('click', () => {
                    bulkDeleteMedia(Array.from(selectedMap.keys()));
                });

                inspector.querySelector('.mf-inspector-bulk-clear-btn')?.addEventListener('click', () => {
                    clearSelection();
                });
            }

            function renderInspector(media) {
                if (!inspector) return;
                if (!media) {
                    inspector.innerHTML = `<div style="text-align: center; color: var(--mf-text-muted); margin-top: 2rem;">Select an item to view details</div>`;
                    return;
                }

                const resolvedMediaUrl = safeUrl(resolveMediaUrl(media.url));
                inspector.innerHTML = `
                    ${media.is_image ? `<img src="${resolvedMediaUrl}" alt="${escapeHtml(media.name)}" class="mf-inspector-thumb" onerror="this.style.display='none';">` : `<div style="font-size: 3rem; text-align: center; padding: 1.5rem 0;">📄</div>`}
                    <div class="mf-inspector-title">${escapeHtml(media.name)}</div>
                    <div class="mf-inspector-row">
                        <span class="mf-inspector-label">Type</span>
                        <span class="mf-inspector-val">${escapeHtml(media.mime_type)}</span>
                    </div>
                    <div class="mf-inspector-row">
                        <span class="mf-inspector-label">Size</span>
                        <span class="mf-inspector-val">${escapeHtml(media.human_size)}</span>
                    </div>
                    ${media.width ? `
                    <div class="mf-inspector-row">
                        <span class="mf-inspector-label">Dimensions</span>
                        <span class="mf-inspector-val">${media.width} × ${media.height}</span>
                    </div>` : ''}
                    <div class="mf-inspector-row">
                        <span class="mf-inspector-label">Disk</span>
                        <span class="mf-inspector-val">${escapeHtml(media.disk || 'public')}</span>
                    </div>
                    <div class="mf-inspector-row">
                        <span class="mf-inspector-label">Visibility</span>
                        <span class="mf-inspector-val">${escapeHtml(media.visibility || 'public')}</span>
                    </div>
                    <div style="margin-top: 1.25rem; display: flex; flex-direction: column; gap: 0.5rem;">
                        ${media.is_image ? `<button type="button" class="mf-btn mf-btn-primary mf-optimize-media-btn">⚡ Optimize & Convert</button>` : ''}
                        <button type="button" class="mf-btn mf-inspector-view-btn" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;">
                            <span>👁️</span> Open in New Tab
                        </button>
                        <button type="button" class="mf-btn mf-inspector-copy-btn" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;">
                            <span>📋</span> Copy Image URL
                        </button>
                        <a href="${resolvedMediaUrl}" target="_blank" download class="mf-btn" style="text-decoration: none; text-align: center;">⬇ Download</a>
                        <button type="button" class="mf-btn mf-btn-danger mf-delete-media-btn">Delete Media</button>
                    </div>
                `;

                inspector.querySelector('.mf-optimize-media-btn')?.addEventListener('click', () => {
                    openOptimizeModal(media);
                });

                inspector.querySelector('.mf-inspector-view-btn')?.addEventListener('click', () => {
                    if (resolvedMediaUrl) window.open(resolvedMediaUrl, '_blank');
                });

                inspector.querySelector('.mf-inspector-copy-btn')?.addEventListener('click', () => {
                    if (resolvedMediaUrl) copyTextToClipboard(resolvedMediaUrl, 'Image URL copied to clipboard!');
                });

                inspector.querySelector('.mf-delete-media-btn')?.addEventListener('click', () => {
                    deleteMedia(media.id);
                });
            }

            function openOptimizeModal(media) {
                const modal = document.createElement('div');
                modal.className = 'mf-modal-backdrop';
                modal.innerHTML = `
                    <div class="mf-modal">
                        <div class="mf-modal-title">⚡ Optimize & Convert Image</div>
                        <div class="mf-modal-desc">Compress quality, convert formats (WebP, JPG, PNG), and resize dimensions.</div>

                        <div class="mf-control-group">
                            <label class="mf-control-label">Target Format</label>
                            <div class="mf-pills-row" id="mf-format-pills">
                                <span class="mf-pill-radio active" data-val="webp">WebP (Recommended)</span>
                                <span class="mf-pill-radio" data-val="jpeg">JPEG</span>
                                <span class="mf-pill-radio" data-val="png">PNG</span>
                            </div>
                        </div>

                        <div class="mf-control-group">
                            <div class="mf-control-label">
                                <span>Compression Quality</span>
                                <span id="mf-quality-val" style="color: var(--mf-primary); font-weight: 700;">82%</span>
                            </div>
                            <input type="range" min="30" max="95" value="82" class="mf-range-slider" id="mf-quality-slider">
                        </div>

                        <div class="mf-control-group">
                            <label class="mf-control-label">Max Dimensions (Optional Resize)</label>
                            <div style="display: flex; gap: 0.5rem;">
                                <input type="number" id="mf-opt-w" class="mf-search-input" placeholder="Width (px)" value="${media.width || ''}" style="width: 50%;">
                                <input type="number" id="mf-opt-h" class="mf-search-input" placeholder="Height (px)" value="${media.height || ''}" style="width: 50%;">
                            </div>
                        </div>

                        <div class="mf-control-group">
                            <label class="mf-control-label">Action</label>
                            <div class="mf-pills-row" id="mf-action-pills">
                                <span class="mf-pill-radio active" data-val="replace">Replace Original</span>
                                <span class="mf-pill-radio" data-val="copy">Save as New Copy</span>
                            </div>
                        </div>

                        <div id="mf-opt-status" style="display: none; margin-top: 1rem;"></div>

                        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.5rem;">
                            <button type="button" class="mf-btn mf-opt-close-btn">Cancel</button>
                            <button type="button" class="mf-btn mf-btn-primary mf-opt-submit-btn">Run Optimization</button>
                        </div>
                    </div>
                `;

                document.body.appendChild(modal);

                let selectedFormat = 'webp';
                let selectedAction = 'replace';

                // Format pills
                modal.querySelectorAll('#mf-format-pills .mf-pill-radio').forEach(pill => {
                    pill.addEventListener('click', () => {
                        modal.querySelectorAll('#mf-format-pills .mf-pill-radio').forEach(p => p.classList.remove('active'));
                        pill.classList.add('active');
                        selectedFormat = pill.dataset.val;
                    });
                });

                // Action pills
                modal.querySelectorAll('#mf-action-pills .mf-pill-radio').forEach(pill => {
                    pill.addEventListener('click', () => {
                        modal.querySelectorAll('#mf-action-pills .mf-pill-radio').forEach(p => p.classList.remove('active'));
                        pill.classList.add('active');
                        selectedAction = pill.dataset.val;
                    });
                });

                // Slider
                const slider = modal.querySelector('#mf-quality-slider');
                const valLabel = modal.querySelector('#mf-quality-val');
                slider.addEventListener('input', () => {
                    valLabel.textContent = slider.value + '%';
                });

                // Close button
                modal.querySelector('.mf-opt-close-btn').addEventListener('click', () => modal.remove());

                // Submit button
                const submitBtn = modal.querySelector('.mf-opt-submit-btn');
                const statusDiv = modal.querySelector('#mf-opt-status');

                submitBtn.addEventListener('click', () => {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Processing...';
                    statusDiv.style.display = 'block';
                    statusDiv.className = 'mf-progress-card';
                    statusDiv.textContent = 'Compressing and converting image...';

                    const payload = {
                        format: selectedFormat,
                        quality: parseInt(slider.value, 10),
                        action: selectedAction,
                        width: modal.querySelector('#mf-opt-w').value ? parseInt(modal.querySelector('#mf-opt-w').value, 10) : null,
                        height: modal.querySelector('#mf-opt-h').value ? parseInt(modal.querySelector('#mf-opt-h').value, 10) : null,
                    };

                    fetch(`${apiUrl.replace('/manager', '')}/media/${media.id}/optimize`, {
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
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Run Optimization';

                        if (data.success) {
                            const savings = data.metrics.savings_percent;
                            statusDiv.className = 'mf-savings-badge';
                            statusDiv.innerHTML = `
                                <span><strong>✓ Optimized!</strong> Saved ${savings > 0 ? savings + '%' : 'size reduced'}.</span>
                                <span>${escapeHtml(data.media.human_size)}</span>
                            `;
                            setTimeout(() => {
                                modal.remove();
                                loadData();
                                renderInspector(data.media);
                            }, 1200);
                        } else {
                            statusDiv.className = 'mf-progress-card';
                            statusDiv.style.borderColor = 'var(--mf-danger)';
                            statusDiv.style.color = 'var(--mf-danger)';
                            statusDiv.textContent = 'Error: ' + (data.error || 'Optimization failed');
                        }
                    })
                    .catch(err => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Run Optimization';
                        statusDiv.textContent = 'Failed: ' + err.message;
                    });
                });
            }

            function deleteMedia(id) {
                const item = selectedItems.get(Number(id)) || currentMediaList.find(m => m.id === Number(id));
                const itemName = item ? item.name : 'this item';
                if (!confirm(`Are you sure you want to delete '${itemName}'?`)) {
                    return;
                }

                const tok = getRawCsrfToken(csrfToken, container);
                const headers = {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                };
                if (tok) headers['X-CSRF-TOKEN'] = tok;
                else {
                    const xsrf = getXsrfCookie();
                    if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;
                }

                const endpoint = `${apiUrl.replace(/\/manager\/?$/, '')}/media/${id}`;
                fetch(endpoint, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: headers
                })
                .then(res => {
                    return res.json().then(data => {
                        if (!res.ok) {
                            const err = new Error(data.error || data.message || `HTTP error ${res.status}`);
                            err.data = data;
                            throw err;
                        }
                        return data;
                    }).catch(e => {
                        if (e.data) throw e;
                        throw new Error(`HTTP error ${res.status}`);
                    });
                })
                .then(data => {
                    if (data.success) {
                        selectedItems.delete(Number(id));
                        loadData();
                        updateSelectionUI();
                        if (data.is_relation_warning) {
                            showToast(data.message, 'warning', 'Relation Notice', 7500);
                        } else {
                            showToast(data.message || 'Media deleted successfully.', 'success', 'Deleted');
                        }
                    } else {
                        if (data.is_relation_warning) {
                            showToast(data.error || data.message || 'Warning: This image cannot be deleted because it is linked to other records.', 'warning', 'Relation Warning', 7500);
                        } else {
                            showToast(data.error || data.message || 'Failed to delete media', 'error', 'Delete Failed');
                        }
                    }
                })
                .catch(err => {
                    console.error('MediaFlow delete error:', err);
                    if (err.data && err.data.is_relation_warning) {
                        showToast(err.data.error || err.data.message || 'Warning: This image cannot be deleted because it is linked to other records or database tables.', 'warning', 'Relation Warning', 7500);
                    } else {
                        showToast(err.message || 'Delete failed', 'error', 'Delete Failed');
                    }
                });
            }

            function bulkDeleteMedia(ids) {
                if (!ids || ids.length === 0) return;
                const count = ids.length;
                const itemLabel = count === 1 ? '1 item' : `${count} items`;
                if (!confirm(`Are you sure you want to permanently delete ${itemLabel}?`)) {
                    return;
                }

                const tok = getRawCsrfToken(csrfToken, container);
                const headers = {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                };
                if (tok) headers['X-CSRF-TOKEN'] = tok;
                else {
                    const xsrf = getXsrfCookie();
                    if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;
                }

                fetch(bulkUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: headers,
                    body: JSON.stringify({
                        action: 'delete',
                        ids: ids.map(id => Number(id))
                    })
                })
                .then(res => {
                    return res.json().then(data => {
                        if (!res.ok) {
                            const err = new Error(data.error || data.message || `HTTP error ${res.status}`);
                            err.data = data;
                            throw err;
                        }
                        return data;
                    }).catch(e => {
                        if (e.data) throw e;
                        throw new Error(`HTTP error ${res.status}`);
                    });
                })
                .then(data => {
                    if (data.success) {
                        ids.forEach(id => selectedItems.delete(Number(id)));
                        loadData();
                        updateSelectionUI();
                        if (data.is_relation_warning) {
                            showToast(data.message, 'warning', 'Relation Notice', 7500);
                        } else {
                            showToast(data.message || 'Items deleted successfully.', 'success', 'Deleted');
                        }
                    } else {
                        if (data.is_relation_warning) {
                            showToast(data.error || data.message || 'Warning: Selected items cannot be deleted because they are referenced by other database tables.', 'warning', 'Relation Warning', 7500);
                        } else {
                            showToast(data.error || data.message || 'Failed to delete items', 'error', 'Delete Failed');
                        }
                    }
                })
                .catch(err => {
                    console.error('MediaFlow bulk delete error:', err);
                    if (err.data && err.data.is_relation_warning) {
                        showToast(err.data.error || err.data.message || 'Warning: Selected items cannot be deleted because they are referenced by other database tables.', 'warning', 'Relation Warning', 7500);
                    } else {
                        showToast(err.message || 'Bulk delete failed', 'error', 'Delete Failed');
                    }
                });
            }

            // Keyboard Delete shortcut
            container.addEventListener('keydown', (e) => {
                if (e.key === 'Delete' || (e.key === 'Backspace' && !['INPUT', 'TEXTAREA'].includes(e.target?.tagName))) {
                    if (selectedItems.size > 0 && !document.querySelector('.mf-modal-backdrop')) {
                        e.preventDefault();
                        bulkDeleteMedia(Array.from(selectedItems.keys()));
                    }
                }
            });

            // Deselect on clicking empty background area of contentArea
            if (contentArea) {
                contentArea.addEventListener('click', (e) => {
                    if (e.target === contentArea || e.target === gridContainer) {
                        clearSelection();
                    }
                });
            }

            // Search with debounce
            if (searchInput) {
                let debounceTimer;
                searchInput.addEventListener('input', (e) => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        currentSearch = e.target.value.trim();
                        loadData();
                    }, 300);
                });
            }

            // New folder creation
            if (newFolderBtn && foldersUrl) {
                newFolderBtn.addEventListener('click', () => {
                    const name = prompt('Enter new folder name:');
                    if (!name || !name.trim()) return;

                    const tok = getRawCsrfToken(csrfToken, container);
                    const headers = {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    };
                    if (tok) headers['X-CSRF-TOKEN'] = tok;
                    else {
                        const xsrf = getXsrfCookie();
                        if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;
                    }

                    fetch(foldersUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: headers,
                        body: JSON.stringify({
                            name: name.trim(),
                            parent_id: currentFolderId
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(`Folder "${data.folder ? data.folder.name : name.trim()}" created successfully.`, 'success', 'Folder Created');
                            loadData();
                        } else {
                            showToast(data.error || 'Failed to create folder', 'error', 'Folder Error');
                        }
                    })
                    .catch(err => {
                        showToast('Failed to create folder: ' + err.message, 'error', 'Folder Error');
                    });
                });
            }

            // Picker Select Button
            if (pickerSelectBtn) {
                pickerSelectBtn.addEventListener('click', () => {
                    const selectedArray = Array.from(selectedItems.values());
                    container.dispatchEvent(new CustomEvent('mediaflow:picked', {
                        bubbles: true,
                        detail: { items: selectedArray, item: selectedArray[0] || null }
                    }));
                });
            }

            // Listen to global media upload events to refresh current directory
            document.addEventListener('mediaflow:uploaded', () => {
                loadData();
            });

            // Initial load
            loadData();
        },

        /**
         * Open Interactive 3D Upload Lifecycle & Engine Visualizer Modal for a file
         */
        openPipelineModal: function (file, options) {
            options = options || {};
            const uploadUrl = options.uploadUrl || '/mediaflow/upload';
            const csrfToken = getRawCsrfToken(options.csrfToken, options.container);
            const folder = options.folder || '';
            const collection = options.collection || 'default';
            const onComplete = options.onComplete || function () {};
            const onError = options.onError || function () {};

            function formatBytes(bytes) {
                if (!bytes || bytes === 0) return '0 B';
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }



            const rawSizeStr = formatBytes(file.size);
            const isImage = file.type && file.type.startsWith('image/');
            const rawExt = file.name.split('.').pop()?.toUpperCase() || (isImage ? 'IMG' : 'FILE');
            let previewUrl = null;
            if (isImage) {
                try {
                    previewUrl = URL.createObjectURL(file);
                } catch (e) {}
            }

            const existing = document.getElementById('mf-pipeline-modal-backdrop');
            if (existing) existing.remove();

            const backdrop = document.createElement('div');
            backdrop.id = 'mf-pipeline-modal-backdrop';
            backdrop.className = 'mf-pipeline-modal-backdrop';

            backdrop.innerHTML = `
                <div class="mf-pipeline-modal" role="dialog" aria-modal="true">
                    <div class="mf-pipeline-modal-header">
                        <div>
                            <div class="mf-pipeline-modal-title">
                                <span>✨</span>
                                <span>3D Upload Lifecycle & Engine Visualizer</span>
                            </div>
                            <div class="mf-pipeline-modal-desc">
                                Processing real file: <strong>${escapeHtml(file.name)}</strong> (${rawSizeStr})
                            </div>
                        </div>
                        <div class="mf-pipeline-modal-actions">
                            <span class="mf-pipeline-pill" id="mf-modal-stage-pill">Stage 1/5: Disk Pick</span>
                            <span class="mf-pipeline-pill" id="mf-modal-stat-savings" style="display: none; background: rgba(16, 185, 129, 0.2); color: #34d399; border-color: rgba(16, 185, 129, 0.4);">
                                ⚡ Size Savings
                            </span>
                            <button type="button" class="mf-pipeline-btn" id="mf-modal-toggle-3d">📐 Toggle 3D / Flat</button>
                            <button type="button" class="mf-pipeline-close-btn" id="mf-modal-close" aria-label="Close modal">&times;</button>
                        </div>
                    </div>

                    <div class="mf-pipeline-viewport" id="mf-modal-viewport">
                        <div class="mf-pipeline-conduit-track" style="display: none !important;">
                            <div class="mf-pipeline-conduit-beam" id="mf-modal-conduit" style="display: none !important;"></div>
                        </div>

                        <div class="mf-pipeline-deck" id="mf-modal-deck">
                            <div class="mf-pipeline-pedestal active" data-step="1" id="mf-modal-ped-1">
                                <div class="mf-pipeline-ped-icon">🖥️</div>
                                <div class="mf-pipeline-ped-step">Step 1</div>
                                <div class="mf-pipeline-ped-title">Disk Pick</div>
                                <div class="mf-pipeline-ped-desc">Raw file loaded from client disk</div>
                            </div>
                            <div class="mf-pipeline-pedestal" data-step="2" id="mf-modal-ped-2">
                                <div class="mf-pipeline-ped-icon">📥</div>
                                <div class="mf-pipeline-ped-step">Step 2</div>
                                <div class="mf-pipeline-ped-title">Temp Buffer</div>
                                <div class="mf-pipeline-ped-desc">Chunk staging in <code>mediaflow-temp</code> & EXIF scrub</div>
                            </div>
                            <div class="mf-pipeline-pedestal" data-step="3" id="mf-modal-ped-3">
                                <div class="mf-pipeline-ped-icon">⚡</div>
                                <div class="mf-pipeline-ped-step">Step 3</div>
                                <div class="mf-pipeline-ped-title">Engine & Rename</div>
                                <div class="mf-pipeline-ped-desc">UUID hashing, WebP conversion & byte compression</div>
                            </div>
                            <div class="mf-pipeline-pedestal" data-step="4" id="mf-modal-ped-4">
                                <div class="mf-pipeline-ped-icon">🗄️</div>
                                <div class="mf-pipeline-ped-step">Step 4</div>
                                <div class="mf-pipeline-ped-title">Target Storage</div>
                                <div class="mf-pipeline-ped-desc">SHA-256 integrity check & variant generation</div>
                            </div>
                            <div class="mf-pipeline-pedestal" data-step="5" id="mf-modal-ped-5">
                                <div class="mf-pipeline-ped-icon">🌐</div>
                                <div class="mf-pipeline-ped-step">Step 5</div>
                                <div class="mf-pipeline-ped-title">Delivery CDN</div>
                                <div class="mf-pipeline-ped-desc">Edge CDN routing or HMAC signed streaming</div>
                            </div>
                        </div>

                        <div class="mf-pipeline-token" id="mf-modal-token">
                            <div class="mf-pipeline-token-thumb">
                                ${previewUrl ? `<img src="${previewUrl}" alt="Upload Preview">` : `<span>📄</span>`}
                                <div class="mf-pipeline-token-scanner"></div>
                            </div>
                            <div class="mf-pipeline-token-name" id="mf-modal-token-name">${escapeHtml(file.name)}</div>
                            <div class="mf-pipeline-token-meta">
                                <span id="mf-modal-token-size">${rawSizeStr}</span>
                                <span class="mf-pipeline-token-badge" id="mf-modal-token-badge">${rawExt}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mf-pipeline-footer">
                        <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1;">
                            <div class="mf-pipeline-progress-wrap">
                                <div class="mf-pipeline-progress-bar" id="mf-modal-prog-bar"></div>
                            </div>
                            <span style="font-size: 0.8rem; color: #94a3b8; white-space: nowrap;" id="mf-modal-prog-text">
                                Preparing upload stream...
                            </span>
                        </div>
                        <div style="display: flex; gap: 0.5rem;" id="mf-modal-footer-actions">
                            <button type="button" class="mf-pipeline-btn primary" id="mf-modal-done-btn" style="display: none;">
                                ✨ Done / View Asset
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(backdrop);
            requestAnimationFrame(() => backdrop.classList.add('active'));

            const viewport = backdrop.querySelector('#mf-modal-viewport');
            const deck = backdrop.querySelector('#mf-modal-deck');
            const token = backdrop.querySelector('#mf-modal-token');
            const conduit = backdrop.querySelector('#mf-modal-conduit');
            const stagePill = backdrop.querySelector('#mf-modal-stage-pill');
            const toggle3dBtn = backdrop.querySelector('#mf-modal-toggle-3d');
            const closeBtn = backdrop.querySelector('#mf-modal-close');
            const doneBtn = backdrop.querySelector('#mf-modal-done-btn');
            const progBar = backdrop.querySelector('#mf-modal-prog-bar');
            const progText = backdrop.querySelector('#mf-modal-prog-text');

            const tokenName = backdrop.querySelector('#mf-modal-token-name');
            const tokenSize = backdrop.querySelector('#mf-modal-token-size');
            const tokenBadge = backdrop.querySelector('#mf-modal-token-badge');
            const statSavings = backdrop.querySelector('#mf-modal-stat-savings');

            let currentStep = 1;
            let uploadedMedia = null;
            let xhr = null;

            function positionToken(step, customData) {
                currentStep = step;
                const ped = backdrop.querySelector('#mf-modal-ped-' + step);
                if (!ped) return;

                backdrop.querySelectorAll('.mf-pipeline-pedestal').forEach(p => p.classList.remove('active'));
                ped.classList.add('active');

                const pedRect = ped.getBoundingClientRect();
                const viewRect = viewport.getBoundingClientRect();
                const left = pedRect.left - viewRect.left + (pedRect.width / 2) - (token.offsetWidth / 2);
                const top = pedRect.top - viewRect.top - 55;

                token.style.left = left + 'px';
                token.style.top = top + 'px';

                if (step === 1) {
                    conduit.style.width = '0%';
                    token.classList.remove('scanning');
                    tokenName.textContent = file.name;
                    tokenSize.textContent = rawSizeStr;
                    tokenBadge.textContent = rawExt;
                    tokenBadge.style.background = '#4f46e5';
                    stagePill.textContent = 'Stage 1/5: Disk Pick';
                    if (statSavings) statSavings.style.display = 'none';
                } else if (step === 2) {
                    conduit.style.width = '25%';
                    token.classList.add('scanning');
                    tokenBadge.textContent = 'BUFFER';
                    tokenBadge.style.background = '#3b82f6';
                    stagePill.textContent = 'Stage 2/5: Temp Buffer';
                    if (statSavings) statSavings.style.display = 'none';
                } else if (step === 3) {
                    conduit.style.width = '50%';
                    token.classList.remove('scanning');
                    tokenName.textContent = 'generating-uuid.webp';
                    tokenBadge.textContent = 'OPTIMIZING';
                    tokenBadge.style.background = '#f59e0b';
                    stagePill.textContent = 'Stage 3/5: Engine & Rename';
                    if (statSavings) statSavings.style.display = 'none';
                } else if (step === 4) {
                    conduit.style.width = '75%';
                    token.classList.remove('scanning');
                    const media = customData || uploadedMedia;
                    if (media) {
                        tokenName.textContent = media.uuid ? `${media.uuid.slice(0, 8)}....webp` : 'persisted.webp';
                        tokenSize.textContent = media.human_size || rawSizeStr;
                        tokenBadge.textContent = 'PERSISTED';
                        tokenBadge.style.background = '#10b981';
                        if (statSavings && file.size && media.size_bytes && file.size > media.size_bytes) {
                            const pct = Math.round((1 - (media.size_bytes / file.size)) * 100);
                            statSavings.textContent = `⚡ -${pct}% Size Savings`;
                            statSavings.style.display = 'inline-flex';
                        }
                    }
                    stagePill.textContent = 'Stage 4/5: Target Storage';
                } else if (step === 5) {
                    conduit.style.width = '100%';
                    token.classList.remove('scanning');
                    const media = customData || uploadedMedia;
                    tokenBadge.textContent = 'READY';
                    tokenBadge.style.background = '#10b981';
                    stagePill.textContent = '✓ Stage 5/5: Delivery Ready';
                    if (statSavings && media && file.size && media.size_bytes && file.size > media.size_bytes) {
                        const pct = Math.round((1 - (media.size_bytes / file.size)) * 100);
                        statSavings.textContent = `⚡ -${pct}% Size Savings`;
                        statSavings.style.display = 'inline-flex';
                    }
                    progBar.style.width = '100%';
                    progBar.style.background = '#10b981';
                    progText.textContent = '✓ Pipeline Processing Complete!';
                    doneBtn.style.display = 'inline-flex';
                }
            }

            let is3d = true;
            backdrop.addEventListener('mousemove', (e) => {
                if (!is3d) return;
                const rect = backdrop.getBoundingClientRect();
                const x = (e.clientX - rect.left) / rect.width - 0.5;
                const y = (e.clientY - rect.top) / rect.height - 0.5;
                const rotX = 18 - (y * 16);
                const rotY = -5 + (x * 20);
                deck.style.transform = `rotateX(${rotX}deg) rotateY(${rotY}deg) rotateZ(0.5deg)`;
            });

            backdrop.addEventListener('mouseleave', () => {
                if (is3d) deck.style.transform = `rotateX(18deg) rotateY(-5deg) rotateZ(0.5deg)`;
            });

            toggle3dBtn.addEventListener('click', () => {
                is3d = !is3d;
                deck.classList.toggle('flat-mode', !is3d);
                toggle3dBtn.textContent = is3d ? '📐 Toggle 3D / Flat' : '🧊 Switch to 3D View';
                positionToken(currentStep);
            });

            backdrop.querySelectorAll('.mf-pipeline-pedestal').forEach(ped => {
                ped.addEventListener('click', () => {
                    const targetStep = parseInt(ped.getAttribute('data-step'));
                    positionToken(targetStep);
                });
            });

            function closeModal() {
                if (xhr && xhr.readyState !== 4) {
                    xhr.abort();
                }
                if (previewUrl) {
                    try { URL.revokeObjectURL(previewUrl); } catch (e) {}
                }
                backdrop.classList.remove('active');
                setTimeout(() => backdrop.remove(), 300);
            }

            closeBtn.addEventListener('click', closeModal);
            doneBtn.addEventListener('click', () => {
                if (uploadedMedia) onComplete(uploadedMedia);
                closeModal();
            });

            document.addEventListener('keydown', function escHandler(e) {
                if (e.key === 'Escape' && document.getElementById('mf-pipeline-modal-backdrop')) {
                    document.removeEventListener('keydown', escHandler);
                    closeModal();
                }
            });

            setTimeout(() => {
                positionToken(1);
                setTimeout(() => {
                    positionToken(2);
                    startUpload();
                }, 350);
            }, 100);

            window.addEventListener('resize', () => positionToken(currentStep));

            function startUpload(isRetry) {
                const formData = new FormData();
                formData.append('file', file);
                let targetFolder = null;
                if (options.folder_id !== undefined && options.folder_id !== null && options.folder_id !== '' && options.folder_id !== 'null' && options.folder_id !== 'undefined') {
                    targetFolder = String(options.folder_id);
                } else if (options.folder !== undefined && options.folder !== null && options.folder !== '' && options.folder !== 'null' && options.folder !== 'undefined') {
                    targetFolder = String(options.folder);
                }
                if (targetFolder) {
                    formData.append('folder', targetFolder);
                    formData.append('folder_id', targetFolder);
                }
                if (collection) formData.append('collection', collection);

                xhr = new XMLHttpRequest();
                xhr.open('POST', uploadUrl, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                applyCsrfHeaders(xhr, csrfToken, options.container, formData);

                xhr.upload.onprogress = function (e) {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        progBar.style.width = Math.min(percent, 90) + '%';
                        progText.textContent = `Streaming chunks: ${percent}% (${formatBytes(e.loaded)} / ${formatBytes(e.total)})`;

                        if (percent >= 100 && currentStep === 2) {
                            positionToken(3);
                            progText.textContent = 'Server engine converting to WebP & generating UUID...';
                        }
                    }
                };

                xhr.onload = function () {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            const res = JSON.parse(xhr.responseText);
                            if (res.success && res.media) {
                                uploadedMedia = res.media;
                                progBar.style.width = '95%';
                                progText.textContent = 'Persisting to target storage & generating variants...';
                                positionToken(4, res.media);

                                document.dispatchEvent(new CustomEvent('mediaflow:uploaded', {
                                    detail: { media: res.media }
                                }));

                                setTimeout(() => {
                                    positionToken(5, res.media);
                                    if (typeof onComplete === 'function') {
                                        onComplete(res.media);
                                    }
                                }, 650);
                            } else {
                                handleModalError(res.error || 'Server rejected file upload', xhr.status);
                            }
                        } catch (e) {
                            handleModalError('Malformed response from server', xhr.status);
                        }
                    } else if (xhr.status === 419 && !isRetry) {
                        progText.textContent = 'Session token refreshed, retrying upload...';
                        fetchFreshCsrfToken((fresh) => {
                            if (fresh) {
                                startUpload(true);
                            } else {
                                handleModalError('Session expired or CSRF token mismatch (419). Please refresh the page.', 419);
                            }
                        });
                    } else {
                        let errMsg = `Upload failed (${xhr.status})`;
                        if (xhr.status === 419) {
                            errMsg = 'Session expired or CSRF token mismatch (419). Please refresh the page.';
                        } else if (xhr.status === 413) {
                            errMsg = 'File is too large for server limits (413 Payload Too Large).';
                        } else {
                            try {
                                const errRes = JSON.parse(xhr.responseText);
                                if (errRes.error) errMsg = errRes.error;
                            } catch (e) {}
                        }
                        handleModalError(errMsg, xhr.status);
                    }
                };

                xhr.onerror = function () {
                    handleModalError('Network error while uploading', 0);
                };

                xhr.send(formData);
            }

            function handleModalError(msg, status) {
                token.classList.remove('scanning');
                if (conduit) conduit.style.background = '#ef4444';
                tokenBadge.textContent = 'ERROR';
                tokenBadge.style.background = '#ef4444';
                stagePill.textContent = '⚠️ Upload Error';
                stagePill.style.background = 'rgba(239, 68, 68, 0.2)';
                stagePill.style.color = '#f87171';
                progBar.style.background = '#ef4444';
                progText.textContent = `Error: ${msg}`;
                progText.style.color = '#f87171';

                const footerActions = backdrop.querySelector('#mf-modal-footer-actions');
                if (footerActions && status === 419) {
                    footerActions.innerHTML = `
                        <button type="button" class="mf-pipeline-btn primary" id="mf-modal-retry-btn" style="background: #4f46e5; border-color: #4f46e5; display: inline-flex;">
                            🔄 Refresh Token & Retry
                        </button>
                    `;
                    footerActions.querySelector('#mf-modal-retry-btn')?.addEventListener('click', () => {
                        progText.textContent = 'Fetching fresh session token...';
                        fetchFreshCsrfToken((fresh) => {
                            if (fresh) {
                                progText.textContent = 'Retrying upload...';
                                tokenBadge.textContent = rawExt;
                                tokenBadge.style.background = '#4f46e5';
                                stagePill.textContent = 'Stage 2/5: Temp Buffer';
                                stagePill.style.background = '';
                                stagePill.style.color = '';
                                progBar.style.background = '';
                                startUpload(true);
                            } else {
                                window.location.reload();
                            }
                        });
                    });
                }

                if (typeof onError === 'function') onError(msg);
            }
        },

        /**
         * Initialize Shortcut Form Input Field (<x-mediaflow-input />)
         */
        initInput: function (container) {
            const dropzone = container.querySelector('.mf-input-dropzone');
            const fileInput = container.querySelector('.mf-file-input');
            const hiddenInput = container.querySelector('.mf-input-hidden-value');
            const previewCard = container.querySelector('.mf-input-preview-card');
            const thumbBox = container.querySelector('.mf-input-thumb-box');
            const metaName = container.querySelector('.mf-input-meta-name');
            const metaSub = container.querySelector('.mf-input-meta-sub');
            const replaceBtn = container.querySelector('.mf-input-replace-btn');
            const removeBtn = container.querySelector('.mf-input-remove-btn');
            const pickerBtn = container.querySelector('.mf-input-picker-btn');
            const viewBtn = container.querySelector('.mf-input-view-btn');
            const copyBtn = container.querySelector('.mf-input-copy-btn');
            let currentMediaUrl = container.dataset.currentUrl || '';

            const uploadUrl = resolveEndpointUrl(container.dataset.uploadUrl || '/mediaflow/upload');
            const managerUrl = resolveEndpointUrl(container.dataset.managerUrl || '/mediaflow/manager');
            const folder = container.dataset.folder || '';
            const collection = container.dataset.collection || 'default';
            const csrfToken = getRawCsrfToken(null, container);

            if (!dropzone || !fileInput) return;

            // Open in new tab (fullscreen view)
            if (viewBtn) {
                viewBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const url = currentMediaUrl || container.dataset.currentUrl;
                    if (url) {
                        window.open(url, '_blank');
                    } else {
                        showToast('No image URL available to open.', 'info', 'Notice');
                    }
                });
            }

            // Copy image URL
            if (copyBtn) {
                copyBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const url = currentMediaUrl || container.dataset.currentUrl;
                    if (url) {
                        copyTextToClipboard(url, 'Image URL copied to clipboard!');
                    } else {
                        showToast('No image URL available to copy.', 'info', 'Notice');
                    }
                });
            }

            // Click dropzone to browse
            dropzone.addEventListener('click', (e) => {
                if (e.target.closest('.mf-input-picker-btn')) return;
                fileInput.click();
            });

            // Replace button triggers file browse
            if (replaceBtn) {
                replaceBtn.addEventListener('click', () => {
                    fileInput.click();
                });
            }

            // Remove button clears selection
            if (removeBtn) {
                removeBtn.addEventListener('click', () => {
                    if (hiddenInput) hiddenInput.value = '';
                    if (previewCard) previewCard.style.display = 'none';
                    if (dropzone) dropzone.style.display = 'block';
                    currentMediaUrl = '';
                    container.dataset.currentUrl = '';
                    if (viewBtn) viewBtn.style.display = 'none';
                    if (copyBtn) copyBtn.style.display = 'none';
                    container.dataset.hasValue = 'false';
                    container.dispatchEvent(new CustomEvent('mediaflow:removed', { bubbles: true }));
                });
            }

            // Drag & drop handlers
            ['dragenter', 'dragover'].forEach(name => {
                dropzone.addEventListener(name, (e) => {
                    e.preventDefault();
                    dropzone.classList.add('mf-dragover');
                });
            });

            ['dragleave', 'drop'].forEach(name => {
                dropzone.addEventListener(name, (e) => {
                    e.preventDefault();
                    dropzone.classList.remove('mf-dragover');
                });
            });

            dropzone.addEventListener('drop', (e) => {
                if (e.dataTransfer?.files?.length) {
                    processFile(e.dataTransfer.files[0]);
                }
            });

            fileInput.addEventListener('change', () => {
                if (fileInput.files?.length) {
                    processFile(fileInput.files[0]);
                    fileInput.value = '';
                }
            });

            const useInputPipeline = container.dataset.pipeline === 'true' || container.dataset.visualizer === 'true';

            const acceptRule = (container.dataset.accept || fileInput.getAttribute('accept') || '*').trim();
            const typeRule = (container.dataset.type || '').trim().toLowerCase();

            function processFile(file) {
                if (!file) return;
                if (!isFileAccepted(file, acceptRule)) {
                    showToast(`Disallowed file type: "${file.name}". Please choose a file matching: ${acceptRule}`, 'warning', 'Invalid File Type');
                    return;
                }

                if (useInputPipeline && typeof window.MediaFlow.openPipelineModal === 'function') {
                    window.MediaFlow.openPipelineModal(file, {
                        container: container,
                        uploadUrl: uploadUrl,
                        folder: folder,
                        collection: collection,
                        csrfToken: getRawCsrfToken(csrfToken, container),
                        onComplete: function (media) {
                            setMedia(media);
                        }
                    });
                } else {
                    uploadDirectInputFile(file);
                }
            }

            function uploadDirectInputFile(file, isRetry) {
                const titleEl = dropzone.querySelector('.mf-input-title');
                const origTitle = titleEl ? titleEl.innerHTML : '';
                if (titleEl) {
                    titleEl.innerHTML = `<span style="display: inline-flex; align-items: center; gap: 0.4rem; color: var(--mf-primary); font-weight: 600;"><span>⏳</span> Uploading ${escapeHtml(file.name)}...</span>`;
                }

                const formData = new FormData();
                formData.append('file', file);
                if (folder) {
                    formData.append('folder', folder);
                    formData.append('folder_id', folder);
                }
                if (collection) formData.append('collection', collection);

                const xhr = new XMLHttpRequest();
                xhr.open('POST', uploadUrl, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                applyCsrfHeaders(xhr, csrfToken, container, formData);

                xhr.upload.onprogress = function (e) {
                    if (e.lengthComputable && titleEl) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        titleEl.innerHTML = `<span style="display: inline-flex; align-items: center; gap: 0.4rem; color: var(--mf-primary); font-weight: 600;"><span>⏳</span> Uploading ${percent}%...</span>`;
                    }
                };

                xhr.onload = function () {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            const res = JSON.parse(xhr.responseText);
                            if (res.success && res.media) {
                                setMedia(res.media);
                                document.dispatchEvent(new CustomEvent('mediaflow:uploaded', {
                                    detail: { media: res.media }
                                }));
                                showToast(`"${res.media.original_name || 'File'}" uploaded successfully.`, 'success', 'Uploaded');
                            } else {
                                if (titleEl) titleEl.innerHTML = origTitle;
                                showToast(res.error || 'Failed to upload media', 'error', 'Upload Error');
                            }
                        } catch (e) {
                            if (titleEl) titleEl.innerHTML = origTitle;
                            showToast('Upload response error: invalid JSON response', 'error', 'Upload Error');
                        }
                    } else if (xhr.status === 419 && !isRetry) {
                        fetchFreshCsrfToken((fresh) => {
                            if (fresh) uploadDirectInputFile(file, true);
                            else {
                                if (titleEl) titleEl.innerHTML = origTitle;
                                showToast('Session expired or CSRF mismatch (419). Please refresh the page.', 'error', 'Session Expired');
                            }
                        });
                    } else {
                        if (titleEl) titleEl.innerHTML = origTitle;
                        let errMsg = `Upload failed (${xhr.status})`;
                        try {
                            const err = JSON.parse(xhr.responseText);
                            if (err.error) errMsg = err.error;
                        } catch (e) {}
                        showToast(errMsg, 'error', 'Upload Error');
                    }
                };

                xhr.onerror = function () {
                    if (titleEl) titleEl.innerHTML = origTitle;
                    showToast('Network error while uploading', 'error', 'Network Error');
                };

                xhr.send(formData);
            }

            function setMedia(media) {
                if (!media) return;
                if (hiddenInput) hiddenInput.value = media.id || media.uuid;

                const fullUrl = safeUrl(resolveMediaUrl(media.url));
                currentMediaUrl = fullUrl;
                container.dataset.currentUrl = fullUrl;
                if (viewBtn) viewBtn.style.display = 'inline-flex';
                if (copyBtn) copyBtn.style.display = 'inline-flex';

                if (thumbBox) {
                    const imgUrl = safeUrl(resolveMediaUrl(media.thumbnail_url || media.url));
                    const ext = (media.extension || (media.original_name ? media.original_name.split('.').pop() : '')).toLowerCase();
                    const mime = (media.mime_type || '').toLowerCase();
                    const isImg = media.is_image || mime.startsWith('image/') || imgUrl.match(/\.(jpeg|jpg|png|webp|gif|svg)($|\?)/i);
                    const isVid = media.is_video || mime.startsWith('video/') || ['mp4', 'webm', 'ogg', 'mov'].includes(ext);
                    const isAud = media.is_audio || mime.startsWith('audio/') || ['mp3', 'wav', 'ogg', 'm4a'].includes(ext);
                    const isDoc = media.is_document || mime === 'application/pdf' || ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'].includes(ext);

                    if (isImg) {
                        thumbBox.innerHTML = `<img src="${imgUrl}" alt="${escapeHtml(media.original_name || media.name || 'Preview')}" class="mf-input-preview-img" onerror="this.onerror=null; if('${fullUrl}' && this.src !== '${fullUrl}') { this.src='${fullUrl}'; }">`;
                    } else if (ext === 'pdf' || mime === 'application/pdf') {
                        thumbBox.innerHTML = `<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(239, 68, 68, 0.12); color: #ef4444; border-radius: 6px;"><span style="font-size: 1.5rem;">📄</span><span style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase;">PDF</span></div>`;
                    } else if (isVid) {
                        thumbBox.innerHTML = `<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(168, 85, 247, 0.12); color: #a855f7; border-radius: 6px;"><span style="font-size: 1.5rem;">🎬</span><span style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase;">${ext.toUpperCase() || 'VIDEO'}</span></div>`;
                    } else if (isAud) {
                        thumbBox.innerHTML = `<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(6, 182, 212, 0.12); color: #06b6d4; border-radius: 6px;"><span style="font-size: 1.5rem;">🎵</span><span style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase;">${ext.toUpperCase() || 'AUDIO'}</span></div>`;
                    } else if (isDoc) {
                        thumbBox.innerHTML = `<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(59, 130, 246, 0.12); color: #3b82f6; border-radius: 6px;"><span style="font-size: 1.5rem;">📝</span><span style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase;">${ext.toUpperCase() || 'DOC'}</span></div>`;
                    } else {
                        thumbBox.innerHTML = `<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(148, 163, 184, 0.12); color: #94a3b8; border-radius: 6px;"><span style="font-size: 1.5rem;">📁</span><span style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase;">${ext.toUpperCase() || 'FILE'}</span></div>`;
                    }
                }

                if (metaName) metaName.textContent = media.original_name || media.uuid || 'Selected Media';
                if (metaSub) metaSub.textContent = media.human_size || (media.size_bytes ? formatBytes(media.size_bytes) : 'Uploaded');

                if (dropzone) dropzone.style.display = 'none';
                if (previewCard) previewCard.style.display = 'flex';
                container.dataset.hasValue = 'true';

                container.dispatchEvent(new CustomEvent('mediaflow:change', {
                    bubbles: true,
                    detail: { media: media }
                }));
            }

            // Picker modal button
            if (pickerBtn) {
                pickerBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    window.MediaFlow.openPickerModal({
                        container: container,
                        theme: container.dataset.mfTheme,
                        mode: container.dataset.mfMode,
                        folderColor: container.dataset.mfFolder,
                        managerUrl: managerUrl,
                        uploadUrl: uploadUrl,
                        folder: folder,
                        collection: collection,
                        isolated: container.dataset.isolated === 'true',
                        scope: container.dataset.pickerScope || 'all',
                        accept: acceptRule,
                        type: typeRule,
                        csrfToken: getRawCsrfToken(csrfToken, container),
                        onSelect: function (media) {
                            setMedia(media);
                        }
                    });
                });
            }

            function formatBytes(bytes) {
                if (!bytes || bytes === 0) return '0 B';
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
        },

        /**
         * Open Media Library Picker Modal with Folder Navigation and Direct Upload
         */
        openPickerModal: function (options) {
            options = options || {};
            const managerUrl = resolveEndpointUrl(options.managerUrl || '/mediaflow/manager');
            const uploadUrl = resolveEndpointUrl(options.uploadUrl || '/mediaflow/upload');
            const onSelect = options.onSelect || function () {};
            const csrfToken = getRawCsrfToken(options.csrfToken, options.container);
            const pickerAccept = (options.accept || '*').trim();
            const pickerType = (options.type || '').trim().toLowerCase();

            let currentFolderId = null;
            let currentFolderName = '';
            let currentSearch = '';

            const existing = document.getElementById('mf-picker-modal-backdrop');
            if (existing) existing.remove();

            const backdrop = document.createElement('div');
            backdrop.id = 'mf-picker-modal-backdrop';
            backdrop.className = 'mf-modal-backdrop active';
            backdrop.style.zIndex = '9999999';

            // Inherit theme, color mode, and folder styling
            const callerTheme = options.theme || options.container?.dataset.mfTheme || document.documentElement.getAttribute('data-mf-theme') || document.body.getAttribute('data-mf-theme');
            const callerMode = options.mode || options.container?.dataset.mfMode || document.documentElement.getAttribute('data-mf-mode') || document.body.getAttribute('data-mf-mode');
            const callerFolder = options.folderColor || options.container?.dataset.mfFolder || document.documentElement.getAttribute('data-mf-folder') || document.body.getAttribute('data-mf-folder');
            if (callerTheme) backdrop.setAttribute('data-mf-theme', callerTheme);
            if (callerMode) backdrop.setAttribute('data-mf-mode', callerMode);
            if (callerFolder) backdrop.setAttribute('data-mf-folder', callerFolder);

            const isFrontendScope = options.scope === 'frontend' || options.isolated === true;
            const headerTitleText = isFrontendScope ? (options.folder ? `Select from '${escapeHtml(options.folder)}'` : 'Select Media') : 'Select Media from Library';
            const rootCrumbText = isFrontendScope ? (options.folder || options.collection || 'Files') : 'All Media';

            backdrop.innerHTML = `
                <div class="mf-modal mf-picker-modal">
                    <div class="mf-picker-header">
                        <div style="font-weight: 700; font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem; color: var(--mf-text-main);">
                            <span>🗂</span>
                            <span>${headerTitleText}</span>
                        </div>
                        <button type="button" class="mf-btn" id="mf-picker-close-btn" style="border: none; font-size: 1.4rem; line-height: 1; padding: 0.2rem 0.5rem; cursor: pointer;">&times;</button>
                    </div>

                    <div class="mf-picker-toolbar">
                        <div class="mf-picker-breadcrumbs" id="mf-picker-breadcrumbs">
                            <span class="mf-picker-crumb active">${escapeHtml(rootCrumbText)}</span>
                        </div>

                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <div class="mf-search-box" style="width: 200px;">
                                <svg class="mf-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="text" class="mf-search-input" id="mf-picker-search" placeholder="Search...">
                            </div>

                            <label class="mf-btn mf-btn-primary" style="cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.825rem; padding: 0.45rem 0.8rem;">
                                <span>📤</span>
                                <span id="mf-picker-upload-btn-text">Upload File</span>
                                <input type="file" id="mf-picker-file-input" accept="${escapeHtml(pickerAccept)}" style="display: none;">
                            </label>
                        </div>
                    </div>

                    <div id="mf-picker-modal-content" style="flex: 1; overflow-y: auto; min-height: 380px; position: relative;">
                        <div class="mf-drop-overlay" id="mf-picker-drop-overlay" style="display: none;">
                            <div class="mf-drop-overlay-box">
                                <span style="font-size: 2.75rem;">📥</span>
                                <div class="mf-drop-overlay-text" style="font-weight: 700; margin-top: 0.4rem;">Drop file to upload to this directory</div>
                            </div>
                        </div>
                        <div id="mf-picker-list-area">
                            <div style="text-align: center; padding: 3rem; color: var(--mf-text-muted);">
                                Loading media library...
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(backdrop);

            let isModalActive = true;
            const onMediaUploaded = () => {
                if (isModalActive) {
                    loadPickerData();
                }
            };
            document.addEventListener('mediaflow:uploaded', onMediaUploaded);

            function close() {
                isModalActive = false;
                document.removeEventListener('mediaflow:uploaded', onMediaUploaded);
                backdrop.remove();
            }

            backdrop.querySelector('#mf-picker-close-btn').addEventListener('click', close);
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) close();
            });

            const contentArea = backdrop.querySelector('#mf-picker-modal-content');
            const dropOverlay = backdrop.querySelector('#mf-picker-drop-overlay');
            const listArea = backdrop.querySelector('#mf-picker-list-area');
            const breadcrumbsEl = backdrop.querySelector('#mf-picker-breadcrumbs');
            const uploadBtnText = backdrop.querySelector('#mf-picker-upload-btn-text');
            const fileInput = backdrop.querySelector('#mf-picker-file-input');
            const searchInput = backdrop.querySelector('#mf-picker-search');

            // Search filtering
            let searchDebounce;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchDebounce);
                searchDebounce = setTimeout(() => {
                    currentSearch = e.target.value.trim();
                    loadPickerData();
                }, 300);
            });

            // Direct upload within picker
            fileInput.addEventListener('change', () => {
                if (fileInput.files && fileInput.files[0]) {
                    uploadAndSelect(fileInput.files[0]);
                    fileInput.value = '';
                }
            });

            // Drag and drop within picker
            ['dragenter', 'dragover'].forEach(name => {
                contentArea.addEventListener(name, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropOverlay.style.display = 'flex';
                });
            });

            ['dragleave', 'drop'].forEach(name => {
                contentArea.addEventListener(name, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    if (e.type === 'drop') {
                        dropOverlay.style.display = 'none';
                        if (e.dataTransfer?.files?.length) {
                            uploadAndSelect(e.dataTransfer.files[0]);
                        }
                    } else if (e.target === dropOverlay || !contentArea.contains(e.relatedTarget)) {
                        dropOverlay.style.display = 'none';
                    }
                });
            });

            function uploadAndSelect(file) {
                if (!file) return;
                if (pickerAccept && pickerAccept !== '*' && pickerAccept !== '*/*') {
                    if (!isFileAccepted(file, pickerAccept)) {
                        showToast(`Disallowed file type: "${file.name}". Please choose a file matching: ${pickerAccept}`, 'warning', 'Invalid File Type');
                        return;
                    }
                }

                listArea.innerHTML = `
                    <div style="text-align: center; padding: 3rem; color: var(--mf-primary);">
                        <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">⚡</div>
                        <div style="font-weight: 700;">Uploading & optimizing file to ${currentFolderName || 'All Media'}...</div>
                    </div>
                `;

                const formData = new FormData();
                formData.append('file', file);
                let targetFolder = null;
                if (currentFolderId !== null && currentFolderId !== undefined && currentFolderId !== '' && currentFolderId !== 'null' && currentFolderId !== 'undefined') {
                    targetFolder = String(currentFolderId);
                } else if (options.folder_id !== undefined && options.folder_id !== null && options.folder_id !== '' && options.folder_id !== 'null' && options.folder_id !== 'undefined') {
                    targetFolder = String(options.folder_id);
                } else if (options.folder !== undefined && options.folder !== null && options.folder !== '' && options.folder !== 'null' && options.folder !== 'undefined') {
                    targetFolder = String(options.folder);
                }
                if (targetFolder) {
                    formData.append('folder', targetFolder);
                    formData.append('folder_id', targetFolder);
                }

                const xhr = new XMLHttpRequest();
                xhr.open('POST', uploadUrl, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                applyCsrfHeaders(xhr, csrfToken, options.container, formData);

                xhr.onload = function () {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            const res = JSON.parse(xhr.responseText);
                            if (res.success && res.media) {
                                document.dispatchEvent(new CustomEvent('mediaflow:uploaded', {
                                    detail: { media: res.media }
                                }));
                                onSelect(res.media);
                                close();
                                return;
                            }
                        } catch (e) {}
                    }

                    if (xhr.status === 419) {
                        fetchFreshCsrfToken((fresh) => {
                            if (fresh) {
                                uploadAndSelect(file);
                            } else {
                                showToast('Session expired or CSRF token mismatch (419). Please refresh the page.', 'error', 'Session Expired');
                                loadPickerData();
                            }
                        });
                        return;
                    }

                    showToast('Failed to upload file. Please try again.', 'error', 'Upload Error');
                    loadPickerData();
                };

                xhr.onerror = function () {
                    showToast('Network transport error while uploading.', 'error', 'Network Error');
                    loadPickerData();
                };

                xhr.send(formData);
            }

            function renderBreadcrumbs(breadcrumbs) {
                breadcrumbsEl.innerHTML = '';

                if (breadcrumbs && breadcrumbs.length > 0) {
                    currentFolderName = breadcrumbs[breadcrumbs.length - 1].name;
                } else {
                    currentFolderName = '';
                }

                if (uploadBtnText) {
                    uploadBtnText.textContent = currentFolderName ? `Upload to ${currentFolderName}` : 'Upload File';
                }

                // Root item
                const root = document.createElement('span');
                root.className = 'mf-picker-crumb' + (currentFolderId === null ? ' active' : '');
                root.textContent = rootCrumbText;
                root.addEventListener('click', () => {
                    currentFolderId = null;
                    loadPickerData();
                });
                breadcrumbsEl.appendChild(root);

                breadcrumbs.forEach((b, idx) => {
                    const sep = document.createElement('span');
                    sep.textContent = ' / ';
                    sep.style.color = 'var(--mf-text-subtle)';
                    breadcrumbsEl.appendChild(sep);

                    const crumb = document.createElement('span');
                    crumb.className = 'mf-picker-crumb' + (idx === breadcrumbs.length - 1 ? ' active' : '');
                    crumb.textContent = b.name;
                    crumb.addEventListener('click', () => {
                        currentFolderId = b.id;
                        loadPickerData();
                    });
                    breadcrumbsEl.appendChild(crumb);
                });
            }

            function loadPickerData() {
                let url = `${managerUrl}?mode=picker&ajax=1`;
                if (currentFolderId !== null) {
                    url += `&folder_id=${currentFolderId}`;
                } else if (options.folder) {
                    url += `&folder=${encodeURIComponent(options.folder)}`;
                }
                if (options.collection) {
                    url += `&collection=${encodeURIComponent(options.collection)}`;
                }
                if (options.isolated) {
                    url += `&isolated=1`;
                }
                if (options.scope) {
                    url += `&scope=${encodeURIComponent(options.scope)}`;
                }
                if (pickerAccept && pickerAccept !== '*' && pickerAccept !== '*/*') {
                    url += `&accept=${encodeURIComponent(pickerAccept)}`;
                }
                if (pickerType) {
                    url += `&type=${encodeURIComponent(pickerType)}`;
                }
                if (currentSearch) {
                    url += `&search=${encodeURIComponent(currentSearch)}`;
                }

                fetch(url, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    renderBreadcrumbs(data.breadcrumbs || []);
                    renderPickerGrid(data.folders || [], data.media || []);
                })
                .catch(() => {
                    listArea.innerHTML = `
                        <div style="text-align: center; padding: 3rem; color: var(--mf-danger);">
                            Failed to load media library items.
                        </div>
                    `;
                });
            }

            function renderPickerGrid(folders, mediaItems) {
                if (folders.length === 0 && mediaItems.length === 0) {
                    listArea.innerHTML = `
                        <div style="text-align: center; padding: 3.5rem 1rem; color: var(--mf-text-muted);">
                            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📂</div>
                            <div style="font-weight: 700; font-size: 1rem; color: var(--mf-text-main);">This folder is empty</div>
                            <div style="font-size: 0.85rem; margin-top: 0.35rem;">Upload a file directly into this directory using the button above or drag & drop.</div>
                        </div>
                    `;
                    return;
                }

                let html = '<div class="mf-picker-grid">';

                // Render Folders
                folders.forEach(f => {
                    html += `
                        <div class="mf-picker-item mf-picker-folder" data-folder-id="${f.id}" title="Open folder '${escapeHtml(f.name)}'">
                            <div style="width: 100%; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 2.2rem;">
                                📁
                            </div>
                            <div style="font-size: 0.75rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--mf-text-main);">
                                ${escapeHtml(f.name)}
                            </div>
                            <div style="font-size: 0.7rem; color: var(--mf-text-muted);">
                                ${f.media_count || 0} items
                            </div>
                        </div>
                    `;
                });

                // Render Media
                mediaItems.forEach(m => {
                    const thumb = safeUrl(resolveMediaUrl(m.thumbnail_url || m.url));
                    const full = safeUrl(resolveMediaUrl(m.url));
                    const ext = (m.extension || (m.original_name ? m.original_name.split('.').pop() : '')).toLowerCase();
                    const mime = (m.mime_type || '').toLowerCase();
                    const isImg = m.is_image || mime.startsWith('image/');
                    const isVid = m.is_video || mime.startsWith('video/') || ['mp4', 'webm', 'ogg', 'mov'].includes(ext);
                    const isAud = m.is_audio || mime.startsWith('audio/') || ['mp3', 'wav', 'ogg', 'm4a'].includes(ext);
                    const isDoc = m.is_document || mime === 'application/pdf' || ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'].includes(ext);

                    let previewHtml = '';
                    if (isImg) {
                        previewHtml = `<img src="${thumb}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null; if('${full}' && this.src !== '${full}') { this.src='${full}'; }">`;
                    } else if (ext === 'pdf' || mime === 'application/pdf') {
                        previewHtml = `<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(239, 68, 68, 0.12); color: #ef4444;"><span style="font-size: 2rem;">📄</span><span style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase;">PDF</span></div>`;
                    } else if (isVid) {
                        previewHtml = `<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(168, 85, 247, 0.12); color: #a855f7;"><span style="font-size: 2rem;">🎬</span><span style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase;">${ext.toUpperCase() || 'VIDEO'}</span></div>`;
                    } else if (isAud) {
                        previewHtml = `<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(6, 182, 212, 0.12); color: #06b6d4;"><span style="font-size: 2rem;">🎵</span><span style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase;">${ext.toUpperCase() || 'AUDIO'}</span></div>`;
                    } else if (isDoc) {
                        previewHtml = `<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(59, 130, 246, 0.12); color: #3b82f6;"><span style="font-size: 2rem;">📝</span><span style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase;">${ext.toUpperCase() || 'DOC'}</span></div>`;
                    } else {
                        previewHtml = `<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(148, 163, 184, 0.12); color: #94a3b8;"><span style="font-size: 2rem;">📁</span><span style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase;">${ext.toUpperCase() || 'FILE'}</span></div>`;
                    }

                    html += `
                        <div class="mf-picker-item" data-media-id="${m.id}" title="Select '${escapeHtml(m.original_name || m.name)}'">
                            <div class="mf-picker-item-actions">
                                <button type="button" class="mf-picker-action-btn mf-picker-view-btn" data-url="${full}" title="Open in new tab (Fullscreen)">👁️</button>
                                <button type="button" class="mf-picker-action-btn mf-picker-copy-btn" data-url="${full}" title="Copy URL">📋</button>
                            </div>
                            <div style="width: 100%; height: 80px; border-radius: var(--mf-radius-sm); overflow: hidden; background: var(--mf-bg-hover); display: flex; align-items: center; justify-content: center; margin-bottom: 0.4rem;">
                                ${previewHtml}
                            </div>
                            <div style="font-size: 0.75rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--mf-text-main);">
                                ${escapeHtml(m.original_name || m.name)}
                            </div>
                            <div style="font-size: 0.7rem; color: var(--mf-text-muted);">
                                ${escapeHtml(m.human_size)}
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
                listArea.innerHTML = html;

                // Bind view and copy buttons in picker
                listArea.querySelectorAll('.mf-picker-view-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const url = btn.dataset.url;
                        if (url) window.open(url, '_blank');
                    });
                });

                listArea.querySelectorAll('.mf-picker-copy-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const url = btn.dataset.url;
                        if (url) copyTextToClipboard(url, 'Image URL copied to clipboard!');
                    });
                });

                // Bind folder clicks
                listArea.querySelectorAll('.mf-picker-folder').forEach(item => {
                    item.addEventListener('click', () => {
                        currentFolderId = parseInt(item.dataset.folderId, 10);
                        loadPickerData();
                    });
                });

                // Bind media item clicks
                listArea.querySelectorAll('[data-media-id]').forEach(item => {
                    item.addEventListener('click', (e) => {
                        if (e.target.closest('.mf-picker-item-actions')) return;
                        const id = parseInt(item.dataset.mediaId, 10);
                        const selectedMedia = mediaItems.find(m => m.id === id);
                        if (selectedMedia) {
                            onSelect(selectedMedia);
                            close();
                        }
                    });
                });
            }



            // Initial load
            loadPickerData();
        },

        initDocs: function(container) {
            if (!container) return;

            // 1. Copy to clipboard on code blocks
            container.querySelectorAll('.mf-code-copy-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const code = btn.getAttribute('data-code') || btn.closest('.mf-code-container')?.querySelector('code')?.innerText || '';
                    if (code) {
                        navigator.clipboard.writeText(code.trim()).then(() => {
                            const originalHTML = btn.innerHTML;
                            btn.innerHTML = '<span>✓ Copied!</span>';
                            btn.style.borderColor = 'var(--mf-success, #10b981)';
                            btn.style.color = 'var(--mf-success, #10b981)';
                            setTimeout(() => {
                                btn.innerHTML = originalHTML;
                                btn.style.borderColor = '';
                                btn.style.color = '';
                            }, 2000);
                        }).catch(() => {
                            // Fallback
                            const textarea = document.createElement('textarea');
                            textarea.value = code.trim();
                            document.body.appendChild(textarea);
                            textarea.select();
                            document.execCommand('copy');
                            document.body.removeChild(textarea);
                            btn.innerHTML = '<span>✓ Copied!</span>';
                            setTimeout(() => { btn.innerHTML = '<span>📋 Copy</span>'; }, 2000);
                        });
                    }
                });
            });

            // 2. Real-time search filter in sidebar
            const searchInput = container.querySelector('#mf-docs-search-input');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const q = e.target.value.toLowerCase().trim();
                    container.querySelectorAll('.mf-docs-nav-link').forEach(link => {
                        const text = link.innerText.toLowerCase();
                        link.style.display = (!q || text.includes(q)) ? 'flex' : 'none';
                    });
                });
            }

            // 3. Mobile sidebar toggle & backdrop
            const toggleBtn = container.querySelector('#mf-docs-toggle-sidebar') || document.querySelector('#mf-docs-toggle-sidebar');
            const sidebar = container.querySelector('.mf-docs-sidebar') || document.querySelector('.mf-docs-sidebar');
            let backdrop = document.querySelector('.mf-docs-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.className = 'mf-docs-backdrop';
                document.body.appendChild(backdrop);
            }

            if (toggleBtn && sidebar) {
                const closeSidebar = () => {
                    sidebar.classList.remove('open');
                    backdrop.classList.remove('show');
                };

                toggleBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = sidebar.classList.toggle('open');
                    backdrop.classList.toggle('show', isOpen);
                });

                backdrop.addEventListener('click', closeSidebar);

                // Auto-close when clicking any link on mobile
                sidebar.querySelectorAll('.mf-docs-nav-link').forEach(link => {
                    link.addEventListener('click', closeSidebar);
                });

                // Auto-close on Escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                        closeSidebar();
                    }
                });
            }

            // 4. Smooth scrolling & active state updater for all in-page anchors
            const updateActiveLink = (targetId) => {
                document.querySelectorAll('.mf-nav-link, .mf-docs-nav-link').forEach(link => {
                    const href = link.getAttribute('href');
                    if (href === '#' + targetId) {
                        link.classList.add('active');
                    } else if (href && href.startsWith('#')) {
                        link.classList.remove('active');
                    }
                });
            };

            document.querySelectorAll('a[href^="#"]').forEach(link => {
                link.addEventListener('click', (e) => {
                    const href = link.getAttribute('href');
                    if (!href || href === '#' || href.length < 2) return;
                    const targetEl = document.querySelector(href);
                    if (targetEl) {
                        e.preventDefault();
                        targetEl.scrollIntoView({ behavior: 'smooth' });
                        if (history.pushState) {
                            history.pushState(null, null, href);
                        } else {
                            location.hash = href;
                        }
                        updateActiveLink(href.substring(1));
                    }
                });
            });

            // 5. ScrollSpy / Active nav link updater on scroll
            const sections = document.querySelectorAll('.mf-docs-section');
            if (sections.length > 0 && window.IntersectionObserver) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const id = entry.target.getAttribute('id');
                            if (id) updateActiveLink(id);
                        }
                    });
                }, { rootMargin: '-20% 0px -70% 0px' });

                sections.forEach(sec => observer.observe(sec));
            }

            // 6. Interactive Settings & Compression Simulator
            const simContainer = document.querySelector('#mf-settings-simulator');
            if (simContainer) {
                window.MediaFlow.initSettingsSimulator(simContainer);
            }
        },

        initSettingsSimulator: function(container) {
            if (!container || container.dataset.simInitialized === 'true') return;
            container.dataset.simInitialized = 'true';

            let currentFormat = 'webp';
            let currentQuality = 82;
            let stripExif = true;
            let autoOptimize = true;
            let maxWidth = 2560;
            let maxHeight = 2560;

            const formatPills = container.querySelectorAll('#mf-sim-format-pills .mf-sim-pill');
            const qualitySlider = container.querySelector('#mf-sim-quality-slider');
            const qualityVal = container.querySelector('#mf-sim-quality-val');
            const stripExifCheck = container.querySelector('#mf-sim-strip-exif');
            const autoOptCheck = container.querySelector('#mf-sim-auto-optimize');
            const maxWInput = container.querySelector('#mf-sim-max-w');
            const maxHInput = container.querySelector('#mf-sim-max-h');
            const optSizeEl = container.querySelector('#mf-sim-opt-size');
            const optFormatEl = container.querySelector('#mf-sim-opt-format');
            const optSavingsEl = container.querySelector('#mf-sim-opt-savings');
            const barEl = container.querySelector('#mf-sim-bar');
            const ratioTextEl = container.querySelector('#mf-sim-ratio-text');
            const exifStatusEl = container.querySelector('#mf-sim-exif-status');
            const codeOutputEl = container.querySelector('#mf-sim-code-output');
            const themeSwatches = container.querySelectorAll('#mf-sim-theme-swatches .mf-sim-swatch');
            const modePills = container.querySelectorAll('#mf-sim-mode-pills .mf-sim-pill');

            const recalculate = () => {
                const rawKB = 3502; // 3.42 MB
                const factors = {
                    webp: 0.05 + (currentQuality / 100) * 0.18,
                    avif: 0.035 + (currentQuality / 100) * 0.14,
                    jpeg: 0.12 + (currentQuality / 100) * 0.40,
                    png: 0.65 + (currentQuality / 100) * 0.28
                };

                let compSize = Math.round(rawKB * (factors[currentFormat] || 0.18));
                if (stripExif) {
                    compSize = Math.max(22, compSize - 28);
                }

                const savedPct = Math.max(1, Math.min(99, ((1 - (compSize / rawKB)) * 100))).toFixed(1);
                const payloadPct = (100 - parseFloat(savedPct)).toFixed(1);

                if (optSizeEl) {
                    optSizeEl.innerText = compSize >= 1024 ? (compSize / 1024).toFixed(2) + ' MB' : compSize + ' KB';
                }
                if (optFormatEl) {
                    optFormatEl.innerText = currentFormat.toUpperCase() + ' (' + currentQuality + '%)';
                }
                if (optSavingsEl) {
                    optSavingsEl.innerText = '-' + savedPct + '%';
                }
                if (barEl) {
                    barEl.style.width = Math.max(3, payloadPct) + '%';
                }
                if (ratioTextEl) {
                    ratioTextEl.innerText = payloadPct + '% of original';
                }
                if (exifStatusEl) {
                    exifStatusEl.innerText = stripExif ? 'EXIF: Stripped (Sanitized)' : 'EXIF: Retained (Metadata intact)';
                }

                if (codeOutputEl) {
                    codeOutputEl.innerText = `// config/mediaflow.php
'images' => [
    'default_format' => '${currentFormat}',
    'default_quality' => ${currentQuality},
    'max_width' => ${maxWidth},
    'max_height' => ${maxHeight},
    'auto_optimize' => ${autoOptimize ? 'true' : 'false'},
    'strip_exif' => ${stripExif ? 'true' : 'false'},
],

// PHP Facade On-Demand Execution
$result = MediaFlow::optimize($media, [
    'format' => '${currentFormat}',
    'quality' => ${currentQuality},
    'action' => 'copy', // or 'replace'
]);
// Metrics: ${compSize >= 1024 ? (compSize / 1024).toFixed(2) + ' MB' : compSize + ' KB'} (-${savedPct}% reduction)`;
                }
            };

            // Format Selection
            formatPills.forEach(pill => {
                pill.addEventListener('click', () => {
                    formatPills.forEach(p => p.classList.remove('active'));
                    pill.classList.add('active');
                    currentFormat = pill.getAttribute('data-format') || 'webp';
                    recalculate();
                });
            });

            // Quality Slider
            if (qualitySlider) {
                qualitySlider.addEventListener('input', (e) => {
                    currentQuality = parseInt(e.target.value, 10);
                    if (qualityVal) qualityVal.innerText = currentQuality + '%';
                    recalculate();
                });
            }

            // Checkboxes & Inputs
            if (stripExifCheck) {
                stripExifCheck.addEventListener('change', (e) => {
                    stripExif = e.target.checked;
                    recalculate();
                });
            }
            if (autoOptCheck) {
                autoOptCheck.addEventListener('change', (e) => {
                    autoOptimize = e.target.checked;
                    recalculate();
                });
            }
            if (maxWInput) {
                maxWInput.addEventListener('input', (e) => {
                    maxWidth = parseInt(e.target.value, 10) || 2560;
                    recalculate();
                });
            }
            if (maxHInput) {
                maxHInput.addEventListener('input', (e) => {
                    maxHeight = parseInt(e.target.value, 10) || 2560;
                    recalculate();
                });
            }

            // Theme Swatches (Live Theme Palette Mutator)
            const themePalettes = {
                indigo: { primary: '#4f46e5', hover: '#4338ca', light: 'rgba(79, 70, 229, 0.12)' },
                emerald: { primary: '#10b981', hover: '#059669', light: 'rgba(16, 185, 129, 0.12)' },
                violet: { primary: '#8b5cf6', hover: '#7c3aed', light: 'rgba(139, 92, 246, 0.12)' },
                sky: { primary: '#0284c7', hover: '#0369a1', light: 'rgba(2, 132, 199, 0.12)' },
                amber: { primary: '#f59e0b', hover: '#d97706', light: 'rgba(245, 158, 11, 0.12)' },
                rose: { primary: '#f43f5e', hover: '#e11d48', light: 'rgba(244, 63, 94, 0.12)' },
                slate: { primary: '#334155', hover: '#1e293b', light: 'rgba(51, 65, 85, 0.12)' }
            };

            themeSwatches.forEach(swatch => {
                swatch.addEventListener('click', () => {
                    themeSwatches.forEach(s => s.classList.remove('active'));
                    swatch.classList.add('active');
                    const themeName = swatch.getAttribute('data-theme') || 'indigo';
                    const palette = themePalettes[themeName] || themePalettes.indigo;
                    
                    document.documentElement.setAttribute('data-mf-theme', themeName);
                    document.documentElement.style.setProperty('--mf-primary', palette.primary);
                    document.documentElement.style.setProperty('--mf-primary-hover', palette.hover);
                    document.documentElement.style.setProperty('--mf-primary-light', palette.light);
                });
            });

            // Color Mode (System / Light / Dark)
            modePills.forEach(pill => {
                pill.addEventListener('click', () => {
                    modePills.forEach(p => p.classList.remove('active'));
                    pill.classList.add('active');
                    const mode = pill.getAttribute('data-mode') || 'system';
                    if (mode === 'system') {
                        document.documentElement.removeAttribute('data-mf-mode');
                        document.documentElement.classList.remove('dark', 'light');
                    } else if (mode === 'dark') {
                        document.documentElement.setAttribute('data-mf-mode', 'dark');
                        document.documentElement.classList.add('dark');
                        document.documentElement.classList.remove('light');
                    } else {
                        document.documentElement.setAttribute('data-mf-mode', 'light');
                        document.documentElement.classList.add('light');
                        document.documentElement.classList.remove('dark');
                    }
                });
            });

            recalculate();
        }
    };

    // Auto-initialize components on page load or immediately if document is already parsed
    function initAllMediaFlowComponents() {
        document.querySelectorAll('.mf-uploader').forEach(el => window.MediaFlow.initUploader(el));
        document.querySelectorAll('.mf-input-field').forEach(el => window.MediaFlow.initInput(el));
        document.querySelectorAll('.mf-manager-wrapper').forEach(el => window.MediaFlow.initManager(el));
        const docsContainer = document.querySelector('.mf-docs-container');
        if (docsContainer) window.MediaFlow.initDocs(docsContainer);
        const simContainer = document.querySelector('#mf-settings-simulator');
        if (simContainer) window.MediaFlow.initSettingsSimulator(simContainer);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllMediaFlowComponents);
    } else {
        initAllMediaFlowComponents();
    }
})();
