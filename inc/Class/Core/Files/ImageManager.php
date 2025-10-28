<?php
declare(strict_types=1);

namespace Core\Files;

/**
 * Utilities for working with images stored through the filesystem abstraction.
 * Provides helpers for resizing, cropping and exporting images to WebP format.
 */
final class ImageManager
{
    public function __construct(private readonly Storage $storage) {}

    /**
     * Resize an image and save the result to the given destination.
     *
     * @throws \InvalidArgumentException
     */
    public function resize(string $source, string $destination, int $width, int $height, bool $preserveAspectRatio = true, ?int $quality = null): void
    {
        if ($width <= 0 || $height <= 0) {
            throw new \InvalidArgumentException('Width and height must be positive integers.');
        }

        $image = $this->loadImage($source);
        $resized = null;

        try {
            $origWidth = imagesx($image);
            $origHeight = imagesy($image);
            if ($origWidth === 0 || $origHeight === 0) {
                throw new \RuntimeException("Unable to determine image size for {$source}.");
            }

            $targetWidth = $width;
            $targetHeight = $height;

            if ($preserveAspectRatio) {
                $ratio = min($width / $origWidth, $height / $origHeight);
                $targetWidth = max(1, (int) round($origWidth * $ratio));
                $targetHeight = max(1, (int) round($origHeight * $ratio));
            }

            $resized = imagecreatetruecolor($targetWidth, $targetHeight);
            if (!$resized instanceof \GdImage) {
                throw new \RuntimeException('Unable to create destination image resource.');
            }

            $this->preserveTransparency($image, $resized);

            if (!imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $origWidth, $origHeight)) {
                throw new \RuntimeException("Unable to resize image: {$source}.");
            }

            $this->saveImage($resized, $destination, $quality);
        } finally {
            if ($resized instanceof \GdImage) {
                imagedestroy($resized);
            }

            imagedestroy($image);
        }
    }

    /**
     * Crop a portion of an image.
     */
    public function crop(string $source, string $destination, int $width, int $height, int $x = 0, int $y = 0, ?int $quality = null): void
    {
        if ($width <= 0 || $height <= 0) {
            throw new \InvalidArgumentException('Width and height must be positive integers.');
        }

        $image = $this->loadImage($source);
        $cropped = null;

        try {
            $crop = imagecrop($image, [
                'x' => $x,
                'y' => $y,
                'width' => $width,
                'height' => $height,
            ]);

            if ($crop === false) {
                throw new \RuntimeException("Unable to crop image: {$source}.");
            }

            $cropped = $crop;
            imagealphablending($cropped, false);
            imagesavealpha($cropped, true);

            $this->saveImage($cropped, $destination, $quality);
        } finally {
            if ($cropped instanceof \GdImage) {
                imagedestroy($cropped);
            }

            imagedestroy($image);
        }
    }

    /**
     * Convert an image to the WebP format.
     */
    public function convertToWebp(string $source, string $destination, int $quality = 80): void
    {
        $image = $this->loadImage($source);

        try {
            $this->saveImage($image, $destination, $quality, 'webp');
        } finally {
            imagedestroy($image);
        }
    }

    /**
     * Load an image into a GD resource.
     */
    private function loadImage(string $path): \GdImage
    {
        $data = $this->storage->read($path);
        $image = imagecreatefromstring($data);
        if (!$image instanceof \GdImage) {
            throw new \RuntimeException("Unable to create image from {$path}.");
        }

        return $image;
    }

    private function preserveTransparency(\GdImage $source, \GdImage $target): void
    {
        $transparentIndex = imagecolortransparent($source);
        if ($transparentIndex >= 0) {
            $transparentColor = imagecolorsforindex($source, $transparentIndex);
            $allocate = imagecolorallocate(
                $target,
                $transparentColor['red'],
                $transparentColor['green'],
                $transparentColor['blue']
            );
            if ($allocate !== false) {
                imagefill($target, 0, 0, $allocate);
                imagecolortransparent($target, $allocate);
            }
        } else {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
            if ($transparent !== false) {
                imagefill($target, 0, 0, $transparent);
            }
        }
    }

    private function saveImage(\GdImage $image, string $destination, ?int $quality = null, ?string $forceFormat = null): void
    {
        $format = $forceFormat ?? strtolower((string) pathinfo($destination, PATHINFO_EXTENSION));
        if ($format === '') {
            throw new \InvalidArgumentException('Destination must include a file extension.');
        }

        $quality = $quality === null ? null : max(0, min(100, $quality));

        $data = $this->exportImageData($image, $format, $quality);
        $this->storage->write($destination, $data);
    }

    private function exportImageData(\GdImage $image, string $format, ?int $quality): string
    {
        ob_start();

        try {
            switch ($format) {
                case 'jpg':
                case 'jpeg':
                    if (!imagejpeg($image, null, $quality ?? 85)) {
                        throw new \RuntimeException('Failed to export JPEG image.');
                    }
                    break;
                case 'png':
                    $compression = $quality === null ? 6 : (int) round((100 - $quality) * 9 / 100);
                    $compression = min(max($compression, 0), 9);
                    if (!imagepng($image, null, $compression)) {
                        throw new \RuntimeException('Failed to export PNG image.');
                    }
                    break;
                case 'gif':
                    if (!imagegif($image)) {
                        throw new \RuntimeException('Failed to export GIF image.');
                    }
                    break;
                case 'webp':
                    if (!function_exists('imagewebp')) {
                        throw new \RuntimeException('WebP support is not enabled in the GD extension.');
                    }

                    if (!imagewebp($image, null, $quality ?? 80)) {
                        throw new \RuntimeException('Failed to export WebP image.');
                    }
                    break;
                default:
                    throw new \InvalidArgumentException("Unsupported image format: {$format}");
            }

            $data = ob_get_clean();
            if ($data === false) {
                throw new \RuntimeException('Failed to capture image output buffer.');
            }
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        return $data;
    }
}
