import { test, expect, Page } from '@playwright/test';

// Student attendance (task #12), driven through the real "Input Absensi"
// form on Siswa/detail. tests/Siswa/AbsensiSiswaTest.php covers the server
// rules over HTTP; this covers what only a browser shows: the form fields,
// the table, the rekap boxes, the pop-up messages, and that a note is shown
// as text rather than run as HTML.
//
// Uses seeded peserta #1 ('Test Child') and pengajar #1 ('Test Teacher').
// The seed empties absensi_siswa on every run (tests/serve-test-app.sh
// reseeds), and the tests run in order because they share that student.
// Dates are pinned to March 2026 so the monthly quota never depends on today.

const STUDENT_URL = 'Siswa/detail/1';
const XSS_NOTE = '<img src=x onerror="window.__xss = 1">';

test.describe.configure({ mode: 'serial' });

async function login(page: Page) {
  await page.goto('Login');
  await page.fill('#username', 'test_admin');
  await page.fill('#password', 'TestPass123!');
  await page.click('#form_login button[type="submit"]');
  await page.waitForURL(/\/Dashboard/, { waitUntil: 'domcontentloaded' });
}

async function openStudentForMarch(page: Page) {
  await page.goto(STUDENT_URL, { waitUntil: 'domcontentloaded' });
  await page.fill('#filter_date_from', '2026-03-01');
  await page.fill('#filter_date_to', '2026-03-31');
  await page.click('#btnFilterAbsensi');
}

/** Fills and submits the Input Absensi modal; returns the pop-up's text after closing it. */
async function inputAbsensi(
  page: Page,
  a: { tanggal: string; sesi: '1' | '2'; status: string; keterangan?: string },
): Promise<string> {
  await page.click('button[data-target="#modalInputAbsensi"]');
  const form = page.locator('#formInputAbsensi');
  await expect(form).toBeVisible();

  await form.locator('input[name="tanggal"]').fill(a.tanggal);
  await form.locator('select[name="id_guru"]').selectOption({ label: 'Test Teacher' });
  await form.locator('select[name="jumlah_sesi"]').selectOption(a.sesi);
  await form.locator('select[name="status_hadir"]').selectOption(a.status);
  await form.locator('textarea[name="keterangan"]').fill(a.keterangan ?? '');
  await form.locator('button[type="submit"]').click();

  const popup = page.locator('.swal2-popup');
  await expect(popup).toBeVisible();
  const text = (await popup.innerText()).trim();
  await page.click('.swal2-confirm');
  await expect(popup).toBeHidden();
  return text;
}

test.beforeEach(async ({ page }) => {
  await login(page);
  await openStudentForMarch(page);
});

test('form offers teacher, session count and Sakit', async ({ page }) => {
  await page.click('button[data-target="#modalInputAbsensi"]');
  const form = page.locator('#formInputAbsensi');

  await expect(form.locator('select[name="id_guru"] option', { hasText: 'Test Teacher' })).toHaveCount(1);
  await expect(form.locator('select[name="jumlah_sesi"] option')).toHaveText(['1 Sesi', '2 Sesi (2 Jam)']);
  await expect(form.locator('select[name="status_hadir"] option')).toHaveText(['Hadir', 'Izin', 'Sakit', 'Alpha']);
});

test('saved attendance shows teacher, sessions and rekap, with notes as plain text', async ({ page }) => {
  await expect(page.locator('#tbody-absensi')).toContainText('Tidak ada data absensi');

  expect(await inputAbsensi(page, { tanggal: '2026-03-02', sesi: '2', status: 'Hadir', keterangan: XSS_NOTE }))
    .toContain('Absensi berhasil disimpan');
  expect(await inputAbsensi(page, { tanggal: '2026-03-03', sesi: '1', status: 'Sakit' }))
    .toContain('Absensi berhasil disimpan');

  const hadirRow = page.locator('#tbody-absensi tr', { hasText: '02-03-2026' });
  await expect(hadirRow.locator('td')).toHaveText(['02-03-2026', 'Test Teacher', '2 Sesi', 'Hadir', XSS_NOTE, '']);
  await expect(page.locator('#tbody-absensi tr', { hasText: '03-03-2026' })).toContainText('Sakit');

  // The note is shown literally, not turned into an <img> whose onerror runs.
  await expect(page.locator('#tbody-absensi img')).toHaveCount(0);
  expect(await page.evaluate(() => (window as any).__xss)).toBeUndefined();

  // "Hadir" counts sessions (2), not days; Total Hari counts days (2).
  const rekap = page.locator('#rekap-absensi');
  await expect(rekap).toContainText('2 Hadir (Sesi)');
  await expect(rekap).toContainText('1 Sakit');
  await expect(rekap).toContainText('2 Total Hari');
});

test('the 8th session is announced and a 9th is refused', async ({ page }) => {
  // 2 sessions already used on 02-03 by the previous test.
  for (const tanggal of ['2026-03-04', '2026-03-05']) {
    expect(await inputAbsensi(page, { tanggal, sesi: '2', status: 'Hadir' })).toContain('Absensi berhasil disimpan');
  }
  expect(await inputAbsensi(page, { tanggal: '2026-03-06', sesi: '2', status: 'Hadir' }))
    .toContain('telah memenuhi 8x kehadiran');

  expect(await inputAbsensi(page, { tanggal: '2026-03-09', sesi: '1', status: 'Hadir' }))
    .toContain('Sisa kuota tidak cukup');
  await expect(page.locator('#tbody-absensi')).not.toContainText('09-03-2026');
  await expect(page.locator('#rekap-absensi')).toContainText('8 Hadir (Sesi)');
});

test('a second entry on the same date is refused', async ({ page }) => {
  expect(await inputAbsensi(page, { tanggal: '2026-03-02', sesi: '1', status: 'Izin' }))
    .toContain('Absensi untuk tanggal 02/03/2026 sudah ada');
  await expect(page.locator('#tbody-absensi tr', { hasText: '02-03-2026' })).toHaveCount(1);
});
