<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use LaravelBoilerplate\Shared\Application\Storage\FileStorage;
use LaravelBoilerplate\Shared\Application\Storage\StoragePath;
use LaravelBoilerplate\Shared\Application\Storage\StoredFileNotFound;
use Symfony\Component\Uid\Uuid;

// Работает с реальным S3 (RustFS из compose, бакет app-test)
beforeEach(function (): void {
    $this->storage = $this->app->make(FileStorage::class);
    $this->prefix = 'tests/'.Uuid::v7()->toRfc4122();
});

afterEach(function (): void {
    Storage::disk('s3')->deleteDirectory($this->prefix);
});

it('stores, reads and deletes files', function (): void {
    $path = StoragePath::of($this->prefix.'/hello.txt');

    $this->storage->put($path, 'hello', 'text/plain');

    expect($this->storage->exists($path))->toBeTrue()
        ->and(stream_get_contents($this->storage->readStream($path)))->toBe('hello');

    $this->storage->delete($path);

    expect($this->storage->exists($path))->toBeFalse();
});

it('throws not found for missing files', function (): void {
    $this->storage->readStream(StoragePath::of($this->prefix.'/missing.txt'));
})->throws(StoredFileNotFound::class);

it('issues working download urls with a UTF-8 file name', function (): void {
    $path = StoragePath::of($this->prefix.'/report.txt');
    $this->storage->put($path, 'download me', 'text/plain');

    $url = $this->storage->temporaryDownloadUrl($path, new DateInterval('PT5M'), 'отчёт.txt');
    $response = Http::get($url);

    expect($response->successful())->toBeTrue()
        ->and($response->body())->toBe('download me')
        ->and($response->header('Content-Disposition'))->toContain("filename*=UTF-8''");
});

it('issues working upload urls', function (): void {
    $path = StoragePath::of($this->prefix.'/uploaded.txt');

    $upload = $this->storage->temporaryUploadUrl($path, new DateInterval('PT5M'), 'text/plain');

    $response = Http::withHeaders($upload->headers)
        ->withBody('uploaded directly', 'text/plain')
        ->put($upload->url);

    expect($response->successful())->toBeTrue()
        ->and($this->storage->exists($path))->toBeTrue();
});

it('rejects presigned ttl above the SigV4 limit', function (): void {
    $this->storage->temporaryDownloadUrl(StoragePath::of($this->prefix.'/x.txt'), new DateInterval('P8D'));
})->throws(InvalidArgumentException::class);
