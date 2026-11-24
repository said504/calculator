<?php
$content = file_get_contents('c:/Users/asus/Documents/xamps/htdocs/calculator/style.css');
$lines = explode("\n", $content);
echo "Total lines: " . count($lines) . "\n";
foreach ($lines as $i => $line) {
    if (strpos($line, '/* ===') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
