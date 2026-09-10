<?php

require __DIR__ . '/../vendor/autoload.php';

// Safety rail: this suite executes real HTTP requests against a running
// instance of the app, including requests that (pre-fix) are expected to
// succeed at things like enumerating student PII or bypassing auth checks.
// It must never be pointed at a database holding real data. We only trust
// an explicit opt-in via TEST_DB_NAME=admintool_test (set by tests/run-tests.sh),
// never a default.
$dbName = getenv('TEST_DB_NAME');
if ($dbName !== 'admintool_test') {
    fwrite(STDERR, "\nRefusing to run: TEST_DB_NAME must be 'admintool_test' (got: " .
        var_export($dbName, true) . ").\n" .
        "This suite intentionally exercises known vulnerabilities and must only run\n" .
        "against the disposable, synthetic-data test database. Run via tests/run-tests.sh.\n\n");
    exit(1);
}

define('ADMINTOOL_BASE_URL', getenv('ADMINTOOL_BASE_URL') ?: 'http://127.0.0.1:8089');
