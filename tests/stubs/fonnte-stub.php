<?php
/**
 * Stand-in for https://api.fonnte.com/send, run by tests/run-tests.sh as its
 * own `php -S` server. The app under test gets its URL as
 * OUTBOUND_FONNTE_URL, so if the production-only gate in sendWa() were ever
 * bypassed, the request would land here instead of at Fonnte.
 *
 * Every request appends one line to the file named by FONNTE_STUB_HITS
 * (method and path only, never the body or headers) and gets a
 * Fonnte-shaped answer. tests/Outbound/KirimInvoiceNotSentTest.php counts
 * those lines.
 */
$hits = getenv('FONNTE_STUB_HITS');
if (is_string($hits) && $hits !== '') {
    file_put_contents(
        $hits,
        date('c') . ' ' . ($_SERVER['REQUEST_METHOD'] ?? '?') . ' ' . parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) . "\n",
        FILE_APPEND | LOCK_EX
    );
}

header('Content-Type: application/json');
echo json_encode(['status' => true, 'reason' => 'fonnte stub: recorded, nothing sent']);
