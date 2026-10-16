<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route & Middleware Configuration
    |--------------------------------------------------------------------------
    |
    | Defines the URL prefix and middleware stack for package web and API routes.
    | By default, routes are guarded with 'web' and 'auth'. In local/demo mode
    | or custom deployments, middleware can be adjusted.
    |
    */
    'route_prefix' => env('MEDIAFLOW_ROUTE_PREFIX', 'mediaflow'),

    'middleware' => env('MEDIAFLOW_MIDDLEWARE') ? array_filter(array_map('trim', explode(',', env('MEDIAFLOW_MIDDLEWARE')))) : ['web'],

    'api_middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Access Control & Authorization (Guards, Roles & Permissions)
    |--------------------------------------------------------------------------
    |
    | Define who can access the MediaFlow UI and management endpoints.
    | - 'enabled': If true (the default), authentication is required on all
    |   MediaFlow management routes. Only disable this for local development:
    |   with it off, anyone who can reach the app can upload, delete media and
    |   read or change storage settings.
    | - 'guard': The authentication guard (e.g. 'web', 'admin', 'sanctum', or null for default).
    | - 'roles': Array or comma-delimited list of roles allowed access (e.g. ['admin', 'manager']).
    | - 'permissions': Array or comma-delimited list of permissions allowed access.
    | - 'gate': Optional Laravel Gate name checked via Gate::allows().
    | - 'custom_middleware': Extra custom middleware stack applied to MediaFlow routes.
    |
    */
    'auth' => [
        'enabled' => env('MEDIAFLOW_AUTH_ENABLED', true),
        'guard' => env('MEDIAFLOW_GUARD', env('MEDIAFLOW_AUTH_GUARD', null)),
        'roles' => env('MEDIAFLOW_ROLES') ? array_filter(array_map('trim', explode(',', env('MEDIAFLOW_ROLES')))) : [],
        'permissions' => env('MEDIAFLOW_PERMISSIONS') ? array_filter(array_map('trim', explode(',', env('MEDIAFLOW_PERMISSIONS')))) : [],
        'gate' => env('MEDIAFLOW_GATE', null),
        'custom_middleware' => env('MEDIAFLOW_CUSTOM_MIDDLEWARE') ? array_filter(array_map('trim', explode(',', env('MEDIAFLOW_CUSTOM_MIDDLEWARE')))) : [],

        // When false (default), access-control values set in config/.env act as a
        // floor: settings saved from the web UI can add restrictions (e.g. extra
        // roles) but can never switch authentication off or clear config roles.
        'allow_ui_override' => env('MEDIAFLOW_AUTH_ALLOW_UI_OVERRIDE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Table Prefix & Names (Legacy & Spatie Media Collision-Proof)
    |--------------------------------------------------------------------------
    |
    | If your host application already contains an existing `media` table or
    | Model (e.g. from Spatie MediaLibrary or existing app migrations),
    | MediaFlow uses isolated tables and namespaces.
    |
    | You can customize the table prefix (e.g. 'mf_') or specify individual
    | table names via environment variables.
    |
    */
    'table_prefix' => env('MEDIAFLOW_TABLE_PREFIX', 'mediaflow_'),

    'tables' => [
        'media' => env('MEDIAFLOW_TABLE_MEDIA', env('MEDIAFLOW_TABLE_PREFIX', 'mediaflow_') . 'media'),
        'folders' => env('MEDIAFLOW_TABLE_FOLDERS', env('MEDIAFLOW_TABLE_PREFIX', 'mediaflow_') . 'folders'),
        'variants' => env('MEDIAFLOW_TABLE_VARIANTS', env('MEDIAFLOW_TABLE_PREFIX', 'mediaflow_') . 'variants'),
        'upload_sessions' => env('MEDIAFLOW_TABLE_UPLOAD_SESSIONS', env('MEDIAFLOW_TABLE_PREFIX', 'mediaflow_') . 'upload_sessions'),
        'settings' => env('MEDIAFLOW_TABLE_SETTINGS', env('MEDIAFLOW_TABLE_PREFIX', 'mediaflow_') . 'settings'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Disks
    |--------------------------------------------------------------------------
    |
    | The default disk where finalized media files are stored, and the temporary
    | disk used during chunking or pre-processing before final storage.
    |
    */
    'default_disk' => env('MEDIAFLOW_DISK', 'public'),

    'temporary_disk' => env('MEDIAFLOW_TEMP_DISK', 'local'),

    'temporary_path' => 'mediaflow-temp',

    // Optional disk used for uploads with visibility "private". Point this at a
    // non-public disk (e.g. 'local' or a private S3 bucket) so private files are
    // never reachable through the public storage symlink. Null = default_disk.
    'private_disk' => env('MEDIAFLOW_PRIVATE_DISK', null),

    /*
    |--------------------------------------------------------------------------
    | Cloud Storage & CDN Provider Presets (10 Providers)
    |--------------------------------------------------------------------------
    |
    | Seamless configuration for 10 popular object storage and CDN providers:
    | - 'aws' (Amazon S3)
    | - 'cloudflare_r2' (Cloudflare R2 Object Storage)
    | - 'contabo' (Contabo Object Storage)
    | - 'digitalocean' (DigitalOcean Spaces)
    | - 'wasabi' (Wasabi Cloud Storage)
    | - 'backblaze' (Backblaze B2)
    | - 'gcs' (Google Cloud Storage)
    | - 'azure' (Microsoft Azure Blob Storage)
    | - 'cloudinary' (Cloudinary Media Storage & CDN)
    | - 'minio' (MinIO Self-Hosted S3)
    |
    */
    'provider' => [
        'name' => env('MEDIAFLOW_PROVIDER', 'local'),
        'key' => env('MEDIAFLOW_PROVIDER_KEY', null),
        'secret' => env('MEDIAFLOW_PROVIDER_SECRET', null),
        'account_id' => env('MEDIAFLOW_PROVIDER_ACCOUNT_ID', env('CLOUDFLARE_ACCOUNT_ID', env('CLOUDFLARE_R2_ACCOUNT_ID', null))),
        'bucket' => env('MEDIAFLOW_PROVIDER_BUCKET', null),
        'public_url' => env('MEDIAFLOW_PROVIDER_PUBLIC_URL', null),
        'region' => env('MEDIAFLOW_PROVIDER_REGION', null),
        'endpoint' => env('MEDIAFLOW_PROVIDER_ENDPOINT', null),
        'path_style' => env('MEDIAFLOW_PROVIDER_PATH_STYLE', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload Constraints & Security
    |--------------------------------------------------------------------------
    |
    | Server-side limits for uploads. Client limits are for UI only; these limits
    | are enforced strictly by the backend upload pipeline.
    |
    */
    'max_file_size_kb' => env('MEDIAFLOW_MAX_FILE_SIZE_KB', 20480), // 20 MB default

    'allowed_mimes' => [
        // Images
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/svg+xml',
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        // Videos
        'video/mp4',
        'video/quicktime',
        'video/webm',
        'video/ogg',
        // Audio
        'audio/mpeg',
        'audio/mp3',
        'audio/wav',
        'audio/ogg',
        'audio/mp4',
        // Archives
        'application/zip',
        'application/x-zip-compressed',
    ],

    // Deny active or executable script uploads regardless of extension.
    // Note: stored files always get an extension derived from the detected
    // MIME type, never the one supplied by the client.
    'blocked_extensions' => [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'pht', 'phtml', 'phar', 'phps',
        'exe', 'sh', 'bash', 'bat', 'cmd', 'com', 'js', 'mjs', 'vbs', 'scr', 'cgi', 'pl', 'py',
        'html', 'htm', 'xhtml', 'shtml', 'xml', 'svgz', 'htaccess', 'jsp', 'asp', 'aspx',
    ],

    // Reject SVG files that contain scripts, event handlers, external
    // references or embedded HTML. Keep enabled unless you fully trust uploaders.
    'sanitize_svg' => true,

    // Images larger than this many megapixels are rejected to avoid
    // decompression-bomb memory exhaustion in GD.
    'max_image_megapixels' => env('MEDIAFLOW_MAX_IMAGE_MEGAPIXELS', 50),

    /*
    |--------------------------------------------------------------------------
    | Image Processing Configuration
    |--------------------------------------------------------------------------
    |
    | Image manipulation settings including thumbnail generation, EXIF stripping,
    | WebP conversion, and synchronous vs asynchronous queue processing.
    |
    */
    'images' => [
        'driver' => env('MEDIAFLOW_IMAGE_DRIVER', 'gd'), // only 'gd' ships today; bind your own ImageProcessor for Imagick
        'default_format' => env('MEDIAFLOW_DEFAULT_FORMAT', 'webp'),
        'default_quality' => env('MEDIAFLOW_DEFAULT_QUALITY', 82),
        'auto_optimize' => env('MEDIAFLOW_AUTO_OPTIMIZE', true),
        'max_width' => env('MEDIAFLOW_MAX_WIDTH', 2560),
        'max_height' => env('MEDIAFLOW_MAX_HEIGHT', 2560),
        'strip_exif' => true, // re-encodes JPEG/PNG/WebP uploads to drop EXIF/GPS metadata
        'generate_thumbnails' => true,
        'optimize' => true,
        'queue_processing' => env('MEDIAFLOW_QUEUE_PROCESSING', false), // reserved: variants are generated synchronously today
        'queue_name' => env('MEDIAFLOW_QUEUE', 'default'),
        'presets' => [
            'thumb' => [
                'width' => 200,
                'height' => 200,
                'fit' => 'crop',
                'format' => 'webp',
                'quality' => 80,
            ],
            'medium' => [
                'width' => 800,
                'height' => 800,
                'fit' => 'contain',
                'format' => 'webp',
                'quality' => 85,
            ],
            'large' => [
                'width' => 1600,
                'height' => 1600,
                'fit' => 'contain',
                'format' => 'webp',
                'quality' => 85,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery URL Configuration
    |--------------------------------------------------------------------------
    |
    | Decouples storage from delivery URL resolution.
    | 'driver' options:
    |   - 'disk': Use Laravel's default Storage::disk()->url()
    |   - 'cdn': Prefix with MEDIAFLOW_CDN_URL (custom domain / CDN)
    |   - 'signed': Expiring signed URLs for private assets
    |
    */
    'delivery' => [
        'driver' => env('MEDIAFLOW_DELIVERY_DRIVER', 'disk'),
        'base_url' => env('MEDIAFLOW_CDN_URL', null),
        'signed_url_expiration_minutes' => env('MEDIAFLOW_SIGNED_EXPIRY', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Interface Settings
    |--------------------------------------------------------------------------
    |
    | Toggles for the embedded or standalone Blade MediaFlow UI.
    |
    */
    'ui' => [
        'enabled' => true,
        'animations' => true,
        'show_pipeline' => true,
        'show_dev_nav' => env('MEDIAFLOW_DEV_NAV', false),
        'show_header' => env('MEDIAFLOW_SHOW_HEADER', true),
        'items_per_page' => 24,
        'allow_delete' => true,
        'allow_rename' => true,
        'allow_move' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy and Ownership Scoping
    |--------------------------------------------------------------------------
    |
    | If 'user_scoped' is enabled, every media/folder query made while a user
    | is authenticated is limited to records whose owner_id matches that user,
    | so users can only see, move or delete their own files.
    | ('tenant_column' is reserved and not enforced yet.)
    |
    */
    'scope' => [
        'user_scoped' => env('MEDIAFLOW_USER_SCOPED', false),
        'tenant_column' => null,
    ],
];
