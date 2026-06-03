<?php
declare(strict_types=1);

class PromocionRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function obtenerActivas(): array {
        $stmt = $this->db->prepare("
            SELECT * FROM promociones
            WHERE activa = 1
              AND fecha_inicio <= CURDATE()
              AND fecha_fin >= CURDATE()
            ORDER BY id ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerTodas(): array {
        $stmt = $this->db->prepare("SELECT * FROM promociones ORDER BY activa DESC, fecha_fin DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function crear(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO promociones (titulo, descripcion, imagen, fecha_inicio, fecha_fin, activa)
            VALUES (:titulo, :descripcion, :imagen, :fecha_inicio, :fecha_fin, :activa)
        ");
        $stmt->execute([
            ':titulo'       => $data['titulo'],
            ':descripcion'  => $data['descripcion'] ?? null,
            ':imagen'       => $data['imagen'] ?? null,
            ':fecha_inicio' => $data['fecha_inicio'],
            ':fecha_fin'    => $data['fecha_fin'],
            ':activa'       => (int)($data['activa'] ?? 1),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE promociones SET titulo=:titulo, descripcion=:descripcion, imagen=:imagen,
                fecha_inicio=:fecha_inicio, fecha_fin=:fecha_fin, activa=:activa
            WHERE id=:id
        ");
        return $stmt->execute([
            ':titulo'       => $data['titulo'],
            ':descripcion'  => $data['descripcion'] ?? null,
            ':imagen'       => $data['imagen'] ?? null,
            ':fecha_inicio' => $data['fecha_inicio'],
            ':fecha_fin'    => $data['fecha_fin'],
            ':activa'       => (int)($data['activa'] ?? 1),
            ':id'           => $id,
        ]);
    }

    public function toggleActiva(int $id): bool {
        $stmt = $this->db->prepare("UPDATE promociones SET activa = NOT activa WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function eliminar(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM promociones WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
