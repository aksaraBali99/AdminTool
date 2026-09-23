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
TRUNCATE TABLE pph21_komponen;
TRUNCATE TABLE absensi_guru;
TRUNCATE TABLE payroll_guru;
TRUNCATE TABLE tagihan;
TRUNCATE TABLE pengeluaran;
TRUNCATE TABLE audit_log;

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

-- Tariffs chosen so that generate_payroll_guru's annualized subtotal
-- (subtotal * 12) lands in the SECOND pph21_komponen bracket below, not the
-- first/only one — proving the "pick the highest applicable bracket" query
-- actually discriminates between brackets, not just returning a trivial
-- single-row result. See tests/Financial/PayrollCalculationTest.php.
INSERT INTO pengajar (id_pengajar, nama, jk, no_hp, no_rek, tarif_per_jam_anak, tarif_per_jam_dewasa, biaya_transport) VALUES
(1, 'Test Teacher', 'L', '5550000010', '0000000000', 100000, 75000, 50000);

-- Two brackets: annualized income under 60,000,000 pays 5%, at/over pays 15%.
INSERT INTO pph21_komponen (id, nama_komponen, batas_bawah, batas_atas, persentase, status) VALUES
(1, 'Test Bracket 1', 0, 60000000, 5.00, 'aktif'),
(2, 'Test Bracket 2', 60000000, 250000000, 15.00, 'aktif');

-- 10 "Hadir" (present) days in June 2026, 5 hours/day of "anak" class, one
-- arrival each -> total_jam_anak=50, total_kedatangan=10. With the tariffs
-- above: honor_anak = 50 * 100000 = 5,000,000; transport = 10 * 50000 =
-- 500,000; subtotal = 5,500,000; annualized = 66,000,000, which lands in
-- Test Bracket 2 (15%) -> pph21_nominal = 825,000; gaji_bersih = 4,675,000.
INSERT INTO absensi_guru (id_guru, tanggal, jam_mulai, jam_selesai, total_jam, status_hadir, id_branch, tipe_kelas, tarif_per_jam, biaya_transport, jumlah_kedatangan, is_deleted) VALUES
(1, '2026-06-01', '09:00:00', '14:00:00', 5.00, 'Hadir', 1, 'anak', 100000, 50000, 1, 0),
(1, '2026-06-02', '09:00:00', '14:00:00', 5.00, 'Hadir', 1, 'anak', 100000, 50000, 1, 0),
(1, '2026-06-03', '09:00:00', '14:00:00', 5.00, 'Hadir', 1, 'anak', 100000, 50000, 1, 0),
(1, '2026-06-04', '09:00:00', '14:00:00', 5.00, 'Hadir', 1, 'anak', 100000, 50000, 1, 0),
(1, '2026-06-05', '09:00:00', '14:00:00', 5.00, 'Hadir', 1, 'anak', 100000, 50000, 1, 0),
(1, '2026-06-08', '09:00:00', '14:00:00', 5.00, 'Hadir', 1, 'anak', 100000, 50000, 1, 0),
(1, '2026-06-09', '09:00:00', '14:00:00', 5.00, 'Hadir', 1, 'anak', 100000, 50000, 1, 0),
(1, '2026-06-10', '09:00:00', '14:00:00', 5.00, 'Hadir', 1, 'anak', 100000, 50000, 1, 0),
(1, '2026-06-11', '09:00:00', '14:00:00', 5.00, 'Hadir', 1, 'anak', 100000, 50000, 1, 0),
(1, '2026-06-12', '09:00:00', '14:00:00', 5.00, 'Hadir', 1, 'anak', 100000, 50000, 1, 0),
-- Decoy rows that generate_payroll_guru's query must exclude:
(1, '2026-05-15', '09:00:00', '14:00:00', 5.00, 'Hadir', 1, 'anak', 100000, 50000, 1, 0), -- wrong month
(1, '2026-06-15', '09:00:00', '14:00:00', 5.00, 'Alpha', 1, 'anak', 100000, 50000, 1, 0), -- not Hadir
(1, '2026-06-16', '09:00:00', '14:00:00', 5.00, 'Hadir', 1, 'anak', 100000, 50000, 1, 1); -- is_deleted

