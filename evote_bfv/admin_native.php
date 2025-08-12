<?php
declare(strict_types=1);
require __DIR__.'/config.php';
$totPlain = (int)$db->query("SELECT COUNT(*) FROM tabel_plain")->fetchColumn();
$totEnc   = (int)$db->query("SELECT COUNT(*) FROM tabel_encrypted")->fetchColumn();
$aggPlain = $db->query("SELECT kandidat_id, COUNT(*) c FROM tabel_plain GROUP BY kandidat_id")->fetchAll();
$aggEnc   = $db->query("SELECT 1 k,0 c UNION SELECT 2,0 UNION SELECT 3,0 UNION SELECT 4,0 UNION SELECT 5,0")->fetchAll(PDO::FETCH_KEY_PAIR);

$tmp = [1=>0,2=>0,3=>0,4=>0,5=>0];
foreach ($db->query("SELECT k.cand, COUNT(*) c FROM (SELECT 1 cand UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) k LEFT JOIN (SELECT kandidat_enc FROM tabel_encrypted) e ON 1=1") as $r) {}
?>

<!doctype html>
<meta charset="utf-8"><title>Admin E-Voting</title>
<style>
 body{font-family:system-ui;padding:32px;background:#f8fafc}
 .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
 .card{background:#fff;border-radius:16px;box-shadow:0 8px 20px rgba(0,0,0,.06);padding:18px}
 table{width:100%;border-collapse:collapse}
 th,td{padding:8px;border-bottom:1px solid #e5e7eb;text-align:left}
 .btn{display:inline-block;padding:10px 14px;border-radius:10px;background:#111827;color:#fff;text-decoration:none}
</style>
<h2>Dashboard</h2>
<div class="grid">
  <div class="card"><div>Total (plain): <b><?= $totPlain ?></b></div></div>
  <div class="card"><div>Total (encrypted): <b><?= $totEnc ?></b></div></div>
  <div class="card"><a class="btn" href="verify.php" target="_blank">Jalankan Verifikasi</a></div>
</div>

<div class="card" style="margin-top:16px">
  <h3>Log Verifikasi Terbaru</h3>
  <table>
    <thead><tr><th>waktu_cek</th><th>NIK</th><th>plain</th><th>decrypt</th><th>status</th></tr></thead>
    <tbody>
    <?php foreach ($db->query("SELECT waktu_cek, nik, hasil_plain, hasil_decrypt, status FROM tabel_log_verifikasi ORDER BY id DESC LIMIT 50") as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['waktu_cek']) ?></td>
        <td><?= htmlspecialchars($r['nik']) ?></td>
        <td><?= htmlspecialchars($r['hasil_plain']) ?></td>
        <td><?= htmlspecialchars($r['hasil_decrypt']) ?></td>
        <td><?= htmlspecialchars($r['status']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
