<?php

namespace MediaFlow\Laravel\Contracts;

interface StorageAdapter
{
    /**
     * Get the underlying disk name.
     */
    public function getDiskName(): string;

    /**
     * Write contents to the given storage path.
     */
    public function write(string $path, string $contents, array $options = []): bool;

    /**
     * Write stream to the given storage path.
     *
     * @param resource $resource
     */
    public function writeStream(string $path, $resource, array $options = []): bool;

    /**
     * Read contents from the given storage path.
     */
    public function read(string $path): ?string;

    /**
     * Read stream from the given storage path.
     *
     * @return resource|null
     */
    public function readStream(string $path);

    /**
     * Delete file at the given storage path.
     */
    public function delete(string $path): bool;

    /**
     * Check if a file exists at the given storage path.
     */
    public function exists(string $path): bool;

    /**
     * Resolve the public or storage-specific URL.
     */
    public function url(string $path): string;

    /**
     * Resolve a temporary expiring URL.
     */
    public function temporaryUrl(string $path, \DateTimeInterface $expiration, array $options = []): string;

    /**
     * Get file size in bytes.
     */
    public function size(string $path): int;

    /**
     * Get MIME type.
     */
    public function mimeType(string $path): ?string;

    /**
     * Copy an object to a new path.
     */
    public function copy(string $from, string $to): bool;

    /**
     * Move an object to a new path.
     */
    public function move(string $from, string $to): bool;

    /**
     * Test connection to the storage provider safely.
     *
     * @return array{success: bool, message: string, details: array}
     */
    public function testConnection(): array;
}
