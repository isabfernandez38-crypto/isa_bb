<?php
declare(strict_types=1);

class Cache {
    private string $cacheDir;
    private int $defaultTtl = 1800; // 30 minutos

    private static array $ttlMap = [
        'menu_all'         => 1800,
        'horarios'         => 3600,
        'mesas_disponibles'=> 60,
    ];

    public function __construct() {
        $this->cacheDir = defined('CACHE_DIR') ? CACHE_DIR : dirname(__DIR__, 2) . '/cache/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    private function getFile(string $key): string {
        // M1: Usar hash SHA-256 para evitar colisiones de claves similares
        return $this->cacheDir . hash('sha256', $key) . '.json';
    }

    public function get(string $key): mixed {
        $file = $this->getFile($key);
        if (!file_exists($file)) return null;

        $raw = file_get_contents($file);
        if ($raw === false) return null;

        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['expires_at'], $data['payload'])) return null;

        if (time() > $data['expires_at']) {
            unlink($file);
            return null;
        }

        return $data['payload'];
    }

    public function set(string $key, mixed $data, int $ttl = null): void {
        if ($ttl === null) {
            // Buscar TTL específico por prefijo de clave
            foreach (self::$ttlMap as $prefix => $t) {
                if (strpos($key, $prefix) === 0) {
                    $ttl = $t;
                    break;
                }
            }
            $ttl = $ttl ?? $this->defaultTtl;
        }

        $payload = [
            'expires_at' => time() + $ttl,
            'created_at' => time(),
            'payload'    => $data,
        ];

        file_put_contents($this->getFile($key), json_encode($payload, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    public function delete(string $key): void {
        $file = $this->getFile($key);
        if (file_exists($file)) unlink($file);
    }

    public function flush(): void {
        $files = glob($this->cacheDir . '*.json');
        if ($files) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }
}
