<?php
declare(strict_types=1);

class RateLimiter {
    private static function getDir(): string {
        $dir = (defined('CACHE_DIR') ? CACHE_DIR : dirname(__DIR__, 2) . '/cache/') . 'ratelimit/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private static function getFile(string $key): string {
        return self::getDir() . md5($key) . '.json';
    }

    private static function readData(string $key): array {
        $file = self::getFile($key);
        if (!file_exists($file)) {
            return ['count' => 0, 'window_start' => time(), 'blocked_until' => 0];
        }
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : ['count' => 0, 'window_start' => time(), 'blocked_until' => 0];
    }

    private static function writeData(string $key, array $data): void {
        file_put_contents(self::getFile($key), json_encode($data), LOCK_EX);
    }

    public static function check(string $key, int $maxAttempts, int $windowSeconds): bool {
        $data = self::readData($key);
        $now  = time();

        // Verificar si está en período de bloqueo explícito
        if (isset($data['blocked_until']) && $data['blocked_until'] > $now) {
            $retryAfter = $data['blocked_until'] - $now;
            Logger::warning('Rate limit bloqueado', ['key' => $key, 'retry_after' => $retryAfter]);
            http_response_code(429);
            header("Retry-After: $retryAfter");
            echo json_encode([
                'error'       => 'Demasiadas solicitudes. Por favor, espera antes de intentar de nuevo.',
                'retry_after' => $retryAfter,
            ]);
            exit;
        }

        // Resetear ventana si expiró
        if (($now - $data['window_start']) >= $windowSeconds) {
            $data = ['count' => 0, 'window_start' => $now, 'blocked_until' => 0];
            self::writeData($key, $data);
        }

        // Verificar límite
        if ($data['count'] >= $maxAttempts) {
            $retryAfter = $windowSeconds - ($now - $data['window_start']);
            $data['blocked_until'] = $now + $retryAfter;
            self::writeData($key, $data);
            Logger::warning('Rate limit excedido', ['key' => $key, 'count' => $data['count'], 'retry_after' => $retryAfter]);
            http_response_code(429);
            header("Retry-After: $retryAfter");
            echo json_encode([
                'error'       => 'Demasiadas solicitudes. Por favor, espera antes de intentar de nuevo.',
                'retry_after' => $retryAfter,
            ]);
            exit;
        }

        return true;
    }

    public static function increment(string $key): void {
        $data = self::readData($key);
        $now  = time();
        if (!isset($data['window_start'])) $data['window_start'] = $now;
        $data['count'] = ($data['count'] ?? 0) + 1;
        self::writeData($key, $data);
    }

    public static function reset(string $key): void {
        $file = self::getFile($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }
}
