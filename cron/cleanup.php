<?php
/**
 * M5: Script de mantenimiento — ejecutar cada 30 minutos via cron o Task Scheduler
 * Windows: schtasks /create /sc minute /mo 30 /tn "MaiceloCleanup" /tr "php C:\xampp\htdocs\maicelo\cron\cleanup.php"
 * Linux:   * /30 * * * * php /var/www/html/maicelo/cron/cleanup.php
 */
declare(strict_types=1);

// Solo CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Solo accesible desde CLI');
}

require_once dirname(__DIR__) . '/config/bootstrap.php';

$db    = Database::getInstance();
$stats = [];

// 1. Liberar mesas cuyas reservas expiraron (reservas de días pasados)
$stmt = $db->prepare("
    UPDATE mesas m
    SET m.estado = 'disponible'
    WHERE m.estado = 'reservada'
      AND m.id NOT IN (
          SELECT DISTINCT r.mesa_id
          FROM reservas r
          WHERE r.mesa_id IS NOT NULL
            AND r.fecha >= CURDATE()
            AND r.estado IN ('pendiente', 'confirmada')
      )
");
$stmt->execute();
$stats['mesas_liberadas'] = $stmt->rowCount();

// 2. Marcar como canceladas las reservas pendientes de más de 24h en el pasado
$stmt = $db->prepare("
    UPDATE reservas
    SET estado = 'no_asistio'
    WHERE estado IN ('pendiente', 'confirmada')
      AND fecha < CURDATE()
      AND fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$stmt->execute();
$stats['reservas_caducadas'] = $stmt->rowCount();

// 3. M7: Eliminar conversaciones inactivas de más de 90 días
$stmt = $db->prepare("
    DELETE FROM conversaciones
    WHERE ultima_actividad < DATE_SUB(NOW(), INTERVAL 90 DAY)
      AND reserva_generada = 0
");
$stmt->execute();
$stats['conversaciones_limpiadas'] = $stmt->rowCount();

// 4. Limpiar archivos de cache expirados
$cacheDir = CACHE_DIR;
$cleaned  = 0;
foreach (glob($cacheDir . '*.json') ?: [] as $file) {
    $data = json_decode((string)file_get_contents($file), true);
    if (!is_array($data) || !isset($data['expires_at']) || time() > (int)$data['expires_at']) {
        @unlink($file);
        $cleaned++;
    }
}
$stats['cache_files_limpiados'] = $cleaned;

// 5. Rotar logs de rate limiting viejos (> 24h)
foreach (glob(CACHE_DIR . 'ratelimit/*.json') ?: [] as $file) {
    if (filemtime($file) < time() - 86400) {
        @unlink($file);
    }
}

Logger::info('Cron cleanup ejecutado', $stats);
echo '[' . date('Y-m-d H:i:s') . '] Cleanup OK: ' . json_encode($stats) . PHP_EOL;
