<?php

declare(strict_types=1);

namespace Maatify\Turnstile\Tests\Regression;

use Maatify\Turnstile\TurnstileRequestCall;
use Maatify\Turnstile\TurnstileValidation;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * The public class names are the integration surface of the 5.x releases and
 * must keep resolving under the Maatify\Turnstile namespace, while the files
 * live under src/{Responsibility}/.
 */
final class ClassLocationTest extends TestCase
{
    public function testPublicClassesKeepTheirNamesAndLiveUnderSrc(): void
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
        $this->assertSame('Maatify\Turnstile\TurnstileRequestCall', TurnstileRequestCall::class);
        $this->assertSame('Maatify\Turnstile\TurnstileValidation', TurnstileValidation::class);
    }

    public function testLegacyDirectoryIsGone(): void
    {
        $this->assertDirectoryDoesNotExist(__DIR__ . '/../../turnstile');
    }
}
