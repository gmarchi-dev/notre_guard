<?php

/**
 * Gera os ícones da PWA. Rodar só quando a identidade visual mudar:
 *   php scripts/generate-icons.php
 */
$target = __DIR__.'/../public/icons';

if (! is_dir($target)) {
    mkdir($target, 0755, true);
}

$font = 'C:\Windows\Fonts\arialbd.ttf';

/**
 * @param  int  $padding  margem interna - ícone maskable precisa de área segura
 */
function icon(string $path, int $size, int $padding, string $font): void
{
    $image = imagecreatetruecolor($size, $size);
    imagesavealpha($image, true);

    $background = imagecolorallocate($image, 11, 18, 32);   // #0b1220
    $shield = imagecolorallocate($image, 37, 99, 235);      // #2563eb
    $text = imagecolorallocate($image, 241, 245, 249);      // #f1f5f9

    imagefilledrectangle($image, 0, 0, $size, $size, $background);

    // Escudo: retângulo com a base afunilada.
    $box = $size - 2 * $padding;
    $left = $padding;
    $right = $size - $padding;
    $top = $padding;
    $shoulder = $top + (int) ($box * 0.55);
    $bottom = $size - $padding;
    $middle = (int) ($size / 2);

    imagefilledpolygon($image, [
        $left, $top,
        $right, $top,
        $right, $shoulder,
        $middle, $bottom,
        $left, $shoulder,
    ], $shield);

    if (is_readable($font)) {
        $fontSize = $box * 0.30;
        $metrics = imagettfbbox($fontSize, 0, $font, 'NG');
        $width = $metrics[2] - $metrics[0];
        $height = $metrics[1] - $metrics[7];

        imagettftext(
            $image,
            $fontSize,
            0,
            (int) ($middle - $width / 2),
            (int) ($top + $box * 0.42 + $height / 2),
            $text,
            $font,
            'NG',
        );
    }

    imagepng($image, $path);
    imagedestroy($image);

    echo "gerado: {$path}\n";
}

icon("{$target}/notre-guard-192.png", 192, 20, $font);
icon("{$target}/notre-guard-512.png", 512, 54, $font);
// Maskable: 20% de área segura em cada lado, senão o Android corta o desenho.
icon("{$target}/notre-guard-maskable.png", 512, 102, $font);
