<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$test_cases = [
    ['mode' => '1'],
    ['mode' => '2', 'tab' => 'aritmetika', 'target' => 'Un'],
    ['mode' => '2', 'tab' => 'aritmetika', 'target' => 'a'],
    ['mode' => '2', 'tab' => 'aritmetika', 'target' => 'b'],
    ['mode' => '2', 'tab' => 'aritmetika', 'target' => 'n'],
    ['mode' => '2', 'tab' => 'geometri', 'target' => 'Un'],
    ['mode' => '3', 'tab' => 'aritmetika'],
    ['mode' => '3', 'tab' => 'geometri'],
    ['mode' => '4', 'tab' => 'aritmetika', 'm4_type' => 'tengah'],
    ['mode' => '4', 'tab' => 'aritmetika', 'm4_type' => 'sisipan'],
    ['mode' => '4', 'tab' => 'geometri', 'm4_type' => 'tengah'],
    ['mode' => '4', 'tab' => 'geometri', 'm4_type' => 'sisipan'],
    ['mode' => '5'],
];

foreach ($test_cases as $tc) {
    $_GET = $tc;
    $_POST = [];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    ob_start();
    include 'c:/Users/asus/Documents/xamps/htdocs/calculator/index.php';
    $out = ob_get_clean();
    echo "Tested mode " . ($tc['mode'] ?? 'default') . " tab " . ($tc['tab'] ?? '') . " target " . ($tc['target'] ?? '') . " type " . ($tc['m4_type'] ?? '') . ": OK, length " . strlen($out) . "\n";
}
