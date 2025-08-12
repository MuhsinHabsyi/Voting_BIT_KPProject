<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

$user = 'root';
$pass = 'root';
$db = 'kp_bfv';
$host = 'localhost';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$db  = new PDO($dsn, $user, $pass, [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Pepper untuk hash NIK (join terenkripsi <-> plain)
const NIK_HASH_PEPPER = 'PepperHAHA';

// Folder simpan kunci Paillier
const PAILLIER_KEYDIR = __DIR__ . '/keys';
// Path biner he_bfv dan folder kunci (buat jika belum ada)
const BFV_CLI   = __DIR__ . DIRECTORY_SEPARATOR . 'he-bfv' . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'he_bfv' ;
const BFV_KEYDIR = __DIR__ . DIRECTORY_SEPARATOR . 'keys_bfv';

