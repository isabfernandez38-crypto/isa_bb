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
    $db = Database::getInstance();

    $stmt = $db->query("
        SELECT
            m.id, m.nombre, m.descripcion, m.precio,
            m.precio_alt, m.unidad_alt, m.imagen,
            m.es_destacado, m.es_nuevo,
            c.nombre  AS categoria_nombre,
            c.slug    AS categoria_slug,
            c.icono   AS categoria_icono,
            c.orden   AS categoria_orden
        FROM menu m
        JOIN categorias_menu c ON c.id = m.categoria_id
        WHERE m.es_disponible = 1 AND c.activa = 1
        ORDER BY c.orden ASC, m.orden ASC, m.nombre ASC
    ");
    $platos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categorias = [];
    $agrupado   = [];
    $slugsVistos = [];

    foreach ($platos as $p) {
        $slug = $p['categoria_slug'];

        if (!isset($slugsVistos[$slug])) {
            $slugsVistos[$slug] = true;
            $categorias[] = [
                'nombre' => $p['categoria_nombre'],
                'slug'   => $slug,
                'icono'  => $p['categoria_icono'],
                'orden'  => (int)$p['categoria_orden'],
            ];
            $agrupado[$slug] = [];
        }

        // Resolver URL de imagen
        if (empty($p['imagen'])) {
            $p['imagen_url'] = null;
        } elseif (str_starts_with($p['imagen'], 'http')) {
            $p['imagen_url'] = $p['imagen'];
        } else {
            $p['imagen_url'] = APP_URL . '/' . ltrim($p['imagen'], '/');
        }

        $agrupado[$slug][] = $p;
    }

    echo json_encode([
        'success'    => true,
        'total'      => count($platos),
        'categorias' => $categorias,
        'agrupado'   => $agrupado,
        'platos'     => $platos,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error'  => 'Error al obtener el menú',
        'detail' => APP_DEBUG ? $e->getMessage() : null,
    ]);
}
