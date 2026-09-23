<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageService
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    public function storeProductImage(UploadedFile $file, int $productId): array
    {
        $this->assertImage($file);
        $base = 'products/'.$productId.'/'.Str::uuid();

        return $this->storeSizesFromPath($file->getRealPath(), $base, [
            'thumb' => config('shop.image.thumb'),
            'medium' => config('shop.image.medium'),
            'large' => config('shop.image.large'),
        ]);
    }

    /**
     * Store product image sizes from a local file path (already validated).
     *
     * @return array{path_thumb: string, path_medium: string, path_large: string}
     */
    public function storeProductImageFromPath(string $absolutePath, int $productId, ?string $basename = null): array
    {
        $base = 'products/'.$productId.'/'.($basename ?: (string) Str::uuid());

        return $this->storeSizesFromPath($absolutePath, $base, [
            'thumb' => config('shop.image.thumb'),
            'medium' => config('shop.image.medium'),
            'large' => config('shop.image.large'),
        ]);
    }

    public function storeSingle(UploadedFile $file, string $directory, int $maxEdge = 1600): string
    {
        $this->assertImage($file);

        return $this->storeSingleFromPath($file->getRealPath(), $directory, $maxEdge);
    }

    public function storeSingleFromPath(string $absolutePath, string $directory, int $maxEdge = 1600): string
    {
        $path = trim($directory, '/').'/'.Str::uuid().'.webp';
        $image = $this->manager->read($absolutePath);
        $image->scaleDown($maxEdge, $maxEdge);
        Storage::disk('public')->put($path, (string) $image->toWebp(config('shop.image.quality')));

        return $path;
    }

    /**
     * Store a wide hero/slider banner cropped to exact dimensions.
     */
    public function storeHeroBannerFromPath(string $absolutePath, string $directory, int $width, int $height): string
    {
        $path = trim($directory, '/').'/'.Str::uuid().'.webp';
        $image = $this->manager->read($absolutePath);
        $image->cover($width, $height);
        Storage::disk('public')->put($path, (string) $image->toWebp(config('shop.image.quality')));

        return $path;
    }

    public function storeGenerated(string $binaryWebp, string $path): string
    {
        Storage::disk('public')->put($path, $binaryWebp);

        return $path;
    }

    public function delete(string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    public function deleteMany(array $paths): void
    {
        Storage::disk('public')->delete(array_filter($paths));
    }

    /**
     * @param  array<string, int>  $sizes
     * @return array<string, string>
     */
    private function storeSizesFromPath(string $absolutePath, string $base, array $sizes): array
    {
        $paths = [];

        foreach ($sizes as $name => $edge) {
            $image = $this->manager->read($absolutePath);
            $image->cover($edge, $edge);
            $path = $base.'_'.$name.'.webp';
            Storage::disk('public')->put($path, (string) $image->toWebp(config('shop.image.quality')));
            $paths['path_'.$name] = $path;
        }

        return $paths;
    }

    private function storeSizes(UploadedFile $file, string $base, array $sizes): array
    {
        return $this->storeSizesFromPath($file->getRealPath(), $base, $sizes);
    }

    private function assertImage(UploadedFile $file): void
    {
        $maxKb = config('shop.image.max_kb');

        if ($file->getSize() > $maxKb * 1024) {
            abort(422, 'Image must be smaller than '.$maxKb.' KB.');
        }

        $mime = $file->getMimeType();
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            abort(422, 'Only JPG, PNG or WebP images are allowed.');
        }
    }
}
