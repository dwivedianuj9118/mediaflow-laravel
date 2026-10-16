<?php

namespace MediaFlow\Laravel\Storage;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CloudProviderManager
{
    /**
     * Map of 10 supported cloud storage and CDN providers.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $providers = [
        'cloudflare_r2' => [
            'id' => 'cloudflare_r2',
            'name' => 'Cloudflare R2',
            'icon' => '⚡',
            'badge' => 'Zero Egress Fees',
            'driver' => 's3',
            'description' => 'S3-compatible object storage with $0 egress bandwidth fees and high-speed global Cloudflare CDN integration.',
            'default_region' => 'auto',
            'endpoint_template' => 'https://{account_id}.r2.cloudflarestorage.com',
            'public_url_hint' => 'https://pub-xxx.r2.dev or custom domain https://media.yourdomain.com',
            'key_label' => 'R2 Access Key ID',
            'secret_label' => 'R2 Secret Access Key',
            'account_id_label' => 'Cloudflare Account ID (32-character hex from Overview)',
            'bucket_label' => 'R2 Bucket Name',
            'region_label' => 'Region (default: auto)',
            'path_style' => false,
            'doc_url' => 'https://developers.cloudflare.com/r2/api/s3/tokens/',
        ],
        'aws' => [
            'id' => 'aws',
            'name' => 'Amazon Web Services (AWS S3)',
            'icon' => '🔶',
            'badge' => 'Industry Standard',
            'driver' => 's3',
            'description' => 'High durability, scalable cloud storage backed by Amazon Web Services infrastructure and CloudFront CDN.',
            'default_region' => 'us-east-1',
            'endpoint_template' => 'https://s3.{region}.amazonaws.com',
            'public_url_hint' => 'https://{bucket}.s3.{region}.amazonaws.com or CloudFront https://cdn.example.com',
            'key_label' => 'AWS Access Key ID',
            'secret_label' => 'AWS Secret Access Key',
            'bucket_label' => 'S3 Bucket Name',
            'region_label' => 'AWS Region (e.g. us-east-1)',
            'path_style' => false,
            'doc_url' => 'https://docs.aws.amazon.com/AmazonS3/latest/userguide/GetStartedWithS3.html',
        ],
        'contabo' => [
            'id' => 'contabo',
            'name' => 'Contabo Object Storage',
            'icon' => '🌐',
            'badge' => 'High Capacity & Low Cost',
            'driver' => 's3',
            'description' => 'High-capacity, cost-effective S3-compatible cloud storage with high bandwidth in European, US, and Asian regions.',
            'default_region' => 'eu2',
            'endpoint_template' => 'https://{region}.contabostorage.com',
            'public_url_hint' => 'https://{region}.contabostorage.com/{bucket} or custom CDN domain',
            'key_label' => 'Contabo S3 Access Key',
            'secret_label' => 'Contabo S3 Secret Key',
            'bucket_label' => 'Contabo Bucket Name',
            'region_label' => 'Contabo Region (e.g. eu2, sin1, us-central1)',
            'path_style' => true,
            'doc_url' => 'https://contabo.com/en/object-storage/',
        ],
        'digitalocean' => [
            'id' => 'digitalocean',
            'name' => 'DigitalOcean Spaces',
            'icon' => '🌊',
            'badge' => 'Integrated Edge CDN',
            'driver' => 's3',
            'description' => 'Simple S3-compatible object storage with built-in CDN edge distribution across global POPs.',
            'default_region' => 'nyc3',
            'endpoint_template' => 'https://{region}.digitaloceanspaces.com',
            'public_url_hint' => 'https://{bucket}.{region}.cdn.digitaloceanspaces.com or custom domain',
            'key_label' => 'Spaces Access Key',
            'secret_label' => 'Spaces Secret Key',
            'bucket_label' => 'Space (Bucket) Name',
            'region_label' => 'Datacenter Region (e.g. nyc3, ams3, sgp1)',
            'path_style' => false,
            'doc_url' => 'https://docs.digitalocean.com/products/spaces/',
        ],
        'wasabi' => [
            'id' => 'wasabi',
            'name' => 'Wasabi Cloud Storage',
            'icon' => '🟢',
            'badge' => 'Hot Cloud Storage',
            'driver' => 's3',
            'description' => 'Extremely fast S3-compliant cloud storage with flat pricing, predictable costs, and no egress charges.',
            'default_region' => 'us-east-1',
            'endpoint_template' => 'https://s3.{region}.wasabisys.com',
            'public_url_hint' => 'https://s3.{region}.wasabisys.com/{bucket} or custom CDN domain',
            'key_label' => 'Wasabi Access Key',
            'secret_label' => 'Wasabi Secret Key',
            'bucket_label' => 'Wasabi Bucket Name',
            'region_label' => 'Wasabi Region (e.g. us-east-1, eu-central-1)',
            'path_style' => false,
            'doc_url' => 'https://wasabi.com/cloud-storage/',
        ],
        'backblaze' => [
            'id' => 'backblaze',
            'name' => 'Backblaze B2',
            'icon' => '🔴',
            'badge' => 'Affordable S3 Storage',
            'driver' => 's3',
            'description' => 'Low-cost S3-compatible object storage with free bandwidth egress partnerships (Bandwidth Alliance / Cloudflare).',
            'default_region' => 'us-west-004',
            'endpoint_template' => 'https://s3.{region}.backblazeb2.com',
            'public_url_hint' => 'https://f004.backblazeb2.com/file/{bucket} or Cloudflare CDN',
            'key_label' => 'keyID (Application Key ID)',
            'secret_label' => 'applicationKey (Secret)',
            'bucket_label' => 'B2 Bucket Name',
            'region_label' => 'B2 Region (e.g. us-west-004, us-east-005)',
            'path_style' => false,
            'doc_url' => 'https://www.backblaze.com/docs/cloud-storage-s3-compatible-api',
        ],
        'gcs' => [
            'id' => 'gcs',
            'name' => 'Google Cloud Storage (GCS)',
            'icon' => '🔵',
            'badge' => 'Google Cloud Interop',
            'driver' => 's3',
            'description' => 'Google Cloud Storage buckets configured using Google Cloud S3 Interoperability API keys and Cloud CDN.',
            'default_region' => 'auto',
            'endpoint_template' => 'https://storage.googleapis.com',
            'public_url_hint' => 'https://storage.googleapis.com/{bucket} or Google Cloud CDN domain',
            'key_label' => 'Interoperability Access Key',
            'secret_label' => 'Interoperability Secret Key',
            'bucket_label' => 'GCS Bucket Name',
            'region_label' => 'Storage Region (or auto)',
            'path_style' => true,
            'doc_url' => 'https://cloud.google.com/storage/docs/interoperability',
        ],
        'azure' => [
            'id' => 'azure',
            'name' => 'Microsoft Azure Blob Storage',
            'icon' => '🔷',
            'badge' => 'Enterprise Cloud',
            'driver' => 's3',
            'description' => 'Microsoft Azure Blob storage accessible via Azure Storage Account credentials and Azure Front Door / CDN.',
            'default_region' => 'global',
            'endpoint_template' => 'https://{account_name}.blob.core.windows.net',
            'public_url_hint' => 'https://{account_name}.blob.core.windows.net/{container} or Azure CDN',
            'key_label' => 'Storage Account Name',
            'secret_label' => 'Account Primary Key / SAS Token',
            'bucket_label' => 'Blob Container Name',
            'region_label' => 'Region (optional)',
            'path_style' => false,
            'doc_url' => 'https://learn.microsoft.com/en-us/azure/storage/blobs/',
        ],
        'cloudinary' => [
            'id' => 'cloudinary',
            'name' => 'Cloudinary Storage & CDN',
            'icon' => '🎨',
            'badge' => 'Media & Transformations',
            'driver' => 's3',
            'description' => 'High-speed media cloud asset storage, automatic image optimization, and worldwide Akamai / Fastly CDN delivery.',
            'default_region' => 'global',
            'endpoint_template' => 'https://api.cloudinary.com/v1_1/{cloud_name}',
            'public_url_hint' => 'https://res.cloudinary.com/{cloud_name}/image/upload',
            'key_label' => 'Cloudinary API Key',
            'secret_label' => 'Cloudinary API Secret',
            'bucket_label' => 'Cloud Name (Environment ID)',
            'region_label' => 'Region (optional)',
            'path_style' => false,
            'doc_url' => 'https://cloudinary.com/documentation',
        ],
        'minio' => [
            'id' => 'minio',
            'name' => 'MinIO / Self-Hosted S3',
            'icon' => '🦙',
            'badge' => 'Self-Hosted & Private Cloud',
            'driver' => 's3',
            'description' => 'Lightweight, ultra-fast self-hosted S3-compatible object storage container designed for local, Docker, or Kubernetes clusters.',
            'default_region' => 'us-east-1',
            'endpoint_template' => 'http://localhost:9000',
            'public_url_hint' => 'http://localhost:9000/{bucket} or reverse-proxy https://storage.yourdomain.com',
            'key_label' => 'MinIO Root User / Access Key',
            'secret_label' => 'MinIO Root Password / Secret',
            'bucket_label' => 'Bucket Name',
            'region_label' => 'Region (default: us-east-1)',
            'path_style' => true,
            'doc_url' => 'https://min.io/docs/minio/linux/index.html',
        ],
    ];

    /**
     * Get list of all 10 supported cloud providers with metadata.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get metadata for a specific provider by ID.
     */
    public function getProvider(string $id): ?array
    {
        return $this->providers[$id] ?? null;
    }

