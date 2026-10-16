<?php

namespace MediaFlow\Laravel\Storage;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use MediaFlow\Laravel\Contracts\StorageAdapter;

class LocalStorageAdapter implements StorageAdapter
{
    protected string $disk;

    public function __construct(string $disk = 'public')
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
        $url = $this->storage()->url($path);

        // Development convenience: when the generated URL points at localhost/127.0.0.1
        // (e.g. APP_URL=http://localhost while serving on :8899), rewrite it to the
        // current request origin so images load. Production hosts are never rewritten,
        // which keeps a spoofed Host header from changing the URLs we hand out.
        if (app()->bound('request')) {
            try {
                $request = request();
                if ($request && $request->hasHeader('host')
                    && preg_match('#^https?://(?:localhost|127\.0\.0\.1)(?::\d+)?(/.*)$#i', $url, $matches)) {
                    $requestHost = strtolower($request->getHost());
                    if (in_array($requestHost, ['localhost', '127.0.0.1'], true)) {
                        $url = $request->getSchemeAndHttpHost() . $matches[1];
                    }
                }
            } catch (\Throwable $e) {
                // Ignore and retain original url
            }
        }

        return $url;
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiration, array $options = []): string
    {
        throw new \RuntimeException("Disk [{$this->disk}] does not support driver-level temporary URLs.");
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
        $payload = 'mediaflow_connection_test_' . time();

        try {
            // Test Write
            $written = $this->storage()->put($probeKey, $payload);
            if (!$written) {
                return [
                    'success' => false,
                    'message' => 'Failed to write test probe to disk.',
                    'details' => ['disk' => $this->disk, 'writable' => false],
                ];
            }

            // Test Read
            $readBack = $this->storage()->get($probeKey);
            if ($readBack !== $payload) {
                $this->storage()->delete($probeKey);
                return [
                    'success' => false,
                    'message' => 'Integrity mismatch when reading test probe.',
                    'details' => ['disk' => $this->disk, 'readable' => false],
                ];
            }

            // Test Delete
            $this->storage()->delete($probeKey);

            return [
                'success' => true,
                'message' => "Successfully connected and verified read/write permissions on disk [{$this->disk}].",
                'details' => [
                    'disk' => $this->disk,
                    'driver' => config("filesystems.disks.{$this->disk}.driver", 'local'),
                    'writable' => true,
                    'readable' => true,
                ],
            ];
        } catch (\Throwable $e) {
            // Clean up probe if possible
            try {
                $this->storage()->delete($probeKey);
            } catch (\Throwable $cleanupError) {
                // Ignore cleanup error
            }

            return [
                'success' => false,
                'message' => 'Storage test failed: ' . $e->getMessage(),
                'details' => [
                    'disk' => $this->disk,
                    'error_class' => class_basename($e),
                ],
            ];
        }
    }
}
