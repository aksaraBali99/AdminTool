-- Seed data for the `admintool_test` database ONLY.
--
-- SAFETY: every value here is synthetic/fake by design. Never point this
-- file (or the test suite that consumes it) at a database containing real
-- user data. In particular:
--   * `no_hp` values use the reserved 555 fake-number pattern.
--   * `token_wa` is an obviously invalid placeholder, and no seeded
--     `peserta` row satisfies Cron's `get_peserta_aktif()` filter
--     (status = 'Registrasi Kelas' AND status_siswa = 'Aktif'), so even
--     if a test accidentally hits the vulnerable Cron endpoint, its
--     per-student loop body (DB insert + outbound WhatsApp API call)
--     never executes.
--   * All test-account passwords are the well-known fixture value
--     `TestPass123!` — never reuse it anywhere real.

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE user;
TRUNCATE TABLE data_konfig;
TRUNCATE TABLE peserta;
TRUNCATE TABLE peserta_jadwal;
TRUNCATE TABLE jadwal_kelas;
TRUNCATE TABLE pengajar;
TRUNCATE TABLE data_jenis_kelas;
TRUNCATE TABLE data_branch;

-- Test accounts, one per role. Password for all three: TestPass123!
INSERT INTO user (id_user, username, password, jabatan, nama, jk, usia, no_hp) VALUES
(1, 'test_superadmin', '$2y$10$HVXT1Uy83kGgAkGeyzvYleKHgJnKbD//2YA5lVUurjJs2GoE4T43a', 'superadmin', 'Test Superadmin', 'L', 30, '5550000001'),
(2, 'test_admin',      '$2y$10$HVXT1Uy83kGgAkGeyzvYleKHgJnKbD//2YA5lVUurjJs2GoE4T43a', 'admin',      'Test Admin',      'L', 30, '5550000002'),
(3, 'test_finance',    '$2y$10$HVXT1Uy83kGgAkGeyzvYleKHgJnKbD//2YA5lVUurjJs2GoE4T43a', 'finance',    'Test Finance',    'P', 30, '5550000003');

INSERT INTO data_konfig (id_konfig, nama_apk, token_wa, logo) VALUES
(0, 'AdminTool Test', 'TEST_INVALID_TOKEN_DO_NOT_USE', '');

INSERT INTO data_branch (id_branch, nama_branch, alamat, status) VALUES
(1, 'Test Branch', 'Test Address', 'aktif');

INSERT INTO data_jenis_kelas (id_jenis_kelas, nama_kelas, gender, usia, tipe, biaya, biaya_regis, biaya_buku, nama_buku) VALUES
(1, 'Test Class', 'all', '5-12', 'anak', 500000, 100000, 50000, 'Test Book');

INSERT INTO pengajar (id_pengajar, nama, jk, no_hp, no_rek, tarif_per_jam_anak, tarif_per_jam_dewasa, biaya_transport) VALUES
(1, 'Test Teacher', 'L', '5550000010', '0000000000', 50000, 75000, 25000);

INSERT INTO jadwal_kelas (id, id_kelas, id_guru, hari, jam_mulai, jam_selesai, id_branch, tipe_kelas, is_aktif) VALUES
(1, 1, 1, 1, '09:00:00', '10:00:00', 1, 'anak', 1);

-- Deliberately NOT status='Registrasi Kelas' + status_siswa='Aktif' together,
-- so this row is visible to the Pub PII-leak test but invisible to Cron's
-- billing/WhatsApp loop (see safety note above).
INSERT INTO peserta (id_peserta, nama_ortu, no_hp, nama_anak, status, catatan, jk, is_aktif, tgl_non_aktif, status_siswa) VALUES
(1, 'Test Parent', '5550000099', 'Test Child', 'Trial', '', 'L', 1, '2000-01-01', 'Aktif');

INSERT INTO peserta_jadwal (id_jadwal_peserta, id_peserta, id_jadwal_pengajar, id_jadwal_kelas) VALUES
(1, 1, 1, 1);

SET FOREIGN_KEY_CHECKS = 1;
