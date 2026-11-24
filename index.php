<?php
// ==========================================
//  SeqMath — Kalkulator Baris & Deret (PHP)
// ==========================================

$tab   = $_POST['tab']  ?? ($_GET['tab']  ?? 'aritmetika');
$mode  = $_POST['mode'] ?? ($_GET['mode'] ?? '1');

// ---------- Helper ----------
if (!function_exists('fmt')) {
    function fmt($num) {
        if ($num == (int)$num && abs($num) < 1e15) {
            return number_format((int)$num, 0, ',', '.');
        }
        return rtrim(rtrim(number_format($num, 6, ',', '.'), '0'), ',');
    }
}

if (!function_exists('previewBarisan')) {
    function previewBarisan($terms, $max = 12) {
        if (count($terms) > $max) {
            $awal  = array_map('fmt', array_slice($terms, 0, 6));
            $akhir = array_map('fmt', array_slice($terms, -3));
            return implode(', ', $awal) . ', ..., ' . implode(', ', $akhir);
        }
        return implode(', ', array_map('fmt', $terms));
    }
}

// ==========================================
//  MODE 1 — Cari Rumus dari Barisan
// ==========================================
$m1 = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === '1') {
    $raw = trim($_POST['barisan'] ?? '');
    $nCari = isset($_POST['n_cari']) && $_POST['n_cari'] !== '' ? intval($_POST['n_cari']) : null;

    // Parse input: pisah koma / spasi
    $parts = preg_split('/[\s,;]+/', $raw);
    $suku = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p !== '' && is_numeric($p)) $suku[] = floatval($p);
    }

    if (count($suku) >= 2) {
        $a = $suku[0];
        $count = count($suku);

        // Cek aritmetika: selisih konstan?
        $isArit = true;
        $b = $suku[1] - $suku[0];
        for ($i = 2; $i < $count; $i++) {
            if (abs(($suku[$i] - $suku[$i - 1]) - $b) > 1e-9) {
                $isArit = false;
                break;
            }
        }

        // Cek geometri: rasio konstan?
        $isGeo = true;
        $r = ($suku[0] != 0) ? $suku[1] / $suku[0] : null;
        if ($r === null) {
            $isGeo = false;
        } else {
            for ($i = 2; $i < $count; $i++) {
                if ($suku[$i - 1] == 0 || abs(($suku[$i] / $suku[$i - 1]) - $r) > 1e-9) {
                    $isGeo = false;
                    break;
                }
            }
        }

        $m1 = [
            'suku'    => $suku,
            'a'       => $a,
            'count'   => $count,
            'isArit'  => $isArit,
            'isGeo'   => $isGeo,
            'b'       => $isArit ? $b : null,
            'r'       => $isGeo  ? $r : null,
            'nCari'   => $nCari,
            'raw'     => $raw,
        ];

        // Tentukan target n (jika diisi pakai n_cari, jika tidak pakai jumlah suku yang diinput)
        $targetN = ($nCari && $nCari >= 1) ? $nCari : $count;
        $m1['targetN'] = $targetN;

        if ($isArit) {
            $m1['Un'] = $a + ($targetN - 1) * $b;
            $m1['Sn'] = ($targetN / 2) * (2 * $a + ($targetN - 1) * $b);
            $terms = [];
            for ($i = 1; $i <= max($targetN, $count); $i++) {
                $terms[] = $a + ($i - 1) * $b;
            }
            $m1['terms'] = $terms;
        }

        if ($isGeo) {
            $m1['Un'] = $a * pow($r, $targetN - 1);
            $m1['Sn'] = ($r == 1) ? $a * $targetN : $a * (pow($r, $targetN) - 1) / ($r - 1);
            $m1['Sinf'] = (abs($r) < 1 && $r != 0) ? $a / (1 - $r) : null;
            $m1['konvergen'] = (abs($r) < 1 && $r != 0);
            $terms = [];
            for ($i = 1; $i <= max($targetN, $count); $i++) {
                $terms[] = $a * pow($r, $i - 1);
            }
            $m1['terms'] = $terms;
        }
    }
}

// ==========================================
//  MODE 2 — Hitung Parameter / Variabel
// ==========================================
$target = $_POST['target'] ?? ($_GET['target'] ?? 'Un');
if ($target === 'two_terms') {
    $target = 'Un';
}

// ---------- Hitung Aritmetika ----------
$ar = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === '2' && $tab === 'aritmetika') {
    $error = null;
    $steps = [];
    $terms = [];

    if ($target === 'Un') {
        // Lengkap: diketahui a, b, n -> cari Un dan Sn
        $a = isset($_POST['ar_a']) && $_POST['ar_a'] !== '' ? floatval($_POST['ar_a']) : null;
        $b = isset($_POST['ar_b']) && $_POST['ar_b'] !== '' ? floatval($_POST['ar_b']) : null;
        $n = isset($_POST['ar_n']) && $_POST['ar_n'] !== '' ? intval($_POST['ar_n']) : null;

        if ($a !== null && $b !== null && $n !== null) {
            if ($n < 1) {
                $error = "Jumlah suku (n) harus minimal 1.";
            } else {
                $Un = $a + ($n - 1) * $b;
                $Sn = ($n / 2) * (2 * $a + ($n - 1) * $b);
                for ($i = 1; $i <= $n; $i++) $terms[] = $a + ($i - 1) * $b;

                $steps[] = "<strong>Identifikasi Parameter yang Diketahui:</strong><br>" .
                           "• Suku pertama (a) = <code>" . fmt($a) . "</code><br>" .
                           "• Beda tiap suku (b) = <code>" . fmt($b) . "</code><br>" .
                           "• Banyak suku yang dihitung (n) = <code>$n</code>";
                $steps[] = "<strong>Menghitung Suku ke-$n (U_{$n}):</strong><br>" .
                           "Rumus: <code>U_n = a + (n − 1) · b</code><br>" .
                           "Substitusi nilai: <code>U_{$n} = " . fmt($a) . " + ($n − 1) × " . fmt($b) . "</code><br>" .
                           "<code>= " . fmt($a) . " + (" . ($n - 1) . ") × " . fmt($b) . "</code><br>" .
                           "<code>= " . fmt($a) . " + " . fmt(($n - 1) * $b) . " = <strong>" . fmt($Un) . "</strong></code>";
                $steps[] = "<strong>Menyusun Rumus Umum Barisan:</strong><br>" .
                           "<code>U_n = " . fmt($a) . " + (n − 1) × " . fmt($b) . " = " . fmt($b) . "n + (" . fmt($a - $b) . ")</code>";
                $steps[] = "<strong>Menghitung Jumlah $n Suku Pertama (S_{$n}):</strong><br>" .
                           "Rumus: <code>S_n = (n/2) · [2a + (n − 1)b]</code><br>" .
                           "Substitusi nilai: <code>S_{$n} = ($n / 2) × [2 × " . fmt($a) . " + ($n − 1) × " . fmt($b) . "]</code><br>" .
                           "<code>= " . fmt($n / 2) . " × [" . fmt(2 * $a) . " + " . fmt(($n - 1) * $b) . "]</code><br>" .
                           "<code>= " . fmt($n / 2) . " × [" . fmt(2 * $a + ($n - 1) * $b) . "] = <strong>" . fmt($Sn) . "</strong></code>";

                $ar = compact('a', 'b', 'n', 'Un', 'Sn', 'terms', 'steps', 'target');
            }
        }
    } elseif ($target === 'a') {
        // Cari a: diketahui b, n, Un
        $b  = isset($_POST['ar_b']) && $_POST['ar_b'] !== '' ? floatval($_POST['ar_b']) : null;
        $n  = isset($_POST['ar_n']) && $_POST['ar_n'] !== '' ? intval($_POST['ar_n']) : null;
        $Un = isset($_POST['ar_Un']) && $_POST['ar_Un'] !== '' ? floatval($_POST['ar_Un']) : null;

        if ($b !== null && $n !== null && $Un !== null) {
            if ($n < 1) {
                $error = "Nilai n harus minimal 1.";
            } else {
                $a = $Un - ($n - 1) * $b;
                $Sn = ($n / 2) * (2 * $a + ($n - 1) * $b);
                for ($i = 1; $i <= max($n, 5); $i++) $terms[] = $a + ($i - 1) * $b;

                $steps[] = "<strong>Identifikasi Parameter yang Diketahui:</strong><br>" .
                           "• Beda barisan (b) = <code>" . fmt($b) . "</code><br>" .
                           "• Letak suku (n) = <code>$n</code><br>" .
                           "• Nilai suku ke-$n (U_{$n}) = <code>" . fmt($Un) . "</code><br>" .
                           "• Yang dicari: Suku pertama (a).";
                $steps[] = "<strong>Rumus Dasar Suku ke-n:</strong><br>" .
                           "<code>U_n = a + (n − 1)b</code>";
                $steps[] = "<strong>Substitusi Nilai ke Dalam Rumus:</strong><br>" .
                           "<code>" . fmt($Un) . " = a + ($n − 1) × " . fmt($b) . "</code><br>" .
                           "<code>" . fmt($Un) . " = a + (" . ($n - 1) . ") × " . fmt($b) . "</code><br>" .
                           "<code>" . fmt($Un) . " = a + " . fmt(($n - 1) * $b) . "</code>";
                $steps[] = "<strong>Isolasi Variabel (a) / Pindah Ruas:</strong><br>" .
                           "Kurangkan kedua ruas dengan <code>" . fmt(($n - 1) * $b) . "</code>:<br>" .
                           "<code>a = " . fmt($Un) . " − " . fmt(($n - 1) * $b) . "</code><br>" .
                           "&rarr; <strong>Suku Pertama (a) = <code>" . fmt($a) . "</code></strong>";
                $steps[] = "<strong>Rumus Barisan Lengkap:</strong><br>" .
                           "<code>U_n = " . fmt($a) . " + (n − 1) × " . fmt($b) . "</code>";
                $steps[] = "<strong>Menghitung Jumlah $n Suku Pertama (S_{$n}):</strong><br>" .
                           "<code>S_{$n} = ($n/2) × (a + U_{$n}) = ($n/2) × (" . fmt($a) . " + " . fmt($Un) . ") = <strong>" . fmt($Sn) . "</strong></code>";

                $ar = compact('a', 'b', 'n', 'Un', 'Sn', 'terms', 'steps', 'target');
            }
        }
    } elseif ($target === 'b') {
        // Cari b: diketahui a, n, Un
        $a  = isset($_POST['ar_a']) && $_POST['ar_a'] !== '' ? floatval($_POST['ar_a']) : null;
        $n  = isset($_POST['ar_n']) && $_POST['ar_n'] !== '' ? intval($_POST['ar_n']) : null;
        $Un = isset($_POST['ar_Un']) && $_POST['ar_Un'] !== '' ? floatval($_POST['ar_Un']) : null;

        if ($a !== null && $n !== null && $Un !== null) {
            if ($n <= 1) {
                $error = "Banyak suku (n) harus lebih besar dari 1 untuk menentukan beda (b).";
            } else {
                $b = ($Un - $a) / ($n - 1);
                $Sn = ($n / 2) * (2 * $a + ($n - 1) * $b);
                for ($i = 1; $i <= max($n, 5); $i++) $terms[] = $a + ($i - 1) * $b;

                $steps[] = "<strong>Identifikasi Parameter yang Diketahui:</strong><br>" .
                           "• Suku pertama (a) = <code>" . fmt($a) . "</code><br>" .
                           "• Banyak suku (n) = <code>$n</code><br>" .
                           "• Nilai suku ke-$n (U_{$n}) = <code>" . fmt($Un) . "</code><br>" .
                           "• Yang dicari: Beda antar suku (b).";
                $steps[] = "<strong>Rumus Dasar Suku ke-n:</strong><br>" .
                           "<code>U_n = a + (n − 1)b</code>";
                $steps[] = "<strong>Substitusi Nilai ke Dalam Rumus:</strong><br>" .
                           "<code>" . fmt($Un) . " = " . fmt($a) . " + ($n − 1) · b</code><br>" .
                           "<code>" . fmt($Un) . " = " . fmt($a) . " + " . ($n - 1) . "b</code>";
                $steps[] = "<strong>Pindah Ruas Variabel Suku Pertama:</strong><br>" .
                           "Kurangkan kedua ruas dengan suku pertama <code>" . fmt($a) . "</code>:<br>" .
                           "<code>" . ($n - 1) . "b = " . fmt($Un) . " − " . fmt($a) . "</code><br>" .
                           "<code>" . ($n - 1) . "b = " . fmt($Un - $a) . "</code>";
                $steps[] = "<strong>Membagi untuk Menemukan Beda (b):</strong><br>" .
                           "Bagi kedua ruas dengan <code>" . ($n - 1) . "</code>:<br>" .
                           "<code>b = " . fmt($Un - $a) . " / " . ($n - 1) . "</code><br>" .
                           "&rarr; <strong>Beda (b) = <code>" . fmt($b) . "</code></strong>";
                $steps[] = "<strong>Rumus Barisan Lengkap:</strong><br>" .
                           "<code>U_n = " . fmt($a) . " + (n − 1) × " . fmt($b) . "</code>";
                $steps[] = "<strong>Menghitung Jumlah $n Suku Pertama (S_{$n}):</strong><br>" .
                           "<code>S_{$n} = ($n/2) × (a + U_{$n}) = ($n/2) × (" . fmt($a) . " + " . fmt($Un) . ") = <strong>" . fmt($Sn) . "</strong></code>";

                $ar = compact('a', 'b', 'n', 'Un', 'Sn', 'terms', 'steps', 'target');
            }
        }
    } elseif ($target === 'n') {
        // Cari n: diketahui a, b, Un
        $a  = isset($_POST['ar_a']) && $_POST['ar_a'] !== '' ? floatval($_POST['ar_a']) : null;
        $b  = isset($_POST['ar_b']) && $_POST['ar_b'] !== '' ? floatval($_POST['ar_b']) : null;
        $Un = isset($_POST['ar_Un']) && $_POST['ar_Un'] !== '' ? floatval($_POST['ar_Un']) : null;

        if ($a !== null && $b !== null && $Un !== null) {
            if ($b == 0) {
                if ($Un == $a) {
                    $error = "Karena beda b = 0, semua suku bernilai $a. Nilai n bisa bilangan berapa pun.";
                } else {
                    $error = "Beda b = 0, nilai suku tidak pernah mencapai " . fmt($Un);
                }
            } else {
                $calcN = ($Un - $a) / $b + 1;
                $roundedN = round($calcN);
                if ($calcN < 1) {
                    $error = "Nilai n yang didapat kurang dari 1 (n = " . fmt($calcN) . "). Suku tidak ditemukan.";
                } elseif (abs($calcN - $roundedN) > 1e-5) {
                    $error = "Nilai " . fmt($Un) . " bukan suku barisan aritmetika ini (didapat n = " . fmt($calcN) . " bukan bilangan bulat positif).";
                } else {
                    $n = (int)$roundedN;
                    $Sn = ($n / 2) * (2 * $a + ($n - 1) * $b);
                    for ($i = 1; $i <= max($n, 5); $i++) $terms[] = $a + ($i - 1) * $b;

                    $steps[] = "<strong>Identifikasi Parameter yang Diketahui:</strong><br>" .
                               "• Suku pertama (a) = <code>" . fmt($a) . "</code><br>" .
                               "• Beda barisan (b) = <code>" . fmt($b) . "</code><br>" .
                               "• Nilai suku target/terakhir (U_n) = <code>" . fmt($Un) . "</code><br>" .
                               "• Yang dicari: Banyak suku / urutan suku ke berapa (n).";
                    $steps[] = "<strong>Rumus Dasar Suku ke-n:</strong><br>" .
                               "<code>U_n = a + (n − 1)b</code>";
                    $steps[] = "<strong>Substitusi Nilai ke Dalam Rumus:</strong><br>" .
                               "<code>" . fmt($Un) . " = " . fmt($a) . " + (n − 1) × " . fmt($b) . "</code>";
                    $steps[] = "<strong>Pindah Ruas Suku Pertama (a):</strong><br>" .
                               "Kurangkan kedua ruas dengan <code>" . fmt($a) . "</code>:<br>" .
                               "<code>(n − 1) × " . fmt($b) . " = " . fmt($Un) . " − " . fmt($a) . "</code><br>" .
                               "<code>(n − 1) × " . fmt($b) . " = " . fmt($Un - $a) . "</code>";
                    $steps[] = "<strong>Bagi dengan Nilai Beda (b):</strong><br>" .
                               "<code>n − 1 = " . fmt($Un - $a) . " / " . fmt($b) . "</code><br>" .
                               "<code>n − 1 = " . fmt(($Un - $a) / $b) . "</code>";
                    $steps[] = "<strong>Tambahkan 1 untuk Memperoleh Nilai n:</strong><br>" .
                               "<code>n = " . fmt(($Un - $a) / $b) . " + 1</code><br>" .
                               "&rarr; <strong>Banyak Suku (n) = <code>$n</code></strong>";
                    $steps[] = "<strong>Menghitung Jumlah $n Suku Pertama (S_{$n}):</strong><br>" .
                               "<code>S_{$n} = ($n/2) × [2 × " . fmt($a) . " + ($n − 1) × " . fmt($b) . "] = <strong>" . fmt($Sn) . "</strong></code>";

                    $ar = compact('a', 'b', 'n', 'Un', 'Sn', 'terms', 'steps', 'target');
                }
            }
        }
    } elseif ($target === 'two_terms') {
        // Diketahui 2 suku sembarang (Up dan Uq): cari a, b, rumus Un, dan Sn
        $p       = isset($_POST['ar_p']) && $_POST['ar_p'] !== '' ? intval($_POST['ar_p']) : null;
        $Up      = isset($_POST['ar_Up']) && $_POST['ar_Up'] !== '' ? floatval($_POST['ar_Up']) : null;
        $q       = isset($_POST['ar_q']) && $_POST['ar_q'] !== '' ? intval($_POST['ar_q']) : null;
        $Uq      = isset($_POST['ar_Uq']) && $_POST['ar_Uq'] !== '' ? floatval($_POST['ar_Uq']) : null;
        $targetN = isset($_POST['ar_target_n']) && $_POST['ar_target_n'] !== '' ? intval($_POST['ar_target_n']) : null;

        if ($p !== null && $Up !== null && $q !== null && $Uq !== null) {
            if ($p < 1 || $q < 1) {
                $error = "Indeks suku (p dan q) harus minimal 1.";
            } elseif ($p === $q) {
                $error = "Indeks suku ke-p dan ke-q tidak boleh sama ($p = $q). Masukkan dua suku yang berbeda.";
            } else {
                if ($p > $q) {
                    $tmpP = $p; $tmpUp = $Up;
                    $p = $q; $Up = $Uq;
                    $q = $tmpP; $Uq = $tmpUp;
                }

                $deltaIdx = $q - $p;
                $deltaU   = $Uq - $Up;
                $b        = $deltaU / $deltaIdx;
                $a        = $Up - ($p - 1) * $b;

                $n = ($targetN && $targetN >= 1) ? $targetN : max($q, 5);
                $Un = $a + ($n - 1) * $b;
                $Sn = ($n / 2) * (2 * $a + ($n - 1) * $b);

                $limitTerms = min(max($n, $q), 100);
                for ($i = 1; $i <= $limitTerms; $i++) {
                    $terms[] = $a + ($i - 1) * $b;
                }

                $steps[] = "<strong>Identifikasi Masalah (Tanpa Suku Pertama dan Beda):</strong><br>" .
                           "• Diketahui suku ke-$p: <code>U_{$p} = " . fmt($Up) . "</code><br>" .
                           "• Diketahui suku ke-$q: <code>U_{$q} = " . fmt($Uq) . "</code><br>" .
                           "• Suku pertama (<code>a</code>) dan beda (<code>b</code>) <strong>tidak diketahui</strong> dan harus dicari melalui sistem persamaan.";
                $steps[] = "<strong>Menyusun Sistem Persamaan Linear Dua Variabel (SPLDV):</strong><br>" .
                           "Rumus umum suku ke-n: <code>U_n = a + (n − 1)b</code><br>" .
                           "• Persamaan (1): <code>U_{$p} = a + ($p − 1)b = " . fmt($Up) . "</code> &rarr; <code>a + " . ($p - 1) . "b = " . fmt($Up) . "</code><br>" .
                           "• Persamaan (2): <code>U_{$q} = a + ($q − 1)b = " . fmt($Uq) . "</code> &rarr; <code>a + " . ($q - 1) . "b = " . fmt($Uq) . "</code>";
                $steps[] = "<strong>Metode Eliminasi (Menemukan Nilai Beda b):</strong><br>" .
                           "Kurangkan Persamaan (2) dengan Persamaan (1) untuk mengeliminasi variabel <code>a</code>:<br>" .
                           "<code>[a + " . ($q - 1) . "b] − [a + " . ($p - 1) . "b] = " . fmt($Uq) . " − " . fmt($Up) . "</code><br>" .
                           "<code>(" . ($q - 1) . " − " . ($p - 1) . ")b = " . fmt($deltaU) . "</code><br>" .
                           "<code>{$deltaIdx}b = " . fmt($deltaU) . "</code><br>" .
                           "<code>b = " . fmt($deltaU) . " / {$deltaIdx} = " . fmt($b) . "</code> &rarr; <strong>Beda (b) = <code>" . fmt($b) . "</code></strong>";
                $steps[] = "<strong>Metode Substitusi (Menemukan Suku Pertama a):</strong><br>" .
                           "Substitusikan nilai <code>b = " . fmt($b) . "</code> ke Persamaan (1):<br>" .
                           "<code>a + " . ($p - 1) . " × (" . fmt($b) . ") = " . fmt($Up) . "</code><br>" .
                           "<code>a + " . fmt(($p - 1) * $b) . " = " . fmt($Up) . "</code><br>" .
                           "<code>a = " . fmt($Up) . " − " . fmt(($p - 1) * $b) . " = " . fmt($a) . "</code> &rarr; <strong>Suku Pertama (a) = <code>" . fmt($a) . "</code></strong>";
                $steps[] = "<strong>Menyusun Rumus Umum Barisan Lengkap (Uₙ):</strong><br>" .
                           "<code>U_n = a + (n − 1)b = " . fmt($a) . " + (n − 1) × " . fmt($b) . "</code><br>" .
                           "Penyederhanaan: <code>U_n = " . fmt($b) . "n + (" . fmt($a - $b) . ")</code>";
                $steps[] = "<strong>Menghitung Nilai Suku ke-$n (U_{$n}) & Jumlah Deret (S_{$n}):</strong><br>" .
                           "• Nilai suku ke-$n: <code>U_{$n} = " . fmt($a) . " + ($n − 1) × " . fmt($b) . " = <strong>" . fmt($Un) . "</strong></code><br>" .
                           "• Jumlah $n suku pertama: <code>S_{$n} = ($n/2) × [2 × " . fmt($a) . " + ($n − 1) × " . fmt($b) . "] = <strong>" . fmt($Sn) . "</strong></code>";

                $ar = compact('a', 'b', 'n', 'Un', 'Sn', 'terms', 'steps', 'target', 'p', 'Up', 'q', 'Uq');
            }
        }
    }

    if ($error) $ar = ['error' => $error, 'target' => $target];
}

