-- 20261009_01_add_teacher_and_session_to_absensi_siswa.sql
-- Why: Revamp student attendance (add teacher dropdown, sessions count, and sick leave)
-- Backward compatible: yes (new columns are nullable/have defaults)

CREATE TABLE schema_migrations (
    `filename` VARCHAR(255) PRIMARY KEY,
    applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE absensi_siswa 
ADD COLUMN id_guru INT(11) NULL AFTER tanggal;

ALTER TABLE absensi_siswa 
ADD COLUMN jumlah_sesi INT(11) DEFAULT 1 AFTER id_guru;

ALTER TABLE absensi_siswa 
MODIFY COLUMN status_hadir ENUM('Hadir','Izin','Sakit','Alpha') NOT NULL;

INSERT INTO schema_migrations (filename) VALUES ('20261009_01_add_teacher_and_session_to_absensi_siswa.sql');

-- Rollback
-- ALTER TABLE absensi_siswa MODIFY COLUMN status_hadir ENUM('Hadir','Izin','Alpha') NOT NULL;
-- ALTER TABLE absensi_siswa DROP COLUMN jumlah_sesi;
-- ALTER TABLE absensi_siswa DROP COLUMN id_guru;
-- DELETE FROM schema_migrations WHERE filename = '20261009_01_add_teacher_and_session_to_absensi_siswa.sql';