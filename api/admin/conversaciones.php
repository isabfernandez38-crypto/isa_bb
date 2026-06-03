<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
session_check();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$repo    = new ConversacionRepository();
$pagina  = max(1, (int)($_GET['pagina'] ?? 1));
$result  = $repo->obtenerTodas($pagina, 20);

// Si se pide detalle de una conversación
if (!empty($_GET['id'])) {
    $id       = (int)$_GET['id'];
    $mensajes = $repo->obtenerMensajesDe($id);
    echo json_encode(['success' => true, 'mensajes' => $mensajes]);
    exit;
}

echo json_encode(['success' => true] + $result);
