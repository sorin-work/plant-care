<?php

namespace App\Tests\Unit\Photo;

use App\Photo\InvalidPhotoException;
use App\Photo\PhotoStorage;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PhotoStorageTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/plant-photos-'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
    }

    private function upload(string $mime = 'image/png'): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'up');
        copy(__DIR__.'/../../fixtures/leaf.png', $tmp);

        return new UploadedFile($tmp, 'leaf.png', $mime, null, true);
    }

    public function testStoresWithRandomName(): void
    {
        $storage = new PhotoStorage($this->dir);
        $name = $storage->store($this->upload());

        self::assertMatchesRegularExpression('/^[a-f0-9]{32}\.png$/', $name);
        self::assertTrue($storage->exists($name));
        self::assertFileExists($this->dir.'/'.$name);
    }

    public function testRejectsUnsupportedType(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($tmp, '%PDF-1.4');

        $this->expectException(InvalidPhotoException::class);

        (new PhotoStorage($this->dir))->store(new UploadedFile($tmp, 'doc.pdf', 'application/pdf', null, true));
    }

    public function testIgnoresTheClientMimeType(): void
    {
        // a real PNG sent as application/octet-stream is fine
        $name = (new PhotoStorage($this->dir))->store($this->upload('application/octet-stream'));

        self::assertStringEndsWith('.png', $name);
    }

    public function testRejectsHtmlPretendingToBeAnImage(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($tmp, '<html><script>alert(document.cookie)</script></html>');

        $this->expectException(InvalidPhotoException::class);

        (new PhotoStorage($this->dir))->store(new UploadedFile($tmp, 'leaf.png', 'image/png', null, true));
    }

    public function testRejectsSvg(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($tmp, '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>');

        $this->expectException(InvalidPhotoException::class);

        (new PhotoStorage($this->dir))->store(new UploadedFile($tmp, 'leaf.svg', 'image/svg+xml', null, true));
    }

    public function testDelete(): void
    {
        $storage = new PhotoStorage($this->dir);
        $name = $storage->store($this->upload());
        $storage->delete($name);

        self::assertFalse($storage->exists($name));
    }

    public function testRejectsPathTraversal(): void
    {
        $storage = new PhotoStorage($this->dir);

        self::assertFalse($storage->exists('../../.env'));
        $this->expectException(InvalidArgumentException::class);
        $storage->path('../secret.png');
    }
}
