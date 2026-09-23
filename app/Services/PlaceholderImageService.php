<?php

namespace App\Services;

class PlaceholderImageService
{
    public function productWebp(string $label, string $hex, int $size = 800): string
    {
        return $this->canvas($size, $size, $hex, $label, (int) ($size / 18));
    }

    public function bannerWebp(string $label, string $hex, int $width, int $height): string
    {
        return $this->canvas($width, $height, $hex, $label, 42);
    }

    public function canvas(int $width, int $height, string $hex, string $label, int $fontSize): string
    {
        $image = imagecreatetruecolor($width, $height);
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%02x%02x%02x');
        $bg = imagecolorallocate($image, $r, $g, $b);
        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        $overlay = imagecolorallocatealpha($image, 255, 255, 255, 108);
        imagefilledrectangle($image, 0, (int) ($height * 0.62), $width, $height, $overlay);

        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 28, 25, 23);
        $text = mb_strtoupper(mb_substr($label, 0, 28));
        $this->centeredText($image, $text, $fontSize, $ink, (int) ($height * 0.74));
        $this->centeredText($image, 'KESHVYA', max(12, (int) ($fontSize * 0.45)), $white, (int) ($height * 0.18));

        ob_start();
        imagewebp($image, null, 82);
        $data = ob_get_clean();
        imagedestroy($image);

        return $data;
    }

    private function centeredText($image, string $text, int $size, int $color, int $y): void
    {
        $font = 5;
        $width = imagesx($image);
        $textWidth = imagefontwidth($font) * strlen($text);
        $x = (int) (($width - $textWidth) / 2);
        imagestring($image, $font, max(8, $x), $y, $text, $color);
    }
}
