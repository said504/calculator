<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$posts = [
    // Model 1
    ['mode' => '1', 'barisan' => '3, 6, 12, 24', 'n_cari' => '10'],
    // Model 2
    ['mode' => '2', 'tab' => 'aritmetika', 'target' => 'Un', 'ar_a' => '2', 'ar_b' => '3', 'ar_n' => '5'],
    // Model 3
    ['mode' => '3', 'tab' => 'aritmetika', 'm3_p' => '3', 'm3_Up' => '10', 'm3_q' => '7', 'm3_Uq' => '22', 'm3_target_n' => '30'],
    ['mode' => '3', 'tab' => 'geometri', 'm3_gp' => '2', 'm3_gUp' => '6', 'm3_gq' => '5', 'm3_gUq' => '48', 'm3_gtarget_n' => '7'],
    // Model 4
    ['mode' => '4', 'tab' => 'aritmetika', 'm4_type' => 'tengah', 'm4_a' => '3', 'm4_Un' => '27', 'm4_b' => '2'],
    ['mode' => '4', 'tab' => 'aritmetika', 'm4_type' => 'sisipan', 'm4_x' => '4', 'm4_y' => '28', 'm4_k' => '5', 'm4_n' => '10'],
    // Model 5
    ['mode' => '5', 'm5_A' => '2', 'm5_B' => '3', 'm5_k' => '10'],
];

foreach ($posts as $idx => $p) {
    $_GET = ['mode' => $p['mode'], 'tab' => $p['tab'] ?? '', 'target' => $p['target'] ?? '', 'm4_type' => $p['m4_type'] ?? ''];
    $_POST = $p;
    $_SERVER['REQUEST_METHOD'] = 'POST';

    ob_start();
    include 'c:/Users/asus/Documents/xamps/htdocs/calculator/index.php';
    $out = ob_get_clean();
    echo "POST case $idx (Mode {$p['mode']}): OK, output length " . strlen($out) . "\n";
}
