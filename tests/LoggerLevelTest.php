<?php

declare(strict_types=1);

namespace Maatify\Turnstile\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Static check: every Logger::RecordLog() call must pass a PSR-3 level in the
 * level slot (named `level:`), and every internal $this->log() call must pass
 * a PSR-3 LogLevel constant as its level argument, never a log-file name.
 */
final class LoggerLevelTest extends TestCase
{
    private const PSR3_LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    public function testRecordLogCallsUsePsr3Level(): void
    {
        $calls = 0;
        $found = 0;
        foreach (self::sourceFiles() as $file) {
            $violations = self::recordLogViolations((string)file_get_contents($file), $found);
            $calls += $found;
            $this->assertSame([], $violations, basename($file));
        }
        $this->assertGreaterThan(0, $calls);
    }

    public function testLogCallsPassPsr3LevelConstant(): void
    {
        $calls = 0;
        $found = 0;
        foreach (self::sourceFiles() as $file) {
            $violations = self::logCallViolations((string)file_get_contents($file), $found);
            $calls += $found;
            $this->assertSame([], $violations, basename($file));
        }
        $this->assertGreaterThan(0, $calls);
    }

    public function testDetectsNonPsr3LevelInRecordLog(): void
    {
        $found = 0;
        $bad = '<?php Logger::RecordLog($log, "CloudflareTurnstile/file");';
        $this->assertNotSame([], self::recordLogViolations($bad, $found));
        $bad = '<?php Logger::RecordLog($log, level: "file_name", logFile: "x");';
        $this->assertNotSame([], self::recordLogViolations($bad, $found));
        $good = '<?php Logger::RecordLog($log, level: LogLevel::INFO, logFile: "x");';
        $this->assertSame([], self::recordLogViolations($good, $found));
    }

    public function testDetectsNonPsr3LevelInLogCalls(): void
    {
        $found = 0;
        $bad = '<?php $this->log("success", "invalid-level", success: $r); $this->log("failed", LogLevel::ERROR, error_details: $m);';
        $this->assertCount(1, self::logCallViolations($bad, $found));
        $this->assertSame(2, $found);
        $bad = '<?php $this->log("failed", error_details: $m);';
        $this->assertCount(1, self::logCallViolations($bad, $found));
    }

    /** @return list<string> */
    private static function recordLogViolations(string $code, int &$found): array
    {
        $code = self::stripComments($code);
        preg_match_all('/Logger::RecordLog\((.*?)\);/s', $code, $m);
        $found = count($m[1]);
        $violations = [];
        foreach ($m[1] as $args) {
            if (!preg_match('/\blevel\s*:\s*([^,\n]+)/', $args, $lv)) {
                $violations[] = 'missing named level: ' . trim($args);
                continue;
            }
            $value = trim($lv[1]);
            if (!str_starts_with($value, '$') && !self::isPsr3Level($value)) { // variables: validated at the $this->log() callers
                $violations[] = 'invalid level: ' . trim($lv[1]);
            }
        }
        return $violations;
    }

    /** @return list<string> */
    private static function logCallViolations(string $code, int &$found): array
    {
        $code = self::stripComments($code);
        preg_match_all('/\$this->log\(\s*([^,)]+)\s*(?:,\s*([^,)]+))?/', $code, $m, PREG_SET_ORDER);
        $found = count($m);
        $violations = [];
        foreach ($m as $call) {
            $level = trim($call[2] ?? '');
            if ($level === '' || preg_match('/^\w+\s*:(?!:)/', $level) === 1 || !self::isPsr3Level($level)) {
                $violations[] = 'invalid level in log(' . trim($call[1]) . '): ' . $level;
            }
        }
        return $violations;
    }

    /** @return list<string> */
    private static function sourceFiles(): array
    {
        $files = glob(__DIR__ . '/../turnstile/*.php');
        if ($files === false) {
            self::fail('Unable to list turnstile/*.php');
        }
        return $files;
    }

    private static function isPsr3Level(string $value): bool
    {
        if (preg_match('/^(?:\\\\?Psr\\\\Log\\\\)?LogLevel::([A-Z]+)$/', $value, $c)) {
            return in_array(strtolower($c[1]), self::PSR3_LEVELS, true);
        }
        return in_array(strtolower(trim($value, '\'"')), self::PSR3_LEVELS, true);
    }

    private static function stripComments(string $code): string
    {
        return (string)preg_replace('#/\*.*?\*/|//[^\n]*#s', '', $code);
    }
}
