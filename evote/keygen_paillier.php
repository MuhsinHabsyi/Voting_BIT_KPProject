<?php
declare(strict_types=1);
require __DIR__.'/config.php';
require __DIR__.'/crypto.php';

$prov = he(); // konstruksi sekaligus generate/simpan kunci jika belum ada
echo "Paillier keys ready in: ".PAILLIER_KEYDIR.PHP_EOL;
