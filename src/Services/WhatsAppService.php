<?php
declare(strict_types=1);

class WhatsAppService {
    private string $apiUrl;
    private string $apiKey;
    private string $instance;

    public function __construct() {
        $this->apiUrl   = rtrim($_ENV['EVOLUTION_API_URL'] ?? '', '/');
        $this->apiKey   = $_ENV['EVOLUTION_API_KEY'] ?? '';
        $this->instance = $_ENV['EVOLUTION_INSTANCE'] ?? '';
    }

    private function limpiarNumero(string $telefono): string {
        // Eliminar caracteres no numéricos excepto +
        $numero = preg_replace('/[^0-9]/', '', $telefono);

        // Si empieza con 0, reemplazar con 51
        if (strpos($numero, '0') === 0) {
            $numero = '51' . substr($numero, 1);
        }

        // Si no empieza con 51, agregar prefijo de Perú
        if (strpos($numero, '51') !== 0) {
            $numero = '51' . $numero;
        }

        return $numero;
    }

    private function enviarMensaje(string $telefono, string $texto): bool {
        if (empty($this->apiKey) || empty($this->instance)) {
            Logger::warning('WhatsApp: API key o instancia no configurada');
            return false;
        }

        $numero  = $this->limpiarNumero($telefono);
        $url     = "{$this->apiUrl}/message/sendText/{$this->instance}";
        $payload = json_encode(['number' => $numero, 'text' => $texto]);

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\napikey: {$this->apiKey}\r\n",
                'content' => $payload,
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) {
            Logger::warning('WhatsApp: fallo al enviar mensaje', ['numero' => $numero]);
            return false;
        }

        $data = json_decode($response, true);
        $ok   = isset($data['key']['id']) || isset($data['status']);
        if (!$ok) {
            Logger::warning('WhatsApp: respuesta inesperada', ['response' => $response]);
        }
        return $ok;
    }

    public function enviarConfirmacionReserva(array $reserva): bool {
        $fecha = $this->formatearFecha($reserva['fecha']);
        $hora  = $this->formatearHora($reserva['hora']);

        $mensaje = "✅ *Reserva Confirmada - Maicelo Restobar*\n\n"
            . "Hola {$reserva['nombre_cliente']}! Tu reserva ha sido confirmada 🎉\n\n"
            . "📋 *Código:* {$reserva['codigo']}\n"
            . "📅 *Fecha:* {$fecha}\n"
            . "⏰ *Hora:* {$hora}\n"
            . "👥 *Personas:* {$reserva['num_personas']}\n"
            . "📍 *Lugar:* Calle Armando Blondet 149, San Isidro\n\n"
            . "_Para cancelar comunícate al +51 991 917 732 con al menos 2 horas de anticipación._\n\n"
            . "¡Te esperamos! 🍽️";

        $ok = $this->enviarMensaje($reserva['telefono'], $mensaje);
        if ($ok) {
            Logger::info('WhatsApp: confirmación enviada', ['codigo' => $reserva['codigo']]);
        }
        return $ok;
    }

    public function enviarRecordatorio(array $reserva): bool {
        $hora = $this->formatearHora($reserva['hora']);

        $mensaje = "⏰ *Recordatorio - Maicelo Restobar*\n\n"
            . "Hola {$reserva['nombre_cliente']}! Te recordamos que tienes una reserva hoy\n"
            . "a las {$hora} para {$reserva['num_personas']} personas.\n\n"
            . "📍 Calle Armando Blondet 149, San Isidro\n\n"
            . "¡Te esperamos! 🍽️";

        return $this->enviarMensaje($reserva['telefono'], $mensaje);
    }

    private function formatearFecha(string $fecha): string {
        $diasEs   = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
        $mesesEs  = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        $ts       = strtotime($fecha);
        $diaSem   = (int)date('N', $ts) - 1;
        $dia      = (int)date('j', $ts);
        $mes      = (int)date('n', $ts) - 1;
        $anio     = date('Y', $ts);
        return "{$diasEs[$diaSem]} {$dia} de {$mesesEs[$mes]} de {$anio}";
    }

    private function formatearHora(string $hora): string {
        // M4: Validar que el formato sea HH:MM antes de explotar
        $parts = explode(':', $hora);
        if (count($parts) < 2) {
            return $hora; // fallback: devolver tal cual
        }
        $h    = (int)$parts[0];
        $m    = str_pad((string)(int)$parts[1], 2, '0', STR_PAD_LEFT);
        $ampm = $h >= 12 ? 'PM' : 'AM';
        $h12  = $h > 12 ? $h - 12 : ($h === 0 ? 12 : $h);
        return sprintf('%d:%s %s', $h12, $m, $ampm);
    }
}
