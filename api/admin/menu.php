<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
session_check();

$method = $_SERVER['REQUEST_METHOD'];
$repo   = new MenuRepository();
$cache  = new Cache();

if ($method === 'GET') {
    $platos = $repo->obtenerTodoAdmin();
    echo json_encode(['success' => true, 'platos' => $platos]);
    exit;
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    if (empty($body['nombre']) || empty($body['categoria_id']) || !isset($body['precio'])) {
        http_response_code(422);
        echo json_encode(['error' => 'nombre, categoria_id y precio son requeridos']);
        exit;
    }

    $id = $repo->crear($body);
    $cache->delete('menu_all');
    // Limpiar caché de categoría
    $categorias = $repo->obtenerCategorias();
    foreach ($categorias as $cat) {
        $cache->delete("menu_categoria_{$cat['slug']}");
    }

    echo json_encode(['success' => true, 'id' => $id]);
    exit;
}

if ($method === 'PUT') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($body['id'] ?? 0);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'id es requerido']);
        exit;
    }

    // Acción especial: toggle
    if (isset($body['toggle'])) {
        if ($body['toggle'] === 'disponible') {
            $ok = $repo->toggleDisponible($id);
        } elseif ($body['toggle'] === 'destacado') {
            $ok = $repo->toggleDestacado($id);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'toggle inválido']);
            exit;
        }
        $cache->delete('menu_all');
        echo json_encode(['success' => $ok]);
        exit;
    }

    unset($body['id']);
    $ok = $repo->actualizar($id, $body);
    $cache->delete('menu_all');
    $categorias = $repo->obtenerCategorias();
    foreach ($categorias as $cat) {
        $cache->delete("menu_categoria_{$cat['slug']}");
    }

    echo json_encode(['success' => $ok]);
    exit;
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'id es requerido']);
        exit;
    }

    $ok = $repo->eliminar($id);
    $cache->delete('menu_all');

    echo json_encode(['success' => $ok]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
