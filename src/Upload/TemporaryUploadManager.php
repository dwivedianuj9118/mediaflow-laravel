<?php

namespace MediaFlow\Laravel\Upload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use MediaFlow\Laravel\Models\UploadSession;

class TemporaryUploadManager
{
    protected string $disk;
    protected string $path;

    public function __construct()
    {
        $this->disk = config('mediaflow.temporary_disk', 'local');
        $this->path = trim(config('mediaflow.temporary_path', 'mediaflow-temp'), '/');
    }

    /**
     * Store an uploaded file into temporary quarantine storage.
     */
    public function store(UploadedFile $file, array $options = []): UploadSession
    {
        $sessionId = (string) Str::uuid();
        $safeExtension = app(ValidationService::class)->safeExtension($file);
        $storedFilename = "{$sessionId}.{$safeExtension}";
        $tempPath = "{$this->path}/{$storedFilename}";

        Storage::disk($this->disk)->putFileAs($this->path, $file, $storedFilename);

        return UploadSession::create([
            'id' => $sessionId,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'temp_disk' => $this->disk,
            'temp_path' => $tempPath,
            'status' => 'uploaded',
            'options' => $options,
            'expires_at' => now()->addHours(6),
        ]);
    }

    /**
     * Retrieve local filesystem path for a temporary session.
     */
    public function getLocalPath(UploadSession $session): string
    {
        return Storage::disk($session->temp_disk)->path($session->temp_path);
    }

    /**
     * Clean up temporary storage and delete session.
     */
    public function cleanup(UploadSession $session): bool
    {
        try {
            Storage::disk($session->temp_disk)->delete($session->temp_path);
            $session->delete();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Clean expired temporary upload sessions and orphaned files.
     */
    public function cleanExpired(bool $dryRun = false): int
    {
        $expiredSessions = UploadSession::where('expires_at', '<', now())->get();
        $count = 0;

        foreach ($expiredSessions as $session) {
            $count++;
            if (!$dryRun) {
                $this->cleanup($session);
            }
        }

        return $count;
    }
}
