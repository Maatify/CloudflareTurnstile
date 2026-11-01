<?php

declare(strict_types=1);

namespace Maatify\Turnstile\Tests\Regression;

use Maatify\Turnstile\Tests\Support\TestCall;
use Maatify\Turnstile\Tests\Support\UsesLocalProvider;
use PHPUnit\Framework\TestCase;

/**
 * The secret key, the Turnstile token and other request/response values must
 * never reach the log, whatever maatify/logger version is installed.
 */
final class SensitiveLoggingTest extends TestCase
{
    use UsesLocalProvider;

    private const SECRET = 'CANARY-SECRET-7f3a9c';
    private const TOKEN = 'CANARY-TOKEN-b81d2e';
    private const IP = 'CANARY-IP-203.0.113.77';

    /** @var array<string, int> log file sizes before the test */
    private array $sizesBefore = [];

    protected function setUp(): void
    {
        $this->sizesBefore = $this->logSizes();
    }

    public function testSuccessfulProviderResponseLogsNoRequestValues(): void
    {
        $this->post('/object');
        $logged = $this->loggedText();
        $this->assertStringContainsString('param_names', $logged);
        $this->assertNoCanary($logged);
    }

    public function testRejectedValidationLogsNoRequestValues(): void
    {
        $this->post('/fixture/failure-invalid-input-secret');
        $logged = $this->loggedText();
        $this->assertStringContainsString('invalid-input-secret', $logged);
        $this->assertNoCanary($logged);
    }

    public function testNonObjectResponseLogsNoRequestValues(): void
    {
        $this->post('/number');
        $logged = $this->loggedText();
        $this->assertStringContainsString('There is no response from server', $logged);
        $this->assertNoCanary($logged);
    }

    public function testTransportErrorLogsNoRequestValues(): void
    {
        $this->post('/capture', 'http://127.0.0.1:1/unreachable');
        $logged = $this->loggedText();
        $this->assertStringContainsString('CURL Error', $logged);
        $this->assertNoCanary($logged);
    }

    public function testOptionalResponseFieldsAreNotLogged(): void
    {
        $this->post('/fixture/success-with-optional-fields');
        $logged = $this->loggedText();
        $this->assertStringContainsString('hostname', $logged);
        $this->assertStringNotContainsString('cdata', $logged);
        $this->assertStringNotContainsString('ephemeral_id', $logged);
    }

    /**
     * @param non-empty-string $path
     * @param non-empty-string|null $url
     */
    private function post(string $path, ?string $url = null): void
    {
        $call = new TestCall($url ?? (self::$baseUrl . $path));
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
        $loggerFile = (string)(new \ReflectionClass(\Maatify\Logger\Logger::class))->getFileName();
        $roots = [dirname($loggerFile, 3) . '/logs', dirname($loggerFile, 5) . '/logs', dirname(__DIR__, 2) . '/logs'];
        $sizes = [];
        foreach (array_unique($roots) as $root) {
            if (!is_dir($root)) {
                continue;
            }
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file instanceof \SplFileInfo && $file->isFile()) {
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
