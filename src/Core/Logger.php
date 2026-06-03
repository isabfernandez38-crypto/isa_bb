<?php
declare(strict_types=1);

class Logger {
    private const MAX_SIZE = 10 * 1024 * 1024; // 10 MB

    public static function debug(string $msg, array $context = []): void {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            self::write('DEBUG', $msg, $context);
        }
    }

    public static function info(string $msg, array $context = []): void {
        self::write('INFO', $msg, $context);
    }

    public static function warning(string $msg, array $context = []): void {
        self::write('WARNING', $msg, $context);
    }

    public static function error(string $msg, array $context = []): void {
        self::write('ERROR', $msg, $context);
    }

    public static function critical(string $msg, array $context = []): void {
        self::write('CRITICAL', $msg, $context);
    }

    private static function write(string $level, string $msg, array $ctx): void {
        $logFile = (defined('LOGS_DIR') ? LOGS_DIR : dirname(__DIR__, 2) . '/logs/') . 'app.log';
        $logDir  = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Rotar si supera 10 MB
        if (file_exists($logFile) && filesize($logFile) > self::MAX_SIZE) {
            rename($logFile, $logFile . '.' . date('YmdHis') . '.bak');
        }

        $date    = date('Y-m-d H:i:s');
        $ctxStr  = empty($ctx) ? '' : ' | context: ' . json_encode($ctx, JSON_UNESCAPED_UNICODE);
        $line    = "[{$date}][{$level}] {$msg}{$ctxStr}" . PHP_EOL;

        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("[{$level}] {$msg}{$ctxStr}");
        }
    }
}
