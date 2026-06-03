<?php
declare(strict_types=1);

class HorarioRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function obtenerTodos(): array {
        $stmt = $this->db->prepare("SELECT * FROM horarios ORDER BY FIELD(dia,'lunes','martes','miercoles','jueves','viernes','sabado','domingo')");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerPorDia(string $diaSemana): ?array {
        $stmt = $this->db->prepare("SELECT * FROM horarios WHERE dia = :dia");
        $stmt->execute([':dia' => strtolower($diaSemana)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function estaAbierto(string $fecha, string $hora): bool {
        // Determinar día de la semana en español
        $diasMap = [
            'Monday'    => 'lunes',
            'Tuesday'   => 'martes',
            'Wednesday' => 'miercoles',
            'Thursday'  => 'jueves',
            'Friday'    => 'viernes',
            'Saturday'  => 'sabado',
            'Sunday'    => 'domingo',
        ];

        $ts      = strtotime($fecha);
        $diaNom  = $diasMap[date('l', $ts)] ?? null;
        if (!$diaNom) return false;

        $horario = $this->obtenerPorDia($diaNom);
        if (!$horario || $horario['cerrado']) return false;

        $apertura = $horario['hora_apertura'];
        $cierre   = $horario['hora_cierre'];

        // Si el cierre es '00:00:00' significa medianoche (fin del día)
        // Convertir a minutos para comparar
        $horaMin     = $this->timeToMinutes($hora);
        $aperturaMin = $this->timeToMinutes($apertura);
        $cierreMin   = $this->timeToMinutes($cierre);

        if ($cierreMin === 0) {
            // Horario hasta medianoche: apertura hasta 23:59
            return $horaMin >= $aperturaMin && $horaMin <= 1439;
        }

        if ($cierreMin < $aperturaMin) {
            // Cruza medianoche
            return $horaMin >= $aperturaMin || $horaMin <= $cierreMin;
        }

        return $horaMin >= $aperturaMin && $horaMin <= $cierreMin;
    }

    private function timeToMinutes(string $time): int {
        [$h, $m] = explode(':', $time);
        return (int)$h * 60 + (int)$m;
    }
}
