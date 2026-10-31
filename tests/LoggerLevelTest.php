<?php

declare(strict_types=1);

namespace Maatify\Turnstile\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Static check: every Logger::RecordLog() call must pass a PSR-3 level
 * in the level slot (2nd argument, or `level:`), never a log-file name.
 */
final class LoggerLevelTest extends TestCase
{
    private const PSR3_LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    public function testRecordLogCallsUsePsr3Level(): void
    {
        $calls = 0;
        foreach (glob(__DIR__ . '/../turnstile/*.php') as $file) {
            $code = preg_replace('#/\*.*?\*/|//[^\n]*#s', '', (string)file_get_contents($file));
            preg_match_all('/Logger::RecordLog\((.*?)\);/s', $code, $m);
            foreach ($m[1] as $args) {
                $calls++;
                $this->assertMatchesRegularExpression('/\blevel\s*:/', $args, basename($file) . ': use named argument level:');
                preg_match('/\blevel\s*:\s*([^,\n]+)/', $args, $lv);
                $value = trim($lv[1]);
                if (preg_match('/^LogLevel::([A-Z]+)$/', $value, $c)) {
                    $value = $c[1];
                }
                $value = strtolower(trim($value, '\'"'));
                if (str_starts_with($value, '$')) {
                    continue; // variable: validated at the call that supplies it
                }
                $this->assertContains($value, self::PSR3_LEVELS, basename($file) . ": invalid level '$value'");
            }
        }
        $this->assertGreaterThan(0, $calls);
    }

    public function testLogCallersPassPsr3Constants(): void
    {
        $code = (string)file_get_contents(__DIR__ . '/../turnstile/TurnstileRequestCall.php');
        preg_match_all('/\$this->log\(\'\w+\',\s*LogLevel::([A-Z]+)/', $code, $m);
        $this->assertNotEmpty($m[1]);
        foreach ($m[1] as $c) {
            $this->assertContains(strtolower($c), self::PSR3_LEVELS);
        }
    }
}
