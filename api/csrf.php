<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';

$ip = get_client_ip();
RateLimiter::check("csrf_{$ip}", 30, 60);
RateLimiter::increment("csrf_{$ip}");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

echo json_encode(['token' => CsrfProtection::generateToken()]);