// ---------- Hitung Geometri ----------
$ge = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === '2' && $tab === 'geometri') {
    $error = null;
    $steps = [];
    $terms = [];

    if ($target === 'Un') {
        // Lengkap: diketahui a, r, n -> cari Un dan Sn
        $a = isset($_POST['ge_a']) && $_POST['ge_a'] !== '' ? floatval($_POST['ge_a']) : null;
        $r = isset($_POST['ge_r']) && $_POST['ge_r'] !== '' ? floatval($_POST['ge_r']) : null;
        $n = isset($_POST['ge_n']) && $_POST['ge_n'] !== '' ? intval($_POST['ge_n']) : null;

        if ($a !== null && $r !== null && $n !== null) {
            if ($n < 1) {
                $error = "Jumlah suku (n) harus minimal 1.";
            } else {
                $Un = $a * pow($r, $n - 1);
                $Sn = ($r == 1) ? $a * $n : $a * (pow($r, $n) - 1) / ($r - 1);
                $Sinf = (abs($r) < 1 && $r != 0) ? $a / (1 - $r) : ($r == 0 ? $a : null);
                $konvergen = ($Sinf !== null);
                for ($i = 1; $i <= $n; $i++) $terms[] = $a * pow($r, $i - 1);

                $steps[] = "<strong>Identifikasi Parameter yang Diketahui:</strong><br>" .
                           "• Suku pertama (a) = <code>" . fmt($a) . "</code><br>" .
                           "• Rasio pengali (r) = <code>" . fmt($r) . "</code><br>" .
                           "• Banyak suku yang dihitung (n) = <code>$n</code>";
                $steps[] = "<strong>Menghitung Suku ke-$n (U_{$n}):</strong><br>" .
                           "Rumus: <code>U_n = a · r^{n−1}</code><br>" .
                           "Substitusi nilai: <code>U_{$n} = " . fmt($a) . " × (" . fmt($r) . ")^{" . ($n - 1) . "}</code><br>" .
                           "<code>= " . fmt($a) . " × (" . fmt($r) . ")^{" . ($n - 1) . "}</code><br>" .
                           "<code>= " . fmt($a) . " × " . fmt(pow($r, $n - 1)) . " = <strong>" . fmt($Un) . "</strong></code>";
                $steps[] = "<strong>Rumus Umum Barisan:</strong><br>" .
                           "<code>U_n = " . fmt($a) . " × (" . fmt($r) . ")^{n − 1}</code>";
                if ($r == 1) {
                    $steps[] = "<strong>Menghitung Jumlah $n Suku Pertama (S_{$n}):</strong><br>" .
                               "Karena r = 1: <code>S_{$n} = a × n = " . fmt($a) . " × $n = <strong>" . fmt($Sn) . "</strong></code>";
                } else {
                    $steps[] = "<strong>Menghitung Jumlah $n Suku Pertama (S_{$n}):</strong><br>" .
                               "Rumus deret: <code>S_n = a · (r^n − 1) / (r − 1)</code><br>" .
                               "Substitusi nilai: <code>S_{$n} = " . fmt($a) . " × [(" . fmt($r) . ")^{$n} − 1] / [" . fmt($r) . " − 1]</code><br>" .
                               "<code>= " . fmt($a) . " × [" . fmt(pow($r, $n) - 1) . "] / [" . fmt($r - 1) . "] = <strong>" . fmt($Sn) . "</strong></code>";
                }
                if ($konvergen) {
                    $steps[] = "<strong>Deret Geometri Tak Hingga (S_∞):</strong><br>" .
                               "Karena nilai mutlak rasio <code>|r| = " . fmt(abs($r)) . " &lt; 1</code>, maka deret ini <strong>konvergen</strong> (memiliki batas jumlah):<br>" .
                               "<code>S_∞ = a / (1 − r) = " . fmt($a) . " / (1 − " . fmt($r) . ") = " . fmt($a) . " / " . fmt(1 - $r) . " = <strong>" . fmt($Sinf) . "</strong></code>";
                }

                $ge = compact('a', 'r', 'n', 'Un', 'Sn', 'Sinf', 'konvergen', 'terms', 'steps', 'target');
            }
        }
    } elseif ($target === 'a') {
        // Cari a: diketahui r, n, Un
        $r  = isset($_POST['ge_r']) && $_POST['ge_r'] !== '' ? floatval($_POST['ge_r']) : null;
        $n  = isset($_POST['ge_n']) && $_POST['ge_n'] !== '' ? intval($_POST['ge_n']) : null;
        $Un = isset($_POST['ge_Un']) && $_POST['ge_Un'] !== '' ? floatval($_POST['ge_Un']) : null;

        if ($r !== null && $n !== null && $Un !== null) {
            if ($n < 1) {
                $error = "Nilai n harus minimal 1.";
            } elseif ($r == 0 && $n > 1) {
                $error = "Rasio r tidak boleh 0 untuk n > 1.";
            } else {
                $powVal = pow($r, $n - 1);
                if ($powVal == 0) {
                    $error = "Pangkat r^(n-1) bernilai 0, tidak dapat dibagi.";
                } else {
                    $a = $Un / $powVal;
                    $Sn = ($r == 1) ? $a * $n : $a * (pow($r, $n) - 1) / ($r - 1);
                    $Sinf = (abs($r) < 1 && $r != 0) ? $a / (1 - $r) : ($r == 0 ? $a : null);
                    $konvergen = ($Sinf !== null);
                    for ($i = 1; $i <= max($n, 5); $i++) $terms[] = $a * pow($r, $i - 1);

                    $steps[] = "<strong>Identifikasi Parameter yang Diketahui:</strong><br>" .
                               "• Rasio pengali (r) = <code>" . fmt($r) . "</code><br>" .
                               "• Suku ke-$n (n) = <code>$n</code><br>" .
                               "• Nilai suku ke-$n (U_{$n}) = <code>" . fmt($Un) . "</code><br>" .
                               "• Yang dicari: Suku pertama (a).";
                    $steps[] = "<strong>Rumus Dasar Suku ke-n Geometri:</strong><br>" .
                               "<code>U_n = a · r^{n − 1}</code>";
                    $steps[] = "<strong>Substitusi Nilai ke Dalam Rumus:</strong><br>" .
                               "<code>" . fmt($Un) . " = a × (" . fmt($r) . ")^{" . ($n - 1) . "}</code><br>" .
                               "<code>" . fmt($Un) . " = a × " . fmt($powVal) . "</code>";
                    $steps[] = "<strong>Isolasi Variabel (a) / Pembagian:</strong><br>" .
                               "Bagi kedua ruas dengan <code>" . fmt($powVal) . "</code>:<br>" .
                               "<code>a = " . fmt($Un) . " / " . fmt($powVal) . "</code><br>" .
                               "&rarr; <strong>Suku Pertama (a) = <code>" . fmt($a) . "</code></strong>";
                    $steps[] = "<strong>Rumus Barisan Lengkap:</strong><br>" .
                               "<code>U_n = " . fmt($a) . " × (" . fmt($r) . ")^{n − 1}</code>";
                    $steps[] = "<strong>Jumlah $n Suku Pertama (S_{$n}):</strong><br>" .
                               "<code>S_{$n} = <strong>" . fmt($Sn) . "</strong></code>";

                    $ge = compact('a', 'r', 'n', 'Un', 'Sn', 'Sinf', 'konvergen', 'terms', 'steps', 'target');
                }
            }
        }
    } elseif ($target === 'b' || $target === 'r') {
        // Cari r: diketahui a, n, Un
        $a  = isset($_POST['ge_a']) && $_POST['ge_a'] !== '' ? floatval($_POST['ge_a']) : null;
        $n  = isset($_POST['ge_n']) && $_POST['ge_n'] !== '' ? intval($_POST['ge_n']) : null;
        $Un = isset($_POST['ge_Un']) && $_POST['ge_Un'] !== '' ? floatval($_POST['ge_Un']) : null;

        if ($a !== null && $n !== null && $Un !== null) {
            if ($n <= 1) {
                $error = "Banyak suku (n) harus lebih besar dari 1 untuk menentukan rasio (r).";
            } elseif ($a == 0) {
                $error = "Nilai suku pertama (a) tidak boleh 0 pada barisan geometri.";
            } else {
                $ratio = $Un / $a;
                $k = $n - 1;
                if ($ratio < 0 && $k % 2 === 0) {
                    $error = "Tidak ada rasio real karena perbandingan U_n/a bernilai negatif dengan pangkat genap (akar pangkat $k dari nilai negatif).";
                } else {
                    $r = ($ratio < 0) ? -pow(abs($ratio), 1 / $k) : pow($ratio, 1 / $k);
                    $Sn = ($r == 1) ? $a * $n : $a * (pow($r, $n) - 1) / ($r - 1);
                    $Sinf = (abs($r) < 1 && $r != 0) ? $a / (1 - $r) : ($r == 0 ? $a : null);
                    $konvergen = ($Sinf !== null);
                    for ($i = 1; $i <= max($n, 5); $i++) $terms[] = $a * pow($r, $i - 1);

                    $steps[] = "<strong>Identifikasi Parameter yang Diketahui:</strong><br>" .
                               "• Suku pertama (a) = <code>" . fmt($a) . "</code><br>" .
                               "• Banyak suku (n) = <code>$n</code><br>" .
                               "• Nilai suku ke-$n (U_{$n}) = <code>" . fmt($Un) . "</code><br>" .
                               "• Yang dicari: Rasio pengali (r).";
                    $steps[] = "<strong>Rumus Dasar Suku ke-n Geometri:</strong><br>" .
                               "<code>U_n = a · r^{n − 1}</code>";
                    $steps[] = "<strong>Substitusi Nilai ke Dalam Rumus:</strong><br>" .
                               "<code>" . fmt($Un) . " = " . fmt($a) . " × r^{" . ($n - 1) . "}</code><br>" .
                               "<code>" . fmt($Un) . " = " . fmt($a) . " × r^{$k}</code>";
                    $steps[] = "<strong>Pindah Ruas (Membagi dengan Suku Pertama a):</strong><br>" .
                               "<code>r^{$k} = " . fmt($Un) . " / " . fmt($a) . " = " . fmt($ratio) . "</code>";
                    $steps[] = "<strong>Menarik Akar Pangkat $k:</strong><br>" .
                               "<code>r = (" . fmt($ratio) . ")^{1/{$k}} = " . fmt($r) . "</code><br>" .
                               "&rarr; <strong>Rasio (r) = <code>" . fmt($r) . "</code></strong>";
                    $steps[] = "<strong>Rumus Barisan Lengkap:</strong><br>" .
                               "<code>U_n = " . fmt($a) . " × (" . fmt($r) . ")^{n − 1}</code>";
                    $steps[] = "<strong>Jumlah $n Suku Pertama (S_{$n}):</strong><br>" .
                               "<code>S_{$n} = <strong>" . fmt($Sn) . "</strong></code>";

                    $ge = compact('a', 'r', 'n', 'Un', 'Sn', 'Sinf', 'konvergen', 'terms', 'steps', 'target');
                }
            }
        }
    } elseif ($target === 'n') {
        // Cari n: diketahui a, r, Un
        $a  = isset($_POST['ge_a']) && $_POST['ge_a'] !== '' ? floatval($_POST['ge_a']) : null;
        $r  = isset($_POST['ge_r']) && $_POST['ge_r'] !== '' ? floatval($_POST['ge_r']) : null;
        $Un = isset($_POST['ge_Un']) && $_POST['ge_Un'] !== '' ? floatval($_POST['ge_Un']) : null;

        if ($a !== null && $r !== null && $Un !== null) {
            if ($a == 0 || $r == 0 || $r == 1) {
                $error = "Nilai a dan r tidak boleh 0, dan r tidak boleh 1 untuk mencari n dengan barisan geometri.";
            } else {
                $ratio = $Un / $a;
                if ($ratio <= 0) {
                    $error = "Nilai U_n / a harus bernilai positif agar dapat dihitung dengan logaritma.";
                } else {
                    $calcN = (log($ratio) / log($r)) + 1;
                    $roundedN = round($calcN);
                    if ($calcN < 1) {
                        $error = "Nilai n yang didapat kurang dari 1. Suku tersebut tidak ada.";
                    } elseif (abs($calcN - $roundedN) > 1e-4) {
                        $error = "Nilai " . fmt($Un) . " bukan suku barisan geometri ini (didapat n = " . fmt($calcN) . " bukan bilangan bulat positif).";
                    } else {
                        $n = (int)$roundedN;
                        $Sn = ($r == 1) ? $a * $n : $a * (pow($r, $n) - 1) / ($r - 1);
                        $Sinf = (abs($r) < 1 && $r != 0) ? $a / (1 - $r) : null;
                        $konvergen = ($Sinf !== null);
                        for ($i = 1; $i <= max($n, 5); $i++) $terms[] = $a * pow($r, $i - 1);

                        $steps[] = "<strong>Identifikasi Parameter yang Diketahui:</strong><br>" .
                                   "• Suku pertama (a) = <code>" . fmt($a) . "</code><br>" .
                                   "• Rasio barisan (r) = <code>" . fmt($r) . "</code><br>" .
                                   "• Nilai suku target/terakhir (U_n) = <code>" . fmt($Un) . "</code><br>" .
                                   "• Yang dicari: Banyak suku (n).";
                        $steps[] = "<strong>Rumus Dasar Suku ke-n Geometri:</strong><br>" .
                                   "<code>U_n = a · r^{n − 1}</code>";
                        $steps[] = "<strong>Substitusi Nilai ke Dalam Rumus:</strong><br>" .
                                   "<code>" . fmt($Un) . " = " . fmt($a) . " × (" . fmt($r) . ")^{n − 1}</code>";
                        $steps[] = "<strong>Bagi Kedua Ruas dengan Suku Pertama (a):</strong><br>" .
                                   "<code>(" . fmt($r) . ")^{n − 1} = " . fmt($Un) . " / " . fmt($a) . " = " . fmt($ratio) . "</code>";
                        $steps[] = "<strong>Terapkan Logaritma Natural (ln) pada Kedua Ruas:</strong><br>" .
                                   "<code>ln((" . fmt($r) . ")^{n − 1}) = ln(" . fmt($ratio) . ")</code><br>" .
                                   "<code>(n − 1) · ln(" . fmt($r) . ") = ln(" . fmt($ratio) . ")</code>";
                        $steps[] = "<strong>Selesaikan Nilai n:</strong><br>" .
                                   "<code>n − 1 = ln(" . fmt($ratio) . ") / ln(" . fmt($r) . ") = " . fmt($calcN - 1) . "</code><br>" .
                                   "<code>n = " . fmt($calcN - 1) . " + 1</code><br>" .
                                   "&rarr; <strong>Banyak Suku (n) = <code>$n</code></strong>";
                        $steps[] = "<strong>Jumlah $n Suku Pertama (S_{$n}):</strong><br>" .
                                   "<code>S_{$n} = <strong>" . fmt($Sn) . "</strong></code>";

                        $ge = compact('a', 'r', 'n', 'Un', 'Sn', 'Sinf', 'konvergen', 'terms', 'steps', 'target');
                    }
                }
            }
        }
    } elseif ($target === 'two_terms') {
        // Diketahui 2 suku sembarang (Up dan Uq): cari a, r, rumus Un, dan Sn
        $p       = isset($_POST['ge_p']) && $_POST['ge_p'] !== '' ? intval($_POST['ge_p']) : null;
        $Up      = isset($_POST['ge_Up']) && $_POST['ge_Up'] !== '' ? floatval($_POST['ge_Up']) : null;
        $q       = isset($_POST['ge_q']) && $_POST['ge_q'] !== '' ? intval($_POST['ge_q']) : null;
        $Uq      = isset($_POST['ge_Uq']) && $_POST['ge_Uq'] !== '' ? floatval($_POST['ge_Uq']) : null;
        $targetN = isset($_POST['ge_target_n']) && $_POST['ge_target_n'] !== '' ? intval($_POST['ge_target_n']) : null;

        if ($p !== null && $Up !== null && $q !== null && $Uq !== null) {
            if ($p < 1 || $q < 1) {
                $error = "Indeks suku (p dan q) harus minimal 1.";
            } elseif ($p === $q) {
                $error = "Indeks suku ke-p dan ke-q tidak boleh sama ($p = $q). Masukkan dua suku yang berbeda.";
            } elseif ($Up == 0 || $Uq == 0) {
                $error = "Nilai suku pada barisan geometri tidak boleh 0.";
            } else {
                if ($p > $q) {
                    $tmpP = $p; $tmpUp = $Up;
                    $p = $q; $Up = $Uq;
                    $q = $tmpP; $Uq = $tmpUp;
                }

                $deltaIdx = $q - $p;
                $ratioVal = $Uq / $Up;

                if ($ratioVal < 0 && $deltaIdx % 2 === 0) {
                    $error = "Tidak ada rasio real karena perbandingan U_{$q}/U_{$p} = " . fmt($ratioVal) . " bernilai negatif dengan selisih pangkat genap ($deltaIdx).";
                } else {
                    $r = ($ratioVal < 0) ? -pow(abs($ratioVal), 1 / $deltaIdx) : pow($ratioVal, 1 / $deltaIdx);
                    $powP = pow($r, $p - 1);

                    if ($powP == 0) {
                        $error = "Nilai r^{" . ($p - 1) . "} bernilai 0, suku pertama tidak dapat dihitung.";
                    } else {
                        $a = $Up / $powP;
                        $n = ($targetN && $targetN >= 1) ? $targetN : max($q, 5);
                        $Un = $a * pow($r, $n - 1);
                        $Sn = ($r == 1) ? $a * $n : $a * (pow($r, $n) - 1) / ($r - 1);
                        $Sinf = (abs($r) < 1 && $r != 0) ? $a / (1 - $r) : null;
                        $konvergen = ($Sinf !== null);

                        $limitTerms = min(max($n, $q), 50);
                        for ($i = 1; $i <= $limitTerms; $i++) {
                            $terms[] = $a * pow($r, $i - 1);
                        }

                        $steps[] = "<strong>Identifikasi Masalah (Tanpa Suku Pertama dan Rasio):</strong><br>" .
                                   "• Diketahui suku ke-$p: <code>U_{$p} = " . fmt($Up) . "</code><br>" .
                                   "• Diketahui suku ke-$q: <code>U_{$q} = " . fmt($Uq) . "</code><br>" .
                                   "• Suku pertama (<code>a</code>) dan rasio (<code>r</code>) <strong>tidak diketahui</strong> dan harus dicari melalui perbandingan persamaan.";
                        $steps[] = "<strong>Menyusun Sistem Persamaan Geometri:</strong><br>" .
                                   "Rumus dasar: <code>U_n = a · r^{n−1}</code><br>" .
                                   "• Dari suku ke-$p: <code>U_{$p} = a · r^{" . ($p - 1) . "} = " . fmt($Up) . "</code> (Persamaan 1)<br>" .
                                   "• Dari suku ke-$q: <code>U_{$q} = a · r^{" . ($q - 1) . "} = " . fmt($Uq) . "</code> (Persamaan 2)";
                        $steps[] = "<strong>Metode Pembagian Persamaan (Menemukan Rasio r):</strong><br>" .
                                   "Bagi Persamaan (2) dengan Persamaan (1) untuk mengeliminasi suku <code>a</code>:<br>" .
                                   "<code>(a · r^{" . ($q - 1) . "}) / (a · r^{" . ($p - 1) . "}) = " . fmt($Uq) . " / " . fmt($Up) . "</code><br>" .
                                   "<code>r^{" . ($q - 1) . " − " . ($p - 1) . "} = " . fmt($ratioVal) . "</code><br>" .
                                   "<code>r^{$deltaIdx} = " . fmt($ratioVal) . "</code><br>" .
                                   "Tarik akar pangkat {$deltaIdx}: <code>r = (" . fmt($ratioVal) . ")^{1/{$deltaIdx}} = " . fmt($r) . "</code><br>" .
                                   "&rarr; <strong>Rasio (r) = <code>" . fmt($r) . "</code></strong>";
                        $steps[] = "<strong>Metode Substitusi (Menemukan Suku Pertama a):</strong><br>" .
                                   "Substitusikan nilai <code>r = " . fmt($r) . "</code> ke Persamaan (1):<br>" .
                                   "<code>a · (" . fmt($r) . ")^{" . ($p - 1) . "} = " . fmt($Up) . "</code><br>" .
                                   "<code>a · " . fmt($powP) . " = " . fmt($Up) . "</code><br>" .
                                   "<code>a = " . fmt($Up) . " / " . fmt($powP) . " = " . fmt($a) . "</code> &rarr; <strong>Suku Pertama (a) = <code>" . fmt($a) . "</code></strong>";
                        $steps[] = "<strong>Menyusun Rumus Umum Barisan Lengkap (Uₙ):</strong><br>" .
                                   "<code>U_n = " . fmt($a) . " × (" . fmt($r) . ")^{n − 1}</code>";
                        $steps[] = "<strong>Menghitung Nilai Suku ke-$n (U_{$n}) & Jumlah Deret (S_{$n}):</strong><br>" .
                                   "• Nilai suku ke-$n: <code>U_{$n} = " . fmt($a) . " × (" . fmt($r) . ")^{" . ($n - 1) . "} = <strong>" . fmt($Un) . "</strong></code><br>" .
                                   "• Jumlah $n suku: <code>S_{$n} = <strong>" . fmt($Sn) . "</strong></code>";
                        if ($konvergen) {
                            $steps[] = "<strong>Deret Geometri Tak Hingga (S_∞):</strong><br>" .
                                       "Karena nilai mutlak rasio <code>|r| = " . fmt(abs($r)) . " &lt; 1</code>, maka deret ini konvergen:<br>" .
                                       "<code>S_∞ = a / (1 − r) = " . fmt($a) . " / (1 − " . fmt($r) . ") = <strong>" . fmt($Sinf) . "</strong></code>";
                        }

                        $ge = compact('a', 'r', 'n', 'Un', 'Sn', 'Sinf', 'konvergen', 'terms', 'steps', 'target', 'p', 'Up', 'q', 'Uq');
                    }
                }
            }
        }
    }

    if ($error) $ge = ['error' => $error, 'target' => $target];
}

