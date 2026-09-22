<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Storage;

use DateInterval;
use DateTimeImmutable;
use Illuminate\Filesystem\FilesystemAdapter;
use InvalidArgumentException;
use LaravelBoilerplate\Shared\Application\Storage\FileStorage;
use LaravelBoilerplate\Shared\Application\Storage\PresignedUpload;
use LaravelBoilerplate\Shared\Application\Storage\StoragePath;
use LaravelBoilerplate\Shared\Application\Storage\StoredFileNotFound;
use League\Flysystem\UnableToReadFile;
use LogicException;
use Psr\Clock\ClockInterface;

final readonly class LaravelFileStorage implements FileStorage
{
    // Предел SigV4 для presigned URL
    private const int MAX_TTL_SECONDS = 7 * 24 * 3600;

    public function __construct(
        private FilesystemAdapter $disk,
        private FilesystemAdapter $presignDisk,
        private ClockInterface $clock,
    ) {}

    public function put(StoragePath $path, mixed $contents, ?string $contentType = null): void
    {
        $options = $contentType !== null ? ['ContentType' => $contentType] : [];

        $this->disk->put($path->toString(), $contents, $options);
    }

    public function readStream(StoragePath $path)
    {
        try {
            $stream = $this->disk->readStream($path->toString());
        } catch (UnableToReadFile) {
            throw StoredFileNotFound::at($path);
        }

        if (! is_resource($stream)) {
            throw StoredFileNotFound::at($path);
        }

        return $stream;
    }

    public function exists(StoragePath $path): bool
    {
        return $this->disk->exists($path->toString());
    }

    public function delete(StoragePath $path): void
    {
        $this->disk->delete($path->toString());
    }

    public function temporaryDownloadUrl(StoragePath $path, DateInterval $ttl, ?string $downloadName = null): string
    {
        $options = $downloadName !== null
            ? ['ResponseContentDisposition' => $this->attachmentDisposition($downloadName)]
            : [];

        return $this->presignDisk->temporaryUrl($path->toString(), $this->expiresAt($ttl), $options);
    }

    public function temporaryUploadUrl(StoragePath $path, DateInterval $ttl, string $contentType): PresignedUpload
    {
        $expiresAt = $this->expiresAt($ttl);

        $result = $this->presignDisk->temporaryUploadUrl($path->toString(), $expiresAt, ['ContentType' => $contentType]);

        $url = $result['url'] ?? null;
        $rawHeaders = $result['headers'] ?? [];

        if (! is_string($url) || ! is_array($rawHeaders)) {
            throw new LogicException('Unexpected presigned upload response from filesystem');
        }

        $headers = [];

        foreach ($rawHeaders as $name => $value) {
            // Host выставит клиент сам; остальные заголовки входят в подпись
            if (! is_string($name) || strcasecmp($name, 'Host') === 0) {
                continue;
            }

            $parts = [];

            foreach (is_array($value) ? $value : [$value] as $item) {
                if (is_scalar($item)) {
                    $parts[] = (string) $item;
                }
            }

            if ($parts !== []) {
                $headers[$name] = implode(', ', $parts);
            }
        }

        return new PresignedUpload($url, $headers, $expiresAt);
    }

    private function expiresAt(DateInterval $ttl): DateTimeImmutable
    {
        $now = $this->clock->now();
        $expiresAt = $now->add($ttl);
        $seconds = $expiresAt->getTimestamp() - $now->getTimestamp();

        if ($seconds < 1 || $seconds > self::MAX_TTL_SECONDS) {
            throw new InvalidArgumentException('Presigned URL TTL must be between 1 second and 7 days');
        }

        return $expiresAt;
    }

    /**
     * RFC 6266: ASCII-фолбэк для старых клиентов плюс filename* в UTF-8.
     */
    private function attachmentDisposition(string $filename): string
    {
        $fallback = preg_replace('/[^\x20-\x7E]|["\\\\]/u', '_', $filename) ?? 'download';

        return sprintf('attachment; filename="%s"; filename*=UTF-8\'\'%s', $fallback, rawurlencode($filename));
    }
}
