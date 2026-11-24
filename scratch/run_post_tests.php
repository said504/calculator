<?php
$posts = [
    // Model 1
    ['mode' => '1', 'barisan' => '3, 6, 12, 24', 'n_cari' => '10'],
    // Model 2
    ['mode' => '2', 'tab' => 'aritmetika', 'target' => 'Un', 'ar_a' => '2', 'ar_b' => '3', 'ar_n' => '5'],
    // Model 3
    ['mode' => '3', 'tab' => 'aritmetika', 'm3_ar_p' => '3', 'm3_ar_Up' => '10', 'm3_ar_q' => '7', 'm3_ar_Uq' => '22', 'm3_ar_n' => '30'],
    ['mode' => '3', 'tab' => 'geometri', 'm3_ge_p' => '2', 'm3_ge_Up' => '6', 'm3_ge_q' => '5', 'm3_ge_Uq' => '48', 'm3_ge_n' => '7'],
    // Model 4
    ['mode' => '4', 'tab' => 'aritmetika', 'm4_type' => 'tengah', 'm4_ar_a' => '3', 'm4_ar_Un' => '27', 'm4_ar_n' => '5'],
    ['mode' => '4', 'tab' => 'aritmetika', 'm4_type' => 'sisipan', 'm4_ar_x' => '4', 'm4_ar_y' => '28', 'm4_ar_k' => '5'],
    ['mode' => '4', 'tab' => 'geometri', 'm4_type' => 'tengah', 'm4_ge_a' => '2', 'm4_ge_Un' => '32', 'm4_ge_n' => '5'],
    ['mode' => '4', 'tab' => 'geometri', 'm4_type' => 'sisipan', 'm4_ge_x' => '3', 'm4_ge_y' => '48', 'm4_ge_k' => '3'],
    // Model 5
    ['mode' => '5', 'm5_A' => '2', 'm5_B' => '3', 'm5_k' => '10'],
];

foreach ($posts as $idx => $p) {
    $code = 'error_reporting(E_ALL); ini_set("display_errors", 1); ';
    $code .= '$_POST = ' . var_export($p, true) . '; ';
    $code .= '$_GET = ' . var_export(['mode' => $p['mode'], 'tab' => $p['tab'] ?? '', 'target' => $p['target'] ?? '', 'm4_type' => $p['m4_type'] ?? ''], true) . '; ';
    $code .= '$_SERVER["REQUEST_METHOD"] = "POST"; ';
    $code .= 'include "c:/Users/asus/Documents/xamps/htdocs/calculator/index.php"; ';
    
    $tmp = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($tmp, '<?php ' . $code);
    $out = shell_exec('php ' . escapeshellarg($tmp) . ' 2>&1');
    unlink($tmp);
    
    if (preg_match('/(Warning|Notice|Fatal error|Parse error):/i', $out, $m)) {
        echo "FAIL case $idx (Mode {$p['mode']}): " . substr($out, 0, 300) . "\n";
    } else {
        echo "OK case $idx (Mode {$p['mode']}): output length " . strlen($out) . "\n";
    }
}
