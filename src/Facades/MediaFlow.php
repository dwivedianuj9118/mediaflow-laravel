<?php

namespace MediaFlow\Laravel\Facades;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use MediaFlow\Laravel\Contracts\DeliveryAdapter;
use MediaFlow\Laravel\Contracts\StorageAdapter;
use MediaFlow\Laravel\Models\Media;
use MediaFlow\Laravel\Models\MediaFolder;
use MediaFlow\Laravel\Upload\UploadManager;

/**
 * @method static Media upload(UploadedFile $file, array $options = [])
 * @method static bool delete(Media|int $media, bool $purgeStorage = true)
 * @method static Media move(Media|int $media, ?int $targetFolderId)
 * @method static Media rename(Media|int $media, string $newDisplayName)
 * @method static array optimize(Media|int $media, array $options = [])
 * @method static MediaFolder createFolder(string $name, ?int $parentId = null, ?string $ownerId = null, ?string $tenantId = null)
 * @method static array testStorageConnection(?string $disk = null)
 * @method static DeliveryAdapter delivery()
 * @method static StorageAdapter disk(?string $disk = null)
 * @method static \MediaFlow\Laravel\Upload\UploadManager authorizeUsing(?\Closure $callback)
 * @method static bool hasAuthorizationCallback()
 * @method static bool isAuthorized($request, $user = null)
 *
 * @see UploadManager
 */
class MediaFlow extends Facade
{
    /**
     * Register a custom authorization callback for MediaFlow access.
     */
    public static function authorize(\Closure $callback): void
    {
        app('mediaflow')->authorizeUsing($callback);
    }

    protected static function getFacadeAccessor(): string
    {
        return 'mediaflow';
    }
}
