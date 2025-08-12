<?php
declare(strict_types=1);
require __DIR__.'/config.php';
require __DIR__.'/crypto.php';

$provider = he();
$now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s.u');

// 1) Hitung agregat dari plain
$plainAgg = [1=>0,2=>0,3=>0,4=>0,5=>0];
foreach ($db->query("SELECT kandidat_id, COUNT(*) c FROM tabel_plain GROUP BY kandidat_id") as $r) {
  $plainAgg[(int)$r['kandidat_id']] = (int)$r['c'];
}

// 2) Decrypt semua encrypted & hitung agregat
$encAgg = [1=>0,2=>0,3=>0,4=>0,5=>0];
$rows = $db->query("SELECT id, nik_hash, kandidat_enc, waktu_vote FROM tabel_encrypted");
$encMap = []; // key: nik_hash|waktu_vote -> kandidat_decrypt
foreach ($rows as $r) {
  $k = $provider->decryptCandidate($r['kandidat_enc']);
  if ($k >= 1 && $k <= 5) $encAgg[$k]++;
  $encMap[$r['nik_hash'].'|'.$r['waktu_vote']] = $k;
}

// 3) Row-wise compare (join via nik_hash + waktu_vote)
$plainRows = $db->query("SELECT nik, kandidat_id, waktu_vote FROM tabel_plain");
$insertLog = $db->prepare("INSERT INTO tabel_log_verifikasi (nik, hasil_plain, hasil_decrypt, status, waktu_cek) VALUES (?,?,?,?,?)");

$mismatchCount = 0; $matchCount = 0;
foreach ($plainRows as $p) {
  $key = hash_hmac('sha256', $p['nik'], NIK_HASH_PEPPER) . '|' . $p['waktu_vote'];
  $dec = $encMap[$key] ?? null;
  $status = ($dec === (int)$p['kandidat_id']) ? 'match' : 'mismatch';
  if ($status === 'mismatch') $mismatchCount++; else $matchCount++;
  $insertLog->execute([
    $p['nik'],
    (string)$p['kandidat_id'],
    ($dec === null ? 'NULL' : (string)$dec),
    $status,
    $now
  ]);
}

// 4) Status akhir
$allSame = ($plainAgg == $encAgg) && ($mismatchCount === 0);
header('Content-Type: application/json');
echo json_encode([
  'total_plain' => array_sum($plainAgg),
  'total_enc'   => array_sum($encAgg),
  'agg_plain'   => $plainAgg,
  'agg_enc'     => $encAgg,
  'match'       => $matchCount,
  'mismatch'    => $mismatchCount,
  'status'      => $allSame ? 'Akurat' : 'Ada Perbedaan'
], JSON_PRETTY_PRINT);
