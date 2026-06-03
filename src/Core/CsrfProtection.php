<?php
declare(strict_types=1);

class CsrfProtection {
    private const TOKEN_EXPIRY    = 7200; // 2 horas
    private const TOKEN_POOL_SIZE = 5;    // A2: hasta 5 tokens activos (múltiples pestañas)

    public static function generateToken(): string {
        if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }

        $now   = time();
        $token = bin2hex(random_bytes(32));

        // Purgar tokens expirados del pool
        $_SESSION['csrf_tokens'] = array_filter(
            $_SESSION['csrf_tokens'],
            static fn(int $ts) => ($now - $ts) <= self::TOKEN_EXPIRY
        );

        // Si el pool está lleno, eliminar el más antiguo
        if (count($_SESSION['csrf_tokens']) >= self::TOKEN_POOL_SIZE) {
            asort($_SESSION['csrf_tokens']);
            reset($_SESSION['csrf_tokens']);
            $oldest = key($_SESSION['csrf_tokens']);
            unset($_SESSION['csrf_tokens'][$oldest]);
        }

        $_SESSION['csrf_tokens'][$token] = $now;

        // Backward compat: mantener también los campos legacy
        $_SESSION['csrf_token']    = $token;
        $_SESSION['csrf_token_ts'] = $now;

        return $token;
    }

    public static function validateToken(string $token): bool {
        if (empty($token)) {
            Logger::warning('CSRF: token vacío');
            return false;
        }

        $now = time();

        // Validación con pool (soporta múltiples pestañas)
        if (!empty($_SESSION['csrf_tokens']) && is_array($_SESSION['csrf_tokens'])) {
            if (isset($_SESSION['csrf_tokens'][$token])) {
                $ts = (int)$_SESSION['csrf_tokens'][$token];
                if (($now - $ts) > self::TOKEN_EXPIRY) {
                    unset($_SESSION['csrf_tokens'][$token]);
                    Logger::warning('CSRF: token expirado');
                    return false;
                }
                // One-time use: consumir del pool
                unset($_SESSION['csrf_tokens'][$token]);
                return true;
            }
        }

        // Fallback legacy (tokens generados antes del pool)
        if (!empty($_SESSION['csrf_token']) && !empty($_SESSION['csrf_token_ts'])) {
            if (($now - (int)$_SESSION['csrf_token_ts']) > self::TOKEN_EXPIRY) {
                unset($_SESSION['csrf_token'], $_SESSION['csrf_token_ts']);
                Logger::warning('CSRF: token legacy expirado');
                return false;
            }
            if (hash_equals($_SESSION['csrf_token'], $token)) {
                unset($_SESSION['csrf_token'], $_SESSION['csrf_token_ts']);
                return true;
            }
        }

        Logger::warning('CSRF: token inválido', ['ip' => get_client_ip()]);
        return false;
    }

    public static function getToken(): string {
        $now = time();

        if (!empty($_SESSION['csrf_tokens']) && is_array($_SESSION['csrf_tokens'])) {
            // Purgar expirados
            $_SESSION['csrf_tokens'] = array_filter(
                $_SESSION['csrf_tokens'],
                static fn(int $ts) => ($now - $ts) <= self::TOKEN_EXPIRY
            );

            if (!empty($_SESSION['csrf_tokens'])) {
                // Devolver el token más reciente sin consumirlo
                arsort($_SESSION['csrf_tokens']);
                return (string)array_key_first($_SESSION['csrf_tokens']);
            }
        }

        return self::generateToken();
    }

    public static function getTokenField(): string {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
