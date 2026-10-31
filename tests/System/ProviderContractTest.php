<?php

declare(strict_types=1);

namespace Maatify\Turnstile\Tests\System;

use Maatify\Turnstile\Tests\Support\TestCall;
use Maatify\Turnstile\Tests\Support\TestValidation;
use Maatify\Turnstile\Tests\Support\UsesLocalProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Deterministic provider-contract verification for Cloudflare Turnstile
 * siteverify (EXTERNAL_PROVIDER_VERIFICATION_STANDARD §4.1): request
 * construction/serialization and response/error mapping, driven by the
 * fixtures in tests/Fixtures/siteverify. Proves the implemented contract,
 * not current remote truth (see PROVENANCE.json).
 */
final class ProviderContractTest extends TestCase
{
    use UsesLocalProvider;

    private const FIXTURES = __DIR__ . '/../Fixtures/siteverify';

    protected function tearDown(): void
    {
        unset($_POST['cf-turnstile-response']);
    }

    public function testRequestIsAJsonPostWithSecretAndResponseOnly(): void
    {
        $result = (new TestCall(self::$baseUrl . '/capture'))->curlPost(['secret' => 's3cret', 'response' => 'tok']);

        $this->assertSame('POST', $result->method);
        $this->assertSame('application/json', $result->content_type);
        $this->assertSame('{"secret":"s3cret","response":"tok"}', $result->raw);
    }

    public function testValidationRequestCarriesTheConfiguredSecretAndThePostedToken(): void
    {
        $_POST['cf-turnstile-response'] = 'posted-token';
        $validation = new TestValidation(self::$baseUrl . '/capture');

        $this->assertTrue($validation->isSuccess());
        $captured = $validation->getResponse();
        $this->assertSame('POST', $captured['method']);
        $this->assertSame('application/json', $captured['content_type']);
        $this->assertSame('{"secret":"test-secret","response":"posted-token"}', $captured['raw']);
    }

    #[DataProvider('successFixtures')]
    public function testSuccessFixturesMapToSuccess(string $name): void
    {
        $_POST['cf-turnstile-response'] = 'token';
        $validation = new TestValidation(self::$baseUrl . '/fixture/' . $name);

        $this->assertTrue($validation->isSuccess());
        $this->assertTrue($validation->success);
        $this->assertSame($this->fixture($name), json_decode(json_encode($validation->getResponse(), JSON_THROW_ON_ERROR), true));
    }

    /** @return array<string, array{string}> */
    public static function successFixtures(): array
    {
        return [
            'success' => ['success'],
            'success with optional fields' => ['success-with-optional-fields'],
        ];
    }

    #[DataProvider('failureFixtures')]
    public function testFailureFixturesMapToFailureAndExposeTheErrorCode(string $name, string $errorCode): void
    {
        $_POST['cf-turnstile-response'] = 'token';
        $validation = new TestValidation(self::$baseUrl . '/fixture/' . $name);

        $this->assertFalse($validation->isSuccess());
        $this->assertSame([$errorCode], $validation->getResponse()['error-codes']);

        $output = $this->runJsonErrors('/fixture/' . $name);
        $this->assertSame($errorCode, $output['more_info'] ?? null);
    }

    /** @return array<string, array{string, string}> */
    public static function failureFixtures(): array
    {
        $codes = [
            'missing-input-secret',
            'invalid-input-secret',
            'missing-input-response',
            'invalid-input-response',
            'bad-request',
            'timeout-or-duplicate',
            'internal-error',
        ];

        return array_combine($codes, array_map(static fn (string $code): array => ['failure-' . $code, $code], $codes));
    }

    public function testEveryFixtureHasProvenanceAndNoSecretLikeContent(): void
    {
        $provenance = $this->fixture('../siteverify/PROVENANCE', 'json');
        $this->assertFalse($provenance['contains_real_secrets']);
        $this->assertIsArray($provenance['fixtures']);

        $files = glob(self::FIXTURES . '/*.json');
        $this->assertIsArray($files);
        $fixtureFiles = array_values(array_filter(array_map('basename', $files), static fn (string $f): bool => $f !== 'PROVENANCE.json'));
        $this->assertNotSame([], $fixtureFiles);

        $allowedKinds = ['REAL_PROVIDER_CAPTURE', 'OFFICIAL_PROVIDER_EXAMPLE', 'SYNTHETIC_CONTRACT_CASE'];
        foreach ($fixtureFiles as $file) {
            $this->assertArrayHasKey($file, $provenance['fixtures'], "$file lacks a provenance entry");
            $entry = $provenance['fixtures'][$file];
            $this->assertIsArray($entry);
            $this->assertContains($entry['source_kind'] ?? null, $allowedKinds, "$file has an invalid source_kind");

            $this->assertNoSecretLikeContent($file);
        }
        $this->assertNoSecretLikeContent('PROVENANCE.json');
        foreach (array_keys($provenance['fixtures']) as $listed) {
            $this->assertContains($listed, $fixtureFiles, "$listed is listed but missing");
        }
    }

    private function assertNoSecretLikeContent(string $file): void
    {
        $content = (string)file_get_contents(self::FIXTURES . '/' . $file);
        $this->assertDoesNotMatchRegularExpression('/0x4[A-Za-z0-9_-]{18,}/', $content, "$file looks like a Turnstile key");
        $this->assertDoesNotMatchRegularExpression('/[A-Za-z0-9_-]{60,}/', $content, "$file contains a token-like string");
    }

    /** @return array<mixed> */
    private function fixture(string $name, string $ext = 'json'): array
    {
        $decoded = json_decode((string)file_get_contents(self::FIXTURES . '/' . $name . '.' . $ext), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        return $decoded;
    }
}
