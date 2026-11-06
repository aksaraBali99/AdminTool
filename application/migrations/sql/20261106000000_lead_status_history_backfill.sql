-- One history row, dated input_at, for each lead currently at Jadwal Trial or
-- Placement Test that has no history row for that status. Safe to re-run.
-- Run by Migration_Lead_status_history_backfill and by
-- tests/Financial/LeadStatusHistoryTest.php.
INSERT INTO lead_status_history (id_peserta, status_lama, status_baru, tgl_update, updated_by)
SELECT p.id_peserta, NULL, p.status, p.input_at, NULL
  FROM peserta p
 WHERE p.status IN ('Jadwal Trial', 'Placement Test')
   AND NOT EXISTS (
       SELECT 1 FROM lead_status_history h
        WHERE h.id_peserta = p.id_peserta
          AND h.status_baru = p.status
   )
