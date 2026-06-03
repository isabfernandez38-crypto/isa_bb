<?php
declare(strict_types=1);

// 1. Cargar variables del archivo .env manualmente
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        // Eliminar comillas si las hay
        if (preg_match('/^["\'](.*)["\']\s*$/', $value, $m)) {
            $value = $m[1];
        }
        putenv("$key=$value");
        $_ENV[$key]    = $value;
        $_SERVER[$key] = $value;
    }
}

// 2. Definir constantes
define('APP_ENV',   $_ENV['APP_ENV']   ?? 'production');
define('APP_URL',   $_ENV['APP_URL']   ?? '');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));
define('BASE_DIR',  dirname(__DIR__));
define('CACHE_DIR', BASE_DIR . '/cache/');
define('LOGS_DIR',  BASE_DIR . '/logs/');

// 3. Timezone
date_default_timezone_set('America/Lima');

// 4. Crear directorios necesarios
foreach ([CACHE_DIR, LOGS_DIR, CACHE_DIR . 'ratelimit/'] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// 5. Autoload manual de clases Core
spl_autoload_register(function (string $class): void {
    $map = [
        'Logger'         => BASE_DIR . '/src/Core/Logger.php',
        'RateLimiter'    => BASE_DIR . '/src/Core/RateLimiter.php',
        'CsrfProtection' => BASE_DIR . '/src/Core/CsrfProtection.php',
        'Cache'          => BASE_DIR . '/src/Core/Cache.php',
        'ErrorHandler'   => BASE_DIR . '/src/Core/ErrorHandler.php',
        'Database'       => BASE_DIR . '/config/database.php',
        // Repositories
        'ReservaRepository'       => BASE_DIR . '/src/Repository/ReservaRepository.php',
        'MenuRepository'          => BASE_DIR . '/src/Repository/MenuRepository.php',
        'HorarioRepository'       => BASE_DIR . '/src/Repository/HorarioRepository.php',
        'MesaRepository'          => BASE_DIR . '/src/Repository/MesaRepository.php',
        'ConversacionRepository'  => BASE_DIR . '/src/Repository/ConversacionRepository.php',
        'PromocionRepository'     => BASE_DIR . '/src/Repository/PromocionRepository.php',
        // Services
        'WhatsAppService' => BASE_DIR . '/src/Services/WhatsAppService.php',
    ];
    if (isset($map[$class]) && file_exists($map[$class])) {
        require_once $map[$class];
    }
});

// Cargar clases Core necesarias de inmediato
require_once BASE_DIR . '/src/Core/Logger.php';
require_once BASE_DIR . '/src/Core/ErrorHandler.php';
require_once BASE_DIR . '/src/Core/RateLimiter.php';
require_once BASE_DIR . '/src/Core/CsrfProtection.php';
require_once BASE_DIR . '/src/Core/Cache.php';
require_once BASE_DIR . '/config/database.php';

// 6. Registrar manejador global de errores
ErrorHandler::register();

// 7. Sesión segura
if (session_status() === PHP_SESSION_NONE) {
    // A3: secure auto-detectado — true en producción (HTTPS), false en desarrollo
    $isSecure = (!in_array(APP_ENV, ['development', 'local', 'dev'], true))
        || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 7200,
        'path'     => '/',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// 8. Headers de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// 9. CORS restrictivo con validación de URLs (A9/M6)
$allowedOrigins = array_values(array_filter(
    array_map('trim', explode(',', $_ENV['ALLOWED_ORIGINS'] ?? '')),
    static fn(string $o) => !empty($o) && (
        filter_var($o, FILTER_VALIDATE_URL) !== false ||
        in_array($o, ['http://localhost', 'https://localhost'], true)
    )
));
// Siempre incluir APP_URL como origen permitido
if (!empty($_ENV['APP_URL']) && !in_array(rtrim($_ENV['APP_URL'], '/'), $allowedOrigins, true)) {
    $allowedOrigins[] = rtrim($_ENV['APP_URL'], '/');
}
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, X-Admin');
}

// Preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// 10. JSON por defecto para rutas /api/
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($requestUri, '/api/') !== false) {
    header('Content-Type: application/json; charset=utf-8');
}

// 11. Helper: verificar sesión admin
function session_check(): void {
    if (empty($_SESSION['user_id'])) {
        // Si es petición AJAX → responder JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(401);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        // Si es petición desde /api/ → JSON
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            http_response_code(401);
            echo json_encode(['error' => 'Sesión requerida']);
            exit;
        }
        // Redirect a login
        header('Location: ' . APP_URL . '/admin/login.php');
        exit;
    }
}

// 12. Helper: obtener IP del cliente
function get_client_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}
