<?php
declare(strict_types=1);

class ReservaRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function crear(array $data): array {
        $codigo = $this->generarCodigo();

        $stmt = $this->db->prepare("
            INSERT INTO reservas
                (codigo, nombre_cliente, telefono, email, fecha, hora, num_personas, mesa_id, comentarios, origen)
            VALUES
                (:codigo, :nombre, :telefono, :email, :fecha, :hora, :personas, :mesa_id, :comentarios, :origen)
        ");

        $stmt->execute([
            ':codigo'      => $codigo,
            ':nombre'      => $data['nombre_cliente'],
            ':telefono'    => $data['telefono'],
            ':email'       => $data['email'] ?? null,
            ':fecha'       => $data['fecha'],
            ':hora'        => $data['hora'],
            ':personas'    => (int)$data['num_personas'],
            ':mesa_id'     => $data['mesa_id'] ?? null,
            ':comentarios' => $data['comentarios'] ?? null,
            ':origen'      => $data['origen'] ?? 'web',
        ]);

        $id = (int)$this->db->lastInsertId();
        return $this->buscarPorId($id);
    }

    private function generarCodigo(): string {
        $fecha = date('Ymd');
        do {
            $num    = random_int(1000, 9999);
            $codigo = "MCL-{$fecha}-{$num}";
            $stmt   = $this->db->prepare("SELECT id FROM reservas WHERE codigo = :codigo");
            $stmt->execute([':codigo' => $codigo]);
        } while ($stmt->fetch());
        return $codigo;
    }

    public function buscarPorId(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT r.*, m.numero as mesa_numero, m.zona as mesa_zona
            FROM reservas r
            LEFT JOIN mesas m ON r.mesa_id = m.id
            WHERE r.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function buscarPorCodigo(string $codigo): ?array {
        $stmt = $this->db->prepare("
            SELECT r.*, m.numero as mesa_numero, m.zona as mesa_zona
            FROM reservas r
            LEFT JOIN mesas m ON r.mesa_id = m.id
            WHERE r.codigo = :codigo
        ");
        $stmt->execute([':codigo' => strtoupper(trim($codigo))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function buscarPorFecha(string $fecha): array {
        $stmt = $this->db->prepare("
            SELECT r.*, m.numero as mesa_numero, m.zona as mesa_zona
            FROM reservas r
            LEFT JOIN mesas m ON r.mesa_id = m.id
            WHERE r.fecha = :fecha
            ORDER BY r.hora ASC
        ");
        $stmt->execute([':fecha' => $fecha]);
        return $stmt->fetchAll();
    }

    public function verificarDisponibilidad(string $fecha, string $hora, int $personas): bool {
        // Verificar que existan mesas con capacidad suficiente no reservadas en ±1 hora
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as disponibles
            FROM mesas m
            WHERE m.activa = 1
              AND m.capacidad >= :personas
              AND m.estado != 'mantenimiento'
              AND m.id NOT IN (
                  SELECT DISTINCT r.mesa_id
                  FROM reservas r
                  WHERE r.fecha = :fecha
                    AND r.mesa_id IS NOT NULL
                    AND r.estado IN ('pendiente','confirmada')
                    AND ABS(TIMESTAMPDIFF(MINUTE, r.hora, :hora)) < 60
              )
        ");
        $stmt->execute([':personas' => $personas, ':fecha' => $fecha, ':hora' => $hora]);
        $row = $stmt->fetch();
        return ($row['disponibles'] ?? 0) > 0;
    }

    public function asignarMesa(string $fecha, string $hora, int $personas): ?int {
        // Buscar la mesa más pequeña que pueda acomodar al grupo
        $stmt = $this->db->prepare("
            SELECT m.id
            FROM mesas m
            WHERE m.activa = 1
              AND m.capacidad >= :personas
              AND m.estado != 'mantenimiento'
              AND m.id NOT IN (
                  SELECT DISTINCT r.mesa_id
                  FROM reservas r
                  WHERE r.fecha = :fecha
                    AND r.mesa_id IS NOT NULL
                    AND r.estado IN ('pendiente','confirmada')
                    AND ABS(TIMESTAMPDIFF(MINUTE, r.hora, :hora)) < 60
              )
            ORDER BY m.capacidad ASC
            LIMIT 1
        ");
        $stmt->execute([':personas' => $personas, ':fecha' => $fecha, ':hora' => $hora]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : null;
    }

    public function actualizarEstado(int $id, string $estado): bool {
        $estados = ['pendiente','confirmada','cancelada','completada','no_show'];
        if (!in_array($estado, $estados, true)) return false;

        $stmt = $this->db->prepare("UPDATE reservas SET estado = :estado WHERE id = :id");
        return $stmt->execute([':estado' => $estado, ':id' => $id]);
    }

    public function cancelarPorCodigo(string $codigo): bool {
        $stmt = $this->db->prepare("
            UPDATE reservas SET estado = 'cancelada'
            WHERE codigo = :codigo AND estado IN ('pendiente','confirmada')
        ");
        $stmt->execute([':codigo' => strtoupper(trim($codigo))]);
        return $stmt->rowCount() > 0;
    }

    public function obtenerProximas(int $limite = 10): array {
        $stmt = $this->db->prepare("
            SELECT r.*, m.numero as mesa_numero
            FROM reservas r
            LEFT JOIN mesas m ON r.mesa_id = m.id
            WHERE r.fecha >= CURDATE()
              AND r.estado IN ('pendiente','confirmada')
            ORDER BY r.fecha ASC, r.hora ASC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function estadisticasPorDia(int $dias = 7): array {
        $stmt = $this->db->prepare("
            SELECT fecha, COUNT(*) as total
            FROM reservas
            WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)
              AND estado != 'cancelada'
            GROUP BY fecha
            ORDER BY fecha ASC
        ");
        $stmt->bindValue(':dias', $dias, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function marcarWhatsappEnviado(int $id): void {
        $stmt = $this->db->prepare("UPDATE reservas SET whatsapp_enviado = 1 WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function contarHoy(): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM reservas WHERE fecha = CURDATE() AND estado != 'cancelada'");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function contarSemana(): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM reservas
            WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              AND estado != 'cancelada'
        ");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function listarPaginado(int $pagina, int $porPagina, array $filtros = []): array {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filtros['fecha'])) {
            $where[]            = 'r.fecha = :fecha';
            $params[':fecha']   = $filtros['fecha'];
        }
        if (!empty($filtros['estado'])) {
            $where[]            = 'r.estado = :estado';
            $params[':estado']  = $filtros['estado'];
        }
        if (!empty($filtros['busqueda'])) {
            $where[]              = '(r.nombre_cliente LIKE :busq OR r.telefono LIKE :busq OR r.codigo LIKE :busq)';
            $params[':busq']      = '%' . $filtros['busqueda'] . '%';
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($pagina - 1) * $porPagina;

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM reservas r WHERE $whereStr");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT r.*, m.numero as mesa_numero, m.zona as mesa_zona
            FROM reservas r
            LEFT JOIN mesas m ON r.mesa_id = m.id
            WHERE $whereStr
            ORDER BY r.fecha DESC, r.hora DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
        $stmt->execute();

        return ['data' => $stmt->fetchAll(), 'total' => $total, 'pagina' => $pagina, 'por_pagina' => $porPagina];
    }
}
