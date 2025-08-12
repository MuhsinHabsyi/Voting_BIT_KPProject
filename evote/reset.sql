-- OPSI 1 (disarankan): TRUNCATE (cepat) + reset AUTO_INCREMENT
-- Catatan: TRUNCATE sudah reset ke 1, ALTER di bawah hanya untuk memastikan.
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE tabel_plain;
TRUNCATE TABLE tabel_encrypted;
TRUNCATE TABLE tabel_log_verifikasi;

ALTER TABLE tabel_plain            AUTO_INCREMENT = 1;
ALTER TABLE tabel_encrypted        AUTO_INCREMENT = 1;
ALTER TABLE tabel_log_verifikasi   AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

