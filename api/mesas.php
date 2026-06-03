<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$fecha   = trim($_GET['fecha']   ?? '');
$hora    = trim($_GET['hora']    ?? '');
$personas = (int)($_GET['personas'] ?? 2);

if (empty($fecha) || empty($hora)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros fecha y hora son requeridos']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(422);
    echo json_encode(['error' => 'Formato de fecha inválido (YYYY-MM-DD)']);
    exit;
}

$cache    = new Cache();
$cacheKey = "mesas_disponibles_{$fecha}_{$hora}_{$personas}";
$data     = $cache->get($cacheKey);

if ($data === null) {
    $repo  = new MesaRepository();
    $mesas = $repo->obtenerDisponibles($fecha, $hora, $personas);
    $data  = ['success' => true, 'mesas' => $mesas, 'disponibles' => count($mesas)];
    $cache->set($cacheKey, $data, 60);
}

echo json_encode($data);
