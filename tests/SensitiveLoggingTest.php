<?php

namespace Maatify\Turnstile\Tests;

use Maatify\Logger\Logger;
use Maatify\Turnstile\TurnstileRequestCall;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Regression: request values must never reach the log, and logging must work
 * with every supported maatify/logger 5.x (verified by scripts/verify-logger-matrix.sh).
 * The real Cloudflare endpoint is never contacted.
 */
final class SensitiveLoggingTest extends TestCase
{
    private const SECRET = 'CANARY-SECRET-7f3a9c';
    private const TOKEN = 'CANARY-TOKEN-b81d2e';
    private const IP = 'CANARY-IP-203.0.113.77';

    private static int $pid = 0;
    private static string $base = '';
    /** @var array<string, int> */
    private array $sizesBefore = [];

    public static function setUpBeforeClass(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $name = stream_socket_get_name($socket, false);
        fclose($socket);
        $port = (int)substr($name, strrpos($name, ':') + 1);
        self::$pid = (int)trim((string)shell_exec(sprintf(
            '%s -S 127.0.0.1:%d %s >/dev/null 2>&1 & echo $!',
            escapeshellarg(PHP_BINARY),
            $port,
            escapeshellarg(__DIR__ . '/router.php')
        )));
        self::$base = "http://127.0.0.1:$port";
        for ($i = 0; $i < 50; $i++) {
            $probe = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
            if ($probe) {
                fclose($probe);
                return;
            }
            usleep(100000);
        }
        self::fail('The local provider stand-in did not start.');
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$pid > 0) {
            shell_exec('kill ' . self::$pid . ' 2>/dev/null');
        }
    }

    protected function setUp(): void
    {
        $this->sizesBefore = $this->logSizes();
    }

    public function testSuccessLogsNoRequestValues(): void
    {
        $this->post(self::$base . '/ok');
        $logged = $this->loggedText();
        $this->assertStringContainsString('param_names', $logged);
        $this->assertStringContainsString('example.test', $logged);
        $this->assertStringNotContainsString('CANARY-CDATA', $logged);
        $this->assertNoCanary($logged);
    }

    public function testRejectedValidationLogsNoRequestValues(): void
    {
        $this->post(self::$base . '/rejected');
        $logged = $this->loggedText();
        $this->assertStringContainsString('invalid-input-secret', $logged);
        $this->assertNoCanary($logged);
    }

    public function testTransportErrorLogsNoRequestValues(): void
    {
        $this->post('http://127.0.0.1:1/unreachable');
        $logged = $this->loggedText();
        $this->assertStringContainsString('CURL Error', $logged);
        $this->assertNoCanary($logged);
    }

    private function post(string $url): void
    {
        $call = new class extends TurnstileRequestCall {
            public function __construct()
            {
                parent::__construct('unused');
            }
        };
        $property = new \ReflectionProperty(TurnstileRequestCall::class, 'url');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }
        $property->setValue($call, $url);
        $call->curlPost(['secret' => self::SECRET, 'response' => self::TOKEN, 'remoteip' => self::IP]);
    }

    private function assertNoCanary(string $logged): void
    {
        foreach ([self::SECRET, self::TOKEN, self::IP] as $canary) {
            $this->assertStringNotContainsString($canary, $logged);
        }
    }

    /**
     * maatify/logger resolves its log directory relative to its own location
     * (a different depth per version), so every candidate directory is watched.
     *
     * @return array<string, int>
     */
    private function logSizes(): array
    {
        $loggerFile = (string)(new ReflectionClass(Logger::class))->getFileName();
        $roots = [dirname($loggerFile, 3) . '/logs', dirname($loggerFile, 4) . '/logs', dirname($loggerFile, 5) . '/logs', dirname(__DIR__) . '/logs'];
        $sizes = [];
        foreach (array_unique($roots) as $root) {
            if (!is_dir($root)) {
                continue;
            }
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file->isFile()) {
                    $sizes[$file->getPathname()] = $file->getSize();
                }
            }
        }
        return $sizes;
    }

    /** Log text appended by this test only (new files in full, grown files from the old size). */
    private function loggedText(): string
    {
        clearstatcache();
        $text = '';
        foreach ($this->logSizes() as $path => $size) {
            $before = $this->sizesBefore[$path] ?? 0;
            if ($size > $before) {
                $text .= (string)file_get_contents($path, false, null, $before);
            }
        }
        $this->assertNotSame('', $text, 'No log entry was written; the check would be vacuous.');
        return $text;
    }
}
