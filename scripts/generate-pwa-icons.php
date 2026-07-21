<?php
$root = dirname(__DIR__);
$dir = $root . '/public/icons';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

function makeIcon(string $path, int $size): void
{
    $im = imagecreatetruecolor($size, $size);
    $bg = imagecolorallocate($im, 11, 18, 32);
    imagefilledrectangle($im, 0, 0, $size - 1, $size - 1, $bg);

    $pad = (int) round($size * 0.18);
    $w = $size - 2 * $pad;
    for ($y = 0; $y < $w; $y++) {
        $t = $y / max(1, $w - 1);
        $r = (int) round(255 + (220 - 255) * $t);
        $g = (int) round(45 + (38 - 45) * $t);
        $b = (int) round(85 + (38 - 85) * $t);
        $c = imagecolorallocate($im, $r, $g, $b);
        imagefilledrectangle($im, $pad, $pad + $y, $pad + $w - 1, $pad + $y, $c);
    }

    $white = imagecolorallocate($im, 255, 255, 255);
    $barH = (int) round($size * 0.12);
    $barW = (int) round($size * 0.42);
    $stemW = (int) round($size * 0.14);
    $top = (int) round($size * 0.32);
    $left = (int) round(($size - $barW) / 2);
    imagefilledrectangle($im, $left, $top, $left + $barW - 1, $top + $barH - 1, $white);
    $stemLeft = (int) round(($size - $stemW) / 2);
    imagefilledrectangle($im, $stemLeft, $top, $stemLeft + $stemW - 1, $top + (int) round($size * 0.40), $white);

    imagepng($im, $path);
    imagedestroy($im);
}

makeIcon($dir . '/icon-192.png', 192);
makeIcon($dir . '/icon-512.png', 512);
echo "icons ok\n";
