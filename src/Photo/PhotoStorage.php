<?php

namespace App\Photo;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Stores uploaded plant photos on disk under random names.
 */
final class PhotoStorage
{
    public const MAX_BYTES = 8 * 1024 * 1024;

    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private readonly Filesystem $fs;

    public function __construct(
        #[Autowire('%env(resolve:PHOTO_DIR)%')]
        private readonly string $directory,
    ) {
        $this->fs = new Filesystem();
    }

    /**
     * @return string the stored file name
     *
     * @throws InvalidPhotoException
     */
    public function store(UploadedFile $file): string
    {
        if (!$file->isValid()) {
            throw new InvalidPhotoException('The photo could not be uploaded. Please try again.');
        }
        if ($file->getSize() > self::MAX_BYTES) {
            throw new InvalidPhotoException('Photos can be at most 8 MB.');
        }

        $extension = self::EXTENSIONS[$file->getClientMimeType()] ?? null;
        if (null === $extension) {
            throw new InvalidPhotoException('Please upload a JPEG, PNG or WebP image.');
        }

        $name = bin2hex(random_bytes(16)).'.'.$extension;
        $this->fs->mkdir($this->directory);
        $file->move($this->directory, $name);

        return $name;
    }

    public function path(string $name): string
    {
        if (!self::isValidName($name)) {
            throw new InvalidArgumentException('Invalid photo name.');
        }

        return $this->directory.'/'.$name;
    }

    public function exists(string $name): bool
    {
        return self::isValidName($name) && is_file($this->path($name));
    }

    public function delete(string $name): void
    {
        if ($this->exists($name)) {
            $this->fs->remove($this->path($name));
        }
    }

    public static function isValidName(string $name): bool
    {
        return 1 === preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name);
    }
}
