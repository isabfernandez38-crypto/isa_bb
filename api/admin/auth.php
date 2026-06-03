<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

$ip     = get_client_ip();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET check ─────────────────────────────────────────────────────────────
if ($method === 'GET') {
    if (!empty($_SESSION['user_id'])) {
        echo json_encode([
            'success'  => true,
            'logueado' => true,
            'usuario'  => [
                'id'     => $_SESSION['user_id'],
                'nombre' => $_SESSION['nombre'] ?? '',
                'rol'    => $_SESSION['rol'] ?? '',
            ],
        ]);
    } else {
        echo json_encode(['success' => true, 'logueado' => false]);
    }
    exit;
}

// ── POST logout ───────────────────────────────────────────────────────────
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'logout') {
    Logger::info('Logout', ['user_id' => $_SESSION['user_id'] ?? null]);
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// ── POST login ────────────────────────────────────────────────────────────
if ($method === 'POST') {
    RateLimiter::check("login_{$ip}", 5, 300);
    RateLimiter::increment("login_{$ip}");

    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $email = trim(filter_var($body['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $pass  = $body['password'] ?? '';

    if (empty($email) || empty($pass)) {
        http_response_code(422);
        echo json_encode(['error' => 'Email y contraseña son requeridos']);
        exit;
    }

    $db   = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = :email AND activo = 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    // Verificar bloqueo por intentos fallidos
    if ($user && !empty($user['bloqueado_hasta'])) {
        if (strtotime($user['bloqueado_hasta']) > time()) {
            $minutos = ceil((strtotime($user['bloqueado_hasta']) - time()) / 60);
            Logger::warning('Login bloqueado', ['email' => $email, 'ip' => $ip]);
            http_response_code(429);
            echo json_encode(['error' => "Cuenta bloqueada temporalmente. Intenta en {$minutos} minutos."]);
            exit;
        }
    }

    // Verificar credenciales (respuesta genérica, no revelar si el email existe)
    $credencialesOk = $user && password_verify($pass, $user['password']);

    if (!$credencialesOk) {
        if ($user) {
            $intentos = (int)$user['login_intentos'] + 1;
            $bloqueo  = null;

            if ($intentos >= 5) {
                $bloqueo = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                Logger::warning('Cuenta bloqueada por intentos', ['email' => $email]);
            }

            $upd = $db->prepare("UPDATE usuarios SET login_intentos = :i, bloqueado_hasta = :b WHERE id = :id");
            $upd->execute([':i' => $intentos, ':b' => $bloqueo, ':id' => $user['id']]);
        }

        Logger::warning('Login fallido', ['email' => $email, 'ip' => $ip]);
        http_response_code(401);
        echo json_encode(['error' => 'Credenciales incorrectas']);
        exit;
    }

    // Login exitoso
    session_regenerate_id(true);
    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['nombre']     = $user['nombre'];
    $_SESSION['rol']        = $user['rol'];
    $_SESSION['login_time'] = time();

    $upd = $db->prepare("UPDATE usuarios SET ultimo_login = NOW(), login_intentos = 0, bloqueado_hasta = NULL WHERE id = :id");
    $upd->execute([':id' => $user['id']]);

    RateLimiter::reset("login_{$ip}");
    Logger::info('Login exitoso', ['email' => $email, 'user_id' => $user['id']]);

    echo json_encode([
        'success' => true,
        'usuario' => [
            'id'     => (int)$user['id'],
            'nombre' => $user['nombre'],
            'rol'    => $user['rol'],
        ],
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
