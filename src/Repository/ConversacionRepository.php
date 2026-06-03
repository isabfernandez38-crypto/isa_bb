<?php
declare(strict_types=1);

class ConversacionRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function crearOObtener(string $sessionId, string $ip): array {
        $stmt = $this->db->prepare("SELECT * FROM conversaciones WHERE session_id = :sid ORDER BY id DESC LIMIT 1");
        $stmt->execute([':sid' => $sessionId]);
        $conv = $stmt->fetch();

        if (!$conv) {
            $stmt = $this->db->prepare("
                INSERT INTO conversaciones (session_id, ip_address) VALUES (:sid, :ip)
            ");
            $stmt->execute([':sid' => $sessionId, ':ip' => $ip]);
            $id   = (int)$this->db->lastInsertId();
            $stmt = $this->db->prepare("SELECT * FROM conversaciones WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $conv = $stmt->fetch();
        }

        return $conv;
    }

    public function agregarMensaje(int $convId, string $rol, string $contenido, ?int $tokens): void {
        $stmt = $this->db->prepare("
            INSERT INTO mensajes (conversacion_id, rol, contenido, tokens_usados)
            VALUES (:conv_id, :rol, :contenido, :tokens)
        ");
        $stmt->execute([
            ':conv_id'  => $convId,
            ':rol'      => $rol,
            ':contenido'=> $contenido,
            ':tokens'   => $tokens,
        ]);
    }

    public function obtenerHistorial(string $sessionId): array {
        $stmt = $this->db->prepare("
            SELECT m.rol, m.contenido, m.created_at
            FROM mensajes m
            JOIN conversaciones c ON m.conversacion_id = c.id
            WHERE c.session_id = :sid
            ORDER BY m.id DESC
            LIMIT 20
        ");
        $stmt->execute([':sid' => $sessionId]);
        return array_reverse($stmt->fetchAll());
    }

    public function actualizarReserva(int $convId, int $reservaId): void {
        $stmt = $this->db->prepare("
            UPDATE conversaciones SET reserva_generada = 1, reserva_id = :rid WHERE id = :id
        ");
        $stmt->execute([':rid' => $reservaId, ':id' => $convId]);
    }

    // A4: Contar mensajes de una conversación para límite por sesión
    public function contarMensajesDe(int $convId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM mensajes WHERE conversacion_id = :id");
        $stmt->execute([':id' => $convId]);
        return (int)$stmt->fetchColumn();
    }

    public function obtenerTodas(int $pagina = 1, int $porPagina = 20): array {
        $offset = ($pagina - 1) * $porPagina;

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM conversaciones");
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        // C8: JOIN + GROUP BY en lugar de subquery N+1
        $stmt = $this->db->prepare("
            SELECT c.*, COUNT(m.id) AS total_mensajes
            FROM conversaciones c
            LEFT JOIN mensajes m ON m.conversacion_id = c.id
            GROUP BY c.id
            ORDER BY c.ultima_actividad DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit',  $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
        $stmt->execute();

        return ['data' => $stmt->fetchAll(), 'total' => $total, 'pagina' => $pagina];
    }

    public function obtenerMensajesDe(int $convId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM mensajes WHERE conversacion_id = :id ORDER BY id ASC
        ");
        $stmt->execute([':id' => $convId]);
        return $stmt->fetchAll();
    }

    public function contarHoy(): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM conversaciones WHERE DATE(iniciada_at) = CURDATE()");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    // M7: Eliminar conversaciones sin reserva más antiguas de $dias días
    public function limpiarAntiguas(int $dias = 90): int {
        $stmt = $this->db->prepare("
            DELETE FROM conversaciones
            WHERE ultima_actividad < DATE_SUB(NOW(), INTERVAL :dias DAY)
              AND reserva_generada = 0
        ");
        $stmt->bindValue(':dias', $dias, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