// ==========================================
//  MODE 3 — Tanpa Suku Pertama (a) & Beda/Rasio (b/r)
// ==========================================
$m3_ar = null;
$m3_ge = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === '3') {
    $error = null;
    $steps = [];
    $terms = [];

    if ($tab === 'aritmetika') {
        $p       = isset($_POST['m3_ar_p']) && $_POST['m3_ar_p'] !== '' ? intval($_POST['m3_ar_p']) : null;
        $Up      = isset($_POST['m3_ar_Up']) && $_POST['m3_ar_Up'] !== '' ? floatval($_POST['m3_ar_Up']) : null;
        $q       = isset($_POST['m3_ar_q']) && $_POST['m3_ar_q'] !== '' ? intval($_POST['m3_ar_q']) : null;
        $Uq      = isset($_POST['m3_ar_Uq']) && $_POST['m3_ar_Uq'] !== '' ? floatval($_POST['m3_ar_Uq']) : null;
        $targetN = isset($_POST['m3_ar_n']) && $_POST['m3_ar_n'] !== '' ? intval($_POST['m3_ar_n']) : null;

        if ($p !== null && $Up !== null && $q !== null && $Uq !== null) {
            if ($p < 1 || $q < 1) {
                $error = "Indeks suku (p dan q) harus minimal 1.";
            } elseif ($p === $q) {
                $error = "Indeks suku ke-p dan ke-q tidak boleh sama ($p = $q). Masukkan dua suku yang berbeda.";
            } else {
                if ($p > $q) {
                    $tmpP = $p; $tmpUp = $Up;
                    $p = $q; $Up = $Uq;
                    $q = $tmpP; $Uq = $tmpUp;
                }

                $deltaIdx = $q - $p;
                $deltaU   = $Uq - $Up;
                $b        = $deltaU / $deltaIdx;
                $a        = $Up - ($p - 1) * $b;

                $n = ($targetN && $targetN >= 1) ? $targetN : max($q, 5);
                $Un = $a + ($n - 1) * $b;
                $Sn = ($n / 2) * (2 * $a + ($n - 1) * $b);

                $limitTerms = min(max($n, $q), 100);
                for ($i = 1; $i <= $limitTerms; $i++) {
                    $terms[] = $a + ($i - 1) * $b;
                }

                $steps[] = "<strong>Identifikasi Masalah (Tanpa Suku Pertama dan Beda):</strong><br>" .
                           "• Diketahui suku ke-$p: <code>U_{$p} = " . fmt($Up) . "</code><br>" .
                           "• Diketahui suku ke-$q: <code>U_{$q} = " . fmt($Uq) . "</code><br>" .
                           "• Nilai suku pertama (<code>a</code>) dan beda (<code>b</code>) <strong>tidak diketahui</strong>, sehingga perlu dihitung melalui SPLDV.";
                $steps[] = "<strong>Menyusun Sistem Persamaan Linear Dua Variabel (SPLDV):</strong><br>" .
                           "Berdasarkan rumus umum <code>U_n = a + (n − 1)b</code>:<br>" .
                           "• Persamaan (1): <code>U_{$p} = a + ($p − 1)b = " . fmt($Up) . "</code> &rarr; <code>a + " . ($p - 1) . "b = " . fmt($Up) . "</code><br>" .
                           "• Persamaan (2): <code>U_{$q} = a + ($q − 1)b = " . fmt($Uq) . "</code> &rarr; <code>a + " . ($q - 1) . "b = " . fmt($Uq) . "</code>";
                $steps[] = "<strong>Metode Eliminasi (Mencari Nilai Beda b):</strong><br>" .
                           "Kurangkan Persamaan (2) dengan Persamaan (1) untuk mengeliminasi variabel <code>a</code>:<br>" .
                           "<code>(a + " . ($q - 1) . "b) − (a + " . ($p - 1) . "b) = " . fmt($Uq) . " − " . fmt($Up) . "</code><br>" .
                           "<code>[a − a] + [" . ($q - 1) . " − " . ($p - 1) . "]b = " . fmt($deltaU) . "</code><br>" .
                           "<code>{$deltaIdx}b = " . fmt($deltaU) . "</code><br>" .
                           "<code>b = " . fmt($deltaU) . " / {$deltaIdx} = " . fmt($b) . "</code><br>" .
                           "&rarr; <strong>Beda (b) = <code>" . fmt($b) . "</code></strong>";
                $steps[] = "<strong>Metode Substitusi (Mencari Suku Pertama a):</strong><br>" .
                           "Substitusikan nilai <code>b = " . fmt($b) . "</code> ke dalam Persamaan (1):<br>" .
                           "<code>a + " . ($p - 1) . " × (" . fmt($b) . ") = " . fmt($Up) . "</code><br>" .
                           "<code>a + " . fmt(($p - 1) * $b) . " = " . fmt($Up) . "</code><br>" .
                           "<code>a = " . fmt($Up) . " − " . fmt(($p - 1) * $b) . " = " . fmt($a) . "</code><br>" .
                           "&rarr; <strong>Suku Pertama (a) = <code>" . fmt($a) . "</code></strong>";
                $steps[] = "<strong>Menyusun Rumus Barisan Lengkap (Uₙ):</strong><br>" .
                           "<code>U_n = a + (n − 1)b = " . fmt($a) . " + (n − 1) × " . fmt($b) . "</code><br>" .
                           "Penyederhanaan: <code>U_n = " . fmt($b) . "n + (" . fmt($a - $b) . ")</code>";
                $steps[] = "<strong>Menghitung Nilai Suku ke-$n (U_{$n}) & Jumlah Deret (S_{$n}):</strong><br>" .
                           "• Nilai suku ke-$n: <code>U_{$n} = " . fmt($a) . " + ($n − 1) × " . fmt($b) . " = <strong>" . fmt($Un) . "</strong></code><br>" .
                           "• Jumlah $n suku pertama: <code>S_{$n} = ($n/2) × [2 × " . fmt($a) . " + ($n − 1) × " . fmt($b) . "] = <strong>" . fmt($Sn) . "</strong></code>";

                $m3_ar = compact('a', 'b', 'n', 'Un', 'Sn', 'terms', 'steps', 'p', 'Up', 'q', 'Uq');
            }
        }
        if ($error) $m3_ar = ['error' => $error];
    } elseif ($tab === 'geometri') {
        $p       = isset($_POST['m3_ge_p']) && $_POST['m3_ge_p'] !== '' ? intval($_POST['m3_ge_p']) : null;
        $Up      = isset($_POST['m3_ge_Up']) && $_POST['m3_ge_Up'] !== '' ? floatval($_POST['m3_ge_Up']) : null;
        $q       = isset($_POST['m3_ge_q']) && $_POST['m3_ge_q'] !== '' ? intval($_POST['m3_ge_q']) : null;
        $Uq      = isset($_POST['m3_ge_Uq']) && $_POST['m3_ge_Uq'] !== '' ? floatval($_POST['m3_ge_Uq']) : null;
        $targetN = isset($_POST['m3_ge_n']) && $_POST['m3_ge_n'] !== '' ? intval($_POST['m3_ge_n']) : null;

        if ($p !== null && $Up !== null && $q !== null && $Uq !== null) {
            if ($p < 1 || $q < 1) {
                $error = "Indeks suku (p dan q) harus minimal 1.";
            } elseif ($p === $q) {
                $error = "Indeks suku ke-p dan ke-q tidak boleh sama ($p = $q). Masukkan dua suku yang berbeda.";
            } elseif ($Up == 0 || $Uq == 0) {
                $error = "Nilai suku pada barisan geometri tidak boleh 0.";
            } else {
                if ($p > $q) {
                    $tmpP = $p; $tmpUp = $Up;
                    $p = $q; $Up = $Uq;
                    $q = $tmpP; $Uq = $tmpUp;
                }

                $deltaIdx = $q - $p;
                $ratioVal = $Uq / $Up;

                if ($ratioVal < 0 && $deltaIdx % 2 === 0) {
                    $error = "Tidak ada rasio real karena perbandingan U_{$q}/U_{$p} = " . fmt($ratioVal) . " bernilai negatif dengan selisih pangkat genap ($deltaIdx).";
                } else {
                    $r = ($ratioVal < 0) ? -pow(abs($ratioVal), 1 / $deltaIdx) : pow($ratioVal, 1 / $deltaIdx);
                    $powP = pow($r, $p - 1);

                    if ($powP == 0) {
                        $error = "Nilai r^{" . ($p - 1) . "} bernilai 0, suku pertama tidak dapat dihitung.";
                    } else {
                        $a = $Up / $powP;
                        $n = ($targetN && $targetN >= 1) ? $targetN : max($q, 5);
                        $Un = $a * pow($r, $n - 1);
                        $Sn = ($r == 1) ? $a * $n : $a * (pow($r, $n) - 1) / ($r - 1);
                        $Sinf = (abs($r) < 1 && $r != 0) ? $a / (1 - $r) : null;
                        $konvergen = ($Sinf !== null);

                        $limitTerms = min(max($n, $q), 50);
                        for ($i = 1; $i <= $limitTerms; $i++) {
                            $terms[] = $a * pow($r, $i - 1);
                        }

                        $steps[] = "<strong>Identifikasi Masalah (Tanpa Suku Pertama dan Rasio):</strong><br>" .
                                   "• Diketahui suku ke-$p: <code>U_{$p} = " . fmt($Up) . "</code><br>" .
                                   "• Diketahui suku ke-$q: <code>U_{$q} = " . fmt($Uq) . "</code><br>" .
                                   "• Suku pertama (<code>a</code>) dan rasio (<code>r</code>) <strong>tidak diketahui</strong> dan harus dicari melalui perbandingan persamaan.";
                        $steps[] = "<strong>Menyusun Sistem Persamaan Geometri:</strong><br>" .
                                   "Rumus dasar: <code>U_n = a · r^{n−1}</code><br>" .
                                   "• Dari suku ke-$p: <code>U_{$p} = a · r^{" . ($p - 1) . "} = " . fmt($Up) . "</code> (Persamaan 1)<br>" .
                                   "• Dari suku ke-$q: <code>U_{$q} = a · r^{" . ($q - 1) . "} = " . fmt($Uq) . "</code> (Persamaan 2)";
                        $steps[] = "<strong>Metode Pembagian Eliminasi (Menemukan Rasio r):</strong><br>" .
                                   "Bagi Persamaan (2) dengan Persamaan (1) untuk mengeliminasi suku <code>a</code>:<br>" .
                                   "<code>(a · r^{" . ($q - 1) . "}) / (a · r^{" . ($p - 1) . "}) = " . fmt($Uq) . " / " . fmt($Up) . "</code><br>" .
                                   "<code>r^{" . ($q - 1) . " − " . ($p - 1) . "} = " . fmt($ratioVal) . "</code><br>" .
                                   "<code>r^{$deltaIdx} = " . fmt($ratioVal) . "</code><br>" .
                                   "Tarik akar pangkat {$deltaIdx}: <code>r = (" . fmt($ratioVal) . ")^{1/{$deltaIdx}} = " . fmt($r) . "</code><br>" .
                                   "&rarr; <strong>Rasio (r) = <code>" . fmt($r) . "</code></strong>";
                        $steps[] = "<strong>Metode Substitusi (Menemukan Suku Pertama a):</strong><br>" .
                                   "Substitusikan nilai <code>r = " . fmt($r) . "</code> ke Persamaan (1):<br>" .
                                   "<code>a · (" . fmt($r) . ")^{" . ($p - 1) . "} = " . fmt($Up) . "</code><br>" .
                                   "<code>a · " . fmt($powP) . " = " . fmt($Up) . "</code><br>" .
                                   "<code>a = " . fmt($Up) . " / " . fmt($powP) . " = " . fmt($a) . "</code> &rarr; <strong>Suku Pertama (a) = <code>" . fmt($a) . "</code></strong>";
                        $steps[] = "<strong>Menyusun Rumus Umum Barisan Lengkap (Uₙ):</strong><br>" .
                                   "<code>U_n = " . fmt($a) . " × (" . fmt($r) . ")^{n − 1}</code>";
                        $steps[] = "<strong>Menghitung Nilai Suku ke-$n (U_{$n}) & Jumlah Deret (S_{$n}):</strong><br>" .
                                   "• Nilai suku ke-$n: <code>U_{$n} = " . fmt($a) . " × (" . fmt($r) . ")^{" . ($n - 1) . "} = <strong>" . fmt($Un) . "</strong></code><br>" .
                                   "• Jumlah $n suku: <code>S_{$n} = <strong>" . fmt($Sn) . "</strong></code>";
                        if ($konvergen) {
                            $steps[] = "<strong>Deret Geometri Tak Hingga (S_∞):</strong><br>" .
                                       "Karena nilai mutlak rasio <code>|r| = " . fmt(abs($r)) . " &lt; 1</code>, maka deret ini konvergen:<br>" .
                                       "<code>S_∞ = a / (1 − r) = " . fmt($a) . " / (1 − " . fmt($r) . ") = <strong>" . fmt($Sinf) . "</strong></code>";
                        }

                        $m3_ge = compact('a', 'r', 'n', 'Un', 'Sn', 'Sinf', 'konvergen', 'terms', 'steps', 'p', 'Up', 'q', 'Uq');
                    }
                }
            }
        }
        if ($error) $m3_ge = ['error' => $error];
    }
}