    /**
     * Build the computed endpoint URL for a given provider and user settings.
     */
    public function resolveEndpoint(string $providerId, array $settings): string
    {
        $preset = $this->getProvider($providerId);
        if (!$preset) {
            return $settings['endpoint'] ?? '';
        }

        $bucket = $settings['bucket'] ?? '';
        $key = $settings['key'] ?? '';
        $region = !empty($settings['region']) ? trim($settings['region']) : $preset['default_region'];

        if (!empty($settings['endpoint'])) {
            $rawEndpoint = trim($settings['endpoint']);

            // If user passed a bare 32-character account ID for Cloudflare R2 in the endpoint field:
            if ($providerId === 'cloudflare_r2' && !str_starts_with($rawEndpoint, 'http://') && !str_starts_with($rawEndpoint, 'https://')) {
                if (preg_match('/^[a-f0-9]{32}$/i', $rawEndpoint)) {
                    $rawEndpoint = "https://{$rawEndpoint}.r2.cloudflarestorage.com";
                } else {
                    $rawEndpoint = 'https://' . $rawEndpoint;
                }
            }

            // Strip trailing slash
            $cleaned = rtrim($rawEndpoint, '/');

            // Strip bucket if user accidentally included it in endpoint (e.g. https://<id>.r2.cloudflarestorage.com/my-bucket)
            if (!empty($bucket) && str_ends_with($cleaned, '/' . $bucket)) {
                $cleaned = substr($cleaned, 0, -strlen('/' . $bucket));
            }

            return $cleaned;
        }

        // Determine account_id for Cloudflare R2
        $accountId = $settings['account_id'] ?? null;
        if (empty($accountId) && $providerId === 'cloudflare_r2') {
            // Check if region contains an account ID (e.g. 32-char hex or non-auto)
            if (!empty($settings['region']) && $settings['region'] !== 'auto' && $settings['region'] !== 'us-east-1') {
                $accountId = trim($settings['region']);
            }
        }
        if (empty($accountId)) {
            $accountId = $key;
        }

        $template = $preset['endpoint_template'];

        // Replace placeholders
        $endpoint = str_replace(
            ['{region}', '{account_id}', '{account_name}', '{cloud_name}', '{bucket}'],
            [$region, $accountId, $key, $bucket, $bucket],
            $template
        );

        return rtrim($endpoint, '/');
    }

