<?php
require 'config.php'; require 'crypto.php';
$bfv = he();
$ct = $bfv->encryptCandidate(3);
echo "CT length = ".strlen($ct).PHP_EOL;
echo "dec -> ".$bfv->decryptCandidate($ct).PHP_EOL;