// ==========================================
//  MODE 4 — Suku Tengah & Sisipan Barisan
// ==========================================
$m4_ar = null;
$m4_ge = null;
$m4_type = $_POST['m4_type'] ?? ($_GET['m4_type'] ?? 'tengah');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === '4') {
    $error = null;
    $steps = [];
    $terms = [];

    if ($tab === 'aritmetika') {
        if ($m4_type === 'tengah') {
            $a  = isset($_POST['m4_ar_a']) && $_POST['m4_ar_a'] !== '' ? floatval($_POST['m4_ar_a']) : null;
            $Un = isset($_POST['m4_ar_Un']) && $_POST['m4_ar_Un'] !== '' ? floatval($_POST['m4_ar_Un']) : null;
            $n  = isset($_POST['m4_ar_n']) && $_POST['m4_ar_n'] !== '' ? intval($_POST['m4_ar_n']) : null;

            if ($a !== null && $Un !== null) {
                if ($n !== null && $n % 2 === 0) {
                    $error = "Suku tengah hanya ada pada barisan dengan banyak suku (n) ganjil. Nilai n = $n adalah genap.";
                } elseif ($n !== null && $n < 3) {
                    $error = "Banyak suku (n) untuk suku tengah minimal harus 3.";
                } else {
                    $Ut = ($a + $Un) / 2;
                    $t = ($n !== null) ? ($n + 1) / 2 : null;
                    $b = ($n !== null && $n > 1) ? ($Un - $a) / ($n - 1) : null;
                    $Sn = ($n !== null) ? $n * $Ut : null;

                    $limit = ($n !== null) ? min($n, 50) : 5;
                    if ($b !== null) {
                        for ($i = 1; $i <= $limit; $i++) $terms[] = $a + ($i - 1) * $b;
                    }

                    $steps[] = "<strong>Identifikasi Nilai Parameter:</strong><br>" .
                               "• Suku pertama (a): <code>" . fmt($a) . "</code><br>" .
                               "• Suku terakhir (U_n): <code>" . fmt($Un) . "</code>" .
                               ($n !== null ? "<br>• Banyak suku (n): <code>$n</code> (bilangan ganjil)" : "");
                    $steps[] = "<strong>Menghitung Nilai Suku Tengah (Uₜ):</strong><br>" .
                               "Rumus: <code>U_t = (a + U_n) / 2</code><br>" .
                               "Substitusi nilai: <code>U_t = (" . fmt($a) . " + " . fmt($Un) . ") / 2 = " . fmt($a + $Un) . " / 2 = <strong>" . fmt($Ut) . "</strong></code>";
                    if ($t !== null) {
                        $steps[] = "<strong>Menentukan Letak Suku Tengah (t):</strong><br>" .
                                   "Rumus indeks: <code>t = (n + 1) / 2 = ($n + 1) / 2 = <strong>$t</strong></code><br>" .
                                   "Artinya, suku tengah adalah suku ke-$t (<code>U_{$t} = " . fmt($Ut) . "</code>).";
                    }
                    if ($Sn !== null) {
                        $steps[] = "<strong>Menghitung Jumlah Deret (Sₙ):</strong><br>" .
                                   "Hubungan suku tengah dengan deret: <code>S_n = n × U_t = $n × " . fmt($Ut) . " = <strong>" . fmt($Sn) . "</strong></code>";
                    }

                    $m4_ar = compact('a', 'Un', 'n', 'Ut', 't', 'b', 'Sn', 'terms', 'steps', 'm4_type');
                }
            }
        } elseif ($m4_type === 'sisipan') {
            $x = isset($_POST['m4_ar_x']) && $_POST['m4_ar_x'] !== '' ? floatval($_POST['m4_ar_x']) : null;
            $y = isset($_POST['m4_ar_y']) && $_POST['m4_ar_y'] !== '' ? floatval($_POST['m4_ar_y']) : null;
            $k = isset($_POST['m4_ar_k']) && $_POST['m4_ar_k'] !== '' ? intval($_POST['m4_ar_k']) : null;

            if ($x !== null && $y !== null && $k !== null) {
                if ($k < 1) {
                    $error = "Banyak bilangan yang disisipkan (k) harus minimal 1.";
                } else {
                    $bBaru = ($y - $x) / ($k + 1);
                    $nBaru = $k + 2;
                    $SnBaru = ($nBaru / 2) * ($x + $y);
                    for ($i = 0; $i < $nBaru; $i++) {
                        $terms[] = $x + $i * $bBaru;
                    }

                    $steps[] = "<strong>Identifikasi Masalah Sisipan Aritmetika:</strong><br>" .
                               "• Bilangan awal (x): <code>" . fmt($x) . "</code><br>" .
                               "• Bilangan akhir (y): <code>" . fmt($y) . "</code><br>" .
                               "• Banyak bilangan yang disisipkan (k): <code>$k</code> bilangan";
                    $steps[] = "<strong>Menghitung Beda Baru (b'):</strong><br>" .
                               "Rumus beda baru: <code>b' = (y − x) / (k + 1)</code><br>" .
                               "Substitusi nilai: <code>b' = (" . fmt($y) . " − " . fmt($x) . ") / ($k + 1) = " . fmt($y - $x) . " / " . ($k + 1) . " = <strong>" . fmt($bBaru) . "</strong></code>";
                    $steps[] = "<strong>Banyak Suku Baru (n'):</strong><br>" .
                               "<code>n' = k + 2 = $k + 2 = <strong>$nBaru</strong></code> suku.";
                    $steps[] = "<strong>Jumlah Deret Setelah Sisipan (S'_{n'}):</strong><br>" .
                               "<code>S'_{$nBaru} = (n'/2) × (x + y) = ($nBaru / 2) × (" . fmt($x) . " + " . fmt($y) . ") = <strong>" . fmt($SnBaru) . "</strong></code>";

                    $m4_ar = compact('x', 'y', 'k', 'bBaru', 'nBaru', 'SnBaru', 'terms', 'steps', 'm4_type');
                }
            }
        }
        if ($error) $m4_ar = ['error' => $error, 'm4_type' => $m4_type];
    } elseif ($tab === 'geometri') {
        if ($m4_type === 'tengah') {
            $a  = isset($_POST['m4_ge_a']) && $_POST['m4_ge_a'] !== '' ? floatval($_POST['m4_ge_a']) : null;
            $Un = isset($_POST['m4_ge_Un']) && $_POST['m4_ge_Un'] !== '' ? floatval($_POST['m4_ge_Un']) : null;
            $n  = isset($_POST['m4_ge_n']) && $_POST['m4_ge_n'] !== '' ? intval($_POST['m4_ge_n']) : null;

            if ($a !== null && $Un !== null) {
                if ($n !== null && $n % 2 === 0) {
                    $error = "Suku tengah hanya ada pada barisan dengan banyak suku (n) ganjil. Nilai n = $n adalah genap.";
                } elseif ($a * $Un < 0) {
                    $error = "Hasil kali a × U_n bernilai negatif (" . fmt($a * $Un) . "), suku tengah real tidak terdefinisi.";
                } else {
                    $Ut = sqrt($a * $Un);
                    $t = ($n !== null) ? ($n + 1) / 2 : null;
                    $r = ($n !== null && $n > 1 && $a != 0) ? pow($Un / $a, 1 / ($n - 1)) : null;

                    $limit = ($n !== null) ? min($n, 50) : 5;
                    if ($r !== null) {
                        for ($i = 1; $i <= $limit; $i++) $terms[] = $a * pow($r, $i - 1);
                    }

                    $steps[] = "<strong>Identifikasi Nilai Parameter Geometri:</strong><br>" .
                               "• Suku pertama (a): <code>" . fmt($a) . "</code><br>" .
                               "• Suku terakhir (U_n): <code>" . fmt($Un) . "</code>" .
                               ($n !== null ? "<br>• Banyak suku (n): <code>$n</code> (bilangan ganjil)" : "");
                    $steps[] = "<strong>Menghitung Nilai Suku Tengah Geometri (Uₜ):</strong><br>" .
                               "Rumus: <code>U_t = √(a · U_n)</code><br>" .
                               "Substitusi nilai: <code>U_t = √(" . fmt($a) . " × " . fmt($Un) . ") = √(" . fmt($a * $Un) . ") = <strong>" . fmt($Ut) . "</strong></code>";
                    if ($t !== null) {
                        $steps[] = "<strong>Menentukan Letak Suku Tengah (t):</strong><br>" .
                                   "<code>t = (n + 1) / 2 = ($n + 1) / 2 = <strong>$t</strong></code> (Suku ke-$t).";
                    }

                    $m4_ge = compact('a', 'Un', 'n', 'Ut', 't', 'r', 'terms', 'steps', 'm4_type');
                }
            }
        } elseif ($m4_type === 'sisipan') {
            $x = isset($_POST['m4_ge_x']) && $_POST['m4_ge_x'] !== '' ? floatval($_POST['m4_ge_x']) : null;
            $y = isset($_POST['m4_ge_y']) && $_POST['m4_ge_y'] !== '' ? floatval($_POST['m4_ge_y']) : null;
            $k = isset($_POST['m4_ge_k']) && $_POST['m4_ge_k'] !== '' ? intval($_POST['m4_ge_k']) : null;

            if ($x !== null && $y !== null && $k !== null) {
                if ($k < 1) {
                    $error = "Banyak bilangan yang disisipkan (k) harus minimal 1.";
                } elseif ($x == 0) {
                    $error = "Suku awal (x) tidak boleh 0 pada barisan geometri.";
                } else {
                    $ratio = $y / $x;
                    $pangkat = $k + 1;
                    if ($ratio < 0 && $pangkat % 2 === 0) {
                        $error = "Tidak ada rasio real karena perbandingan y/x bernilai negatif dengan pangkat akar genap.";
                    } else {
                        $rBaru = ($ratio < 0) ? -pow(abs($ratio), 1 / $pangkat) : pow($ratio, 1 / $pangkat);
                        $nBaru = $k + 2;
                        $SnBaru = ($rBaru == 1) ? $x * $nBaru : $x * (pow($rBaru, $nBaru) - 1) / ($rBaru - 1);
                        for ($i = 0; $i < $nBaru; $i++) {
                            $terms[] = $x * pow($rBaru, $i);
                        }

                        $steps[] = "<strong>Identifikasi Masalah Sisipan Geometri:</strong><br>" .
                                   "• Bilangan awal (x): <code>" . fmt($x) . "</code><br>" .
                                   "• Bilangan akhir (y): <code>" . fmt($y) . "</code><br>" .
                                   "• Disisipkan (k): <code>$k</code> bilangan";
                        $steps[] = "<strong>Menghitung Rasio Baru (r'):</strong><br>" .
                                   "Rumus: <code>r' = ^{(k+1)}√(y / x)</code><br>" .
                                   "Substitusi nilai: <code>r' = ^{" . ($k + 1) . "}√(" . fmt($y) . " / " . fmt($x) . ") = ^{" . ($k + 1) . "}√(" . fmt($ratio) . ") = <strong>" . fmt($rBaru) . "</strong></code>";
                        $steps[] = "<strong>Banyak Suku Baru (n'):</strong><br>" .
                                   "<code>n' = k + 2 = $k + 2 = <strong>$nBaru</strong></code> suku.";
                        $steps[] = "<strong>Jumlah Deret Baru (S'_{n'}):</strong><br>" .
                                   "<code>S'_{$nBaru} = <strong>" . fmt($SnBaru) . "</strong></code>";

                        $m4_ge = compact('x', 'y', 'k', 'rBaru', 'nBaru', 'SnBaru', 'terms', 'steps', 'm4_type');
                    }
                }
            }
        }
        if ($error) $m4_ge = ['error' => $error, 'm4_type' => $m4_type];
    }
}

// ==========================================
//  MODE 5 — Deret ke Suku (Sn -> Un)
// ==========================================
$m5 = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === '5') {
    $A = isset($_POST['m5_A']) && $_POST['m5_A'] !== '' ? floatval($_POST['m5_A']) : null;
    $B = isset($_POST['m5_B']) && $_POST['m5_B'] !== '' ? floatval($_POST['m5_B']) : null;
    $k = isset($_POST['m5_k']) && $_POST['m5_k'] !== '' ? intval($_POST['m5_k']) : 10;

    if ($A !== null && $B !== null) {
        $a = $A + $B; // S1
        $b = 2 * $A;  // Beda
        $c = $B - $A; // Konstanta Un: 2An + (B - A)
        $k = max(1, $k);
        $Uk = 2 * $A * $k + $c;
        $Sk = $A * pow($k, 2) + $B * $k;

        $terms = [];
        $limit = min($k, 50);
        for ($i = 1; $i <= $limit; $i++) {
            $terms[] = 2 * $A * $i + $c;
        }

        $steps = [];
        $steps[] = "<strong>Identifikasi Fungsi Jumlah Deret (Sₙ):</strong><br>" .
                   "Diketahui rumus deret: <code>S_n = " . fmt($A) . "n² " . ($B >= 0 ? '+ ' . fmt($B) . 'n' : '− ' . fmt(abs($B)) . 'n') . "</code>";
        $steps[] = "<strong>Menentukan Suku Pertama (a = S₁):</strong><br>" .
                   "Substitusikan n = 1 ke rumus deret:<br>" .
                   "<code>a = S₁ = " . fmt($A) . "(1)² + " . fmt($B) . "(1) = " . fmt($A) . " + " . fmt($B) . " = <strong>" . fmt($a) . "</strong></code>";
        $steps[] = "<strong>Menentukan Nilai S₂ dan Suku ke-2 (U₂):</strong><br>" .
                   "<code>S₂ = " . fmt($A) . "(2)² + " . fmt($B) . "(2) = " . fmt($A * 4) . " + " . fmt($B * 2) . " = " . fmt($A * 4 + $B * 2) . "</code><br>" .
                   "<code>U₂ = S₂ − S₁ = " . fmt($A * 4 + $B * 2) . " − " . fmt($a) . " = <strong>" . fmt(($A * 4 + $B * 2) - $a) . "</strong></code>";
        $steps[] = "<strong>Menentukan Beda Barisan (b):</strong><br>" .
                   "<code>b = U₂ − U₁ = " . fmt(($A * 4 + $B * 2) - $a) . " − " . fmt($a) . " = <strong>" . fmt($b) . "</strong></code><br>" .
                   "<em>Catatan: Beda pada fungsi kuadrat S_n = An² + Bn selalu bernilai <code>2 × A = 2 × " . fmt($A) . " = " . fmt($b) . "</code>.</em>";
        $steps[] = "<strong>Menurunkan Rumus Umum Suku ke-n (Uₙ = Sₙ − Sₙ₋₁):</strong><br>" .
                   "Berdasarkan aljabar: <code>U_n = 2An + (B − A)</code><br>" .
                   "Substitusi nilai: <code>U_n = 2(" . fmt($A) . ")n + (" . fmt($B) . " − " . fmt($A) . ")</code><br>" .
                   "&rarr; <strong><code>U_n = " . fmt($b) . "n " . ($c >= 0 ? '+ ' . fmt($c) : '− ' . fmt(abs($c))) . "</code></strong>";
        $steps[] = "<strong>Menghitung Nilai Suku ke-$k (U_{$k}) & Jumlahnya (S_{$k}):</strong><br>" .
                   "• Suku ke-$k: <code>U_{$k} = " . fmt($b) . "($k) " . ($c >= 0 ? '+ ' . fmt($c) : '− ' . fmt(abs($c))) . " = <strong>" . fmt($Uk) . "</strong></code><br>" .
                   "• Jumlah $k suku: <code>S_{$k} = " . fmt($A) . "($k)² + " . fmt($B) . "($k) = <strong>" . fmt($Sk) . "</strong></code>";

        $m5 = compact('A', 'B', 'k', 'a', 'b', 'c', 'Uk', 'Sk', 'terms', 'steps');
    }
}

