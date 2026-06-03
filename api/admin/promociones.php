<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
session_check();

$method = $_SERVER['REQUEST_METHOD'];
$repo   = new PromocionRepository();

if ($method === 'GET') {
    echo json_encode(['success' => true, 'promociones' => $repo->obtenerTodas()]);
    exit;
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    if (empty($body['titulo']) || empty($body['fecha_inicio']) || empty($body['fecha_fin'])) {
        http_response_code(422);
        echo json_encode(['error' => 'titulo, fecha_inicio y fecha_fin son requeridos']);
        exit;
    }
    $id = $repo->crear($body);
    echo json_encode(['success' => true, 'id' => $id]);
    exit;
}

if ($method === 'PUT') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($body['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'id requerido']);
        exit;
    }
    if (isset($body['toggle_activa'])) {
        $ok = $repo->toggleActiva($id);
        echo json_encode(['success' => $ok]);
        exit;
    }
    unset($body['id']);
    $ok = $repo->actualizar($id, $body);
    echo json_encode(['success' => $ok]);
    exit;
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'id requerido']);
        exit;
    }
    echo json_encode(['success' => $repo->eliminar($id)]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
