<?php
declare(strict_types=1);

class MesaRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function obtenerTodas(): array {
        $stmt = $this->db->prepare("SELECT * FROM mesas ORDER BY numero ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerDisponibles(string $fecha, string $hora, int $personas): array {
        $stmt = $this->db->prepare("
            SELECT m.*
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
            ORDER BY m.capacidad ASC, m.numero ASC
        ");
        $stmt->execute([':personas' => $personas, ':fecha' => $fecha, ':hora' => $hora]);
        return $stmt->fetchAll();
    }

    public function actualizarEstado(int $id, string $estado): bool {
        $estados = ['disponible','ocupada','reservada','mantenimiento'];
        if (!in_array($estado, $estados, true)) return false;

        $stmt = $this->db->prepare("UPDATE mesas SET estado = :estado WHERE id = :id");
        return $stmt->execute([':estado' => $estado, ':id' => $id]);
    }

    public function contarDisponiblesHoy(): int {
        $fecha = date('Y-m-d');
        $hora  = date('H:i:s');
        $stmt  = $this->db->prepare("
            SELECT COUNT(*) FROM mesas
            WHERE activa = 1
              AND estado != 'mantenimiento'
              AND id NOT IN (
                  SELECT DISTINCT mesa_id FROM reservas
                  WHERE fecha = :fecha
                    AND mesa_id IS NOT NULL
                    AND estado IN ('pendiente','confirmada')
                    AND ABS(TIMESTAMPDIFF(MINUTE, hora, :hora)) < 60
              )
        ");
        $stmt->execute([':fecha' => $fecha, ':hora' => $hora]);
        return (int)$stmt->fetchColumn();
    }
}
