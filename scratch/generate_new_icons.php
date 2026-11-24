<?php
// Generate PNG favicons matching the new 3-bar SeqMath logo with PHP GD

function drawNewAppIcon($size) {
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, false);
    imagesavealpha($img, true);

    $trans = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $trans);
    imagealphablending($img, true);

    // Gradient background squircle (radius = 25% = 0.25 * $size)
    $r = (int)($size * 0.25);
    
    // Draw rounded rect with gradient from (79, 70, 229) to (124, 58, 237)
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            // Check rounded corner distance
            $inCorner = false;
            $dx = 0; $dy = 0;
            if ($x < $r && $y < $r) { $dx = $r - $x; $dy = $r - $y; $inCorner = true; }
            elseif ($x >= $size - $r && $y < $r) { $dx = $x - ($size - $r - 1); $dy = $r - $y; $inCorner = true; }
            elseif ($x < $r && $y >= $size - $r) { $dx = $r - $x; $dy = $y - ($size - $r - 1); $inCorner = true; }
            elseif ($x >= $size - $r && $y >= $size - $r) { $dx = $x - ($size - $r - 1); $dy = $y - ($size - $r - 1); $inCorner = true; }
            
            $inside = true;
            if ($inCorner) {
                $dist = sqrt($dx * $dx + $dy * $dy);
                if ($dist > $r) $inside = false;
            }
            
            if ($inside) {
                // Diagonal gradient (x + y) / (2 * size)
                $t = ($x + $y) / (2.0 * $size);
                $cr = (int)(79 + (124 - 79) * $t);
                $cg = (int)(70 + (58 - 70) * $t);
                $cb = (int)(229 + (237 - 229) * $t);
                $c = imagecolorallocate($img, $cr, $cg, $cb);
                imagesetpixel($img, $x, $y, $c);
            }
        }
    }

    // Colors for bars & trend
    $white = imagecolorallocate($img, 255, 255, 255);
    $yellow = imagecolorallocate($img, 251, 191, 36);

    // Helper: draw filled rounded rectangle
    $drawRoundRect = function($im, $x1, $y1, $x2, $y2, $radius, $col) {
        $w = $x2 - $x1;
        $h = $y2 - $y1;
        $rad = min($radius, (int)($w / 2), (int)($h / 2));
        imagefilledrectangle($im, $x1 + $rad, $y1, $x2 - $rad, $y2, $col);
        imagefilledrectangle($im, $x1, $y1 + $rad, $x2, $y2 - $rad, $col);
        imagefilledellipse($im, $x1 + $rad, $y1 + $rad, $rad * 2, $rad * 2, $col);
        imagefilledellipse($im, $x2 - $rad, $y1 + $rad, $rad * 2, $rad * 2, $col);
        imagefilledellipse($im, $x1 + $rad, $y2 - $rad, $rad * 2, $rad * 2, $col);
        imagefilledellipse($im, $x2 - $rad, $y2 - $rad, $rad * 2, $rad * 2, $col);
    };

    // Calculate bar dimensions based on viewBox 48x48
    // Bar 1: x 11..17, y 26..37 (w 6, h 11)
    // Bar 2: x 21..27, y 20..37 (w 6, h 17)
    // Bar 3: x 31..37, y 13..37 (w 6, h 24)
    $scale = $size / 48.0;
    $barRad = max(1, (int)(2.5 * $scale));

    // Bar 1 (White)
    $drawRoundRect($img, (int)(11 * $scale), (int)(26 * $scale), (int)(17 * $scale), (int)(37 * $scale), $barRad, $white);
    
    // Bar 2 (White)
    $drawRoundRect($img, (int)(21 * $scale), (int)(20 * $scale), (int)(27 * $scale), (int)(37 * $scale), $barRad, $white);
    
    // Bar 3 (Yellow)
    $drawRoundRect($img, (int)(31 * $scale), (int)(13 * $scale), (int)(37 * $scale), (int)(37 * $scale), $barRad, $yellow);

    // Trend line: (14, 23) -> (24, 17) -> (34, 10)
    imagesetthickness($img, max(1, (int)(2.2 * $scale)));
    imageline($img, (int)(14 * $scale), (int)(23 * $scale), (int)(24 * $scale), (int)(17 * $scale), $yellow);
    imageline($img, (int)(24 * $scale), (int)(17 * $scale), (int)(34 * $scale), (int)(10 * $scale), $yellow);

    // Dots
    $dotR1 = max(1, (int)(2.2 * $scale));
    $dotR3 = max(1, (int)(2.6 * $scale));
    imagefilledellipse($img, (int)(14 * $scale), (int)(23 * $scale), $dotR1 * 2, $dotR1 * 2, $white);
    imagefilledellipse($img, (int)(24 * $scale), (int)(17 * $scale), $dotR1 * 2, $dotR1 * 2, $white);
    imagefilledellipse($img, (int)(34 * $scale), (int)(10 * $scale), $dotR3 * 2, $dotR3 * 2, $yellow);

    return $img;
}

$base = dirname(__DIR__);

// 1. Generate PNGs
$sizes = [
    16 => $base . '/favicon-16x16.png',
    32 => $base . '/favicon-32x32.png',
    180 => $base . '/apple-touch-icon.png',
    192 => $base . '/icon-192.png',
    512 => $base . '/icon-512.png',
];

foreach ($sizes as $s => $path) {
    $im = drawNewAppIcon($s);
    imagepng($im, $path);
    imagedestroy($im);
    echo "Generated $s x $s: $path\n";
}

// 2. Generate multi-resolution .ico from 16 and 32 PNGs
function createIco(array $pngFiles, $icoPath) {
    $icoData = pack('vvv', 0, 1, count($pngFiles)); // Header: reserved (0), type (1 = icon), count
    $imageData = '';
    $offset = 6 + (16 * count($pngFiles)); // header (6) + entries (16 each)

    foreach ($pngFiles as $file) {
        $data = file_get_contents($file);
        $size = strlen($data);
        $info = getimagesize($file);
        $w = $info[0] >= 256 ? 0 : $info[0];
        $h = $info[1] >= 256 ? 0 : $info[1];

        // Directory Entry: width, height, colors (0), reserved (0), planes (1), bpp (32), size, offset
        $icoData .= pack('CCCCvvVV', $w, $h, 0, 0, 1, 32, $size, $offset);
        $imageData .= $data;
        $offset += $size;
    }

    file_put_contents($icoPath, $icoData . $imageData);
}

createIco([$base . '/favicon-16x16.png', $base . '/favicon-32x32.png'], $base . '/favicon.ico');
echo "Generated favicon.ico\n";
