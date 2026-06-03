<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';

$ip     = get_client_ip();
$method = $_SERVER['REQUEST_METHOD'];

// Rate limiting para GET también
RateLimiter::check("reservas_{$ip}", 10, 60);
RateLimiter::increment("reservas_{$ip}");

// ── GET ──────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $repo = new ReservaRepository();

    if (!empty($_GET['codigo'])) {
        $reserva = $repo->buscarPorCodigo(trim($_GET['codigo']));
        if (!$reserva) {
            http_response_code(404);
            echo json_encode(['error' => 'Reserva no encontrada']);
            exit;
        }
        echo json_encode(['success' => true, 'reserva' => $reserva]);
        exit;
    }

    if (!empty($_GET['fecha'])) {
        // Requiere sesión admin
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        $fecha    = $_GET['fecha'];
        $reservas = $repo->buscarPorFecha($fecha);
        echo json_encode(['success' => true, 'reservas' => $reservas]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Parámetro requerido: codigo o fecha']);
    exit;
}

// ── POST ─────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    // Validar CSRF
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['csrf_token'] ?? '');
    if (!CsrfProtection::validateToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['error' => 'Token de seguridad inválido o expirado. Recarga la página.']);
        exit;
    }

    // Sanitizar y validar campos
    $errors = [];

    $nombre   = trim(htmlspecialchars($body['nombre_cliente'] ?? '', ENT_QUOTES, 'UTF-8'));
    $telefono = trim(preg_replace('/[^0-9+]/', '', $body['telefono'] ?? ''));
    $email    = trim($body['email'] ?? '');
    $fecha    = trim($body['fecha'] ?? '');
    $hora     = trim($body['hora'] ?? '');
    $personas = (int)($body['num_personas'] ?? 0);
    $coments  = trim(htmlspecialchars($body['comentarios'] ?? '', ENT_QUOTES, 'UTF-8'));
    $origen   = in_array($body['origen'] ?? '', ['web','chat_ia','telefono','admin']) ? $body['origen'] : 'web';

    // Validaciones
    if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 150) {
        $errors[] = 'El nombre debe tener entre 2 y 150 caracteres.';
    }

    if (!preg_match('/^(\+?51)?9\d{8}$/', $telefono) && !preg_match('/^9\d{8}$/', $telefono)) {
        $errors[] = 'El teléfono debe ser un número peruano válido (9 dígitos, empieza con 9).';
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El correo electrónico no es válido.';
    }

    $hoy      = date('Y-m-d');
    $maxFecha = date('Y-m-d', strtotime('+60 days'));
    if (empty($fecha) || $fecha < $hoy) {
        $errors[] = 'La fecha debe ser igual o posterior a hoy.';
    } elseif ($fecha > $maxFecha) {
        $errors[] = 'Solo se pueden hacer reservas hasta 60 días en adelante.';
    }

    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) {
        $errors[] = 'La hora no es válida.';
    }

    if ($personas < 1 || $personas > 8) {
        $errors[] = 'El número de personas debe ser entre 1 y 8.';
    }

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['error' => implode(' ', $errors), 'errors' => $errors]);
        exit;
    }

    // Verificar horario del restaurante
    $horarioRepo = new HorarioRepository();
    if (!$horarioRepo->estaAbierto($fecha, $hora)) {
        http_response_code(422);
        echo json_encode(['error' => 'El restaurante no está abierto en ese horario. Consulta nuestros horarios.']);
        exit;
    }

    // A1: Transacción DB para asignar mesa + crear reserva atómicamente
    $db          = Database::getInstance();
    $reservaRepo = new ReservaRepository();

    try {
        $db->beginTransaction();

        if (!$reservaRepo->verificarDisponibilidad($fecha, $hora, $personas)) {
            $db->rollBack();
            http_response_code(422);
            echo json_encode(['error' => 'No hay mesas disponibles para esa fecha, hora y número de personas.']);
            exit;
        }

        $mesaId = $reservaRepo->asignarMesa($fecha, $hora, $personas);

        $reserva = $reservaRepo->crear([
            'nombre_cliente' => $nombre,
            'telefono'       => $telefono,
            'email'          => $email ?: null,
            'fecha'          => $fecha,
            'hora'           => $hora,
            'num_personas'   => $personas,
            'mesa_id'        => $mesaId,
            'comentarios'    => $coments ?: null,
            'origen'         => $origen,
        ]);

        $db->commit();

    } catch (\Throwable $e) {
        $db->rollBack();
        Logger::error('Error creando reserva', ['msg' => $e->getMessage(), 'ip' => $ip]);
        http_response_code(500);
        echo json_encode(['error' => 'Error interno al crear la reserva. Intenta de nuevo.']);
        exit;
    }

    // C6: Enviar WhatsApp y reportar estado
    $whatsappEnviado = false;
    register_shutdown_function(function () use ($reserva, $reservaRepo, &$whatsappEnviado) {
        try {
            $ws = new WhatsAppService();
            if ($ws->enviarConfirmacionReserva($reserva)) {
                $reservaRepo->marcarWhatsappEnviado($reserva['id']);
                $whatsappEnviado = true;
            }
        } catch (\Throwable $e) {
            Logger::warning('WhatsApp shutdown error: ' . $e->getMessage());
        }
    });

    Logger::info('Reserva creada', ['codigo' => $reserva['codigo'], 'ip' => $ip]);

    echo json_encode([
        'success'          => true,
        'codigo'           => $reserva['codigo'],
        'reserva'          => $reserva,
        'message'          => "Reserva confirmada. Código: {$reserva['codigo']}",
        'whatsapp_enviado' => $whatsappEnviado,
    ]);
    exit;
}

// ── PUT ──────────────────────────────────────────────────────────────────────
if ($method === 'PUT') {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }

    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($body['id'] ?? 0);
    $estado = $body['estado'] ?? '';

    if (!$id || empty($estado)) {
        http_response_code(400);
        echo json_encode(['error' => 'id y estado son requeridos']);
        exit;
    }

    $repo = new ReservaRepository();
    $ok   = $repo->actualizarEstado($id, $estado);
    echo json_encode(['success' => $ok]);
    exit;
}

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }

    $id     = (int)($_GET['id'] ?? 0);
    $codigo = $_GET['codigo'] ?? '';

    $repo = new ReservaRepository();
    if ($id) {
        $ok = $repo->actualizarEstado($id, 'cancelada');
    } elseif ($codigo) {
        $ok = $repo->cancelarPorCodigo($codigo);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'id o codigo es requerido']);
        exit;
    }

    echo json_encode(['success' => $ok]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
