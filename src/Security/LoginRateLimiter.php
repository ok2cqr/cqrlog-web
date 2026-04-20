<?php

declare(strict_types=1);

namespace App\Security;

final class LoginRateLimiter
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_SECONDS = 300;

    private string $storageDir;

    public function __construct(string $storageDir)
    {
        $this->storageDir = $storageDir;
    }

    public function isBlocked(string $ip): bool
    {
        return count($this->getRecentAttempts($ip)) >= self::MAX_ATTEMPTS;
    }

    public function recordFailedAttempt(string $ip): void
    {
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0o777, true);
        }

        $file = $this->getFilePath($ip);
        file_put_contents($file, time() . "\n", FILE_APPEND | LOCK_EX);
    }

    public function reset(string $ip): void
    {
        $file = $this->getFilePath($ip);

        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * @return list<int>
     */
    private function getRecentAttempts(string $ip): array
    {
        $file = $this->getFilePath($ip);

        if (!file_exists($file)) {
            return [];
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return [];
        }

        $cutoff = time() - self::WINDOW_SECONDS;
        $recent = [];

        foreach ($lines as $line) {
            $timestamp = (int) $line;

            if ($timestamp >= $cutoff) {
                $recent[] = $timestamp;
            }
        }

        // Compact file if stale entries were filtered out
        if (count($recent) < count($lines)) {
            if ($recent === []) {
                unlink($file);
            } else {
                file_put_contents($file, implode("\n", $recent) . "\n", LOCK_EX);
            }
        }

        return $recent;
    }

    private function getFilePath(string $ip): string
    {
        return $this->storageDir . '/' . md5($ip) . '.txt';
    }
}
