<?php
// Generate PNG favicons matching the new SeqMath logo using PHP GD

function drawFavicon($size) {
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, false);
    imagesavealpha($img, true);

    $trans = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $trans);
    imagealphablending($img, true);

    // Deep space navy background squircle
    $r = (int)($size * 0.25);
    $bgDark = imagecolorallocate($img, 15, 23, 42);
    $bgIndigo = imagecolorallocate($img, 30, 27, 75);
    
    // Draw rounded rect
    for ($y = 0; $y < $size; $y++) {
        $ratio = $y / $size;
        $cr = (int)(15 + (30 - 15) * $ratio);
        $cg = (int)(23 + (27 - 23) * $ratio);
        $cb = (int)(42 + (75 - 42) * $ratio);
        $lineColor = imagecolorallocate($img, $cr, $cg, $cb);
        
        for ($x = 0; $x < $size; $x++) {
            // Check rounded corner distance
            $inCorner = false;
            $dx = 0; $dy = 0;
            if ($x < $r && $y < $r) { $dx = $r - $x; $dy = $r - $y; $inCorner = true; }
            elseif ($x >= $size - $r && $y < $r) { $dx = $x - ($size - $r - 1); $dy = $r - $y; $inCorner = true; }
            elseif ($x < $r && $y >= $size - $r) { $dx = $r - $x; $dy = $y - ($size - $r - 1); $inCorner = true; }
            elseif ($x >= $size - $r && $y >= $size - $r) { $dx = $x - ($size - $r - 1); $dy = $y - ($size - $r - 1); $inCorner = true; }
            
            if ($inCorner) {
                $dist = sqrt($dx*$dx + $dy*$dy);
                if ($dist <= $r) {
                    imagesetpixel($img, $x, $y, $lineColor);
                }
            } else {
                imagesetpixel($img, $x, $y, $lineColor);
            }
        }
    }

    // Border highlight
    $borderCol = imagecolorallocatealpha($img, 129, 140, 248, 60);
    // Draw Sigma-S curves and glowing golden progression
    $cyan = imagecolorallocate($img, 56, 189, 248);
    $indigo = imagecolorallocate($img, 99, 102, 241);
    $gold = imagecolorallocate($img, 245, 158, 11);
    $goldLight = imagecolorallocate($img, 253, 224, 71);
    $white = imagecolorallocate($img, 255, 255, 255);

    // Scaling factor
    $s = $size / 64.0;

    // Top ribbon
    imagesetthickness($img, max(2, (int)(4 * $s)));
    imageline($img, (int)(48 * $s), (int)(16 * $s), (int)(24 * $s), (int)(16 * $s), $cyan);
    imageline($img, (int)(24 * $s), (int)(16 * $s), (int)(32 * $s), (int)(28 * $s), $indigo);

    // Bottom ribbon
    imageline($img, (int)(48 * $s), (int)(50 * $s), (int)(24 * $s), (int)(50 * $s), $gold);
    imageline($img, (int)(24 * $s), (int)(50 * $s), (int)(32 * $s), (int)(38 * $s), $indigo);

    // Golden curve
    imagesetthickness($img, max(2, (int)(3 * $s)));
    $prevX = (int)(12 * $s);
    $prevY = (int)(48 * $s);
    for ($t = 0.05; $t <= 1.0; $t += 0.05) {
        // Bezier curve
        $cx = pow(1 - $t, 2) * (12 * $s) + 2 * (1 - $t) * $t * (35 * $s) + pow($t, 2) * (52 * $s);
        $cy = pow(1 - $t, 2) * (48 * $s) + 2 * (1 - $t) * $t * (36 * $s) + pow($t, 2) * (12 * $s);
        imageline($img, $prevX, $prevY, (int)$cx, (int)$cy, $goldLight);
        $prevX = (int)$cx;
        $prevY = (int)$cy;
    }

    // Sequence node dots
    $nodeRadius = max(1, (int)(2.5 * $s));
    imagefilledellipse($img, (int)(20 * $s), (int)(44 * $s), $nodeRadius * 2, $nodeRadius * 2, $cyan);
    imagefilledellipse($img, (int)(32 * $s), (int)(34 * $s), $nodeRadius * 2, $nodeRadius * 2, $indigo);
    imagefilledellipse($img, (int)(44 * $s), (int)(22 * $s), $nodeRadius * 2, $nodeRadius * 2, $gold);
    imagefilledellipse($img, (int)(52 * $s), (int)(12 * $s), (int)($nodeRadius * 2.8), (int)($nodeRadius * 2.8), $white);

    return $img;
}

$dir = 'c:/Users/asus/Documents/xamps/htdocs/calculator';

// 16x16
$img16 = drawFavicon(16);
imagepng($img16, "$dir/favicon-16x16.png");

// 32x32
$img32 = drawFavicon(32);
imagepng($img32, "$dir/favicon-32x32.png");

// 180x180 (Apple touch icon)
$img180 = drawFavicon(180);
imagepng($img180, "$dir/apple-touch-icon.png");

// favicon.ico (simple 32x32 ICO header + PNG payload)
$pngData = file_get_contents("$dir/favicon-32x32.png");
$icoHeader = pack('vvv', 0, 1, 1);
$icoEntry = pack('CCCCvvVV', 32, 32, 0, 0, 1, 32, strlen($pngData), 22);
file_put_contents("$dir/favicon.ico", $icoHeader . $icoEntry . $pngData);

echo "Favicons generated successfully!\n";
