<?php
declare(strict_types=1);

class MenuRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function obtenerTodo(): array {
        $stmt = $this->db->prepare("
            SELECT m.*, c.nombre as categoria_nombre, c.slug as categoria_slug, c.icono as categoria_icono
            FROM menu m
            JOIN categorias_menu c ON m.categoria_id = c.id
            WHERE m.es_disponible = 1 AND c.activa = 1
            ORDER BY c.orden ASC, m.orden ASC, m.id ASC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll();

        // Agrupar por categoría
        $agrupado = [];
        foreach ($rows as $row) {
            $slug = $row['categoria_slug'];
            if (!isset($agrupado[$slug])) {
                $agrupado[$slug] = [
                    'nombre' => $row['categoria_nombre'],
                    'slug'   => $slug,
                    'icono'  => $row['categoria_icono'],
                    'platos' => [],
                ];
            }
            $agrupado[$slug]['platos'][] = $this->formatPlato($row);
        }

        return array_values($agrupado);
    }

    public function obtenerPorCategoria(string $slug): array {
        $stmt = $this->db->prepare("
            SELECT m.*, c.nombre as categoria_nombre, c.slug as categoria_slug, c.icono as categoria_icono
            FROM menu m
            JOIN categorias_menu c ON m.categoria_id = c.id
            WHERE c.slug = :slug AND m.es_disponible = 1
            ORDER BY m.orden ASC, m.id ASC
        ");
        $stmt->execute([':slug' => $slug]);
        return array_map([$this, 'formatPlato'], $stmt->fetchAll());
    }

    public function obtenerDestacados(): array {
        $stmt = $this->db->prepare("
            SELECT m.*, c.nombre as categoria_nombre, c.slug as categoria_slug
            FROM menu m
            JOIN categorias_menu c ON m.categoria_id = c.id
            WHERE m.es_destacado = 1 AND m.es_disponible = 1
            ORDER BY m.orden ASC, m.id ASC
        ");
        $stmt->execute();
        return array_map([$this, 'formatPlato'], $stmt->fetchAll());
    }

    public function obtenerTodoAdmin(): array {
        $stmt = $this->db->prepare("
            SELECT m.*, c.nombre as categoria_nombre, c.slug as categoria_slug
            FROM menu m
            JOIN categorias_menu c ON m.categoria_id = c.id
            ORDER BY c.orden ASC, m.orden ASC, m.id ASC
        ");
        $stmt->execute();
        return array_map([$this, 'formatPlato'], $stmt->fetchAll());
    }

    public function crear(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO menu (categoria_id, nombre, descripcion, precio, precio_alt, unidad_alt, imagen, es_destacado, es_disponible, es_nuevo, orden)
            VALUES (:categoria_id, :nombre, :descripcion, :precio, :precio_alt, :unidad_alt, :imagen, :es_destacado, :es_disponible, :es_nuevo, :orden)
        ");
        $stmt->execute([
            ':categoria_id'  => (int)$data['categoria_id'],
            ':nombre'        => $data['nombre'],
            ':descripcion'   => $data['descripcion'] ?? null,
            ':precio'        => (float)$data['precio'],
            ':precio_alt'    => isset($data['precio_alt']) && $data['precio_alt'] !== '' ? (float)$data['precio_alt'] : null,
            ':unidad_alt'    => $data['unidad_alt'] ?? null,
            ':imagen'        => $data['imagen'] ?? null,
            ':es_destacado'  => (int)($data['es_destacado'] ?? 0),
            ':es_disponible' => (int)($data['es_disponible'] ?? 1),
            ':es_nuevo'      => (int)($data['es_nuevo'] ?? 0),
            ':orden'         => (int)($data['orden'] ?? 0),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];

        $allowed = ['categoria_id','nombre','descripcion','precio','precio_alt','unidad_alt','imagen','es_destacado','es_disponible','es_nuevo','orden'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[]       = "$field = :$field";
                $params[":$field"] = $data[$field] === '' ? null : $data[$field];
            }
        }

        if (empty($fields)) return false;

        $stmt = $this->db->prepare("UPDATE menu SET " . implode(', ', $fields) . " WHERE id = :id");
        return $stmt->execute($params);
    }

    public function eliminar(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM menu WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function toggleDisponible(int $id): bool {
        $stmt = $this->db->prepare("UPDATE menu SET es_disponible = NOT es_disponible WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function toggleDestacado(int $id): bool {
        $stmt = $this->db->prepare("UPDATE menu SET es_destacado = NOT es_destacado WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function obtenerCategorias(): array {
        $stmt = $this->db->prepare("SELECT * FROM categorias_menu WHERE activa = 1 ORDER BY orden ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function formatPlato(array $row): array {
        return [
            'id'              => (int)$row['id'],
            'categoria_id'    => (int)$row['categoria_id'],
            'categoria_nombre'=> $row['categoria_nombre'] ?? null,
            'categoria_slug'  => $row['categoria_slug'] ?? null,
            'categoria_icono' => $row['categoria_icono'] ?? null,
            'nombre'          => $row['nombre'],
            'descripcion'     => $row['descripcion'],
            'precio'          => (float)$row['precio'],
            'precio_alt'      => $row['precio_alt'] ? (float)$row['precio_alt'] : null,
            'unidad_alt'      => $row['unidad_alt'],
            'imagen'          => $row['imagen'],
            'es_destacado'    => (bool)$row['es_destacado'],
            'es_disponible'   => (bool)$row['es_disponible'],
            'es_nuevo'        => (bool)$row['es_nuevo'],
        ];
    }
}