-- Income (Paid, June 2026) and a decoy in a different month/status that
-- get_laba_rugi must exclude. See tests/Financial/LabaRugiTest.php. The
-- July row is a "decoy" only for that June-specific test - year-scoped
-- queries (get_rincian_pendapatan, get_pendapatan_per_kategori for 2026)
-- correctly include it too; see tests/Financial/RincianListingsTest.php.
INSERT INTO tagihan (id_peserta, bulan, tahun, jumlah, status_bayar, tipe, tgl_bayar) VALUES
(1, 6, 2026, 2000000, 'Paid', 'Biaya Kelas', '2026-06-05 10:00:00'),
(1, 6, 2026, 500000, 'Pending', 'Biaya Kelas', NULL), -- decoy: not Paid
(1, 7, 2026, 1000000, 'Paid', 'Biaya Kelas', '2026-07-05 10:00:00'); -- decoy: wrong month (for June-specific queries only)

-- Peserta #2's tagihan: the only ones with a peserta whose id_jenis_kelas is
-- actually set, so these are the only rows get_pendapatan_per_kategori(2026)
-- can see at all (see the comment on peserta #2 above). Decoys prove it
-- still filters by status_bayar and tahun even within that narrower set.
INSERT INTO tagihan (id_peserta, bulan, tahun, jumlah, status_bayar, tipe, tgl_bayar) VALUES
(2, 8, 2026, 300000, 'Paid', 'Biaya Kelas', '2026-08-05 10:00:00'),
(2, 8, 2026, 999999, 'Pending', 'Biaya Kelas', NULL), -- decoy: not Paid
(2, 5, 2025, 111111, 'Paid', 'Biaya Kelas', '2025-05-05 10:00:00'); -- decoy: wrong year

-- Expenses (June 2026) and a decoy in a different month.
INSERT INTO pengeluaran (tanggal, kategori, keterangan, jumlah) VALUES
('2026-06-15', 'Test Expense', 'Fixture expense', 700000),
('2026-07-15', 'Test Expense', 'Decoy expense, wrong month', 300000);

INSERT INTO jadwal_kelas (id, id_kelas, id_guru, hari, jam_mulai, jam_selesai, id_branch, tipe_kelas, is_aktif) VALUES
(1, 1, 1, 1, '09:00:00', '10:00:00', 1, 'anak', 1);

-- Deliberately NOT status='Registrasi Kelas' + status_siswa='Aktif' together,
-- so this row is visible to the Pub PII-leak test but invisible to Cron's
-- billing/WhatsApp loop (see safety note above).
INSERT INTO peserta (id_peserta, nama_ortu, no_hp, nama_anak, status, catatan, jk, is_aktif, tgl_non_aktif, status_siswa) VALUES
(1, 'Test Parent', '5550000099', 'Test Child', 'Jadwal Trial', '', 'L', 1, '2000-01-01', 'Aktif');

-- Second peserta, status_siswa='Trial' (distinct from peserta #1's
-- status_siswa 'Aktif' - note `status` and `status_siswa` are different
-- columns; #1's `status`='Trial' is a CRM lead-stage value, unrelated to
-- this). Serves two Dashboard tests: M_dashboard::get_total_murid_trial()
-- needs at least one Trial row plus a non-Trial decoy (peserta #1 is that
-- decoy) to prove the filter discriminates; M_dashboard::get_pendapatan_per_kategori()
-- needs a peserta with `id_jenis_kelas` actually set, since it's an INNER
-- JOIN to data_jenis_kelas and peserta #1 has NULL there (left untouched,
-- so #1's tagihan stays excluded from that one query, matching today's
-- real behavior).
-- Also NOT status='Registrasi Kelas' + status_siswa='Aktif' - same Cron
-- safety rule as #1 applies to every static fixture row.
INSERT INTO peserta (id_peserta, nama_ortu, no_hp, nama_anak, status, catatan, jk, is_aktif, tgl_non_aktif, status_siswa, id_jenis_kelas) VALUES
(2, 'Test Parent 2', '5550000098', 'Test Child 2', 'Jadwal Trial', '', 'P', 1, '2000-01-01', 'Aktif', 1);

INSERT INTO peserta_jadwal (id_jadwal_peserta, id_peserta, id_jadwal_pengajar, id_jadwal_kelas) VALUES
(1, 1, 1, 1);

SET FOREIGN_KEY_CHECKS = 1;
