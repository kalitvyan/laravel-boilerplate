<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Application\Storage;

use DateInterval;

/**
 * Приватное хранилище. Клиентам файлы отдаются только через короткоживущие presigned URL.
 */
interface FileStorage
{
    /**
     * @param  resource|string  $contents
     */
    public function put(StoragePath $path, mixed $contents, ?string $contentType = null): void;

    /**
     * @return resource
     *
     * @throws StoredFileNotFound
     */
    public function readStream(StoragePath $path);

    public function exists(StoragePath $path): bool;

    public function delete(StoragePath $path): void;

    public function temporaryDownloadUrl(StoragePath $path, DateInterval $ttl, ?string $downloadName = null): string;

    public function temporaryUploadUrl(StoragePath $path, DateInterval $ttl, string $contentType): PresignedUpload;
}
