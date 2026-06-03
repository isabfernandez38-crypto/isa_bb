<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    $db   = Database::getInstance();
    $stmt = $db->query("
        SELECT dia, hora_apertura, hora_cierre, cerrado
        FROM horarios
        ORDER BY FIELD(dia,'lunes','martes','miercoles',
                           'jueves','viernes','sabado','domingo')
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $diasMap = [
        1 => 'lunes',    2 => 'martes', 3 => 'miercoles',
        4 => 'jueves',   5 => 'viernes', 6 => 'sabado',
        0 => 'domingo',
    ];
    $hoy = $diasMap[(int)date('w')];

    $horarios = [];
    foreach ($rows as $r) {
        $horarios[] = [
            'dia'           => $r['dia'],
            'hora_apertura' => substr($r['hora_apertura'], 0, 5),
            'hora_cierre'   => substr($r['hora_cierre'],   0, 5),
            'cerrado'       => (bool)(int)$r['cerrado'],
            'es_hoy'        => ($r['dia'] === $hoy),
        ];
    }

    echo json_encode([
        'success'  => true,
        'horarios' => $horarios,
        'hoy'      => $hoy,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error'  => 'Error al obtener horarios',
        'detail' => APP_DEBUG ? $e->getMessage() : null,
    ]);
}
