<?php
declare(strict_types=1);
require __DIR__.'/config.php';

// DataTables params
$draw   = isset($_POST['draw'])   ? (int)$_POST['draw']   : 0;
$start  = isset($_POST['start'])  ? (int)$_POST['start']  : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
$search = isset($_POST['search']['value']) ? trim((string)$_POST['search']['value']) : '';
$orderCol = $_POST['order'][0]['column'] ?? 0;
$orderDir = $_POST['order'][0]['dir'] ?? 'desc';

// Whitelist kolom untuk ORDER BY
$columns = ['waktu_cek','nik','hasil_plain','hasil_decrypt','status'];
$colIdx  = is_numeric($orderCol) ? (int)$orderCol : 0;
$colIdx  = ($colIdx >=0 && $colIdx < count($columns)) ? $colIdx : 0;
$dir     = strtolower($orderDir) === 'asc' ? 'ASC' : 'DESC';
$orderBy = $columns[$colIdx] . ' ' . $dir;

// Hitung total
$total = (int)$db->query("SELECT COUNT(*) FROM tabel_log_verifikasi")->fetchColumn();

// Build WHERE untuk pencarian global
$where = '';
$params = [];
if ($search !== '') {
  $where = "WHERE (nik LIKE :s OR hasil_plain LIKE :s OR hasil_decrypt LIKE :s OR status LIKE :s OR waktu_cek LIKE :s)";
  $params[':s'] = '%'.$search.'%';
}

// Hitung filtered
$sqlCountFiltered = "SELECT COUNT(*) FROM tabel_log_verifikasi " . $where;
$stmt = $db->prepare($sqlCountFiltered);
$stmt->execute($params);
$filtered = (int)$stmt->fetchColumn();

// Data rows
$sql = "SELECT waktu_cek, nik, hasil_plain, hasil_decrypt, status
        FROM tabel_log_verifikasi
        $where
        ORDER BY $orderBy";

if ($length !== -1) {
  // Paging normal
  $sql .= " LIMIT :len OFFSET :off";
}

$stmt = $db->prepare($sql);
foreach ($params as $k=>$v) {
  $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
if ($length !== -1) {
  $stmt->bindValue(':len', $length, PDO::PARAM_INT);
  $stmt->bindValue(':off', $start, PDO::PARAM_INT);
}
$stmt->execute();

$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $data[] = [
    'waktu_cek'     => $row['waktu_cek'],
    'nik'           => $row['nik'],
    'hasil_plain'   => $row['hasil_plain'],
    'hasil_decrypt' => $row['hasil_decrypt'],
    'status'        => $row['status'],
  ];
}

// Response
header('Content-Type: application/json');
echo json_encode([
  'draw' => $draw,
  'recordsTotal' => $total,
  'recordsFiltered' => $filtered,
  'data' => $data
], JSON_UNESCAPED_UNICODE);
