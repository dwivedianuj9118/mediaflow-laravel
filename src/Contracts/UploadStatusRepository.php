<?php

namespace MediaFlow\Laravel\Contracts;

interface UploadStatusRepository
{
    /**
     * Update the upload status and stage for a given media item or session ID.
     */
    public function updateStatus(int|string $mediaId, string $status, ?string $errorMessage = null, array $extra = []): void;

    /**
     * Get the current status details for a media item or session.
     *
     * @return array{status: string, error: ?string, progress?: int, details: array}|null
     */
    public function getStatus(int|string $mediaId): ?array;
}
