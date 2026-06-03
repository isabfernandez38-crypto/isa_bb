<?php
declare(strict_types=1);

class ErrorHandler {
    public static function register(): void {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException(Throwable $e): void {
        Logger::critical($e->getMessage(), [
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        http_response_code(500);

        $isApi = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;
        if ($isApi || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-Type: application/json; charset=utf-8');
            if (defined('APP_DEBUG') && APP_DEBUG) {
                echo json_encode([
                    'error'   => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['error' => 'Error interno del servidor'], JSON_UNESCAPED_UNICODE);
            }
        } else {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                echo '<h1>Error</h1><pre>' . htmlspecialchars($e->getMessage()) . "\n\n" . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            } else {
                echo '<h1>Error interno del servidor</h1>';
            }
        }
    }

    public static function handleError(int $errno, string $errstr, string $errfile = '', int $errline = 0): bool {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    public static function handleShutdown(): void {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            Logger::critical('Fatal error en shutdown', [
                'message' => $error['message'],
                'file'    => $error['file'],
                'line'    => $error['line'],
            ]);
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Error interno del servidor'], JSON_UNESCAPED_UNICODE);
        }
    }
}
