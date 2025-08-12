<?php
declare(strict_types=1);

interface HeProvider {
  public function encryptCandidate(int $kandidatId): string; // -> base64 ciphertext
  public function decryptCandidate(string $ciphertextB64): int; // -> 1..5
  public function hashNik(string $nik): string;
}

final class BFVProvider implements HeProvider {
  public function __construct(private string $cliPath, private string $keydir){}

  public function encryptCandidate(int $kandidatId): string {
    $this->ensureKeys();
    [$code,$out,$err] = $this->runCli([$this->keydir,'encrypt',(string)$kandidatId], null);
    if($code!==0 || $out===''){ throw new RuntimeException("encrypt gagal: $err"); }
    return trim($out);
  }

  public function decryptCandidate(string $ciphertextB64): int {
    $this->ensureKeys();
    [$code,$out,$err] = $this->runCli([$this->keydir,'decrypt'], $ciphertextB64."\n");
    if($code!==0 || !preg_match('/^\d+$/', trim($out))){ throw new RuntimeException("decrypt gagal: $err"); }
    return (int)trim($out);
  }

  public function hashNik(string $nik): string {
    return hash_hmac('sha256', $nik, NIK_HASH_PEPPER);
  }

  /* ---------- helpers ---------- */

  private function ensureKeys(): void {
    if (!is_dir($this->keydir)) { mkdir($this->keydir, 0700, true); }
    $parms = $this->keydir.DIRECTORY_SEPARATOR.'parms.seal';
    $pub   = $this->keydir.DIRECTORY_SEPARATOR.'public.seal';
    $sec   = $this->keydir.DIRECTORY_SEPARATOR.'secret.seal';
    if (!is_file($parms) || !is_file($pub) || !is_file($sec)) {
      [$code,$out,$err] = $this->runCli([$this->keydir,'keygen'], null);
      if($code!==0){ throw new RuntimeException("keygen gagal: $err"); }
    }
  }

  private function runCli(array $args, ?string $stdin): array {
    $cmd = array_merge([$this->cliPath], $args);
    // Windows-friendly: gunakan proc_open + command string dengan quote aman
    $cmdStr = '';
    foreach ($cmd as $p) {
      // escapeshellarg aman untuk Windows & *nix
      $cmdStr .= (strlen($cmdStr)?' ':'') . escapeshellarg($p);
    }
    $descriptors = [
      0 => ['pipe','r'], // stdin
      1 => ['pipe','w'], // stdout
      2 => ['pipe','w'], // stderr
    ];
    $proc = proc_open($cmdStr, $descriptors, $pipes, __DIR__);
    if (!is_resource($proc)) { throw new RuntimeException('Gagal menjalankan he_bfv'); }

    if ($stdin !== null) { fwrite($pipes[0], $stdin); }
    fclose($pipes[0]);

    $out = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $code = proc_close($proc);
    return [$code, (string)$out, (string)$err];
  }
}

/** Factory */
function he(): HeProvider {
  return new BFVProvider(BFV_CLI, BFV_KEYDIR);
}
