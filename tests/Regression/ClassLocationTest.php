<?php

declare(strict_types=1);

namespace Maatify\CloudflareTurnstile\Tests\Regression;

use Maatify\CloudflareTurnstile\Adapter\TurnstileRequestCall;
use Maatify\CloudflareTurnstile\Service\TurnstileValidation;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * The public classes live under the canonical Maatify\CloudflareTurnstile
 * namespace (Maatify\{PackageName}\ + the responsibility directory); the
 * 5.x namespace Maatify\Turnstile is intentionally gone in 6.0.0.
 */
final class ClassLocationTest extends TestCase
{
    public function testPublicClassesResolveUnderTheCanonicalNamespace(): void
    {
        $expected = [
            TurnstileRequestCall::class => 'src/Adapter/TurnstileRequestCall.php',
            TurnstileValidation::class => 'src/Service/TurnstileValidation.php',
        ];
        foreach ($expected as $class => $relative) {
            $this->assertTrue(class_exists($class), $class);
            $file = (string)(new ReflectionClass($class))->getFileName();
            $this->assertSame(realpath(__DIR__ . '/../../' . $relative), realpath($file), $class);
        }
        $this->assertSame('Maatify\CloudflareTurnstile\Adapter\TurnstileRequestCall', TurnstileRequestCall::class);
        $this->assertSame('Maatify\CloudflareTurnstile\Service\TurnstileValidation', TurnstileValidation::class);
    }

    public function testLegacyNamespaceIsGone(): void
    {
        $this->assertFalse(class_exists('Maatify\\Turnstile\\TurnstileValidation'));
        $this->assertFalse(class_exists('Maatify\\Turnstile\\TurnstileRequestCall'));
    }

    public function testLegacyDirectoryIsGone(): void
    {
        $this->assertDirectoryDoesNotExist(__DIR__ . '/../../turnstile');
    }
}
