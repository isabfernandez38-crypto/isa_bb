<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
session_check();

$method = $_SERVER['REQUEST_METHOD'];
$repo   = new MesaRepository();

if ($method === 'GET') {
    echo json_encode(['success' => true, 'mesas' => $repo->obtenerTodas()]);
    exit;
}

if ($method === 'PUT') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $id     = (int)($body['id'] ?? 0);
    $estado = $body['estado'] ?? '';

    if (!$id || empty($estado)) {
        http_response_code(400);
        echo json_encode(['error' => 'id y estado requeridos']);
        exit;
    }

    $ok = $repo->actualizarEstado($id, $estado);
    if ($ok) {
        $cache = new Cache();
        $cache->flush();
    }
    echo json_encode(['success' => $ok]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
