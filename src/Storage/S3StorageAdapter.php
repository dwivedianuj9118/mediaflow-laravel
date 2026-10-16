<?php

namespace MediaFlow\Laravel\Storage;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use MediaFlow\Laravel\Contracts\StorageAdapter;

class S3StorageAdapter implements StorageAdapter
{
    protected string $disk;

    public function __construct(string $disk = 's3')
    {
        $this->disk = $disk;
    }

    public function getDiskName(): string
    {
        return $this->disk;
    }

    protected function storage()
    {
        return Storage::disk($this->disk);
    }

    public function write(string $path, string $contents, array $options = []): bool
    {
        return $this->storage()->put($path, $contents, $options);
    }

    public function writeStream(string $path, $resource, array $options = []): bool
    {
        return $this->storage()->put($path, $resource, $options);
    }

    public function read(string $path): ?string
    {
        return $this->storage()->get($path);
    }

    public function readStream(string $path)
    {
        return $this->storage()->readStream($path);
    }

    public function delete(string $path): bool
    {
        return $this->storage()->delete($path);
    }

    public function exists(string $path): bool
    {
        return $this->storage()->exists($path);
    }

    public function url(string $path): string
    {
        return $this->storage()->url($path);
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiration, array $options = []): string
    {
        return $this->storage()->temporaryUrl($path, $expiration, $options);
    }

    public function size(string $path): int
    {
        return (int) $this->storage()->size($path);
    }

    public function mimeType(string $path): ?string
    {
        return $this->storage()->mimeType($path) ?: null;
    }

    public function copy(string $from, string $to): bool
    {
        return $this->storage()->copy($from, $to);
    }

    public function move(string $from, string $to): bool
    {
        return $this->storage()->move($from, $to);
    }

    public function testConnection(): array
    {
        $probeKey = '.mediaflow-probe-' . Str::random(12) . '.tmp';
        $payload = 'mediaflow_s3_connection_test_' . time();

        $bucket = config("filesystems.disks.{$this->disk}.bucket", 'undefined');
        $region = config("filesystems.disks.{$this->disk}.region", 'undefined');
        $endpoint = config("filesystems.disks.{$this->disk}.endpoint");

        if (!CloudProviderManager::s3DriverInstalled()) {
            return [
                'success' => false,
                'message' => CloudProviderManager::missingDriverMessage(),
                'details' => ['disk' => $this->disk],
            ];
        }

        try {
            // Test Write
            $written = $this->storage()->put($probeKey, $payload);
            if (!$written) {
                return [
                    'success' => false,
                    'message' => 'Failed to write test probe to S3/compatible bucket.',
                    'details' => [
                        'disk' => $this->disk,
                        'bucket' => $bucket,
                        'region' => $region,
                        'writable' => false,
                    ],
                ];
            }

            // Test Read
            $readBack = $this->storage()->get($probeKey);
            if ($readBack !== $payload) {
                $this->storage()->delete($probeKey);
                return [
                    'success' => false,
                    'message' => 'Integrity mismatch when reading test probe from S3.',
                    'details' => [
                        'disk' => $this->disk,
                        'bucket' => $bucket,
                        'readable' => false,
                    ],
                ];
            }

            // Test Delete
            $this->storage()->delete($probeKey);

            return [
                'success' => true,
                'message' => "Successfully connected and verified read/write/delete permissions on S3 bucket [{$bucket}].",
                'details' => [
                    'disk' => $this->disk,
                    'bucket' => $bucket,
                    'region' => $region,
                    'custom_endpoint' => !empty($endpoint),
                    'writable' => true,
                    'readable' => true,
                    'deletable' => true,
                ],
            ];
        } catch (\Throwable $e) {
            try {
                $this->storage()->delete($probeKey);
            } catch (\Throwable $cleanupError) {
                // Ignore cleanup error
            }

            // Safe error message: redact potential secrets/tokens in exception
            $safeMessage = preg_replace('/(aws_secret_access_key|secret|key|token)=([^\s&]+)/i', '$1=***REDACTED***', $e->getMessage());

            return [
                'success' => false,
                'message' => 'S3 connection test failed: ' . $safeMessage,
                'details' => [
                    'disk' => $this->disk,
                    'bucket' => $bucket,
                    'region' => $region,
                    'error_type' => class_basename($e),
                ],
            ];
        }
    }
}
