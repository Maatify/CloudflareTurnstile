<?php

declare(strict_types=1);

namespace Maatify\CloudflareTurnstile\Tests\System;

use Maatify\CloudflareTurnstile\Tests\Support\TestCall;
use Maatify\CloudflareTurnstile\Tests\Support\TestValidation;
use Maatify\CloudflareTurnstile\Tests\Support\UsesLocalProvider;
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
    use UsesLocalProvider;

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
}
