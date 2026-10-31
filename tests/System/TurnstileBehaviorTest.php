<?php

declare(strict_types=1);

namespace Maatify\Turnstile\Tests\System;

use Maatify\Turnstile\Tests\Support\TestCall;
use Maatify\Turnstile\Tests\Support\TestValidation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Deterministic regression tests for the request/validation paths hardened
 * together with PHPStan level max. A local `php -S` server stands in for the
 * provider; the real Cloudflare endpoint is never contacted.
 */
final class TurnstileBehaviorTest extends TestCase
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
            escapeshellarg(__DIR__ . '/../Support/router.php')
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

    protected function tearDown(): void
    {
        unset($_POST['cf-turnstile-response']);
    }

    public function testObjectResponseIsReturnedAsIs(): void
    {
        $result = (new TestCall(self::$baseUrl . '/object'))->curlPost(['response' => 'abc']);

        $this->assertTrue($result->success);
        $this->assertInstanceOf(stdClass::class, $result->echo);
        $this->assertSame('abc', $result->echo->response);
    }

    public function testJsonEncodingFailureReturnsFailureWithoutSendingTheRequest(): void
    {
        // Invalid UTF-8 cannot be JSON-encoded; /object would answer success=true if a request were sent.
        $result = (new TestCall(self::$baseUrl . '/object'))->curlPost(['response' => "\xB1\x31"]);

        $this->assertFalse($result->success);
        $this->assertObjectNotHasProperty('echo', $result);
    }

    #[DataProvider('nonObjectResponses')]
    public function testNonObjectJsonResponseIsRejectedAsFailure(string $path): void
    {
        $result = (new TestCall(self::$baseUrl . $path))->curlPost(['response' => 'abc']);

        $this->assertFalse($result->success);
    }

    /** @return array<string, array{string}> */
    public static function nonObjectResponses(): array
    {
        return [
            'number' => ['/number'],
            'string' => ['/string'],
            'list'   => ['/list'],
            'null'   => ['/null'],
        ];
    }

    public function testStringTokenIsSentToTheProvider(): void
    {
        $_POST['cf-turnstile-response'] = 'real-token';
        $validation = new TestValidation(self::$baseUrl . '/object');

        $this->assertTrue($validation->isSuccess());
        $echo = $validation->getResponse()['echo'];
        $this->assertInstanceOf(stdClass::class, $echo);
        $this->assertSame('real-token', $echo->response);
    }

    public function testNonStringTokenIsSentAsEmptyString(): void
    {
        $_POST['cf-turnstile-response'] = ['not', 'a', 'string'];
        $validation = new TestValidation(self::$baseUrl . '/object');

        $this->assertTrue($validation->isSuccess());
        $echo = $validation->getResponse()['echo'];
        $this->assertInstanceOf(stdClass::class, $echo);
        $this->assertSame('', $echo->response);
    }

    public function testJsonErrorsReportsTheFirstStringErrorCode(): void
    {
        $output = $this->runJsonErrors('/errors');

        $this->assertSame('invalid-input-secret', $output['more_info'] ?? null);
    }

    public function testJsonErrorsIgnoresANonStringErrorCode(): void
    {
        $output = $this->runJsonErrors('/errors-nonstring');

        $this->assertSame('{"success":false,"error-codes":[123]}', $output['more_info'] ?? null);
    }

    /** @return array<mixed> */
    private function runJsonErrors(string $path): array
    {
        $command = sprintf(
            '%s -d display_errors=0 %s %s 2>/dev/null',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(__DIR__ . '/../Support/json_errors_runner.php'),
            escapeshellarg(self::$baseUrl . $path)
        );
        $stdout = (string)shell_exec($command);

        $decoded = json_decode($stdout, true);
        $this->assertIsArray($decoded, 'jsonErrors() output must be JSON, got: ' . $stdout);

        return $decoded;
    }
}
