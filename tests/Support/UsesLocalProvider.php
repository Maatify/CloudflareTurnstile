<?php

declare(strict_types=1);

namespace Maatify\Turnstile\Tests\Support;

/**
 * Starts the deterministic local provider stand-in (tests/Support/router.php)
 * once per test class; the real Cloudflare endpoint is never contacted.
 */
trait UsesLocalProvider
{
    private static int $serverPid = 0;
    /** @var non-empty-string */
    private static string $baseUrl = 'http://127.0.0.1';

    public static function setUpBeforeClass(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($socket === false) {
            self::fail('Cannot reserve a local port.');
        }
        $name = (string)stream_socket_get_name($socket, false);
        fclose($socket);
        $port = (int)substr($name, (int)strrpos($name, ':') + 1);

        $command = sprintf(
            '%s -S 127.0.0.1:%d %s >/dev/null 2>&1 & echo $!',
            escapeshellarg(PHP_BINARY),
            $port,
            escapeshellarg(__DIR__ . '/router.php')
        );
        self::$serverPid = (int)trim((string)shell_exec($command));
        self::$baseUrl = "http://127.0.0.1:$port";

        for ($attempt = 0; $attempt < 50; $attempt++) {
            $probe = @fsockopen('127.0.0.1', $port, $probeErrno, $probeErrstr, 0.1);
            if ($probe !== false) {
                fclose($probe);
                return;
            }
            usleep(100_000);
        }
        self::fail('The local provider stand-in did not start.');
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$serverPid > 0) {
            shell_exec('kill ' . self::$serverPid . ' 2>/dev/null');
            self::$serverPid = 0;
        }
    }

    /** @return array<mixed> */
    private function runJsonErrors(string $path): array
    {
        $command = sprintf(
            '%s -d display_errors=0 %s %s 2>/dev/null',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(__DIR__ . '/json_errors_runner.php'),
            escapeshellarg(self::$baseUrl . $path)
        );
        $stdout = (string)shell_exec($command);

        $decoded = json_decode($stdout, true);
        $this->assertIsArray($decoded, 'jsonErrors() output must be JSON, got: ' . $stdout);

        return $decoded;
    }
}
