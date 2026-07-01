<?php
declare(strict_types=1);

class EmailService {
    public function enviarConfirmacion(array $reserva): bool {
        $apiKey    = $_ENV['RESEND_API_KEY'] ?? '';
        $fromEmail = $_ENV['MAIL_FROM_EMAIL'] ?? 'onboarding@resend.dev';
        $fromName  = $_ENV['MAIL_FROM_NAME'] ?? 'Maicelo Restobar';

        if (empty($apiKey)) {
            Logger::warning('EmailService: RESEND_API_KEY no configurada en .env. Saltando envío.');
            return false;
        }

        $destinatario = $reserva['email'] ?? '';
        if (empty($destinatario)) {
            Logger::warning('EmailService: Correo del cliente vacío. Saltando envío.');
            return false;
        }

        $codigo = $reserva['codigo'];
        $subject = "Reserva Confirmada 🍽️ Código: {$codigo} - Maicelo Restobar";

        // Formatear detalles para el HTML
        $nombreCliente = htmlspecialchars($reserva['nombre_cliente']);
        $fechaFormateada = date('d/m/Y', strtotime($reserva['fecha']));
        $horaFormateada = substr($reserva['hora'], 0, 5);
        $personas = (int)$reserva['num_personas'];
        $mesa = $reserva['mesa_numero'] ? "Mesa " . $reserva['mesa_numero'] : "Por asignar";

        $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/maicelo', '/');
        // Usar link público permanente para garantizar compatibilidad total (Gmail bloquea localhost y base64)
        $logoSrc = "https://files.catbox.moe/vxa5ei.png";

        if (!empty($appUrl) && strpos($appUrl, 'localhost') === false) {
            $logoSrc = "{$appUrl}/assets/images/logo-maicelo.png";
        }

        $htmlContent = $this->obtenerPlantillaHtml($nombreCliente, $codigo, $fechaFormateada, $horaFormateada, $personas, $mesa, $logoSrc);

        $payload = [
            'from'    => "{$fromName} <{$fromEmail}>",
            'to'      => [$destinatario],
            'subject' => $subject,
            'html'    => $htmlContent
        ];

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT        => 15
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr || $httpCode !== 200) {
            Logger::error('EmailService: Error al enviar correo por Resend', [
                'code' => $httpCode,
                'err'  => $curlErr ?: $response
            ]);
            return false;
        }

        Logger::info('EmailService: Correo enviado exitosamente', ['codigo' => $codigo, 'destinatario' => $destinatario]);
        return true;
    }

    private function obtenerPlantillaHtml(
        string $nombre,
        string $codigo,
        string $fecha,
        string $hora,
        int $personas,
        string $mesa,
        string $logoSrc
    ): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Reserva</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0c0c0e; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #e0e0e0; -webkit-font-smoothing: antialiased;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #0c0c0e; padding: 40px 10px;">
        <tr>
            <td align="center">
                <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #121216; border: 1px solid #c9a84c; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.6);">
                    <!-- Cabecera -->
                    <tr>
                        <td align="center" style="padding: 30px 20px; background: linear-gradient(135deg, #1a1a2e, #121216); border-bottom: 1px solid rgba(201, 168, 76, 0.15);">
                            <img src="{$logoSrc}" alt="MAICELO RESTOBAR" style="max-height: 80px; width: auto; display: block; margin: 0 auto 10px auto;">
                            <p style="margin: 5px 0 0 0; color: #9a9a8a; font-size: 14px; letter-spacing: 2px; text-transform: uppercase;">SABOR Y PASIÓN</p>
                        </td>
                    </tr>
                    
                    <!-- Contenido Principal -->
                    <tr>
                        <td style="padding: 40px 35px;">
                            <h2 style="margin: 0 0 15px 0; color: #ffffff; font-size: 20px; font-weight: 600;">¡Hola, {$nombre}!</h2>
                            <p style="margin: 0 0 25px 0; color: #a5a5b5; font-size: 15px; line-height: 1.6;">
                                Tu reserva ha sido registrada y confirmada con éxito. Estamos preparando todo para darte una experiencia inolvidable. A continuación, te compartimos los detalles de tu cita gastronómica:
                            </p>
                            
                            <!-- Tarjeta de Reserva -->
                            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #1a1a24; border-radius: 12px; margin-bottom: 30px; border-left: 4px solid #c9a84c;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="5">
                                            <tr>
                                                <td width="35%" style="color: #9a9a8a; font-size: 14px; font-weight: 600;">Código de Reserva:</td>
                                                <td style="color: #c9a84c; font-size: 15px; font-weight: 700; font-family: monospace;">{$codigo}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #9a9a8a; font-size: 14px; font-weight: 600;">Fecha:</td>
                                                <td style="color: #ffffff; font-size: 14px;">{$fecha}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #9a9a8a; font-size: 14px; font-weight: 600;">Hora:</td>
                                                <td style="color: #ffffff; font-size: 14px;">{$hora} hrs</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #9a9a8a; font-size: 14px; font-weight: 600;">Invitados:</td>
                                                <td style="color: #ffffff; font-size: 14px;">{$personas} personas</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #9a9a8a; font-size: 14px; font-weight: 600;">Mesa:</td>
                                                <td style="color: #ffffff; font-size: 14px; font-weight: 600;">{$mesa}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Botón e Ubicación -->
                            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 10px;">
                                <tr>
                                    <td align="center">
                                        <a href="https://maps.app.goo.gl/KVWMRNe14V4zbLyf8" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #c9a84c, #a8893a); color: #121216; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 15px rgba(201, 168, 76, 0.3);">Cómo llegar al local 📍</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Pie de página -->
                    <tr>
                        <td align="center" style="padding: 30px 20px; background-color: #0b0b0d; border-top: 1px solid rgba(201, 168, 76, 0.1); color: #757585; font-size: 12px; line-height: 1.8;">
                            <p style="margin: 0 0 8px 0; color: #a5a5b5; font-weight: 600;">Calle Armando Blondet 149, San Isidro, Lima - Perú</p>
                            <p style="margin: 0 0 15px 0;">¿Tienes dudas? Escríbenos directamente por WhatsApp al <a href="https://wa.me/51991917732" style="color: #c9a84c; text-decoration: none; font-weight: 600;">+51 991 917 732</a></p>
                            <p style="margin: 0; font-size: 11px; opacity: 0.6;">&copy; 2026 Maicelo Restobar. Todos los derechos reservados.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
