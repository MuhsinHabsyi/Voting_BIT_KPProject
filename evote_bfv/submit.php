<?php
declare(strict_types=1);
require __DIR__.'/config.php';
require __DIR__.'/crypto.php';

$nik = isset($_POST['nik']) ? (string)$_POST['nik'] : '';
$nik = mb_substr(trim($nik), 0, 16); // jaga lebar kolom, tetap "bebas"
$kandidat = isset($_POST['kandidat']) ? (int)$_POST['kandidat'] : 0;
if ($kandidat < 1 || $kandidat > 5) { http_response_code(400); exit('Kandidat invalid'); }

$waktu = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s.u');

$provider = he();
$nikHash  = $provider->hashNik($nik);
$cipher   = $provider->encryptCandidate($kandidat);

// var_dump($cipher, $nikHash); die();

try {
  $db->beginTransaction();

  $stmt = $db->prepare("INSERT INTO tabel_plain (nik, kandidat_id, waktu_vote) VALUES (?,?,?)");
  $stmt->execute([$nik, $kandidat, $waktu]);

  $stmt2 = $db->prepare("INSERT INTO tabel_encrypted (nik_hash, kandidat_enc, waktu_vote) VALUES (?,?,?)");
  $stmt2->execute([$nikHash, $cipher, $waktu]);

  $db->commit();
} catch (Throwable $e) {
  if ($db->inTransaction()) $db->rollBack();
  http_response_code(500);
  exit('Gagal menyimpan: '.$e->getMessage());
}
?>
<!doctype html><meta charset="utf-8">
<style>body{font-family:system-ui;padding:40px}a{display:inline-block;margin-top:20px}</style>
<h3>Terima kasih, suara Anda tercatat.</h3>
<a href="index.php">Kembali</a> · <a href="admin.php">Admin/Dashboard</a>
