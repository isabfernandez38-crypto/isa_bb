<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
session_check();

$method = $_SERVER['REQUEST_METHOD'];
$repo   = new ReservaRepository();

if ($method === 'GET') {
    $pagina    = max(1, (int)($_GET['pagina'] ?? 1));
    $porPagina = max(1, min(100, (int)($_GET['por_pagina'] ?? 20)));
    $filtros   = [
        'fecha'    => $_GET['fecha']    ?? '',
        'estado'   => $_GET['estado']   ?? '',
        'busqueda' => $_GET['busqueda'] ?? '',
    ];
    echo json_encode(['success' => true] + $repo->listarPaginado($pagina, $porPagina, $filtros));
    exit;
}

if ($method === 'PUT') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $id     = (int)($body['id'] ?? 0);
    $estado = $body['estado'] ?? '';

    if (!$id || empty($estado)) {
        http_response_code(400);
        echo json_encode(['error' => 'id y estado son requeridos']);
        exit;
    }

    $ok = $repo->actualizarEstado($id, $estado);

    // Si se confirma, enviar WhatsApp
    if ($ok && $estado === 'confirmada') {
        $reserva = $repo->buscarPorId($id);
        if ($reserva && !$reserva['whatsapp_enviado']) {
            register_shutdown_function(function () use ($reserva, $repo) {
                try {
                    $ws = new WhatsAppService();
                    if ($ws->enviarConfirmacionReserva($reserva)) {
                        $repo->marcarWhatsappEnviado($reserva['id']);
                    }
                } catch (\Throwable $e) {
                    Logger::warning('WhatsApp admin confirm error: ' . $e->getMessage());
                }
            });
        }
    }

    echo json_encode(['success' => $ok]);
    exit;
}

if ($method === 'DELETE') {
    $id     = (int)($_GET['id'] ?? 0);
    $codigo = $_GET['codigo'] ?? '';

    if ($id) {
        $ok = $repo->actualizarEstado($id, 'cancelada');
    } elseif ($codigo) {
        $ok = $repo->cancelarPorCodigo($codigo);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'id o codigo requerido']);
        exit;
    }

    echo json_encode(['success' => $ok]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
