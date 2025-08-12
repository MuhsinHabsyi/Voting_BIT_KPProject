<?php
declare(strict_types=1);
require __DIR__.'/config.php';

// Ringkasan cepat
$totPlain = (int)$db->query("SELECT COUNT(*) FROM tabel_plain")->fetchColumn();
$totEnc   = (int)$db->query("SELECT COUNT(*) FROM tabel_encrypted")->fetchColumn();
$aggPlain = $db->query("SELECT kandidat_id, COUNT(*) c FROM tabel_plain GROUP BY kandidat_id ORDER BY kandidat_id")->fetchAll();

// Hitung akurasi keseluruhan dari tabel_log_verifikasi
$statStmt = $db->query("
  SELECT
    SUM(CASE WHEN status = 'match' THEN 1 ELSE 0 END) AS match_cnt,
    SUM(CASE WHEN status = 'mismatch' THEN 1 ELSE 0 END) AS mismatch_cnt,
    COUNT(*) AS total,
    MAX(waktu_cek) AS last_time
  FROM tabel_log_verifikasi
");
$stat = $statStmt->fetch();

$accText = 'Belum diverifikasi';
$detailText = '';
if (!empty($stat['total']) && (int)$stat['total'] > 0) {
  $acc = ((int)$stat['match_cnt'] / (int)$stat['total']) * 100;
  $accText = number_format($acc, 2) . '%';
  $detailText = sprintf(
    '(%d match / %d mismatch · total %d log%s)',
    (int)$stat['match_cnt'],
    (int)$stat['mismatch_cnt'],
    (int)$stat['total'],
    $stat['last_time'] ? ', terakhir verifikasi: '.$stat['last_time'] : ''
  );
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Admin E-Voting</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root{font-family:system-ui, Arial, sans-serif}
    body{margin:0;background:#f8fafc;padding:24px}
    .grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:16px}
    .card{background:#fff;border-radius:16px;box-shadow:0 8px 20px rgba(0,0,0,.06);padding:18px}
    .btn{display:inline-block;padding:10px 14px;border-radius:10px;background:#111827;color:#fff;text-decoration:none}
    .muted{color:#6b7280}
    table.dataTable tbody tr:hover{background:#f3f4f6}
    .kpi{font-size:22px;font-weight:700}
    .kpi-sub{font-size:12px;color:#6b7280;margin-top:4px}
  </style>

  <!-- jQuery + DataTables + Buttons (CDN) -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
</head>
<body>

  <h2 style="margin:0 0 12px 0">Dashboard</h2>
  <div class="grid">
    <div class="card">
      <div>Total (plain)</div>
      <div class="kpi"><?= $totPlain ?></div>
    </div>
    <div class="card">
      <div>Total (encrypted)</div>
      <div class="kpi"><?= $totEnc ?></div>
    </div>
    <div class="card">
      <div>Persentase Akurasi (Keseluruhan)</div>
      <div class="kpi"><?= htmlspecialchars($accText) ?></div>
      <?php if ($detailText): ?>
        <div class="kpi-sub"><?= htmlspecialchars($detailText) ?></div>
      <?php else: ?>
        <div class="kpi-sub">Jalankan verifikasi untuk melihat akurasi.</div>
      <?php endif; ?>
    </div>
    <div class="card">
      <a class="btn" href="verify.php" target="_blank">Jalankan Verifikasi</a>
      <div class="muted" style="margin-top:6px">Hasil verifikasi & log mismatch akan muncul di tabel di bawah.</div>
    </div>
  </div>

  <div class="card" style="margin-bottom:16px">
    <h3 style="margin:0 0 10px 0">Agregat Suara (Plain)</h3>
    <table style="width:100%;border-collapse:collapse">
      <thead><tr><th style="text-align:left;padding:6px 0">Kandidat</th><th style="text-align:left;padding:6px 0">Suara</th></tr></thead>
      <tbody>
        <?php foreach ($aggPlain as $r): ?>
          <tr>
            <td style="padding:6px 0">Kandidat <?= $r['kandidat_id'] ?></td>
            <td style="padding:6px 0"><?= $r['c'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <h3 style="margin:0 0 10px 0">Log Verifikasi</h3>
    <table id="logTable" class="display" style="width:100%">
      <thead>
        <tr>
          <th>Waktu Cek</th>
          <th>NIK</th>
          <th>Plain</th>
          <th>Decrypt</th>
          <th>Status</th>
        </tr>
      </thead>
    </table>
    <div class="muted" style="margin-top:8px">
      Tip: gunakan kolom pencarian untuk filter NIK/status. Tombol ekspor tersedia di atas tabel.
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script>
    $(function(){
      $('#logTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: 'admin_data.php', type: 'POST' },
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10,25,50,100,-1],[10,25,50,100,'Semua']],
        columns: [
          { data: 'waktu_cek' },
          { data: 'nik' },
          { data: 'hasil_plain' },
          { data: 'hasil_decrypt' },
          { data: 'status' }
        ],
        dom: 'Bfrtip',
        buttons: [
          { extend: 'csv', title: 'log_verifikasi' },
          { extend: 'excel', title: 'log_verifikasi' },
          { extend: 'print', title: 'Log Verifikasi' }
        ],
        language: {
          url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
        }
      });
    });
  </script>
</body>
</html>
