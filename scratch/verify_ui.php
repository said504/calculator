<?php
// Test Mode 1 with arithmetic sequence 3, 7, 11, 15
$_POST = ['mode' => '1', 'barisan' => '3, 7, 11, 15', 'n_cari' => '10'];
$_GET = ['mode' => '1'];
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
include __DIR__ . '/../index.php';
$html = ob_get_clean();

$checks = [
    'KaTeX CDN included' => strpos($html, 'katex.min.js') !== false,
    'Dark mode toggle exists' => strpos($html, 'id="themeToggle"') !== false,
    'Worksheet section exists' => strpos($html, 'worksheet-section') !== false,
    'Toggle steps button exists' => strpos($html, 'id="toggleStepsBtn"') !== false,
    'Step cards exist' => strpos($html, 'step-card') !== false,
    'Formula highlight card exists' => strpos($html, 'formula-highlight-card') !== false,
    'Copy formula button exists' => strpos($html, 'btn-copy-formula') !== false,
    'History section exists' => strpos($html, 'history-section') !== false,
    'Chart.js line chart configured' => strpos($html, "type: 'line'") !== false,
    'Arithmetic blue color [59, 130, 246]' => strpos($html, '[59, 130, 246]') !== false,
];

foreach ($checks as $name => $ok) {
    echo ($ok ? "[PASS]" : "[FAIL]") . " $name\n";
}

// Test Mode 1 with geometric sequence 3, 6, 12, 24
$_POST = ['mode' => '1', 'barisan' => '3, 6, 12, 24', 'n_cari' => '6'];
$_GET = ['mode' => '1'];
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
include __DIR__ . '/../index.php';
$htmlGeo = ob_get_clean();

$geoChecks = [
    'Geometric teal color [16, 185, 129]' => strpos($htmlGeo, '[16, 185, 129]') !== false,
    'Geometric badge exists' => strpos($htmlGeo, 'badge-geo') !== false,
    'Geometric step cards exist' => strpos($htmlGeo, 'step-geo') !== false,
];

$logoChecks = [
    'Inline SVG Logo exists' => strpos($html, 'seqmath-logo-icon') !== false,
    'Wordmark SeqMath exists' => strpos($html, 'Seq<span class="brand-accent">Math</span>') !== false,
    '3 progression bars exist' => strpos($html, 'seq-bar-3') !== false,
    'Trend line exists' => strpos($html, 'seq-trend') !== false,
    'Subtitle exists' => strpos($html, 'Kalkulator Barisan &amp; Deret Aritmetika dan Geometri') !== false,
];

foreach ($logoChecks as $name => $ok) {
    echo ($ok ? "[PASS]" : "[FAIL]") . " $name\n";
}
