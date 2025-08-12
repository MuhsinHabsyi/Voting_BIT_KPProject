<?php
declare(strict_types=1);

/**
 * HeProvider: antarmuka enkripsi kandidat & hash NIK
 */
interface HeProvider {
  public function encryptCandidate(int $kandidatId): string; // -> ciphertext (hex)
  public function decryptCandidate(string $ciphertext): int; // <- kandidatId
  public function hashNik(string $nik): string;              // deterministik untuk join
}

/**
 * Implementasi Paillier murni PHP (GMP).
 * - Kunci disimpan di PAILLIER_KEYDIR: paillier_pub.json & paillier_prv.json (hex).
 * - Ciphertext disimpan sebagai HEX string agar aman di TEXT.
 */
final class PaillierPhpProvider implements HeProvider {

  private \GMP $n;    // modulus
  private \GMP $n2;   // n^2
  private \GMP $g;    // biasanya n+1
  private \GMP $lam;  // λ = lcm(p-1,q-1)
  private \GMP $mu;   // μ = (L(g^λ mod n^2))^-1 mod n

  public function __construct(private string $keydir) {
    if (!extension_loaded('gmp')) {
      throw new \RuntimeException('Ekstensi GMP wajib aktif');
    }
    $this->ensureKeys();
  }

  public function encryptCandidate(int $kandidatId): string {
    if ($kandidatId < 1 || $kandidatId > 5) {
      throw new \InvalidArgumentException('kandidatId harus 1..5');
    }
    $m = gmp_init((string)$kandidatId, 10);

    // r acak di [1, n-1] dan gcd(r, n)=1
    do {
      $r = $this->randBelow($this->n);
    } while (gmp_cmp($r, 0) === 0 || gmp_cmp(gmp_gcd($r, $this->n), 1) !== 0);

    // c = g^m * r^n mod n^2
    $gm = gmp_powm($this->g, $m, $this->n2);
    $rn = gmp_powm($r, $this->n, $this->n2);
    $c  = gmp_mod(gmp_mul($gm, $rn), $this->n2);

    return gmp_strval($c, 16); // HEX
  }

  public function decryptCandidate(string $ciphertext): int {
    $c = gmp_init($ciphertext, 16);
    // u = c^λ mod n^2
    $u = gmp_powm($c, $this->lam, $this->n2);
    // L(u) = (u-1)/n
    $Lu = gmp_div_q(gmp_sub($u, 1), $this->n);
    // m = L(u)*μ mod n
    $m  = gmp_mod(gmp_mul($Lu, $this->mu), $this->n);
    return (int)gmp_strval($m, 10);
  }

  public function hashNik(string $nik): string {
    return hash_hmac('sha256', $nik, NIK_HASH_PEPPER);
  }

  /* ===================== Key Management ====================== */

  private function ensureKeys(): void {
    if (!is_dir($this->keydir)) {
      if (!mkdir($this->keydir, 0700, true) && !is_dir($this->keydir)) {
        throw new \RuntimeException('Gagal membuat keydir');
      }
    }
    $pubFile = $this->keydir.'/paillier_pub.json';
    $prvFile = $this->keydir.'/paillier_prv.json';

    if (is_file($pubFile) && is_file($prvFile)) {
      $pub = json_decode(file_get_contents($pubFile), true, 512, JSON_THROW_ON_ERROR);
      $prv = json_decode(file_get_contents($prvFile), true, 512, JSON_THROW_ON_ERROR);
      $this->loadFromHex($pub, $prv);
      return;
    }

    // Jika belum ada kunci, buat baru (1024-bit n → p,q ~512-bit)
    [$pub, $prv] = $this->generateKeys(1024);
    file_put_contents($pubFile, json_encode($pub, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    file_put_contents($prvFile, json_encode($prv, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    $this->loadFromHex($pub, $prv);
  }

  private function loadFromHex(array $pub, array $prv): void {
    $this->n   = gmp_init($pub['n'], 16);
    $this->n2  = gmp_pow($this->n, 2);
    $this->g   = gmp_init($pub['g'], 16);
    $this->lam = gmp_init($prv['lambda'], 16);
    $this->mu  = gmp_init($prv['mu'], 16);
  }

  private function generateKeys(int $nBits = 1024): array {
    $p = $this->randomPrime(intdiv($nBits, 2));
    do { $q = $this->randomPrime(intdiv($nBits, 2)); } while (gmp_cmp($p, $q) === 0);

    $n  = gmp_mul($p, $q);
    $g  = gmp_add($n, 1);
    $n2 = gmp_pow($n, 2);

    $p1 = gmp_sub($p, 1);
    $q1 = gmp_sub($q, 1);
    $lam = gmp_lcm($p1, $q1); // λ

    // μ = (L(g^λ mod n^2))^-1 mod n
    $u  = gmp_powm($g, $lam, $n2);
    $Lu = gmp_div_q(gmp_sub($u, 1), $n);
    $mu = gmp_invert($Lu, $n);
    if ($mu === false) {
      // Sangat jarang; regenerasi
      return $this->generateKeys($nBits);
    }

    return [
      ['n' => gmp_strval($n, 16), 'g' => gmp_strval($g, 16)],
      ['lambda' => gmp_strval($lam, 16), 'mu' => gmp_strval($mu, 16)]
    ];
  }

  private function randomPrime(int $bits): \GMP {
    // Bangkitkan kandidat bilangan ganjil dengan bit teratas = 1
    while (true) {
      $bytes = intdiv($bits + 7, 8);
      $rnd = random_bytes($bytes);
      // Set top bit & pastikan ganjil
      $rnd[0] = chr(ord($rnd[0]) | (1 << (($bits - 1) % 8)));
      $rnd[$bytes-1] = chr(ord($rnd[$bytes-1]) | 1);
      $n = gmp_import($rnd, 1, GMP_MSW_FIRST | GMP_BIG_ENDIAN);
      if (gmp_prob_prime($n, 25) > 0) return $n;
    }
  }

  private function randBelow(\GMP $mod): \GMP {
    $bits = strlen(gmp_export($mod, 1, GMP_MSW_FIRST | GMP_BIG_ENDIAN)) * 8;
    do {
      $bytes = intdiv($bits + 7, 8);
      $rnd = gmp_import(random_bytes($bytes), 1, GMP_MSW_FIRST | GMP_BIG_ENDIAN);
      $r = gmp_mod($rnd, $mod);
    } while (gmp_cmp($r, 0) === 0);
    return $r;
  }
}

/** Factory tunggal yang dipakai app */
function he(): HeProvider {
  return new PaillierPhpProvider(PAILLIER_KEYDIR);
}
