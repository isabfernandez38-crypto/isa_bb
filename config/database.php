<?php
declare(strict_types=1);

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $host    = $_ENV['DB_HOST']    ?? 'localhost';
                $port    = $_ENV['DB_PORT']    ?? '3306';
                $name    = $_ENV['DB_NAME']    ?? 'maicelo_db';
                $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
                $user    = $_ENV['DB_USER']    ?? 'root';
                $pass    = $_ENV['DB_PASS']    ?? '';

                $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_FOUND_ROWS   => true,
                ]);
            } catch (PDOException $e) {
                // Logger puede no estar disponible aún, usar error_log como fallback
                if (class_exists('Logger')) {
                    Logger::critical('DB connection failed', ['msg' => $e->getMessage()]);
                } else {
                    error_log('[CRITICAL] DB connection failed: ' . $e->getMessage());
                }
                throw new RuntimeException('Error de conexión a la base de datos');
            }
        }
        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}
