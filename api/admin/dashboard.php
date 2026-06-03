<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
session_check();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$reservaRepo = new ReservaRepository();
$mesaRepo    = new MesaRepository();
$convRepo    = new ConversacionRepository();

$reservasHoy     = $reservaRepo->contarHoy();
$reservasSemana  = $reservaRepo->contarSemana();
$totalMesas      = count($mesaRepo->obtenerTodas());
$mesasDisponibles= $mesaRepo->contarDisponiblesHoy();
$convHoy         = $convRepo->contarHoy();
$grafico         = $reservaRepo->estadisticasPorDia(7);
$proximas        = $reservaRepo->obtenerProximas(10);

echo json_encode([
    'success'           => true,
    'reservas_hoy'      => $reservasHoy,
    'reservas_semana'   => $reservasSemana,
    'total_mesas'       => $totalMesas,
    'mesas_disponibles' => $mesasDisponibles,
    'conversaciones_hoy'=> $convHoy,
    'grafico_reservas'  => $grafico,
    'proximas_reservas' => $proximas,
]);