    /**
     * Build a Laravel filesystem disk configuration array.
     */
    public function buildDiskConfig(array $settings): array
    {
        $providerId = $settings['name'] ?? 'aws';
        $preset = $this->getProvider($providerId);

        $endpoint = $this->resolveEndpoint($providerId, $settings);

        // For Cloudflare R2, AWS SigV4 signing requires region 'auto' (or 'us-east-1').
        // If the user entered their 32-char Account ID into the region field, do NOT pass it as the AWS SigV4 region!
        if ($providerId === 'cloudflare_r2') {
            $region = 'auto';
        } else {
            $region = !empty($settings['region']) ? $settings['region'] : ($preset['default_region'] ?? 'us-east-1');
        }

        $bucket = $settings['bucket'] ?? 'mediaflow';
        $pathStyle = ($settings['path_style'] ?? null) !== null ? (bool) $settings['path_style'] : ($preset['path_style'] ?? false);
        $publicUrl = $settings['public_url'] ?? null;

        return [
            'driver' => 's3',
            'key' => $settings['key'] ?? '',
            'secret' => $settings['secret'] ?? '',
            'region' => $region,
            'bucket' => $bucket,
            'url' => $publicUrl,
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => $pathStyle,
            'throw' => false,
        ];
    }

    /**
     * Dynamically register and apply cloud provider configuration to Laravel runtime.
     */
    /**
     * Whether the Flysystem S3 driver (league/flysystem-aws-s3-v3) is installed.
     * It is an optional dependency: all cloud presets need it.
     */
    public static function s3DriverInstalled(): bool
    {
        return class_exists(\League\Flysystem\AwsS3V3\PortableVisibilityConverter::class)
            && class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class)
            && class_exists(\Aws\S3\S3Client::class);
    }

    public static function missingDriverMessage(): string
    {
        return 'Cloud storage needs the S3 driver. Run: composer require league/flysystem-aws-s3-v3 "^3.0"';
    }

    public function apply(array $settings): void
    {
        $providerId = $settings['name'] ?? null;
        if (empty($providerId) || $providerId === 'local') {
            return;
        }

        // Never switch uploads to a disk that cannot work: keep the local disk
        // and log why, instead of failing every upload with "class not found".
        if (!static::s3DriverInstalled()) {
            try {
                logger()->warning('MediaFlow: provider [' . $providerId . '] is configured but ' . static::missingDriverMessage());
            } catch (\Throwable $e) {
                // logging unavailable
            }
            return;
        }

        $diskConfig = $this->buildDiskConfig($settings);

        // 1. Register disk in Laravel filesystems
        config(['filesystems.disks.mediaflow_cloud' => $diskConfig]);

        // 2. Set default mediaflow disk
        config(['mediaflow.default_disk' => 'mediaflow_cloud']);

        // 3. Set delivery CDN URL if public_url is configured
        if (!empty($settings['public_url'])) {
            config(['mediaflow.delivery.driver' => 'cdn']);
            config(['mediaflow.delivery.base_url' => rtrim($settings['public_url'], '/')]);
        }
    }

    /**
     * Test connection to a cloud provider with provided credentials.
     */
    public function testConnection(array $params): array
    {
        $providerId = $params['provider'] ?? $params['name'] ?? 'aws';
        $preset = $this->getProvider($providerId);

        if (!$preset) {
            return [
                'success' => false,
                'message' => "Unknown provider: [{$providerId}].",
            ];
        }

        $key = trim($params['key'] ?? '');
        $secret = trim($params['secret'] ?? '');
        $bucket = trim($params['bucket'] ?? '');

        if (empty($key) || empty($secret) || empty($bucket)) {
            return [
                'success' => false,
                'message' => "Please provide the Key, Secret, and Bucket name for {$preset['name']}.",
                'missing' => array_filter([
                    'key' => empty($key) ? $preset['key_label'] : null,
                    'secret' => empty($secret) ? $preset['secret_label'] : null,
                    'bucket' => empty($bucket) ? $preset['bucket_label'] : null,
                ]),
            ];
        }

        // For Cloudflare R2, check if user forgot Account ID
        if ($providerId === 'cloudflare_r2') {
            $hasExplicitEndpoint = !empty($params['endpoint']);
            $hasAccountIdInRegion = !empty($params['region']) && $params['region'] !== 'auto' && $params['region'] !== 'us-east-1';
            $hasExplicitAccountId = !empty($params['account_id']);

            if (!$hasExplicitEndpoint && !$hasAccountIdInRegion && !$hasExplicitAccountId) {
                return [
                    'success' => false,
                    'message' => "Cloudflare R2 requires your Cloudflare Account ID to construct the endpoint URL (https://<account_id>.r2.cloudflarestorage.com). Please enter your 32-character Account ID (found on your Cloudflare dashboard overview) into the 'Region / Account ID' field or provide the full Endpoint URL.",
                    'details' => [
                        'provider' => $preset['name'],
                        'bucket' => $bucket,
                        'error_type' => 'MissingAccountIdException',
                    ],
                ];
            }
        }

        $testDiskName = 'mf_test_' . substr(md5(uniqid()), 0, 8);
        $diskConfig = $this->buildDiskConfig($params);

        $endpointError = $this->validateEndpoint($diskConfig['endpoint'] ?? '');
        if ($endpointError !== null) {
            return [
                'success' => false,
                'message' => $endpointError,
            ];
        }

        if (!static::s3DriverInstalled()) {
            return [
                'success' => false,
                'message' => static::missingDriverMessage(),
            ];
        }

        // Enable exception throwing for testing so the actual provider error is caught and diagnosed
        $diskConfig['throw'] = true;

        config(["filesystems.disks.{$testDiskName}" => $diskConfig]);

        $startTime = microtime(true);
        $probeKey = 'mediaflow-probe-' . Str::uuid() . '.txt';
        $probeContent = 'MEDIAFLOW_PROBE_OK_' . now()->toIso8601String();

        try {
            $disk = Storage::disk($testDiskName);

            // Write probe file
            $written = $disk->put($probeKey, $probeContent);
            if (!$written) {
                return [
                    'success' => false,
                    'message' => "Could not write probe file to bucket [{$bucket}] on {$preset['name']}. Please verify: 1) Bucket [{$bucket}] exists in your {$preset['name']} account. 2) R2 API Token has 'Object Read & Write' permission. 3) Endpoint and Account ID are correct.",
                    'details' => [
                        'provider' => $preset['name'],
                        'bucket' => $bucket,
                        'endpoint' => $diskConfig['endpoint'],
                    ],
                ];
            }

            // Read probe file
            $read = $disk->get($probeKey);
            if ($read !== $probeContent) {
                $disk->delete($probeKey);
                return [
                    'success' => false,
                    'message' => "Probe verification mismatch on bucket [{$bucket}].",
                ];
            }

            // Delete probe file
            $disk->delete($probeKey);

            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'message' => "✓ Successfully connected to {$preset['name']} bucket [{$bucket}]! Read, write, and delete verified.",
                'latency_ms' => $latencyMs,
                'details' => [
                    'provider' => $preset['name'],
                    'bucket' => $bucket,
                    'endpoint' => $diskConfig['endpoint'],
                    'region' => $diskConfig['region'],
                    'public_url' => $params['public_url'] ?? null,
                ],
            ];
        } catch (\Throwable $e) {
            try {
                Storage::disk($testDiskName)->delete($probeKey);
            } catch (\Throwable $ignored) {
            }

            // If Flysystem S3 driver is missing in consumer app, provide explicit command
            $rawError = $e->getMessage();
            if (str_contains($rawError, 'PortableVisibilityConverter') || str_contains($rawError, 'AwsS3V3Adapter') || str_contains($rawError, 'S3Client')) {
                return [
                    'success' => false,
                    'message' => 'Cloud storage requires the S3 driver. Run in your project: composer require league/flysystem-aws-s3-v3 "^3.0"',
                    'details' => [
                        'provider' => $preset['name'],
                        'bucket' => $bucket,
                        'error_type' => 'MissingDriverException',
                    ],
                ];
            }

            // Extract actionable diagnosis from AWS S3 / Flysystem exceptions
            $diagnostics = null;
            $prev = $e->getPrevious() ?: $e;
            $awsCode = method_exists($prev, 'getAwsErrorCode') ? $prev->getAwsErrorCode() : null;
            $statusCode = method_exists($prev, 'getStatusCode') ? $prev->getStatusCode() : null;

            if ($awsCode === 'AccessDenied' || $statusCode === 403 || str_contains($rawError, 'AccessDenied') || str_contains($rawError, '403 Forbidden')) {
                $diagnostics = "Access Denied (403): Your API token does not have write permission. In Cloudflare Dashboard, verify your R2 API Token has 'Object Read & Write' permission (not Read-Only) and has access to bucket [{$bucket}].";
            } elseif ($awsCode === 'NoSuchBucket' || $statusCode === 404 || str_contains($rawError, 'NoSuchBucket') || str_contains($rawError, '404 Not Found')) {
                $diagnostics = "Bucket Not Found (404): The bucket [{$bucket}] does not exist in your {$preset['name']} account. Please create the bucket in your dashboard first.";
            } elseif ($awsCode === 'InvalidAccessKeyId' || str_contains($rawError, 'InvalidAccessKeyId')) {
                $diagnostics = "Invalid Access Key: The provided R2 Access Key ID is invalid or does not exist. Check your R2 API Token credentials.";
            } elseif ($awsCode === 'SignatureDoesNotMatch' || str_contains($rawError, 'SignatureDoesNotMatch')) {
                $diagnostics = "Signature Mismatch: The Secret Access Key is incorrect. Please re-copy the Secret Key from your R2 API Token (do not use the token value).";
            } elseif (str_contains($rawError, 'Could not resolve host') || str_contains($rawError, 'cURL error 6')) {
                $diagnostics = "Could not resolve host: The endpoint URL [{$diskConfig['endpoint']}] is unreachable. Verify your Cloudflare Account ID.";
            }

            // Redact credentials from error output
            $safeError = preg_replace('/(aws_secret_access_key|secret|key|token)=([^\s&]+)/i', '$1=***REDACTED***', $rawError);

            $message = $diagnostics 
                ? "Connection to {$preset['name']} failed: {$diagnostics}" 
                : "Connection to {$preset['name']} failed: " . $safeError;

            return [
                'success' => false,
                'message' => $message,
                'details' => [
                    'provider' => $preset['name'],
                    'bucket' => $bucket,
                    'endpoint' => $diskConfig['endpoint'],
                    'error_type' => class_basename($e),
                    'aws_code' => $awsCode,
                    'status_code' => $statusCode,
                ],
            ];
        }
    }

    /**
     * Reject endpoints that are not http(s) or that point at cloud metadata /
     * link-local addresses, so the connection test cannot be used for SSRF.
     * Returns an error message, or null when the endpoint is acceptable.
     */
    public function validateEndpoint(string $endpoint): ?string
    {
        if ($endpoint === '') {
            return null;
        }

        $parts = parse_url($endpoint);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower(trim($parts['host'] ?? '', '[]'));

        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return 'The storage endpoint must be a valid http(s) URL.';
        }

        $blockedHosts = ['metadata.google.internal', 'metadata', 'instance-data', 'instance-data.ec2.internal'];
        if (in_array($host, $blockedHosts, true)) {
            return 'The storage endpoint points at a cloud metadata service and is not allowed.';
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (@gethostbynamel($host) ?: []);
        foreach ($ips as $ip) {
            if ($this->isLinkLocal($ip)) {
                return 'The storage endpoint resolves to a link-local / metadata address and is not allowed.';
            }
        }

        return null;
    }

    protected function isLinkLocal(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = ip2long($ip);
            // 169.254.0.0/16 (link-local incl. 169.254.169.254) and 0.0.0.0/8
            return ($long & 0xFFFF0000) === (ip2long('169.254.0.0') & 0xFFFF0000)
                || ($long & 0xFF000000) === 0;
        }

        $ip = strtolower($ip);

        return str_starts_with($ip, 'fe80:') || str_starts_with($ip, 'fd00:ec2:');
    }

    /**
     * Generate an annotated, copyable .env code block for the given provider settings.
     */
    public function generateEnvSnippet(array $params): string
    {
        $providerId = $params['name'] ?? 'cloudflare_r2';
        $preset = $this->getProvider($providerId) ?? $this->providers['cloudflare_r2'];

        $key = $params['key'] ?? 'your_access_key_id';
        $secret = $params['secret'] ?? 'your_secret_access_key';
        $bucket = $params['bucket'] ?? 'my-bucket-name';
        $publicUrl = $params['public_url'] ?? 'https://cdn.example.com';
        $region = $params['region'] ?? $preset['default_region'];
        $endpoint = $this->resolveEndpoint($providerId, $params);

        $accountId = $params['account_id'] ?? null;
        if (empty($accountId) && $providerId === 'cloudflare_r2') {
            if (!empty($params['region']) && $params['region'] !== 'auto' && $params['region'] !== 'us-east-1') {
                $accountId = $params['region'];
            }
        }

        $lines = [
            "# ----------------------------------------------------------------",
            "# MediaFlow Storage & CDN Provider: {$preset['name']}",
            "# ----------------------------------------------------------------",
            "MEDIAFLOW_PROVIDER={$providerId}",
            "MEDIAFLOW_PROVIDER_KEY={$key}",
            "MEDIAFLOW_PROVIDER_SECRET={$secret}",
        ];

        if (!empty($accountId) || $providerId === 'cloudflare_r2') {
            $actId = !empty($accountId) ? $accountId : 'your_32_char_account_id';
            $lines[] = "MEDIAFLOW_PROVIDER_ACCOUNT_ID={$actId}";
            $lines[] = "CLOUDFLARE_ACCOUNT_ID={$actId}";
        }

        $lines[] = "MEDIAFLOW_PROVIDER_BUCKET={$bucket}";
        $lines[] = "MEDIAFLOW_PROVIDER_PUBLIC_URL={$publicUrl}";
        $lines[] = "MEDIAFLOW_PROVIDER_REGION={$region}";
        $lines[] = "MEDIAFLOW_PROVIDER_ENDPOINT={$endpoint}";
        $lines[] = "MEDIAFLOW_DISK=mediaflow_cloud";
        $lines[] = "MEDIAFLOW_DELIVERY_DRIVER=cdn";
        $lines[] = "MEDIAFLOW_CDN_URL={$publicUrl}";

        return implode("\n", $lines);
    }
}
