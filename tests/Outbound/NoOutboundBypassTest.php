<?php

namespace Tests\Outbound;

use PHPUnit\Framework\TestCase;

/**
 * Nothing in application/ may reach the network on its own, around the
 * production-only gate. The only allowed network call is the Fonnte request
 * inside sendWa() (application/helpers/custom_helper.php), and only after
 * its outbound_is_live() check. application/libraries/tcpdf is third-party
 * code that the app only uses to render PDFs locally, and is skipped.
 *
 * Found something here? Send it through sendWa()/MY_Email, or gate it with
 * outbound_is_live(ENVIRONMENT) and add it below with a reason.
 */
final class NoOutboundBypassTest extends TestCase
{
    private const PATTERNS = [
        'curl_init' => '/\bcurl_init\s*\(/i',
        'curl_exec' => '/\bcurl_exec\s*\(/i',
        'curl_multi_exec' => '/\bcurl_multi_exec\s*\(/i',
        'mail()' => '/(?<![\w$>:])mail\s*\(/i',
        'fsockopen' => '/\b(p?fsockopen)\s*\(/i',
        'stream_socket_client' => '/\bstream_socket_client\s*\(/i',
        'file_get_contents(http...)' => '/\bfile_get_contents\s*\(\s*[\'"]https?:\/\//i',
    ];

    private const SKIP_DIRS = [
        'application/libraries/tcpdf/',
    ];

    public function test_no_network_calls_outside_the_gate(): void
    {
        $root = realpath(__DIR__ . '/../..');
        $found = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/application', \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (strtolower($file->getExtension()) !== 'php') {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            foreach (self::SKIP_DIRS as $skip) {
                if (strpos($relative, $skip) === 0) {
                    continue 2;
                }
            }
            foreach (file($file->getPathname()) as $i => $line) {
                foreach (self::PATTERNS as $name => $pattern) {
                    if (preg_match($pattern, $line)) {
                        $found[] = [$relative, $i + 1, $name];
                    }
                }
            }
        }

        $allowed = $this->allowedSendWaLines($root . '/application/helpers/custom_helper.php');
        $unexpected = [];
        foreach ($found as [$file, $lineNo, $name]) {
            if ($file === 'application/helpers/custom_helper.php' && in_array($lineNo, $allowed, true)) {
                continue;
            }
            $unexpected[] = "$file:$lineNo $name";
        }

        $this->assertSame([], $unexpected, "Network calls outside the production-only gate:\n" . implode("\n", $unexpected));
        $this->assertCount(2, $allowed, 'Expected exactly the curl_init and curl_exec of the Fonnte call in sendWa().');
    }

    /**
     * Line numbers of curl_init/curl_exec inside sendWa() that come after
     * its outbound_is_live() check. Anything before the check, or outside
     * sendWa(), is not allowed.
     */
    private function allowedSendWaLines(string $file): array
    {
        $lines = file($file);
        $start = null;
        foreach ($lines as $i => $line) {
            if (preg_match('/^function\s+sendWa\s*\(/', $line)) {
                $start = $i;
                break;
            }
        }
        $this->assertNotNull($start, 'sendWa() not found in custom_helper.php');

        $gate = null;
        $allowed = [];
        for ($i = $start + 1; $i < count($lines); $i++) {
            if (preg_match('/^function\s+\w+\s*\(/', $lines[$i])) {
                break; // next function: sendWa() ended
            }
            if ($gate === null && preg_match('/if\s*\(\s*!\s*outbound_is_live\(ENVIRONMENT\)\s*\)/', $lines[$i])) {
                $gate = $i;
            }
            if (preg_match('/\bcurl_(init|exec)\s*\(/', $lines[$i])) {
                $this->assertNotNull($gate, 'sendWa() calls curl before its outbound_is_live() check (line ' . ($i + 1) . ').');
                $allowed[] = $i + 1;
            }
        }
        return $allowed;
    }
}
