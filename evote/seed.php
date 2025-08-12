<?php
declare(strict_types=1);
require __DIR__.'/config.php';

$n = isset($_GET['n']) ? max(1, (int)$_GET['n']) : 1000;
$ch = curl_init();
for ($i=0;$i<$n;$i++) {
  $nik = str_pad((string)random_int(0, 9999999999999999), 16, '0', STR_PAD_LEFT);
  $k   = random_int(1,5);
  $post = http_build_query(['nik'=>$nik, 'kandidat'=>$k]);
  curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/evote/submit.php',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $post,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5
  ]);
  curl_exec($ch);
}
curl_close($ch);
echo "Inserted $n votes\n";