$hasChart = ($m1 && isset($m1['terms'])) || ($ar && isset($ar['terms'])) || ($ge && isset($ge['terms'])) || ($m3_ar && isset($m3_ar['terms'])) || ($m3_ge && isset($m3_ge['terms'])) || ($m4_ar && isset($m4_ar['terms'])) || ($m4_ge && isset($m4_ge['terms'])) || ($m5 && isset($m5['terms']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeqMath — Kalkulator Barisan &amp; Deret Aritmetika dan Geometri</title>

    <!-- Favicon Multi-format Support with Cache-Busting -->
    <link rel="icon" type="image/svg+xml" href="favicon.svg?v=<?= filemtime(__DIR__ . '/favicon.svg') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png?v=<?= filemtime(__DIR__ . '/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png?v=<?= filemtime(__DIR__ . '/favicon-16x16.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png?v=<?= filemtime(__DIR__ . '/apple-touch-icon.png') ?>">
    <link rel="shortcut icon" href="favicon.ico?v=<?= filemtime(__DIR__ . '/favicon.ico') ?>">
    <!-- Fonts: Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- KaTeX for high-performance math formula rendering -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>

    <!-- Theme & Cache-Busted Stylesheet -->
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
    <script>
        (function() {
            const theme = localStorage.getItem('seqmath_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="container header-container">
        <a href="index.php" class="header-brand" title="SeqMath — Kalkulator Barisan &amp; Deret Aritmetika dan Geometri">
            <div class="logo-wrapper">
                <svg class="seqmath-logo-icon" width="46" height="46" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Logo SeqMath">
                    <defs>
                        <linearGradient id="headerLogoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#4F46E5"/>
                            <stop offset="100%" stop-color="#7C3AED"/>
                        </linearGradient>
                        <filter id="headerLogoShadow" x="-10%" y="-10%" width="125%" height="125%">
                            <feDropShadow dx="0" dy="2" stdDeviation="2" flood-color="#0F172A" flood-opacity="0.25"/>
                        </filter>
                    </defs>
                    <!-- App Icon Squircle Container (25% radius = 12px) -->
                    <rect width="48" height="48" rx="12" fill="url(#headerLogoGrad)" filter="url(#headerLogoShadow)"/>
                    
                    <!-- 3 Progression Bars -->
                    <rect class="seq-bar seq-bar-1" x="11" y="26" width="6" height="11" rx="2.5" fill="#FFFFFF" fill-opacity="0.95"/>
                    <rect class="seq-bar seq-bar-2" x="21" y="20" width="6" height="17" rx="2.5" fill="#FFFFFF" fill-opacity="0.95"/>
                    <rect class="seq-bar seq-bar-3" x="31" y="13" width="6" height="24" rx="2.5" fill="#FBBF24"/>
                    
                    <!-- Progression Trend Line -->
                    <path class="seq-trend" d="M14 23 L24 17 L34 10" stroke="#FBBF24" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    
                    <!-- Coordinate Dots -->
                    <circle class="seq-dot seq-dot-1" cx="14" cy="23" r="2.2" fill="#FFFFFF"/>
                    <circle class="seq-dot seq-dot-2" cx="24" cy="17" r="2.2" fill="#FFFFFF"/>
                    <circle class="seq-dot seq-dot-3" cx="34" cy="10" r="2.6" fill="#FBBF24"/>
                </svg>
            </div>
            <div class="brand-text">
                <span class="logo-title">Seq<span class="brand-accent">Math</span></span>
                <p class="tagline">Kalkulator Barisan &amp; Deret Aritmetika dan Geometri</p>
            </div>
        </a>
        <div class="header-actions">
            <button id="themeToggle" class="theme-toggle-btn" aria-label="Ganti mode gelap/terang" title="Ganti mode gelap/terang">
                <i class="fas fa-moon" id="themeIcon"></i>
            </button>
        </div>
    </div>
</header>

<main class="container main">

    <!-- ========== MODE SWITCHER ========== -->
    <div class="mode-switcher">
        <a href="?mode=1" class="mode-btn <?= $mode === '1' ? 'mode-active' : '' ?>">
            <div class="mode-icon"><i class="fas fa-magnifying-glass-chart"></i></div>
            <span class="mode-title">Model 1 — Dari Barisan</span>
            <span class="mode-desc">Cari rumus suku ke-n ($U_n$) dari barisan angka</span>
        </a>
        <a href="?mode=2&tab=<?= $tab ?>&target=<?= $target ?>" class="mode-btn <?= $mode === '2' ? 'mode-active' : '' ?>">
            <div class="mode-icon"><i class="fas fa-pen-ruler"></i></div>
            <span class="mode-title">Model 2 — Hitung Parameter</span>
            <span class="mode-desc">Lengkap: cari $U_n$, $a$, $b/r$, atau $n$</span>
        </a>
        <a href="?mode=3&tab=<?= $tab ?>" class="mode-btn <?= $mode === '3' ? 'mode-active' : '' ?>">
            <div class="mode-icon"><i class="fas fa-shuffle"></i></div>
            <span class="mode-title">Model 3 — Tanpa (a) & (b)</span>
            <span class="mode-desc">Diketahui 2 suku sembarang ($U_p$ & $U_q$)</span>
        </a>
        <a href="?mode=4&tab=<?= $tab ?>&m4_type=<?= $m4_type ?>" class="mode-btn <?= $mode === '4' ? 'mode-active' : '' ?>">
            <div class="mode-icon"><i class="fas fa-arrows-split-up-and-left"></i></div>
            <span class="mode-title">Model 4 — Suku Tengah & Sisipan</span>
            <span class="mode-desc">Hitung suku tengah ($U_t$) & sisipan $k$ bilangan</span>
        </a>
        <a href="?mode=5" class="mode-btn <?= $mode === '5' ? 'mode-active' : '' ?>">
            <div class="mode-icon"><i class="fas fa-square-root-variable"></i></div>
            <span class="mode-title">Model 5 — Deret ke Suku ($S_n \to U_n$)</span>
            <span class="mode-desc">Tentukan $U_n$ dari rumus deret $S_n = An^2 + Bn$</span>
        </a>
    </div>

    <!-- ============================================================
         MODE 1 — CARI RUMUS DARI BARISAN
         ============================================================ -->
    <?php if ($mode === '1'): ?>

    <section class="tab-content active">
        <div class="grid-2">

            <!-- Input -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-keyboard"></i> Input Barisan</h2>
                </div>
                <div class="card-body">
                    <div class="callout-box">
                        <i class="fas fa-lightbulb callout-icon"></i>
                        <div>
                            <div class="callout-title">Cara Pakai</div>
                            <p class="callout-text">Ketik suku-suku barisan dipisahkan dengan koma atau spasi. Minimal masukkan 2 suku angka berturut-turut.</p>
                        </div>
                    </div>

                    <form method="POST" action="" id="m1Form" onsubmit="return validateSequenceInput()">
                        <input type="hidden" name="mode" value="1">
                        <div class="form-group">
                            <label for="barisan">Suku-suku Barisan</label>
                            <input type="text" name="barisan" id="barisan"
                                   placeholder=""
                                   value="<?= htmlspecialchars($m1['raw'] ?? '') ?>"
                                   autocomplete="off">
                            <div class="input-feedback error" id="barisanFeedback" style="display:none;">
                                <i class="fas fa-circle-exclamation"></i> Masukkan minimal 2 suku angka yang dipisahkan koma atau spasi.
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="n_cari">Hitung sampai suku ke-n (Opsional)</label>
                            <input type="number" name="n_cari" id="n_cari"
                                   placeholder="" min="1" step="1"
                                   value="<?= htmlspecialchars($m1['nCari'] ?? '') ?>">
                        </div>
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-magnifying-glass"></i> Analisis Barisan
                            </button>
                            <a href="?mode=1" class="btn btn-outline">
                                <i class="fas fa-rotate-left"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Hasil Analisis -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-square-poll-vertical"></i> Hasil Analisis</h2>
                </div>
                <div class="card-body">
                    <?php if ($m1): ?>
                        <!-- Badge Jenis Barisan -->
                        <?php if ($m1['isArit'] && $m1['isGeo']): ?>
                            <div class="badge badge-both"><i class="fas fa-check-double"></i> Barisan Aritmetika & Geometri</div>
                        <?php elseif ($m1['isArit']): ?>
                            <div class="badge badge-arit"><i class="fas fa-check"></i> Barisan Aritmetika</div>
                        <?php elseif ($m1['isGeo']): ?>
                            <div class="badge badge-geo"><i class="fas fa-check"></i> Barisan Geometri</div>
                        <?php else: ?>
                            <div class="badge badge-none"><i class="fas fa-xmark"></i> Bukan barisan aritmetika maupun geometri</div>
                        <?php endif; ?>

                        <!-- Parameter Stat Cards -->
                        <div class="result-grid">
                            <div class="result-item">
                                <div class="result-label">Suku Pertama (a)</div>
                                <div class="result-value"><?= fmt($m1['a']) ?></div>
                            </div>
                            <?php if ($m1['isArit']): ?>
                            <div class="result-item highlight-arit">
                                <div class="result-label">Beda (b)</div>
                                <div class="result-value"><?= fmt($m1['b']) ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if ($m1['isGeo']): ?>
                            <div class="result-item highlight-geo">
                                <div class="result-label">Rasio (r)</div>
                                <div class="result-value"><?= fmt($m1['r']) ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="result-item">
                                <div class="result-label">Suku Diinput</div>
                                <div class="result-value"><?= $m1['count'] ?></div>
                            </div>

                            <?php if ($m1['isArit'] || $m1['isGeo']): ?>
                                <div class="result-item <?= $m1['isArit'] ? 'highlight-arit' : 'highlight-geo' ?>">
                                    <div class="result-label">U<sub><?= $m1['targetN'] ?></sub></div>
                                    <div class="result-value"><?= fmt($m1['Un']) ?></div>
                                </div>
                                <div class="result-item <?= $m1['isArit'] ? 'highlight-arit' : 'highlight-geo' ?>">
                                    <div class="result-label">S<sub><?= $m1['targetN'] ?></sub></div>
                                    <div class="result-value"><?= fmt($m1['Sn']) ?></div>
                                </div>
                                <?php if ($m1['isGeo'] && isset($m1['konvergen'])): ?>
                                    <?php if ($m1['konvergen']): ?>
                                    <div class="result-item highlight-geo">
                                        <div class="result-label">S<sub>∞</sub> (Konvergen)</div>
                                        <div class="result-value"><?= fmt($m1['Sinf']) ?></div>
                                    </div>
                                    <?php else: ?>
                                    <div class="result-item">
                                        <div class="result-label">S<sub>∞</sub></div>
                                        <div class="result-value" style="font-size:0.95rem;color:var(--text-muted);">|r| ≥ 1, divergen</div>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Formula Highlight Card (Yellow Warm Accent) with Copy Button -->
                        <?php if ($m1['isArit']): 
                            $formulaText = "Un = " . fmt($m1['b']) . "n " . ($m1['a'] - $m1['b'] >= 0 ? "+ " . fmt($m1['a'] - $m1['b']) : "- " . fmt(abs($m1['a'] - $m1['b'])));
                        ?>
                        <div class="formula-highlight-card">
                            <div class="formula-highlight-header">
                                <span class="formula-highlight-title"><i class="fas fa-star"></i> Rumus Suku ke-n ($U_n$) — Jawaban Soal</span>
                                <button type="button" class="btn-copy-formula" onclick="copyFormula('<?= htmlspecialchars($formulaText) ?>')">
                                    <i class="fas fa-copy"></i> Salin Rumus
                                </button>
                            </div>
                            <div class="formula-display">
                                $$U_n = <?= fmt($m1['b']) ?>n <?= ($m1['a'] - $m1['b'] >= 0 ? '+ ' . fmt($m1['a'] - $m1['b']) : '- ' . fmt(abs($m1['a'] - $m1['b']))) ?>$$
                            </div>
                            <p style="font-size:0.84rem;color:var(--text-secondary);margin-top:4px;">
                                Bentuk dasar: $U_n = a + (n - 1)b = <?= fmt($m1['a']) ?> + (n - 1) \cdot <?= fmt($m1['b']) ?>$
                            </p>
                        </div>
                        <?php endif; ?>

                        <?php if ($m1['isGeo']): 
                            $formulaText = "Un = " . fmt($m1['a']) . " * (" . fmt($m1['r']) . ")^(n-1)";
                        ?>
                        <div class="formula-highlight-card">
                            <div class="formula-highlight-header">
                                <span class="formula-highlight-title"><i class="fas fa-star"></i> Rumus Suku ke-n ($U_n$) — Jawaban Soal</span>
                                <button type="button" class="btn-copy-formula" onclick="copyFormula('<?= htmlspecialchars($formulaText) ?>')">
                                    <i class="fas fa-copy"></i> Salin Rumus
                                </button>
                            </div>
                            <div class="formula-display">
                                $$U_n = <?= fmt($m1['a']) ?> \cdot (<?= fmt($m1['r']) ?>)^{n - 1}$$
                            </div>
                            <?php if ($m1['r'] != 0): ?>
                            <p style="font-size:0.84rem;color:var(--text-secondary);margin-top:4px;">
                                Bentuk alternatif: $U_n = <?= fmt($m1['a'] / $m1['r']) ?> \cdot (<?= fmt($m1['r']) ?>)^n$
                            </p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Riwayat Perhitungan Terakhir -->
                        <div class="history-section">
                            <div class="history-header">
                                <span class="history-title"><i class="fas fa-clock-rotate-left"></i> Riwayat Perhitungan Terakhir</span>
                                <button type="button" class="btn-clear-history" onclick="clearHistory()">Hapus</button>
                            </div>
                            <div class="history-list" id="historyList"></div>
                        </div>

                    <?php else: ?>
                        <!-- Keadaan Kosong (Empty State Ceria) -->
                        <div class="empty-state">
                            <div class="empty-illustration">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3 class="empty-title">Belum Ada Analisis Barisan</h3>
                            <p class="empty-desc">Ketik angka barisan di panel kiri lalu klik <strong>Analisis Barisan</strong> untuk melihat hasil analisis otomatis.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($m1 && isset($m1['terms'])): ?>
        <!-- Visualisasi Barisan (Chart & Tabel) -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-chart-line"></i> Visualisasi Barisan (Grafik Titik/Garis & Tabel)</h2>
            </div>
            <div class="card-body">
                <div class="chart-wrapper">
                    <canvas id="m1-chart"></canvas>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>n</th><th>$U_n$</th><th>Nilai</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($m1['terms'], 0, 50) as $i => $val): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>$U_{<?= $i + 1 ?>}$</td>
                                <td><strong><?= fmt($val) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($m1['terms']) > 50): ?>
                            <tr><td colspan="3" style="color:var(--text-muted);font-style:italic;">... dan <?= count($m1['terms']) - 50 ?> suku lainnya</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Langkah Penyelesaian (Step-by-Step Worksheet) -->
        <div class="card worksheet-section">
            <div class="card-header worksheet-header">
                <h2><i class="fas fa-list-check"></i> Langkah Penyelesaian (Worksheet)</h2>
                <button type="button" class="btn-toggle-steps" id="toggleStepsBtn" onclick="toggleWorksheetSteps()">
                    <i class="fas fa-eye-slash"></i> Sembunyikan Langkah
                </button>
            </div>
            <div class="card-body">
                <div class="worksheet-steps" id="worksheetSteps">
                    <?php $cls = ($m1['isGeo'] && !$m1['isArit']) ? 'step-geo' : ''; ?>
                    <div class="step-card <?= $cls ?>">
                        <span class="step-badge">1</span>
                        <div class="step-content">
                            <strong>Analisis Barisan yang Diinput:</strong><br>
                            Barisan angka: <code><?= implode(', ', array_map('fmt', $m1['suku'])) ?></code><br>
                            Suku pertama langsung diperoleh dari suku ke-1: <strong>$a = U_1 = <?= fmt($m1['a']) ?>$</strong>.
                        </div>
                    </div>

                    <?php if ($m1['isArit']): ?>
                    <div class="step-card">
                        <span class="step-badge">2</span>
                        <div class="step-content">
                            <strong>Uji Selisih Antar Suku (Mencari Beda $b$):</strong><br>
                            Hitung selisih antar dua suku berurutan:<br>
                            &bull; $U_2 - U_1 = <?= fmt($m1['suku'][1]) ?> - <?= fmt($m1['suku'][0]) ?> = <?= fmt($m1['b']) ?>$<br>
                            <?php if (count($m1['suku']) >= 3): ?>
                            &bull; $U_3 - U_2 = <?= fmt($m1['suku'][2]) ?> - <?= fmt($m1['suku'][1]) ?> = <?= fmt($m1['b']) ?>$<br>
                            <?php endif; ?>
                            Karena selisihnya konstan (selalu <strong><?= fmt($m1['b']) ?></strong>), maka barisan ini terbukti <strong>Barisan Aritmetika</strong> dengan <strong>$b = <?= fmt($m1['b']) ?>$</strong>.
                        </div>
                    </div>
                    <div class="step-card">
                        <span class="step-badge">3</span>
                        <div class="step-content">
                            <strong>Menyusun Rumus Suku ke-n ($U_n$):</strong><br>
                            Rumus umum: $U_n = a + (n - 1)b$<br>
                            Substitusi nilai: $U_n = <?= fmt($m1['a']) ?> + (n - 1) \cdot <?= fmt($m1['b']) ?>$<br>
                            Bentuk aljabar: <strong>$U_n = <?= fmt($m1['b']) ?>n + (<?= fmt($m1['a'] - $m1['b']) ?>)$</strong>.
                        </div>
                    </div>
                    <div class="step-card">
                        <span class="step-badge">4</span>
                        <div class="step-content">
                            <strong>Menghitung Nilai Suku ke-<?= $m1['targetN'] ?> ($U_{<?= $m1['targetN'] ?>}$):</strong><br>
                            $U_{<?= $m1['targetN'] ?>} = <?= fmt($m1['a']) ?> + (<?= $m1['targetN'] ?> - 1) \cdot <?= fmt($m1['b']) ?>$<br>
                            $= <?= fmt($m1['a']) ?> + (<?= $m1['targetN'] - 1 ?>) \cdot <?= fmt($m1['b']) ?>$<br>
                            $= <?= fmt($m1['a']) ?> + <?= fmt(($m1['targetN'] - 1) * $m1['b']) ?> = \mathbf{<?= fmt($m1['Un']) ?>}$.
                        </div>
                    </div>
                    <div class="step-card">
                        <span class="step-badge">5</span>
                        <div class="step-content">
                            <strong>Menghitung Jumlah <?= $m1['targetN'] ?> Suku Pertama ($S_{<?= $m1['targetN'] ?>}$):</strong><br>
                            Rumus deret: $S_n = \frac{n}{2} [2a + (n - 1)b]$<br>
                            $S_{<?= $m1['targetN'] ?>} = \frac{<?= $m1['targetN'] ?>}{2} [2 \cdot <?= fmt($m1['a']) ?> + (<?= $m1['targetN'] ?> - 1) \cdot <?= fmt($m1['b']) ?>]$<br>
                            $= <?= fmt($m1['targetN'] / 2) ?> \cdot [<?= fmt(2 * $m1['a']) ?> + <?= fmt(($m1['targetN'] - 1) * $m1['b']) ?>]$<br>
                            $= <?= fmt($m1['targetN'] / 2) ?> \cdot [<?= fmt(2 * $m1['a'] + ($m1['targetN'] - 1) * $m1['b']) ?>] = \mathbf{<?= fmt($m1['Sn']) ?>}$.
                        </div>
                    </div>
                    <?php elseif ($m1['isGeo']): ?>
                    <div class="step-card step-geo">
                        <span class="step-badge">2</span>
                        <div class="step-content">
                            <strong>Uji Rasio Pengali Antar Suku ($r$):</strong><br>
                            Hitung perbandingan antar dua suku berurutan:<br>
                            &bull; $\frac{U_2}{U_1} = \frac{<?= fmt($m1['suku'][1]) ?>}{<?= fmt($m1['suku'][0]) ?>} = <?= fmt($m1['r']) ?>$<br>
                            <?php if (count($m1['suku']) >= 3): ?>
                            &bull; $\frac{U_3}{U_2} = \frac{<?= fmt($m1['suku'][2]) ?>}{<?= fmt($m1['suku'][1]) ?>} = <?= fmt($m1['r']) ?>$<br>
                            <?php endif; ?>
                            Karena rasionya konstan (selalu <strong><?= fmt($m1['r']) ?></strong>), maka barisan ini terbukti <strong>Barisan Geometri</strong> dengan <strong>$r = <?= fmt($m1['r']) ?>$</strong>.
                        </div>
                    </div>
                    <div class="step-card step-geo">
                        <span class="step-badge">3</span>
                        <div class="step-content">
                            <strong>Menyusun Rumus Suku ke-n ($U_n$):</strong><br>
                            Rumus umum: $U_n = a \cdot r^{n - 1}$<br>
                            Substitusi: <strong>$U_n = <?= fmt($m1['a']) ?> \cdot (<?= fmt($m1['r']) ?>)^{n - 1}$</strong><br>
                            <?php if ($m1['r'] != 0): ?>
                            Bentuk alternatif aljabar: <strong>$U_n = <?= fmt($m1['a'] / $m1['r']) ?> \cdot (<?= fmt($m1['r']) ?>)^n$</strong>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="step-card step-geo">
                        <span class="step-badge">4</span>
                        <div class="step-content">
                            <strong>Menghitung Nilai Suku ke-<?= $m1['targetN'] ?> ($U_{<?= $m1['targetN'] ?>}$):</strong><br>
                            $U_{<?= $m1['targetN'] ?>} = <?= fmt($m1['a']) ?> \cdot (<?= fmt($m1['r']) ?>)^{<?= $m1['targetN'] ?> - 1}$<br>
                            $= <?= fmt($m1['a']) ?> \cdot (<?= fmt($m1['r']) ?>)^{<?= $m1['targetN'] - 1 ?>}$<br>
                            $= <?= fmt($m1['a']) ?> \cdot <?= fmt(pow($m1['r'], $m1['targetN'] - 1)) ?> = \mathbf{<?= fmt($m1['Un']) ?>}$.
                        </div>
                    </div>
                    <div class="step-card step-geo">
                        <span class="step-badge">5</span>
                        <div class="step-content">
                            <strong>Menghitung Jumlah <?= $m1['targetN'] ?> Suku Pertama ($S_{<?= $m1['targetN'] ?>}$):</strong><br>
                            <?php if ($m1['r'] == 1): ?>
                            Karena $r = 1$: $S_n = a \cdot n = <?= fmt($m1['a']) ?> \cdot <?= $m1['targetN'] ?> = \mathbf{<?= fmt($m1['Sn']) ?>}$
                            <?php else: ?>
                            Rumus deret: $S_n = \frac{a(r^n - 1)}{r - 1}$<br>
                            $S_{<?= $m1['targetN'] ?>} = \frac{<?= fmt($m1['a']) ?> \cdot [(<?= fmt($m1['r']) ?>)^{<?= $m1['targetN'] ?>} - 1]}{<?= fmt($m1['r']) ?> - 1}$<br>
                            $= \frac{<?= fmt($m1['a']) ?> \cdot [<?= fmt(pow($m1['r'], $m1['targetN']) - 1) ?>]}{<?= fmt($m1['r'] - 1) ?>} = \mathbf{<?= fmt($m1['Sn']) ?>}$.
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($m1['konvergen']): ?>
                    <div class="step-card step-geo">
                        <span class="step-badge">6</span>
                        <div class="step-content">
                            <strong>Deret Geometri Tak Hingga ($S_\infty$):</strong><br>
                            Karena nilai mutlak rasio $|r| = <?= fmt(abs($m1['r'])) ?> < 1$, maka deret ini bersifat <strong>konvergen</strong>:<br>
                            $S_\infty = \frac{a}{1 - r} = \frac{<?= fmt($m1['a']) ?>}{1 - <?= fmt($m1['r']) ?>} = \frac{<?= fmt($m1['a']) ?>}{<?= fmt(1 - $m1['r']) ?>} = \mathbf{<?= fmt($m1['Sinf']) ?>}$.
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <?php endif; ?>

    <!-- ============================================================
         MODEL 2 — HITUNG PARAMETER / VARIABEL
         ============================================================ -->
    <?php if ($mode === '2'): ?>

    <!-- Tab Aritmetika / Geometri -->
    <div class="tabs">
        <a href="?mode=2&tab=aritmetika&target=<?= $target ?>" class="tab <?= $tab === 'aritmetika' ? 'active' : '' ?>" data-tab="aritmetika">
            <i class="fas fa-plus-minus"></i> Aritmetika
        </a>
        <a href="?mode=2&tab=geometri&target=<?= $target ?>" class="tab <?= $tab === 'geometri' ? 'active' : '' ?>" data-tab="geometri">
            <i class="fas fa-xmark"></i> Geometri
        </a>
    </div>

    <!-- ========== ARITMETIKA ========== -->
    <?php if ($tab === 'aritmetika'): ?>
    <!-- Sub-nav: Apa yang ingin dicari? -->
    <div class="subnav">
        <a href="?mode=2&tab=aritmetika&target=Un" class="subnav-btn <?= $target === 'Un' ? 'active' : '' ?>">
            <i class="fas fa-calculator"></i> Cari <b>Uₙ & Sₙ</b> (Lengkap)
        </a>
        <a href="?mode=2&tab=aritmetika&target=a" class="subnav-btn <?= $target === 'a' ? 'active' : '' ?>">
            Cari <b>a</b> (Suku Pertama)
        </a>
        <a href="?mode=2&tab=aritmetika&target=b" class="subnav-btn <?= $target === 'b' ? 'active' : '' ?>">
            Cari <b>b</b> (Beda)
        </a>
        <a href="?mode=2&tab=aritmetika&target=n" class="subnav-btn <?= $target === 'n' ? 'active' : '' ?>">
            Cari <b>n</b> (Banyak Suku)
        </a>
    </div>

    <section class="tab-content active">
        <div class="grid-2">
            <!-- INPUT CARD -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-keyboard"></i>
                        <?php if ($target === 'Un'): ?>
                            Input Parameter Aritmetika Lengkap
                        <?php elseif ($target === 'a'): ?>
                            Cari Suku Pertama (a) Aritmetika
                        <?php elseif ($target === 'b'): ?>
                            Cari Beda (b) Aritmetika
                        <?php elseif ($target === 'n'): ?>
                            Cari Banyak Suku (n) Aritmetika
                        <?php endif; ?>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="formula-box">
                        <p class="formula-label">Rumus yang Digunakan</p>
                        <?php if ($target === 'Un'): ?>
                            <p class="formula">U<sub>n</sub> = a + (n − 1) · b</p>
                            <p class="formula">S<sub>n</sub> = <sup>n</sup>/<sub>2</sub> · (2a + (n − 1) · b)</p>
                        <?php elseif ($target === 'a'): ?>
                            <p class="formula">a = U<sub>n</sub> − (n − 1) · b</p>
                            <p class="formula" style="font-size:0.88rem;color:var(--text-muted);">Diketahui: Beda (b), Banyak suku (n), Nilai U<sub>n</sub></p>
                        <?php elseif ($target === 'b'): ?>
                            <p class="formula">b = (U<sub>n</sub> − a) / (n − 1)</p>
                            <p class="formula" style="font-size:0.88rem;color:var(--text-muted);">Diketahui: Suku pertama (a), Banyak suku (n), Nilai U<sub>n</sub></p>
                        <?php elseif ($target === 'n'): ?>
                            <p class="formula">n = (U<sub>n</sub> − a) / b + 1</p>
                            <p class="formula" style="font-size:0.88rem;color:var(--text-muted);">Diketahui: Suku pertama (a), Beda (b), Nilai U<sub>n</sub></p>
                        <?php endif; ?>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="tab" value="aritmetika">
                        <input type="hidden" name="mode" value="2">
                        <input type="hidden" name="target" value="<?= $target ?>">

                        <?php if ($target === 'Un'): ?>
                            <div class="form-group">
                                <label for="ar_a">Suku Pertama (a)</label>
                                <input type="number" name="ar_a" id="ar_a" placeholder="" step="any"
                                       value="<?= ($ar && isset($ar['a'])) ? htmlspecialchars($ar['a']) : '' ?>">
                            </div>
                            <div class="form-group">
                                <label for="ar_b">Beda (b)</label>
                                <input type="number" name="ar_b" id="ar_b" placeholder="" step="any"
                                       value="<?= ($ar && isset($ar['b'])) ? htmlspecialchars($ar['b']) : '' ?>">
                            </div>
                            <div class="form-group">
                                <label for="ar_n">Jumlah Suku (n)</label>
                                <input type="number" name="ar_n" id="ar_n" placeholder="" min="1" step="1"
                                       value="<?= ($ar && isset($ar['n'])) ? htmlspecialchars($ar['n']) : '' ?>">
                            </div>
                        <?php elseif ($target === 'a'): ?>
                            <div class="form-group">
                                <label for="ar_b">Beda (b)</label>
                                <input type="number" name="ar_b" id="ar_b" placeholder="" step="any"
                                       value="<?= htmlspecialchars($_POST['ar_b'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="ar_n">Suku ke-n (n)</label>
                                <input type="number" name="ar_n" id="ar_n" placeholder="" min="1" step="1"
                                       value="<?= htmlspecialchars($_POST['ar_n'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="ar_Un">Nilai Suku ke-n (U<sub>n</sub>)</label>
                                <input type="number" name="ar_Un" id="ar_Un" placeholder="" step="any"
                                       value="<?= htmlspecialchars($_POST['ar_Un'] ?? '') ?>">
                            </div>
                        <?php elseif ($target === 'b'): ?>
                            <div class="form-group">
                                <label for="ar_a">Suku Pertama (a)</label>
                                <input type="number" name="ar_a" id="ar_a" placeholder="" step="any"
                                       value="<?= htmlspecialchars($_POST['ar_a'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="ar_n">Suku ke-n (n)</label>
                                <input type="number" name="ar_n" id="ar_n" placeholder="" min="2" step="1"
                                       value="<?= htmlspecialchars($_POST['ar_n'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="ar_Un">Nilai Suku ke-n (U<sub>n</sub>)</label>
                                <input type="number" name="ar_Un" id="ar_Un" placeholder="" step="any"
                                       value="<?= htmlspecialchars($_POST['ar_Un'] ?? '') ?>">
                            </div>
                        <?php elseif ($target === 'n'): ?>
                            <div class="form-group">
                                <label for="ar_a">Suku Pertama (a)</label>
                                <input type="number" name="ar_a" id="ar_a" placeholder="" step="any"
                                       value="<?= htmlspecialchars($_POST['ar_a'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="ar_b">Beda (b)</label>
                                <input type="number" name="ar_b" id="ar_b" placeholder="" step="any"
                                       value="<?= htmlspecialchars($_POST['ar_b'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="ar_Un">Nilai Suku Terakhir (U<sub>n</sub>)</label>
                                <input type="number" name="ar_Un" id="ar_Un" placeholder="" step="any"
                                       value="<?= htmlspecialchars($_POST['ar_Un'] ?? '') ?>">
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-calculator"></i> Hitung</button>
                        <a href="?mode=2&tab=aritmetika&target=<?= $target ?>" class="btn btn-outline"><i class="fas fa-rotate-left"></i> Reset</a>
                    </form>
                </div>
            </div>

            <!-- RESULT CARD -->
            <div class="card">
                <div class="card-header"><h2><i class="fas fa-square-poll-vertical"></i> Hasil Aritmetika</h2></div>
                <div class="card-body">
                    <?php if ($ar && isset($ar['error'])): ?>
                        <div class="alert-box">
                            <i class="fas fa-circle-exclamation"></i>
                            <?= htmlspecialchars($ar['error']) ?>
                        </div>
                    <?php elseif ($ar && isset($ar['terms'])): ?>
                        <div class="result-grid">
                            <div class="result-item<?= $target === 'a' ? ' highlight' : '' ?>">
                                <div class="result-label">Suku Pertama (a)</div>
                                <div class="result-value"><?= fmt($ar['a']) ?></div>
                            </div>
                            <div class="result-item<?= $target === 'b' ? ' highlight' : '' ?>">
                                <div class="result-label">Beda (b)</div>
                                <div class="result-value"><?= fmt($ar['b']) ?></div>
                            </div>
                            <div class="result-item<?= $target === 'n' ? ' highlight' : '' ?>">
                                <div class="result-label">Banyak Suku (n)</div>
                                <div class="result-value"><?= $ar['n'] ?></div>
                            </div>
                            <div class="result-item">
                                <div class="result-label">U<sub><?= $ar['n'] ?></sub></div>
                                <div class="result-value"><?= fmt($ar['Un']) ?></div>
                            </div>
                            <div class="result-item<?= $target === 'Un' ? ' highlight' : '' ?>">
                                <div class="result-label">Jumlah <?= $ar['n'] ?> Suku (S<sub><?= $ar['n'] ?></sub>)</div>
                                <div class="result-value"><?= fmt($ar['Sn']) ?></div>
                            </div>
                        </div>

                        <div class="formula-box" style="margin-top:14px;">
                            <p class="formula-label">Rumus Barisan</p>
                            <p class="formula">U<sub>n</sub> = <?= fmt($ar['a']) ?> + (n − 1) × <?= fmt($ar['b']) ?></p>
                        </div>

                        <div class="sequence-preview">
                            <strong>Barisan:</strong> <?= previewBarisan($ar['terms']) ?>
                        </div>
                    <?php else: ?>
                        <div class="result-placeholder">
                            <i class="fas fa-arrow-left"></i>
                            <p>Masukkan nilai parameter dan klik <strong>Hitung</strong></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($ar && isset($ar['terms'])): ?>
        <div class="card visual-card">
            <div class="card-header"><h2><i class="fas fa-chart-bar"></i> Visualisasi Barisan</h2></div>
            <div class="card-body">
                <div class="chart-wrapper"><canvas id="ar-chart"></canvas></div>
                <div class="table-wrapper">
                    <table><thead><tr><th>n</th><th>U<sub>n</sub></th><th>Rumus</th><th>Nilai</th></tr></thead><tbody>
                    <?php foreach (array_slice($ar['terms'], 0, 50) as $i => $v): $idx = $i + 1; ?>
                        <tr><td><?= $idx ?></td><td>U<sub><?= $idx ?></sub></td><td><?= fmt($ar['a']) ?> + (<?= $idx ?>−1)×<?= fmt($ar['b']) ?></td><td><strong><?= fmt($v) ?></strong></td></tr>
                    <?php endforeach; ?>
                    <?php if (count($ar['terms']) > 50): ?><tr><td colspan="4" style="color:var(--text-muted);font-style:italic;">... dan <?= count($ar['terms']) - 50 ?> suku lainnya</td></tr><?php endif; ?>
                    </tbody></table>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2><i class="fas fa-list-ol"></i> Langkah Penyelesaian</h2></div>
            <div class="card-body">
                <?php foreach ($ar['steps'] as $i => $st): ?>
                <div class="step">
                    <span class="step-num"><?= $i + 1 ?></span>
                    <?= $st ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- ========== GEOMETRI ========== -->
    <?php if ($tab === 'geometri'): ?>
    <!-- Sub-nav: Apa yang ingin dicari? -->
    <div class="subnav">
        <a href="?mode=2&tab=geometri&target=Un" class="subnav-btn <?= $target === 'Un' ? 'active geo-active' : '' ?>">
            <i class="fas fa-calculator"></i> Cari <b>Uₙ & Sₙ</b> (Lengkap)
        </a>
        <a href="?mode=2&tab=geometri&target=a" class="subnav-btn <?= $target === 'a' ? 'active geo-active' : '' ?>">
            Cari <b>a</b> (Suku Pertama)
        </a>
        <a href="?mode=2&tab=geometri&target=r" class="subnav-btn <?= ($target === 'r' || $target === 'b') ? 'active geo-active' : '' ?>">
            Cari <b>r</b> (Rasio)
        </a>
        <a href="?mode=2&tab=geometri&target=n" class="subnav-btn <?= $target === 'n' ? 'active geo-active' : '' ?>">
            Cari <b>n</b> (Banyak Suku)
        </a>
    </div>

    <section class="tab-content active">
        <div class="grid-2">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-keyboard"></i>
                        <?php if ($target === 'Un'): ?>
                            Input Parameter Geometri Lengkap
                        <?php elseif ($target === 'a'): ?>
                            Cari Suku Pertama (a) Geometri
                        <?php elseif ($target === 'r' || $target === 'b'): ?>
                            Cari Rasio (r) Geometri
                        <?php elseif ($target === 'n'): ?>
                            Cari Banyak Suku (n) Geometri
                        <?php endif; ?>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="formula-box" style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border-color:#fcd34d;">
                        <p class="formula-label" style="color:#d97706;">Rumus yang Digunakan</p>
                        <?php if ($target === 'Un'): ?>
                            <p class="formula">U<sub>n</sub> = a · r<sup>(n−1)</sup></p>
                            <p class="formula">S<sub>n</sub> = a · (r<sup>n</sup> − 1) / (r − 1)</p>
                            <p class="formula">S<sub>∞</sub> = a / (1 − r), &nbsp; |r| &lt; 1</p>
                        <?php elseif ($target === 'a'): ?>
                            <p class="formula">a = U<sub>n</sub> / r<sup>(n−1)</sup></p>
                            <p class="formula" style="font-size:0.88rem;color:var(--text-muted);">Diketahui: Rasio (r), Banyak suku (n), Nilai U<sub>n</sub></p>
                        <?php elseif ($target === 'r' || $target === 'b'): ?>
                            <p class="formula">r = (U<sub>n</sub> / a)<sup>1/(n−1)</sup></p>
                            <p class="formula" style="font-size:0.88rem;color:var(--text-muted);">Diketahui: Suku pertama (a), Banyak suku (n), Nilai U<sub>n</sub></p>
                        <?php elseif ($target === 'n'): ?>
                            <p class="formula">n = ln(U<sub>n</sub>/a) / ln(r) + 1</p>
                            <p class="formula" style="font-size:0.88rem;color:var(--text-muted);">Diketahui: Suku pertama (a), Rasio (r), Nilai U<sub>n</sub></p>
                        <?php endif; ?>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="tab" value="geometri">
                        <input type="hidden" name="mode" value="2">
                        <input type="hidden" name="target" value="<?= ($target === 'b' ? 'r' : $target) ?>">

                        <?php if ($target === 'Un'): ?>
                            <div class="form-group">
                                <label for="ge_a">Suku Pertama (a)</label>
                                <input type="number" name="ge_a" id="ge_a" placeholder="" step="any"
                                       value="<?= ($ge && isset($ge['a'])) ? htmlspecialchars($ge['a']) : '' ?>">
                            </div>
                            <div class="form-group">
                                <label for="ge_r">Rasio (r)</label>
                                <input type="number" name="ge_r" id="ge_r" placeholder="" step="any"
                                       value="<?= ($ge && isset($ge['r'])) ? htmlspecialchars($ge['r']) : '' ?>">
                            </div>
                            <div class="form-group">
                                <label for="ge_n">Jumlah Suku (n)</label>
                                <input type="number" name="ge_n" id="ge_n" placeholder="" min="1" step="1"
                                       value="<?= ($ge && isset($ge['n'])) ? htmlspecialchars($ge['n']) : '' ?>">
                            </div>
                        <?php elseif ($target === 'a'): ?>
                            <div class="form-group">
                                <label for="ge_r">Rasio (r)</label>
                                <input type="number" name="ge_r" id="ge_r" placeholder="" step="any"
                                       value="<?= htmlspecialchars($_POST['ge_r'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="ge_n">Suku ke-n (n)</label>
                                <input type="number" name="ge_n" id="ge_n" placeholder="" min="1" step="1"
                                       value="<?= htmlspecialchars($_POST['ge_n'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="ge_Un">Nilai Suku ke-n (U<sub>n</sub>)</label>
                                <input type="number" name="ge_Un" id="ge_Un" placeholder="" step="any"
                                       value="<?= htmlspecialchars($_POST['ge_Un'] ?? '') ?>">
                            </div>
                        <?php elseif ($target === 'r' || $target === 'b'): ?>
                            <div class="form-group">
                                <label for="ge_a">Suku Pertama (a)</label>
                                <input type="number" name="ge_a" id="ge_a" placeholder="" step="any"
                                       value="<?= htmlspecialchars($_POST['ge_a'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="ge_n">Suku ke-n (n)</label>
                                <input type="number" name="ge_n" id="ge_n" placeholder="" min="2" step="1"
                                       value="<?= htmlspecialchars($_POST['ge_n'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="ge_Un">Nilai Suku ke-n (U<sub>n</sub>)</label>
                                <input type="number" name="ge_Un" id="ge_Un" placeholder="" step="any"
                                       value="<?= htmlspecialchars($_POST['ge_Un'] ?? '') ?>">
                            </div>
                        <?php elseif ($target === 'n'): ?>
                            <div class="form-group">
                                <label for="ge_a">Suku Pertama (a)</label>
                                <input type="number" name="ge_a" id="ge_a" placeholder="" step="any"
                                       value="<?= htmlspecialchars($_POST['ge_a'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="ge_r">Rasio (r)</label>
                                <input type="number" name="ge_r" id="ge_r" placeholder="" step="any"
                                       value="<?= htmlspecialchars($_POST['ge_r'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="ge_Un">Nilai Suku Terakhir (U<sub>n</sub>)</label>
                                <input type="number" name="ge_Un" id="ge_Un" placeholder="" step="any"
                                       value="<?= htmlspecialchars($_POST['ge_Un'] ?? '') ?>">
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary btn-geo"><i class="fas fa-calculator"></i> Hitung</button>
                        <a href="?mode=2&tab=geometri&target=<?= ($target === 'b' ? 'r' : $target) ?>" class="btn btn-outline"><i class="fas fa-rotate-left"></i> Reset</a>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2><i class="fas fa-square-poll-vertical"></i> Hasil Geometri</h2></div>
                <div class="card-body">
                    <?php if ($ge && isset($ge['error'])): ?>
                        <div class="alert-box">
                            <i class="fas fa-circle-exclamation"></i>
                            <?= htmlspecialchars($ge['error']) ?>
                        </div>
                    <?php elseif ($ge && isset($ge['terms'])): ?>
                        <div class="result-grid">
                            <div class="result-item<?= $target === 'a' ? ' highlight-geo' : '' ?>"><div class="result-label">Suku Pertama (a)</div><div class="result-value"><?= fmt($ge['a']) ?></div></div>
                            <div class="result-item<?= in_array($target, ['r', 'b']) ? ' highlight-geo' : '' ?>"><div class="result-label">Rasio (r)</div><div class="result-value"><?= fmt($ge['r']) ?></div></div>
                            <div class="result-item<?= $target === 'n' ? ' highlight-geo' : '' ?>"><div class="result-label">Banyak Suku (n)</div><div class="result-value"><?= $ge['n'] ?></div></div>
                            <div class="result-item"><div class="result-label">U<sub><?= $ge['n'] ?></sub></div><div class="result-value"><?= fmt($ge['Un']) ?></div></div>
                            <div class="result-item<?= $target === 'Un' ? ' highlight-geo' : '' ?>"><div class="result-label">S<sub><?= $ge['n'] ?></sub></div><div class="result-value"><?= fmt($ge['Sn']) ?></div></div>
                            <?php if ($ge['konvergen']): ?>
                            <div class="result-item highlight-geo"><div class="result-label">S<sub>∞</sub></div><div class="result-value"><?= fmt($ge['Sinf']) ?></div></div>
                            <?php else: ?>
                            <div class="result-item"><div class="result-label">S<sub>∞</sub></div><div class="result-value" style="font-size:1rem;color:var(--text-muted);">|r| ≥ 1, divergen</div></div>
                            <?php endif; ?>
                        </div>
                        <div class="formula-box" style="margin-top:14px;background:linear-gradient(135deg,#fffbeb,#fef3c7);border-color:#fcd34d;">
                            <p class="formula-label" style="color:#d97706;">Rumus Barisan</p>
                            <p class="formula">U<sub>n</sub> = <?= fmt($ge['a']) ?> × <?= fmt($ge['r']) ?><sup>(n−1)</sup></p>
                        </div>
                        <div class="sequence-preview"><strong>Barisan:</strong> <?= previewBarisan($ge['terms']) ?></div>
                    <?php else: ?>
                        <div class="result-placeholder"><i class="fas fa-arrow-left"></i><p>Masukkan nilai parameter dan klik <strong>Hitung</strong></p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($ge && isset($ge['terms'])): ?>
        <div class="card visual-card">
            <div class="card-header"><h2><i class="fas fa-chart-bar"></i> Visualisasi Barisan</h2></div>
            <div class="card-body">
                <div class="chart-wrapper"><canvas id="ge-chart"></canvas></div>
                <div class="table-wrapper">
                    <table class="table-geo"><thead><tr><th>n</th><th>U<sub>n</sub></th><th>Rumus</th><th>Nilai</th></tr></thead><tbody>
                    <?php foreach (array_slice($ge['terms'], 0, 50) as $i => $v): $idx = $i + 1; ?>
                        <tr><td><?= $idx ?></td><td>U<sub><?= $idx ?></sub></td><td><?= fmt($ge['a']) ?> × <?= fmt($ge['r']) ?><sup><?= $idx - 1 ?></sup></td><td><strong><?= fmt($v) ?></strong></td></tr>
                    <?php endforeach; ?>
                    <?php if (count($ge['terms']) > 50): ?><tr><td colspan="4" style="color:var(--text-muted);font-style:italic;">... dan <?= count($ge['terms']) - 50 ?> suku lainnya</td></tr><?php endif; ?>
                    </tbody></table>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2><i class="fas fa-list-ol"></i> Langkah Penyelesaian</h2></div>
            <div class="card-body">
                <?php foreach ($ge['steps'] as $i => $st): ?>
                <div class="step step-geo">
                    <span class="step-num"><?= $i + 1 ?></span>
                    <?= $st ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php endif; /* end mode 2 */ ?>

    <!-- ============================================================
         MODEL 3 — TANPA SUKU PERTAMA (a) & BEDA/RASIO (b/r)
         ============================================================ -->
    <?php if ($mode === '3'): ?>

    <!-- Tab Aritmetika / Geometri -->
    <div class="tabs">
        <a href="?mode=3&tab=aritmetika" class="tab <?= $tab === 'aritmetika' ? 'active' : '' ?>" data-tab="aritmetika">
            <i class="fas fa-plus-minus"></i> Aritmetika (Tanpa a & b)
        </a>
        <a href="?mode=3&tab=geometri" class="tab <?= $tab === 'geometri' ? 'active' : '' ?>" data-tab="geometri">
            <i class="fas fa-xmark"></i> Geometri (Tanpa a & r)
        </a>
    </div>

    <!-- ========== MODEL 3 ARITMETIKA ========== -->
    <?php if ($tab === 'aritmetika'): ?>
    <section class="tab-content active">
        <div class="grid-2">
            <!-- INPUT CARD -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-keyboard"></i> Input 2 Suku Sembarang Aritmetika</h2>
                </div>
                <div class="card-body">
                    <div class="formula-box">
                        <p class="formula-label">Metode Eliminasi & Substitusi (Tanpa a & b)</p>
                        <p class="formula">b = (U<sub>q</sub> − U<sub>p</sub>) / (q − p)</p>
                        <p class="formula">a = U<sub>p</sub> − (p − 1) · b</p>
                        <p class="formula" style="font-size:0.88rem;color:var(--text-muted);">
                            Menentukan nilai suku pertama (<code>a</code>) dan beda (<code>b</code>) dari dua suku sembarang.
                        </p>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="mode" value="3">
                        <input type="hidden" name="tab" value="aritmetika">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="m3_ar_p">Suku ke- (p)</label>
                                <input type="number" name="m3_ar_p" id="m3_ar_p" placeholder="" min="1" step="1" required
                                       value="<?= htmlspecialchars($_POST['m3_ar_p'] ?? ($m3_ar['p'] ?? '')) ?>">
                            </div>
                            <div class="form-group">
                                <label for="m3_ar_Up">Nilai Suku ke-p (U<sub>p</sub>)</label>
                                <input type="number" name="m3_ar_Up" id="m3_ar_Up" placeholder="" step="any" required
                                       value="<?= htmlspecialchars($_POST['m3_ar_Up'] ?? ($m3_ar['Up'] ?? '')) ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="m3_ar_q">Suku ke- (q)</label>
                                <input type="number" name="m3_ar_q" id="m3_ar_q" placeholder="" min="1" step="1" required
                                       value="<?= htmlspecialchars($_POST['m3_ar_q'] ?? ($m3_ar['q'] ?? '')) ?>">
                            </div>
                            <div class="form-group">
                                <label for="m3_ar_Uq">Nilai Suku ke-q (U<sub>q</sub>)</label>
                                <input type="number" name="m3_ar_Uq" id="m3_ar_Uq" placeholder="" step="any" required
                                       value="<?= htmlspecialchars($_POST['m3_ar_Uq'] ?? ($m3_ar['Uq'] ?? '')) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="m3_ar_n">Hitung jumlah Suku pertama sampai suku ke-n <small style="color:var(--text-muted);"></small></label>
                            <input type="number" name="m3_ar_n" id="m3_ar_n" placeholder="" min="1" step="1"
                                   value="<?= htmlspecialchars($_POST['m3_ar_n'] ?? ($m3_ar['n'] ?? '')) ?>">
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-calculator"></i> Hitung Eliminasi & Substitusi</button>
                        <a href="?mode=3&tab=aritmetika" class="btn btn-outline"><i class="fas fa-rotate-left"></i> Reset</a>
                    </form>
                </div>
            </div>

            <!-- RESULT CARD -->
            <div class="card">
                <div class="card-header"><h2><i class="fas fa-square-poll-vertical"></i> Hasil Aritmetika</h2></div>
                <div class="card-body">
                    <?php if ($m3_ar && isset($m3_ar['error'])): ?>
                        <div class="alert-box">
                            <i class="fas fa-circle-exclamation"></i>
                            <?= htmlspecialchars($m3_ar['error']) ?>
                        </div>
                    <?php elseif ($m3_ar && isset($m3_ar['terms'])): ?>
                        <div class="result-grid">
                            <div class="result-item highlight">
                                <div class="result-label">Suku Pertama (a) ★</div>
                                <div class="result-value"><?= fmt($m3_ar['a']) ?></div>
                            </div>
                            <div class="result-item highlight">
                                <div class="result-label">Beda (b) ★</div>
                                <div class="result-value"><?= fmt($m3_ar['b']) ?></div>
                            </div>
                            <div class="result-item">
                                <div class="result-label">Banyak Suku (n)</div>
                                <div class="result-value"><?= $m3_ar['n'] ?></div>
                            </div>
                            <div class="result-item">
                                <div class="result-label">U<sub><?= $m3_ar['n'] ?></sub></div>
                                <div class="result-value"><?= fmt($m3_ar['Un']) ?></div>
                            </div>
                            <div class="result-item highlight">
                                <div class="result-label">Jumlah <?= $m3_ar['n'] ?> Suku (S<sub><?= $m3_ar['n'] ?></sub>)</div>
                                <div class="result-value"><?= fmt($m3_ar['Sn']) ?></div>
                            </div>
                        </div>

                        <div class="formula-box" style="margin-top:14px;">
                            <p class="formula-label">Rumus Barisan Ditemukan</p>
                            <p class="formula">U<sub>n</sub> = <?= fmt($m3_ar['a']) ?> + (n − 1) × <?= fmt($m3_ar['b']) ?></p>
                        </div>

                        <div class="sequence-preview">
                            <strong>Barisan:</strong> <?= previewBarisan($m3_ar['terms']) ?>
                        </div>
                    <?php else: ?>
                        <div class="result-placeholder">
                            <i class="fas fa-arrow-left"></i>
                            <p>Masukkan nilai 2 suku dan klik <strong>Hitung</strong></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($m3_ar && isset($m3_ar['terms'])): ?>
        <div class="card visual-card">
            <div class="card-header"><h2><i class="fas fa-chart-bar"></i> Visualisasi Barisan</h2></div>
            <div class="card-body">
                <div class="chart-wrapper"><canvas id="m3-ar-chart"></canvas></div>
                <div class="table-wrapper">
                    <table><thead><tr><th>n</th><th>U<sub>n</sub></th><th>Rumus</th><th>Nilai</th></tr></thead><tbody>
                    <?php foreach (array_slice($m3_ar['terms'], 0, 50) as $i => $v): $idx = $i + 1; ?>
                        <tr><td><?= $idx ?></td><td>U<sub><?= $idx ?></sub></td><td><?= fmt($m3_ar['a']) ?> + (<?= $idx ?>−1)×<?= fmt($m3_ar['b']) ?></td><td><strong><?= fmt($v) ?></strong></td></tr>
                    <?php endforeach; ?>
                    <?php if (count($m3_ar['terms']) > 50): ?><tr><td colspan="4" style="color:var(--text-muted);font-style:italic;">... dan <?= count($m3_ar['terms']) - 50 ?> suku lainnya</td></tr><?php endif; ?>
                    </tbody></table>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2><i class="fas fa-list-ol"></i> Langkah Penyelesaian (Eliminasi & Substitusi)</h2></div>
            <div class="card-body">
                <?php foreach ($m3_ar['steps'] as $i => $st): ?>
                <div class="step">
                    <span class="step-num"><?= $i + 1 ?></span>
                    <?= $st ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- ========== MODEL 3 GEOMETRI ========== -->
    <?php if ($tab === 'geometri'): ?>
    <section class="tab-content active">
        <div class="grid-2">
            <!-- INPUT CARD -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-keyboard"></i> Input 2 Suku Sembarang Geometri</h2>
                </div>
                <div class="card-body">
                    <div class="formula-box" style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border-color:#fcd34d;">
                        <p class="formula-label" style="color:#d97706;">Metode Pembagian Rasio & Substitusi (Tanpa a & r)</p>
                        <p class="formula">r<sup>(q−p)</sup> = U<sub>q</sub> / U<sub>p</sub> &nbsp;&implies;&nbsp; r = (U<sub>q</sub> / U<sub>p</sub>)<sup>1/(q−p)</sup></p>
                        <p class="formula">a = U<sub>p</sub> / r<sup>(p−1)</sup></p>
                        <p class="formula" style="font-size:0.88rem;color:var(--text-muted);">
                            Menentukan nilai suku pertama (<code>a</code>) dan rasio (<code>r</code>) dari dua suku sembarang.
                        </p>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="mode" value="3">
                        <input type="hidden" name="tab" value="geometri">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="m3_ge_p">Suku ke- (p)</label>
                                <input type="number" name="m3_ge_p" id="m3_ge_p" placeholder="" min="1" step="1" required
                                       value="<?= htmlspecialchars($_POST['m3_ge_p'] ?? ($m3_ge['p'] ?? '')) ?>">
                            </div>
                            <div class="form-group">
                                <label for="m3_ge_Up">Nilai Suku ke-p (U<sub>p</sub>)</label>
                                <input type="number" name="m3_ge_Up" id="m3_ge_Up" placeholder="" step="any" required
                                       value="<?= htmlspecialchars($_POST['m3_ge_Up'] ?? ($m3_ge['Up'] ?? '')) ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="m3_ge_q">Suku ke- (q)</label>
                                <input type="number" name="m3_ge_q" id="m3_ge_q" placeholder="" min="1" step="1" required
                                       value="<?= htmlspecialchars($_POST['m3_ge_q'] ?? ($m3_ge['q'] ?? '')) ?>">
                            </div>
                            <div class="form-group">
                                <label for="m3_ge_Uq">Nilai Suku ke-q (U<sub>q</sub>)</label>
                                <input type="number" name="m3_ge_Uq" id="m3_ge_Uq" placeholder="" step="any" required
                                       value="<?= htmlspecialchars($_POST['m3_ge_Uq'] ?? ($m3_ge['Uq'] ?? '')) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="m3_ge_n">Hitung sampai Suku ke-n <small style="color:var(--text-muted);"></small></label>
                            <input type="number" name="m3_ge_n" id="m3_ge_n" placeholder="" min="1" step="1"
                                   value="<?= htmlspecialchars($_POST['m3_ge_n'] ?? ($m3_ge['n'] ?? '')) ?>">
                        </div>

                        <button type="submit" class="btn btn-primary btn-geo"><i class="fas fa-calculator"></i> Hitung Pembagian & Substitusi</button>
                        <a href="?mode=3&tab=geometri" class="btn btn-outline"><i class="fas fa-rotate-left"></i> Reset</a>
                    </form>
                </div>
            </div>

            <!-- RESULT CARD -->
            <div class="card">
                <div class="card-header"><h2><i class="fas fa-square-poll-vertical"></i> Hasil Geometri</h2></div>
                <div class="card-body">
                    <?php if ($m3_ge && isset($m3_ge['error'])): ?>
                        <div class="alert-box">
                            <i class="fas fa-circle-exclamation"></i>
                            <?= htmlspecialchars($m3_ge['error']) ?>
                        </div>
                    <?php elseif ($m3_ge && isset($m3_ge['terms'])): ?>
                        <div class="result-grid">
                            <div class="result-item highlight-geo"><div class="result-label">Suku Pertama (a) ★</div><div class="result-value"><?= fmt($m3_ge['a']) ?></div></div>
                            <div class="result-item highlight-geo"><div class="result-label">Rasio (r) ★</div><div class="result-value"><?= fmt($m3_ge['r']) ?></div></div>
                            <div class="result-item"><div class="result-label">Banyak Suku (n)</div><div class="result-value"><?= $m3_ge['n'] ?></div></div>
                            <div class="result-item"><div class="result-label">U<sub><?= $m3_ge['n'] ?></sub></div><div class="result-value"><?= fmt($m3_ge['Un']) ?></div></div>
                            <div class="result-item highlight-geo"><div class="result-label">S<sub><?= $m3_ge['n'] ?></sub></div><div class="result-value"><?= fmt($m3_ge['Sn']) ?></div></div>
                            <?php if ($m3_ge['konvergen']): ?>
                            <div class="result-item highlight-geo"><div class="result-label">S<sub>∞</sub></div><div class="result-value"><?= fmt($m3_ge['Sinf']) ?></div></div>
                            <?php else: ?>
                            <div class="result-item"><div class="result-label">S<sub>∞</sub></div><div class="result-value" style="font-size:1rem;color:var(--text-muted);">|r| ≥ 1, divergen</div></div>
                            <?php endif; ?>
                        </div>

                        <div class="formula-box" style="margin-top:14px;background:linear-gradient(135deg,#fffbeb,#fef3c7);border-color:#fcd34d;">
                            <p class="formula-label" style="color:#d97706;">Rumus Barisan Ditemukan</p>
                            <p class="formula">U<sub>n</sub> = <?= fmt($m3_ge['a']) ?> × <?= fmt($m3_ge['r']) ?><sup>(n−1)</sup></p>
                        </div>

                        <div class="sequence-preview"><strong>Barisan:</strong> <?= previewBarisan($m3_ge['terms']) ?></div>
                    <?php else: ?>
                        <div class="result-placeholder"><i class="fas fa-arrow-left"></i><p>Masukkan nilai 2 suku dan klik <strong>Hitung</strong></p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($m3_ge && isset($m3_ge['terms'])): ?>
        <div class="card visual-card">
            <div class="card-header"><h2><i class="fas fa-chart-bar"></i> Visualisasi Barisan</h2></div>
            <div class="card-body">
                <div class="chart-wrapper"><canvas id="m3-ge-chart"></canvas></div>
                <div class="table-wrapper">
                    <table class="table-geo"><thead><tr><th>n</th><th>U<sub>n</sub></th><th>Rumus</th><th>Nilai</th></tr></thead><tbody>
                    <?php foreach (array_slice($m3_ge['terms'], 0, 50) as $i => $v): $idx = $i + 1; ?>
                        <tr><td><?= $idx ?></td><td>U<sub><?= $idx ?></sub></td><td><?= fmt($m3_ge['a']) ?> × <?= fmt($m3_ge['r']) ?><sup><?= $idx - 1 ?></sup></td><td><strong><?= fmt($v) ?></strong></td></tr>
                    <?php endforeach; ?>
                    <?php if (count($m3_ge['terms']) > 50): ?><tr><td colspan="4" style="color:var(--text-muted);font-style:italic;">... dan <?= count($m3_ge['terms']) - 50 ?> suku lainnya</td></tr><?php endif; ?>
                    </tbody></table>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2><i class="fas fa-list-ol"></i> Langkah Penyelesaian (Pembagian & Substitusi)</h2></div>
            <div class="card-body">
                <?php foreach ($m3_ge['steps'] as $i => $st): ?>
                <div class="step step-geo">
                    <span class="step-num"><?= $i + 1 ?></span>
                    <?= $st ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php endif; /* end mode 3 */ ?>

    <!-- ============================================================
         MODEL 4 — SUKU TENGAH & SISIPAN BILANGAN
         ============================================================ -->
    <?php if ($mode === '4'): ?>

    <!-- Tab Aritmetika / Geometri -->
    <div class="tabs">
        <a href="?mode=4&tab=aritmetika&m4_type=<?= $m4_type ?>" class="tab <?= $tab === 'aritmetika' ? 'active' : '' ?>" data-tab="aritmetika">
            <i class="fas fa-plus-minus"></i> Aritmetika
        </a>
        <a href="?mode=4&tab=geometri&m4_type=<?= $m4_type ?>" class="tab <?= $tab === 'geometri' ? 'active' : '' ?>" data-tab="geometri">
            <i class="fas fa-xmark"></i> Geometri
        </a>
    </div>

    <!-- Subnav: Suku Tengah vs Sisipan -->
    <div class="subnav">
        <a href="?mode=4&tab=<?= $tab ?>&m4_type=tengah" class="subnav-btn <?= $m4_type === 'tengah' ? ($tab === 'geometri' ? 'active geo-active' : 'active') : '' ?>">
            <i class="fas fa-arrows-split-up-and-left"></i> Suku Tengah (Uₜ)
        </a>
        <a href="?mode=4&tab=<?= $tab ?>&m4_type=sisipan" class="subnav-btn <?= $m4_type === 'sisipan' ? ($tab === 'geometri' ? 'active geo-active' : 'active') : '' ?>">
            <i class="fas fa-plus"></i> Sisipan Bilangan (k)
        </a>
    </div>

    <!-- ========== MODE 4 ARITMETIKA ========== -->
    <?php if ($tab === 'aritmetika'): ?>
    <section class="tab-content active">
        <div class="grid-2">
            <!-- INPUT CARD -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-keyboard"></i>
                        <?= $m4_type === 'tengah' ? 'Hitung Suku Tengah Aritmetika' : 'Hitung Sisipan Bilangan Aritmetika' ?>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="formula-box">
                        <p class="formula-label">Rumus yang Digunakan</p>
                        <?php if ($m4_type === 'tengah'): ?>
                            <p class="formula">U<sub>t</sub> = (a + U<sub>n</sub>) / 2</p>
                            <p class="formula">t = (n + 1) / 2 &nbsp;|&nbsp; S<sub>n</sub> = n · U<sub>t</sub></p>
                            <p class="formula" style="font-size:0.85rem;color:var(--text-muted);">Hanya berlaku jika banyak suku (n) bilangan ganjil.</p>
                        <?php else: ?>
                            <p class="formula">b' = (y − x) / (k + 1)</p>
                            <p class="formula">n' = k + 2 &nbsp;|&nbsp; S'<sub>n'</sub> = <sup>n'</sup>/<sub>2</sub> · (x + y)</p>
                            <p class="formula" style="font-size:0.85rem;color:var(--text-muted);">Di antara bilangan x dan y disisipkan k buah bilangan.</p>
                        <?php endif; ?>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="mode" value="4">
                        <input type="hidden" name="tab" value="aritmetika">
                        <input type="hidden" name="m4_type" value="<?= $m4_type ?>">

                        <?php if ($m4_type === 'tengah'): ?>
                            <div class="form-group">
                                <label for="m4_ar_a">Suku Pertama (a)</label>
                                <input type="number" name="m4_ar_a" id="m4_ar_a" placeholder="" step="any" required
                                       value="<?= htmlspecialchars($_POST['m4_ar_a'] ?? ($m4_ar['a'] ?? '')) ?>">
                            </div>
                            <div class="form-group">
                                <label for="m4_ar_Un">Suku Terakhir (U<sub>n</sub>)</label>
                                <input type="number" name="m4_ar_Un" id="m4_ar_Un" placeholder="" step="any" required
                                       value="<?= htmlspecialchars($_POST['m4_ar_Un'] ?? ($m4_ar['Un'] ?? '')) ?>">
                            </div>
                            <div class="form-group">
                                <label for="m4_ar_n">Banyak Suku (n) <small style="color:var(--text-muted);"></small></label>
                                <input type="number" name="m4_ar_n" id="m4_ar_n" placeholder="" min="3" step="2"
                                       value="<?= htmlspecialchars($_POST['m4_ar_n'] ?? ($m4_ar['n'] ?? '')) ?>">
                            </div>
                        <?php else: ?>
                            <div class="form-group">
                                <label for="m4_ar_x">Bilangan Awal (x)</label>
                                <input type="number" name="m4_ar_x" id="m4_ar_x" placeholder="" step="any" required
                                       value="<?= htmlspecialchars($_POST['m4_ar_x'] ?? ($m4_ar['x'] ?? '')) ?>">
                            </div>
                            <div class="form-group">
                                <label for="m4_ar_y">Bilangan Akhir (y)</label>
                                <input type="number" name="m4_ar_y" id="m4_ar_y" placeholder="" step="any" required
                                       value="<?= htmlspecialchars($_POST['m4_ar_y'] ?? ($m4_ar['y'] ?? '')) ?>">
                            </div>
                            <div class="form-group">
                                <label for="m4_ar_k">Banyak Bilangan Disisipkan (k)</label>
                                <input type="number" name="m4_ar_k" id="m4_ar_k" placeholder="" min="1" step="1" required
                                       value="<?= htmlspecialchars($_POST['m4_ar_k'] ?? ($m4_ar['k'] ?? '')) ?>">
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-calculator"></i> Hitung</button>
                        <a href="?mode=4&tab=aritmetika&m4_type=<?= $m4_type ?>" class="btn btn-outline"><i class="fas fa-rotate-left"></i> Reset</a>
                    </form>
                </div>
            </div>

            <!-- RESULT CARD -->
            <div class="card">
                <div class="card-header"><h2><i class="fas fa-square-poll-vertical"></i> Hasil Analisis</h2></div>
                <div class="card-body">
                    <?php if ($m4_ar && isset($m4_ar['error'])): ?>
                        <div class="alert-box">
                            <i class="fas fa-circle-exclamation"></i>
                            <?= htmlspecialchars($m4_ar['error']) ?>
                        </div>
                    <?php elseif ($m4_ar): ?>
                        <div class="result-grid">
                            <?php if ($m4_type === 'tengah'): ?>
                                <div class="result-item highlight">
                                    <div class="result-label">Suku Tengah (Uₜ) ★</div>
                                    <div class="result-value"><?= fmt($m4_ar['Ut']) ?></div>
                                </div>
                                <?php if ($m4_ar['t'] !== null): ?>
                                <div class="result-item highlight">
                                    <div class="result-label">Letak Suku ke- (t)</div>
                                    <div class="result-value"><?= $m4_ar['t'] ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if ($m4_ar['Sn'] !== null): ?>
                                <div class="result-item">
                                    <div class="result-label">Jumlah Deret (S<sub><?= $m4_ar['n'] ?></sub>)</div>
                                    <div class="result-value"><?= fmt($m4_ar['Sn']) ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if ($m4_ar['b'] !== null): ?>
                                <div class="result-item">
                                    <div class="result-label">Beda (b)</div>
                                    <div class="result-value"><?= fmt($m4_ar['b']) ?></div>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="result-item highlight">
                                    <div class="result-label">Beda Baru (b') ★</div>
                                    <div class="result-value"><?= fmt($m4_ar['bBaru']) ?></div>
                                </div>
                                <div class="result-item">
                                    <div class="result-label">Banyak Suku Baru (n')</div>
                                    <div class="result-value"><?= $m4_ar['nBaru'] ?></div>
                                </div>
                                <div class="result-item highlight">
                                    <div class="result-label">Jumlah Deret Baru (S'<sub>n'</sub>)</div>
                                    <div class="result-value"><?= fmt($m4_ar['SnBaru']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (isset($m4_ar['terms']) && count($m4_ar['terms']) > 0): ?>
                            <div class="sequence-preview" style="margin-top:14px;">
                                <strong>Barisan Terbentuk:</strong> <?= previewBarisan($m4_ar['terms']) ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="result-placeholder"><i class="fas fa-arrow-left"></i><p>Masukkan nilai dan klik <strong>Hitung</strong></p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($m4_ar && isset($m4_ar['terms']) && count($m4_ar['terms']) > 0): ?>
        <div class="card visual-card">
            <div class="card-header"><h2><i class="fas fa-chart-bar"></i> Visualisasi Barisan</h2></div>
            <div class="card-body">
                <div class="chart-wrapper"><canvas id="m4-ar-chart"></canvas></div>
                <div class="table-wrapper">
                    <table><thead><tr><th>n</th><th>Suku</th><th>Nilai</th></tr></thead><tbody>
                    <?php foreach (array_slice($m4_ar['terms'], 0, 50) as $i => $v): $idx = $i + 1; ?>
                        <tr <?= (isset($m4_ar['t']) && $m4_ar['t'] == $idx) ? 'style="background:#ede9fe;font-weight:700;"' : '' ?>>
                            <td><?= $idx ?></td>
                            <td>U<sub><?= $idx ?></sub> <?= (isset($m4_ar['t']) && $m4_ar['t'] == $idx) ? '★ (Suku Tengah)' : '' ?></td>
                            <td><strong><?= fmt($v) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody></table>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2><i class="fas fa-list-ol"></i> Langkah Penyelesaian</h2></div>
            <div class="card-body">
                <?php foreach ($m4_ar['steps'] as $i => $st): ?>
                <div class="step">
                    <span class="step-num"><?= $i + 1 ?></span>
                    <?= $st ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- ========== MODE 4 GEOMETRI ========== -->
    <?php if ($tab === 'geometri'): ?>
    <section class="tab-content active">
        <div class="grid-2">
            <!-- INPUT CARD -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-keyboard"></i>
                        <?= $m4_type === 'tengah' ? 'Hitung Suku Tengah Geometri' : 'Hitung Sisipan Bilangan Geometri' ?>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="formula-box" style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border-color:#fcd34d;">
                        <p class="formula-label" style="color:#d97706;">Rumus yang Digunakan</p>
                        <?php if ($m4_type === 'tengah'): ?>
                            <p class="formula">U<sub>t</sub> = √(a · U<sub>n</sub>)</p>
                            <p class="formula">t = (n + 1) / 2</p>
                            <p class="formula" style="font-size:0.85rem;color:var(--text-muted);">Hanya berlaku jika banyak suku (n) bilangan ganjil.</p>
                        <?php else: ?>
                            <p class="formula">r' = <sup>(k+1)</sup>√(y / x)</p>
                            <p class="formula">n' = k + 2 &nbsp;|&nbsp; S'<sub>n'</sub> = x · ((r')<sup>n'</sup> − 1) / (r' − 1)</p>
                        <?php endif; ?>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="mode" value="4">
                        <input type="hidden" name="tab" value="geometri">
                        <input type="hidden" name="m4_type" value="<?= $m4_type ?>">

                        <?php if ($m4_type === 'tengah'): ?>
                            <div class="form-group">
                                <label for="m4_ge_a">Suku Pertama (a)</label>
                                <input type="number" name="m4_ge_a" id="m4_ge_a" placeholder="" step="any" required
                                       value="<?= htmlspecialchars($_POST['m4_ge_a'] ?? ($m4_ge['a'] ?? '')) ?>">
                            </div>
                            <div class="form-group">
                                <label for="m4_ge_Un">Suku Terakhir (U<sub>n</sub>)</label>
                                <input type="number" name="m4_ge_Un" id="m4_ge_Un" placeholder="" step="any" required
                                       value="<?= htmlspecialchars($_POST['m4_ge_Un'] ?? ($m4_ge['Un'] ?? '')) ?>">
                            </div>
                            <div class="form-group">
                                <label for="m4_ge_n">Banyak Suku (n) <small style="color:var(--text-muted);"></small></label>
                                <input type="number" name="m4_ge_n" id="m4_ge_n" placeholder="" min="3" step="2"
                                       value="<?= htmlspecialchars($_POST['m4_ge_n'] ?? ($m4_ge['n'] ?? '')) ?>">
                            </div>
                        <?php else: ?>
                            <div class="form-group">
                                <label for="m4_ge_x">Bilangan Awal (x)</label>
                                <input type="number" name="m4_ge_x" id="m4_ge_x" placeholder="" step="any" required
                                       value="<?= htmlspecialchars($_POST['m4_ge_x'] ?? ($m4_ge['x'] ?? '')) ?>">
                            </div>
                            <div class="form-group">
                                <label for="m4_ge_y">Bilangan Akhir (y)</label>
                                <input type="number" name="m4_ge_y" id="m4_ge_y" placeholder="" step="any" required
                                       value="<?= htmlspecialchars($_POST['m4_ge_y'] ?? ($m4_ge['y'] ?? '')) ?>">
                            </div>
                            <div class="form-group">
                                <label for="m4_ge_k">Banyak Bilangan Disisipkan (k)</label>
                                <input type="number" name="m4_ge_k" id="m4_ge_k" placeholder="" min="1" step="1" required
                                       value="<?= htmlspecialchars($_POST['m4_ge_k'] ?? ($m4_ge['k'] ?? '')) ?>">
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary btn-geo"><i class="fas fa-calculator"></i> Hitung</button>
                        <a href="?mode=4&tab=geometri&m4_type=<?= $m4_type ?>" class="btn btn-outline"><i class="fas fa-rotate-left"></i> Reset</a>
                    </form>
                </div>
            </div>

            <!-- RESULT CARD -->
            <div class="card">
                <div class="card-header"><h2><i class="fas fa-square-poll-vertical"></i> Hasil Analisis</h2></div>
                <div class="card-body">
                    <?php if ($m4_ge && isset($m4_ge['error'])): ?>
                        <div class="alert-box"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($m4_ge['error']) ?></div>
                    <?php elseif ($m4_ge): ?>
                        <div class="result-grid">
                            <?php if ($m4_type === 'tengah'): ?>
                                <div class="result-item highlight-geo">
                                    <div class="result-label">Suku Tengah (Uₜ) ★</div>
                                    <div class="result-value"><?= fmt($m4_ge['Ut']) ?></div>
                                </div>
                                <?php if ($m4_ge['t'] !== null): ?>
                                <div class="result-item highlight-geo">
                                    <div class="result-label">Letak Suku (t)</div>
                                    <div class="result-value"><?= $m4_ge['t'] ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if ($m4_ge['r'] !== null): ?>
                                <div class="result-item">
                                    <div class="result-label">Rasio (r)</div>
                                    <div class="result-value"><?= fmt($m4_ge['r']) ?></div>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="result-item highlight-geo">
                                    <div class="result-label">Rasio Baru (r') ★</div>
                                    <div class="result-value"><?= fmt($m4_ge['rBaru']) ?></div>
                                </div>
                                <div class="result-item">
                                    <div class="result-label">Banyak Suku Baru (n')</div>
                                    <div class="result-value"><?= $m4_ge['nBaru'] ?></div>
                                </div>
                                <div class="result-item highlight-geo">
                                    <div class="result-label">Jumlah Deret Baru (S'<sub>n'</sub>)</div>
                                    <div class="result-value"><?= fmt($m4_ge['SnBaru']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (isset($m4_ge['terms']) && count($m4_ge['terms']) > 0): ?>
                            <div class="sequence-preview" style="margin-top:14px;">
                                <strong>Barisan Terbentuk:</strong> <?= previewBarisan($m4_ge['terms']) ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="result-placeholder"><i class="fas fa-arrow-left"></i><p>Masukkan nilai dan klik <strong>Hitung</strong></p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($m4_ge && isset($m4_ge['terms']) && count($m4_ge['terms']) > 0): ?>
        <div class="card visual-card">
            <div class="card-header"><h2><i class="fas fa-chart-bar"></i> Visualisasi Barisan</h2></div>
            <div class="card-body">
                <div class="chart-wrapper"><canvas id="m4-ge-chart"></canvas></div>
                <div class="table-wrapper">
                    <table class="table-geo"><thead><tr><th>n</th><th>Suku</th><th>Nilai</th></tr></thead><tbody>
                    <?php foreach (array_slice($m4_ge['terms'], 0, 50) as $i => $v): $idx = $i + 1; ?>
                        <tr <?= (isset($m4_ge['t']) && $m4_ge['t'] == $idx) ? 'style="background:#fef3c7;font-weight:700;"' : '' ?>>
                            <td><?= $idx ?></td>
                            <td>U<sub><?= $idx ?></sub> <?= (isset($m4_ge['t']) && $m4_ge['t'] == $idx) ? '★ (Suku Tengah)' : '' ?></td>
                            <td><strong><?= fmt($v) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody></table>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2><i class="fas fa-list-ol"></i> Langkah Penyelesaian</h2></div>
            <div class="card-body">
                <?php foreach ($m4_ge['steps'] as $i => $st): ?>
                <div class="step step-geo">
                    <span class="step-num"><?= $i + 1 ?></span>
                    <?= $st ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php endif; /* end mode 4 */ ?>

    <!-- ============================================================
         MODEL 5 — DERET KE RUMUS SUKU (Sn -> Un)
         ============================================================ -->
    <?php if ($mode === '5'): ?>

    <section class="tab-content active">
        <div class="grid-2">
            <!-- INPUT CARD -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-square-root-variable"></i> Input Rumus Deret Aritmetika Sₙ = An² + Bn</h2>
                </div>
                <div class="card-body">
                    <div class="formula-box">
                        <p class="formula-label">Penurunan Rumus Sₙ &rarr; Uₙ</p>
                        <p class="formula">U<sub>n</sub> = S<sub>n</sub> − S<sub>n−1</sub></p>
                        <p class="formula">U<sub>n</sub> = 2An + (B − A) &nbsp;|&nbsp; b = 2A &nbsp;|&nbsp; a = A + B</p>
                        <p class="formula" style="font-size:0.85rem;color:var(--text-muted);">
                            Menentukan rumus suku ke-n dan nilai suku tertentu jika fungsi jumlah n suku pertama diketahui.
                        </p>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="mode" value="5">

                        <div class="form-group">
                            <label for="m5_A">Koefisien A (pada n²)</label>
                            <input type="number" name="m5_A" id="m5_A" placeholder="" step="any" required
                                   value="<?= htmlspecialchars($_POST['m5_A'] ?? ($m5['A'] ?? '')) ?>">
                        </div>
                        <div class="form-group">
                            <label for="m5_B">Koefisien B (pada n)</label>
                            <input type="number" name="m5_B" id="m5_B" placeholder="" step="any" required
                                   value="<?= htmlspecialchars($_POST['m5_B'] ?? ($m5['B'] ?? '')) ?>">
                        </div>
                        <div class="form-group">
                            <label for="m5_k">Hitung sampai suku ke-k <small style="color:var(--text-muted);"></small></label>
                            <input type="number" name="m5_k" id="m5_k" placeholder="" min="1" step="1"
                                   value="<?= htmlspecialchars($_POST['m5_k'] ?? '') ?>">
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-calculator"></i> Analisis Deret ke Suku</button>
                        <a href="?mode=5" class="btn btn-outline"><i class="fas fa-rotate-left"></i> Reset</a>
                    </form>
                </div>
            </div>

            <!-- RESULT CARD -->
            <div class="card">
                <div class="card-header"><h2><i class="fas fa-square-poll-vertical"></i> Hasil Penurunan Rumus</h2></div>
                <div class="card-body">
                    <?php if ($m5): ?>
                        <div class="formula-box" style="margin-bottom:16px;border-left:4px solid var(--primary);">
                            <p class="formula-label"><i class="fas fa-bullseye"></i> Rumus Suku ke-n (Jawaban Soal)</p>
                            <p class="formula" style="font-size:1.35rem;font-weight:700;color:var(--primary);">
                                U<sub>n</sub> = <?= fmt($m5['b']) ?>n <?= ($m5['c'] >= 0 ? '+ ' . fmt($m5['c']) : '− ' . fmt(abs($m5['c']))) ?>
                            </p>
                        </div>

                        <div class="result-grid">
                            <div class="result-item highlight">
                                <div class="result-label">Suku Pertama (a = S₁) ★</div>
                                <div class="result-value"><?= fmt($m5['a']) ?></div>
                            </div>
                            <div class="result-item highlight">
                                <div class="result-label">Beda (b = 2A) ★</div>
                                <div class="result-value"><?= fmt($m5['b']) ?></div>
                            </div>
                            <div class="result-item">
                                <div class="result-label">U<sub><?= $m5['k'] ?></sub></div>
                                <div class="result-value"><?= fmt($m5['Uk']) ?></div>
                            </div>
                            <div class="result-item highlight">
                                <div class="result-label">Jumlah <?= $m5['k'] ?> Suku (S<sub><?= $m5['k'] ?></sub>)</div>
                                <div class="result-value"><?= fmt($m5['Sk']) ?></div>
                            </div>
                        </div>

                        <div class="sequence-preview" style="margin-top:14px;">
                            <strong>Barisan:</strong> <?= previewBarisan($m5['terms']) ?>
                        </div>
                    <?php else: ?>
                        <div class="result-placeholder"><i class="fas fa-arrow-left"></i><p>Masukkan koefisien deret dan klik <strong>Analisis</strong></p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($m5 && isset($m5['terms'])): ?>
        <div class="card visual-card">
            <div class="card-header"><h2><i class="fas fa-chart-bar"></i> Visualisasi Barisan</h2></div>
            <div class="card-body">
                <div class="chart-wrapper"><canvas id="m5-chart"></canvas></div>
                <div class="table-wrapper">
                    <table><thead><tr><th>n</th><th>U<sub>n</sub></th><th>Rumus</th><th>Nilai</th></tr></thead><tbody>
                    <?php foreach (array_slice($m5['terms'], 0, 50) as $i => $v): $idx = $i + 1; ?>
                        <tr>
                            <td><?= $idx ?></td>
                            <td>U<sub><?= $idx ?></sub></td>
                            <td><?= fmt($m5['b']) ?>(<?= $idx ?>) <?= ($m5['c'] >= 0 ? '+ ' . fmt($m5['c']) : '− ' . fmt(abs($m5['c']))) ?></td>
                            <td><strong><?= fmt($v) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody></table>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2><i class="fas fa-list-ol"></i> Langkah Penyelesaian (Penjabaran Aljabar)</h2></div>
            <div class="card-body">
                <?php foreach ($m5['steps'] as $i => $st): ?>
                <div class="step">
                    <span class="step-num"><?= $i + 1 ?></span>
                    <?= $st ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <?php endif; /* end mode 5 */ ?>

</main>

<footer class="footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> <strong>SeqMath</strong> &mdash; Solusi Cerdas Barisan &amp; Deret Matematika | SMKN 1 Kertajati</p>
    </div>
</footer>

<!-- Core Client-side Scripts -->
<script>
// 1. KaTeX Math Auto-Rendering
document.addEventListener("DOMContentLoaded", function() {
    if (typeof renderMathInElement === 'function') {
        renderMathInElement(document.body, {
            delimiters: [
                {left: "$$", right: "$$", display: true},
                {left: "$", right: "$", display: false},
                {left: "\\(", right: "\\)", display: false},
                {left: "\\[", right: "\\]", display: true}
            ],
            throwOnError: false
        });
    }
    
    // Theme Icon Init
    const currentTheme = document.documentElement.getAttribute('data-theme');
    updateThemeIcon(currentTheme === 'dark');
    
    // History Render (Model 1)
    renderHistory();
});

// 2. Dark Mode Toggle
function updateThemeIcon(isDark) {
    const icon = document.getElementById('themeIcon');
    if (!icon) return;
    icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
}

const themeToggleBtn = document.getElementById('themeToggle');
if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        try { localStorage.setItem('seqmath_theme', newTheme); } catch(e){}
        updateThemeIcon(newTheme === 'dark');
    });
}

// 3. Quick Chips Autofill
function fillSequence(val, autoSubmit) {
    const input = document.getElementById('barisan');
    if (input) {
        input.value = val;
        input.focus();
        const feedback = document.getElementById('barisanFeedback');
        if (feedback) feedback.style.display = 'none';
        if (autoSubmit) {
            const form = document.getElementById('m1Form');
            if (form) form.submit();
        }
    }
}

// 4. Client-side Real-time Input Validation
function validateSequenceInput() {
    const input = document.getElementById('barisan');
    const feedback = document.getElementById('barisanFeedback');
    if (!input) return true;
    const val = input.value.trim();
    if (!val) {
        if (feedback) {
            feedback.innerHTML = '<i class="fas fa-circle-exclamation"></i> Harap masukkan suku-suku barisan terlebih dahulu.';
            feedback.style.display = 'block';
        }
        input.focus();
        return false;
    }
    const parts = val.split(/[,\s]+/).filter(Boolean);
    if (parts.length < 2) {
        if (feedback) {
            feedback.innerHTML = '<i class="fas fa-circle-exclamation"></i> Masukkan minimal 2 suku angka berturut-turut dipisahkan koma atau spasi.';
            feedback.style.display = 'block';
        }
        input.focus();
        return false;
    }
    for (const p of parts) {
        if (isNaN(parseFloat(p))) {
            if (feedback) {
                feedback.innerHTML = '<i class="fas fa-circle-exclamation"></i> Input mengandung karakter bukan angka: "<b>' + p + '</b>".';
                feedback.style.display = 'block';
            }
            input.focus();
            return false;
        }
    }
    if (feedback) feedback.style.display = 'none';
    return true;
}

const barisanInput = document.getElementById('barisan');
if (barisanInput) {
    barisanInput.addEventListener('input', function() {
        const feedback = document.getElementById('barisanFeedback');
        if (feedback) feedback.style.display = 'none';
    });
}

// 5. Salin Rumus & Toast Notice
function copyFormula(text) {
    if (!navigator.clipboard) {
        const temp = document.createElement('textarea');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        showToast('Rumus berhasil disalin!');
        return;
    }
    navigator.clipboard.writeText(text).then(() => {
        showToast('Rumus berhasil disalin!');
    }).catch(() => {
        showToast('Gagal menyalin rumus.');
    });
}

function showToast(msg) {
    let toast = document.getElementById('toastNotice');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toastNotice';
        toast.className = 'toast-notice';
        document.body.appendChild(toast);
    }
    toast.innerHTML = '<i class="fas fa-circle-check" style="color:var(--accent);"></i> ' + msg;
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 2500);
}

// 6. Toggle Worksheet Steps Accordion
function toggleWorksheetSteps() {
    const steps = document.getElementById('worksheetSteps');
    const btn = document.getElementById('toggleStepsBtn');
    if (!steps || !btn) return;
    const isHidden = steps.style.display === 'none';
    if (isHidden) {
        steps.style.display = 'flex';
        btn.innerHTML = '<i class="fas fa-eye-slash"></i> Sembunyikan Langkah';
    } else {
        steps.style.display = 'none';
        btn.innerHTML = '<i class="fas fa-eye"></i> Lihat Langkah';
    }
}

// 7. Riwayat Perhitungan (Model 1) di localStorage
function recordHistory(input, type, formula) {
    try {
        const key = 'seqmath_m1_history';
        let list = JSON.parse(localStorage.getItem(key) || '[]');
        list = list.filter(item => item.input !== input);
        list.unshift({
            input: input,
            type: type,
            formula: formula,
            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        });
        list = list.slice(0, 5);
        localStorage.setItem(key, JSON.stringify(list));
    } catch (e) {}
}

function renderHistory() {
    const container = document.getElementById('historyList');
    if (!container) return;
    try {
        const key = 'seqmath_m1_history';
        const list = JSON.parse(localStorage.getItem(key) || '[]');
        if (list.length === 0) {
            container.innerHTML = '<div style="font-size:0.82rem;color:var(--text-muted);font-style:italic;">Belum ada riwayat perhitungan.</div>';
            return;
        }
        container.innerHTML = list.map(item => `
            <div class="history-item" onclick="fillSequence('${item.input.replace(/'/g, "\\'")}', true)" title="Klik untuk muat ulang barisan">
                <div>
                    <span class="chip-badge" style="background:${item.type === 'Aritmetika' ? 'var(--arit)' : 'var(--geo)'};font-size:0.7rem;padding:2px 6px;border-radius:4px;color:#fff;margin-right:6px;">${item.type}</span>
                    <span class="history-meta">${item.input}</span>
                </div>
                <span class="history-time">${item.time}</span>
            </div>
        `).join('');
    } catch (e) {}
}

function clearHistory() {
    try {
        localStorage.removeItem('seqmath_m1_history');
        renderHistory();
        showToast('Riwayat perhitungan telah dihapus.');
    } catch (e) {}
}
</script>

<?php if ($m1 && !empty($m1['raw'])): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    recordHistory(
        <?= json_encode($m1['raw']) ?>,
        <?= json_encode($m1['isGeo'] && !$m1['isArit'] ? 'Geometri' : 'Aritmetika') ?>,
        <?= json_encode($m1['isGeo'] && !$m1['isArit'] ? "Un = {$m1['a']} · ({$m1['r']})^(n-1)" : "Un = {$m1['b']}n + " . ($m1['a'] - $m1['b'])) ?>
    );
});
</script>
<?php endif; ?>

