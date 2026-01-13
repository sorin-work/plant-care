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
        $this->expectException(InvalidPhotoException::class);

        (new PhotoStorage($this->dir))->store($this->upload('application/pdf'));
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