<?php if ($hasChart): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function renderChart(id, terms, color) {
    const el = document.getElementById(id);
    if (!el) return;
    const max = 50;
    const d = terms.slice(0, max);
    const labels = d.map((_, i) => 'U' + (i + 1));
    const [r, g, b] = color;
    new Chart(el.getContext('2d'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                data: d,
                borderColor: `rgb(${r},${g},${b})`,
                backgroundColor: `rgba(${r},${g},${b},0.12)`,
                borderWidth: 2.5,
                fill: true,
                tension: 0.3,
                pointBackgroundColor: `rgb(${r},${g},${b})`,
                pointBorderColor: '#FFFFFF',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0F172A',
                    titleColor: '#FBBF24',
                    bodyColor: '#FFFFFF',
                    bodyFont: { weight: 'bold' },
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    grid: { color: 'rgba(148, 163, 184, 0.15)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
}

<?php if ($m1 && isset($m1['terms'])): ?>
    renderChart('m1-chart', <?= json_encode(array_values($m1['terms'])) ?>,
        <?= ($m1['isGeo'] && !$m1['isArit']) ? '[16, 185, 129]' : '[59, 130, 246]' ?>);
<?php endif; ?>

<?php if ($ar && isset($ar['terms'])): ?>
    renderChart('ar-chart', <?= json_encode(array_values($ar['terms'])) ?>, [59, 130, 246]);
<?php endif; ?>

<?php if ($ge && isset($ge['terms'])): ?>
    renderChart('ge-chart', <?= json_encode(array_values($ge['terms'])) ?>, [16, 185, 129]);
<?php endif; ?>

<?php if ($m3_ar && isset($m3_ar['terms'])): ?>
    renderChart('m3-ar-chart', <?= json_encode(array_values($m3_ar['terms'])) ?>, [59, 130, 246]);
<?php endif; ?>

<?php if ($m3_ge && isset($m3_ge['terms'])): ?>
    renderChart('m3-ge-chart', <?= json_encode(array_values($m3_ge['terms'])) ?>, [16, 185, 129]);
<?php endif; ?>

<?php if ($m4_ar && isset($m4_ar['terms']) && count($m4_ar['terms']) > 0): ?>
    renderChart('m4-ar-chart', <?= json_encode(array_values($m4_ar['terms'])) ?>, [59, 130, 246]);
<?php endif; ?>

<?php if ($m4_ge && isset($m4_ge['terms']) && count($m4_ge['terms']) > 0): ?>
    renderChart('m4-ge-chart', <?= json_encode(array_values($m4_ge['terms'])) ?>, [16, 185, 129]);
<?php endif; ?>

<?php if ($m5 && isset($m5['terms'])): ?>
    renderChart('m5-chart', <?= json_encode(array_values($m5['terms'])) ?>, [59, 130, 246]);
<?php endif; ?>
</script>
<?php endif; ?>

</body>
</html>
