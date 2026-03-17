<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use RuntimeException;
use PDO;

final class Catalogo
{
    public static function IMPUESTOS_OPCIONES(): array
    {
        return ['ITBIS', 'ISC', 'INDOTEL'];
    }

    public static function listarPresentaciones(): array
    {
        return self::listar('presentaciones');
    }

    public static function buscarPresentacion(int $id): ?array
    {
        return self::buscar('presentaciones', $id);
    }

    public static function guardarPresentacion(?int $id, array $data, int $userId): int
    {
        return self::guardar('presentaciones', $id, $data, $userId);
    }

    public static function listarEmpaques(): array
    {
        return self::listar('empaques');
    }

    public static function buscarEmpaque(int $id): ?array
    {
        return self::buscar('empaques', $id);
    }

    public static function guardarEmpaque(?int $id, array $data, int $userId): int
    {
        return self::guardar('empaques', $id, $data, $userId);
    }

    public static function listarTiposArticulo(): array
    {
        return self::listar('tipos_articulo');
    }

    public static function buscarTipoArticulo(int $id): ?array
    {
        return self::buscar('tipos_articulo', $id);
    }

    public static function guardarTipoArticulo(?int $id, array $data, int $userId): int
    {
        return self::guardar('tipos_articulo', $id, $data, $userId);
    }

    public static function listarCategoriasArticulo(): array
    {
        return self::listar('categorias_articulo');
    }

    public static function buscarCategoriaArticulo(int $id): ?array
    {
        return self::buscar('categorias_articulo', $id);
    }

    public static function guardarCategoriaArticulo(?int $id, array $data, int $userId): int
    {
        return self::guardar('categorias_articulo', $id, $data, $userId);
    }

    public static function listarCategoriasArticuloActivas(): array
    {
        $sql = "SELECT id, descripcion
                FROM categorias_articulo
                WHERE estado = 'activo'
                ORDER BY descripcion ASC";
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function listarSubcategoriasArticulo(): array
    {
        $sql = 'SELECT s.id, s.categoria_id, c.descripcion AS categoria_descripcion, s.descripcion, s.estado
                FROM subcategorias_articulo s
                INNER JOIN categorias_articulo c ON c.id = s.categoria_id
                ORDER BY s.id DESC';
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function buscarSubcategoriaArticulo(int $id): ?array
    {
        return self::buscar('subcategorias_articulo', $id);
    }

    public static function guardarSubcategoriaArticulo(?int $id, array $data, int $userId): int
    {
        $categoriaId = (int) ($data['categoria_id'] ?? 0);
        $descripcion = trim((string) ($data['descripcion'] ?? ''));
        $estado = strtolower(trim((string) ($data['estado'] ?? 'activo')));

        if ($categoriaId <= 0) {
            throw new RuntimeException('Debes seleccionar una categoria.');
        }
        if ($descripcion === '') {
            throw new RuntimeException('La descripcion es obligatoria.');
        }
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            $estado = 'activo';
        }

        $stmt = Db::conexion()->prepare('SELECT id FROM categorias_articulo WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $categoriaId]);
        if (!$stmt->fetch()) {
            throw new RuntimeException('La categoria seleccionada no existe.');
        }

        $id = $id !== null && $id > 0 ? $id : null;
        self::validarDescripcionSubcategoriaUnica($categoriaId, $descripcion, $id);

        if ($id !== null) {
            $sql = 'UPDATE subcategorias_articulo
                    SET categoria_id = :categoria_id,
                        descripcion = :descripcion,
                        estado = :estado,
                        updated_by = :updated_by
                    WHERE id = :id';
            $stmt = Db::conexion()->prepare($sql);
            $stmt->execute([
                'categoria_id' => $categoriaId,
                'descripcion' => $descripcion,
                'estado' => $estado,
                'updated_by' => $userId > 0 ? $userId : null,
                'id' => $id,
            ]);
            return $id;
        }

        $sql = 'INSERT INTO subcategorias_articulo (categoria_id, descripcion, estado, created_by, updated_by)
                VALUES (:categoria_id, :descripcion, :estado, :created_by, :updated_by)';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute([
            'categoria_id' => $categoriaId,
            'descripcion' => $descripcion,
            'estado' => $estado,
            'created_by' => $userId > 0 ? $userId : null,
            'updated_by' => $userId > 0 ? $userId : null,
        ]);

        return (int) Db::conexion()->lastInsertId();
    }

    public static function listarArticulos(): array
    {
        $sql = 'SELECT a.id, a.codigo, a.nombre, a.estado, a.stock_actual, a.stock_actual_kg, a.unidad_base_id,
                       COALESCE(a.tiene_receta, 0) AS tiene_receta,
                       (SELECT COALESCE(SUM(v.stock_actual), 0)
                        FROM articulos_variantes_stock v
                        WHERE v.articulo_id = a.id) AS stock_variantes_total,
                       ta.descripcion AS tipo_articulo_descripcion,
                       c.descripcion AS categoria_descripcion,
                       sc.descripcion AS subcategoria_descripcion,
                       f.descripcion AS familia_descripcion,
                       m.descripcion AS marca_descripcion
                FROM articulos a
                LEFT JOIN tipos_articulo ta ON ta.id = a.tipo_articulo_id
                LEFT JOIN categorias_articulo c ON c.id = a.categoria_id
                LEFT JOIN subcategorias_articulo sc ON sc.id = a.subcategoria_id
                LEFT JOIN familias f ON f.id = a.familia_id
                LEFT JOIN marcas m ON m.id = a.marca_id
                ORDER BY a.id DESC';
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function buscarArticulo(int $id): ?array
    {
        $row = self::buscar('articulos', $id);
        if (!$row) {
            return null;
        }

        $impuestosRaw = trim((string) ($row['impuestos'] ?? ''));
        $row['impuestos_array'] = $impuestosRaw === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $impuestosRaw)), static fn ($v): bool => $v !== ''));
        $row['presentacion_ids'] = self::listarRelacionIds('articulos_presentaciones', 'presentacion_id', $id);
        $row['empaque_ids'] = self::listarRelacionIds('articulos_empaques', 'empaque_id', $id);
        if ($row['presentacion_ids'] === [] && (int) ($row['presentacion_id'] ?? 0) > 0) {
            $row['presentacion_ids'] = [(int) $row['presentacion_id']];
        }
        if ($row['empaque_ids'] === [] && (int) ($row['empaque_id'] ?? 0) > 0) {
            $row['empaque_ids'] = [(int) $row['empaque_id']];
        }
        return $row;
    }

    /** @return array<int, array<string, mixed>> */
    public static function listarVariantesRecetaProductoFinalStock(): array
    {
        $sql = "SELECT a.id AS articulo_id,
                       a.codigo AS articulo_codigo,
                       COALESCE(NULLIF(TRIM(a.descripcion), ''), a.nombre) AS articulo_descripcion,
                       ap.presentacion_id,
                       p.descripcion AS presentacion_descripcion,
                       ae.empaque_id,
                       e.descripcion AS empaque_descripcion,
                       COALESCE(v.stock_actual, 0) AS stock_actual
                FROM articulos a
                INNER JOIN articulos_presentaciones ap ON ap.articulo_id = a.id
                INNER JOIN presentaciones p ON p.id = ap.presentacion_id
                INNER JOIN articulos_empaques ae ON ae.articulo_id = a.id
                INNER JOIN empaques e ON e.id = ae.empaque_id
                INNER JOIN recetas_producto_final r ON r.producto_articulo_id = a.id
                                                   AND r.presentacion_id = ap.presentacion_id
                                                   AND r.empaque_id = ae.empaque_id
                LEFT JOIN articulos_variantes_stock v ON v.articulo_id = a.id
                                                     AND v.presentacion_id = ap.presentacion_id
                                                     AND v.empaque_id = ae.empaque_id
                WHERE a.tiene_receta = 1
                  AND a.estado = 'activo'
                ORDER BY a.descripcion ASC, p.descripcion ASC, e.descripcion ASC";
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    public static function guardarArticulo(?int $id, array $data, int $userId): int
    {
        $pdo = Db::conexion();
        $pdo->beginTransaction();

        try {
        $estado = strtolower(trim((string) ($data['estado'] ?? 'activo')));
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            $estado = 'activo';
        }

        $tipoArticuloId = self::intOrNull($data['tipo_articulo_id'] ?? null);
        $id = $id !== null && $id > 0 ? $id : null;
        if ($tipoArticuloId === null) {
            throw new RuntimeException('Debes seleccionar el tipo de articulo.');
        }

        $codigo = '';
        $secuenciaId = null;
        $secuenciaNext = null;

        if ($id !== null) {
            $actual = self::buscar('articulos', $id);
            if (!$actual) {
                throw new RuntimeException('Articulo no encontrado.');
            }

            $tipoActual = self::intOrNull($actual['tipo_articulo_id'] ?? null);
            if ($tipoActual !== null && $tipoArticuloId !== $tipoActual) {
                throw new RuntimeException('No se puede editar el tipo de articulo despues de asignado.');
            }

            $codigo = trim((string) ($actual['codigo'] ?? ''));
        } else {
            $secuencia = self::obtenerSecuenciaActivaPorTipo($tipoArticuloId, $pdo);
            $secuenciaId = (int) ($secuencia['id'] ?? 0);
            $secuenciaNext = (int) ($secuencia['valor_actual'] ?? 0) + (int) ($secuencia['incremento'] ?? 1);
            $codigo = (string) ($secuencia['prefijo'] ?? '') . str_pad((string) $secuenciaNext, (int) ($secuencia['longitud'] ?? 8), '0', STR_PAD_LEFT);
        }

        if ($codigo === '') {
            throw new RuntimeException('No se pudo generar codigo para el articulo.');
        }
        self::validarCodigoArticuloUnico($codigo, $id);

        $nombre = trim((string) ($data['nombre'] ?? ''));
        if ($nombre === '') {
            $nombre = trim((string) ($data['descripcion'] ?? ''));
        }
        if ($nombre === '') {
            $nombre = $codigo;
        }

        $categoriaId = self::intOrNull($data['categoria_id'] ?? null);
        $subcategoriaId = self::intOrNull($data['subcategoria_id'] ?? null);
        $marcaId = self::intOrNull($data['marca_id'] ?? null);
        $familiaId = self::intOrNull($data['familia_id'] ?? null);
        $presentacionIds = self::idsOrEmpty($data['presentacion_ids'] ?? ($data['presentacion_id'] ?? []));
        $empaqueIds = self::idsOrEmpty($data['empaque_ids'] ?? ($data['empaque_id'] ?? []));
        $presentacionId = $presentacionIds[0] ?? null;
        $empaqueId = $empaqueIds[0] ?? null;
        $proveedorDefaultId = self::intOrNull($data['proveedor_default_id'] ?? null);
        $esComprable = isset($data['es_comprable']) ? 1 : 0;
        $insumoReceta = isset($data['insumo_receta']) ? 1 : 0;
        $esFabricable = isset($data['es_fabricable']) ? 1 : 0;
        $tieneReceta = isset($data['tiene_receta']) ? 1 : 0;
        if ($id !== null) {
            $esComprable = (int) ($actual['es_comprable'] ?? 0);
            $insumoReceta = (int) ($actual['insumo_receta'] ?? 0);
            $esFabricable = (int) ($actual['es_fabricable'] ?? 0);
            $tieneReceta = (int) ($actual['tiene_receta'] ?? 0);
        }
        if (($esComprable + $insumoReceta + $esFabricable + $tieneReceta) === 0) {
            throw new RuntimeException('Debes activar al menos una opcion: Es comprable, Insumo receta, Receta base o Receta producto final.');
        }
        if ($esFabricable === 1 && $tieneReceta === 1) {
            throw new RuntimeException('No puedes activar "Receta base" y "Receta producto final" al mismo tiempo.');
        }
        if ($tieneReceta === 1 && ($marcaId === null || $marcaId <= 0)) {
            throw new RuntimeException('Si activas "Receta producto final", la marca es obligatoria.');
        }
        if ($tieneReceta === 1 && ($familiaId === null || $familiaId <= 0)) {
            throw new RuntimeException('Si activas "Receta producto final", la familia es obligatoria.');
        }
        if ($tieneReceta === 1 && $presentacionIds === []) {
            throw new RuntimeException('Si activas "Receta producto final", debes seleccionar al menos una presentacion.');
        }
        if ($tieneReceta === 1 && $empaqueIds === []) {
            throw new RuntimeException('Si activas "Receta producto final", debes seleccionar al menos un empaque.');
        }
        if ($tieneReceta === 1) {
            $familia = self::buscar('familias', (int) $familiaId);
            if (!$familia) {
                throw new RuntimeException('La familia seleccionada no existe.');
            }
            if ((int) ($familia['marca_id'] ?? 0) !== (int) $marcaId) {
                throw new RuntimeException('La familia seleccionada no corresponde a la marca indicada.');
            }
        } else {
            $marcaId = null;
            $familiaId = null;
            $presentacionIds = [];
            $empaqueIds = [];
            $presentacionId = null;
            $empaqueId = null;
        }
        $stockMinimoInput = $data['stock_minimo'] ?? null;
        $stockMinimo = self::decimalOrNull($stockMinimoInput);
        if ($stockMinimoInput === null || trim((string) $stockMinimoInput) === '' || $stockMinimo === null) {
            throw new RuntimeException('El stock minimo es obligatorio.');
        }

        $impuestos = $data['impuestos'] ?? [];
        if (!is_array($impuestos)) {
            $impuestos = [];
        }
        $impuestosLimpio = [];
        foreach ($impuestos as $imp) {
            $imp = strtoupper(trim((string) $imp));
            if (in_array($imp, self::IMPUESTOS_OPCIONES(), true)) {
                $impuestosLimpio[] = $imp;
            }
        }
        $impuestosStr = implode(',', array_values(array_unique($impuestosLimpio)));

        $payload = [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'descripcion' => trim((string) ($data['descripcion'] ?? '')),
            'estado' => $estado,
            'tipo_articulo_id' => $tipoArticuloId,
            'categoria_id' => $categoriaId,
            'subcategoria_id' => $subcategoriaId,
            'marca_id' => $marcaId,
            'familia_id' => $familiaId,
            'unidad_base_id' => trim((string) ($data['unidad_base_id'] ?? 'u')),
            'presentacion_id' => $presentacionId,
            'empaque_id' => $empaqueId,
            'maneja_inventario' => 1,
            'stock_minimo' => $stockMinimo,
            'stock_maximo' => self::decimalOrNull($data['stock_maximo'] ?? null),
            'punto_reorden' => self::decimalOrNull($data['punto_reorden'] ?? null),
            'ubicacion' => trim((string) ($data['ubicacion'] ?? '')),
            'lote' => 0,
            'vence' => 0,
            'es_comprable' => $esComprable,
            'costo_ultimo' => self::decimalOrNull($data['costo_ultimo'] ?? null) ?? 0.0,
            'costo_promedio' => self::decimalOrNull($data['costo_promedio'] ?? null),
            'proveedor_default_id' => $proveedorDefaultId,
            'insumo_receta' => $insumoReceta,
            'es_fabricable' => $esFabricable,
            'tiene_receta' => $tieneReceta,
            'rendimiento' => self::decimalOrNull($data['rendimiento'] ?? null),
            'merma_pct' => self::decimalOrNull($data['merma_pct'] ?? null),
            'impuestos' => $impuestosStr,
            'foto_path' => trim((string) ($data['foto_path'] ?? '')),
        ];

        if (!in_array($payload['unidad_base_id'], ['kg', 'u'], true)) {
            $payload['unidad_base_id'] = 'u';
        }
        if ($esFabricable === 1) {
            $payload['unidad_base_id'] = 'kg';
        } elseif ($tieneReceta === 1) {
            $payload['unidad_base_id'] = 'u';
        }

        if ($id !== null) {
            $set = [];
            foreach (array_keys($payload) as $k) {
                $set[] = $k . ' = :' . $k;
            }
            $sql = 'UPDATE articulos SET ' . implode(', ', $set) . ', updated_by = :updated_by WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($payload + [
                'updated_by' => $userId > 0 ? $userId : null,
                'id' => $id,
            ]);
            self::syncRelacionIds($pdo, 'articulos_presentaciones', 'presentacion_id', $id, $presentacionIds, $userId);
            self::syncRelacionIds($pdo, 'articulos_empaques', 'empaque_id', $id, $empaqueIds, $userId);
            $pdo->commit();
            return $id;
        }

        $fields = array_keys($payload);
        $sql = 'INSERT INTO articulos (' . implode(', ', $fields) . ', created_by, updated_by) VALUES (:' .
            implode(', :', $fields) . ', :created_by, :updated_by)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($payload + [
            'created_by' => $userId > 0 ? $userId : null,
            'updated_by' => $userId > 0 ? $userId : null,
        ]);
        $savedId = (int) $pdo->lastInsertId();
        self::syncRelacionIds($pdo, 'articulos_presentaciones', 'presentacion_id', $savedId, $presentacionIds, $userId);
        self::syncRelacionIds($pdo, 'articulos_empaques', 'empaque_id', $savedId, $empaqueIds, $userId);

        if ($secuenciaId !== null && $secuenciaNext !== null) {
            $upSeq = $pdo->prepare('UPDATE secuencias
                                    SET valor_actual = :valor_actual,
                                        uso_total = uso_total + 1
                                    WHERE id = :id');
            $upSeq->execute([
                'id' => $secuenciaId,
                'valor_actual' => $secuenciaNext,
            ]);
        }

        $pdo->commit();
        return $savedId;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function obtenerSecuenciaActivaPorTipo(int $tipoArticuloId, PDO $pdo): array
    {
        $tipo = self::buscar('tipos_articulo', $tipoArticuloId);
        if (!$tipo) {
            throw new RuntimeException('El tipo de articulo seleccionado no existe.');
        }

        $aplicaBase = trim((string) ($tipo['descripcion'] ?? ''));
        if ($aplicaBase === '') {
            throw new RuntimeException('El tipo de articulo no tiene descripcion valida para secuencia.');
        }

        $aplicaA = $aplicaBase . ' (articulos)';
        $stmt = $pdo->prepare(
            'SELECT id, prefijo, longitud, valor_actual, incremento
             FROM secuencias
             WHERE LOWER(TRIM(aplica_a)) = LOWER(:aplica_a)
               AND activo = 1
             ORDER BY id ASC
             LIMIT 1
             FOR UPDATE'
        );
        $stmt->execute(['aplica_a' => $aplicaA]);
        $row = $stmt->fetch();
        if (!$row) {
            $stmt->execute(['aplica_a' => $aplicaBase]);
            $row = $stmt->fetch();
        }
        if (!$row) {
            throw new RuntimeException('No hay una secuencia activa asignada para el tipo de articulo seleccionado.');
        }

        return $row;
    }

    private static function listar(string $table): array
    {
        $sql = 'SELECT id, descripcion, estado FROM ' . $table . ' ORDER BY id DESC';
        return Db::conexion()->query($sql)->fetchAll() ?: [];
    }

    private static function buscar(string $table, int $id): ?array
    {
        $stmt = Db::conexion()->prepare('SELECT * FROM ' . $table . ' WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function guardar(string $table, ?int $id, array $data, int $userId): int
    {
        $descripcion = trim((string) ($data['descripcion'] ?? ''));
        $estado = strtolower(trim((string) ($data['estado'] ?? 'activo')));

        if ($descripcion === '') {
            throw new RuntimeException('La descripcion es obligatoria.');
        }
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            $estado = 'activo';
        }

        self::validarDescripcionUnica($table, $descripcion, $id);

        if ($id !== null && $id > 0) {
            $sql = 'UPDATE ' . $table . '
                    SET descripcion = :descripcion,
                        estado = :estado,
                        updated_by = :updated_by
                    WHERE id = :id';
            $stmt = Db::conexion()->prepare($sql);
            $stmt->execute([
                'descripcion' => $descripcion,
                'estado' => $estado,
                'updated_by' => $userId > 0 ? $userId : null,
                'id' => $id,
            ]);

            return $id;
        }

        $sql = 'INSERT INTO ' . $table . ' (descripcion, estado, created_by, updated_by)
                VALUES (:descripcion, :estado, :created_by, :updated_by)';
        $stmt = Db::conexion()->prepare($sql);
        $stmt->execute([
            'descripcion' => $descripcion,
            'estado' => $estado,
            'created_by' => $userId > 0 ? $userId : null,
            'updated_by' => $userId > 0 ? $userId : null,
        ]);

        return (int) Db::conexion()->lastInsertId();
    }

    private static function validarDescripcionUnica(string $table, string $descripcion, ?int $exceptId): void
    {
        $stmt = Db::conexion()->prepare('SELECT id FROM ' . $table . ' WHERE descripcion = :descripcion LIMIT 1');
        $stmt->execute(['descripcion' => $descripcion]);
        $row = $stmt->fetch();
        if (!$row) {
            return;
        }
        if ($exceptId !== null && (int) $row['id'] === $exceptId) {
            return;
        }

        throw new RuntimeException('Ya existe un registro con esa descripcion.');
    }

    private static function validarDescripcionSubcategoriaUnica(int $categoriaId, string $descripcion, ?int $exceptId): void
    {
        $stmt = Db::conexion()->prepare('SELECT id FROM subcategorias_articulo WHERE categoria_id = :categoria_id AND descripcion = :descripcion LIMIT 1');
        $stmt->execute([
            'categoria_id' => $categoriaId,
            'descripcion' => $descripcion,
        ]);
        $row = $stmt->fetch();
        if (!$row) {
            return;
        }
        if ($exceptId !== null && (int) $row['id'] === $exceptId) {
            return;
        }

        throw new RuntimeException('Ya existe una subcategoria con esa descripcion para la categoria seleccionada.');
    }

    private static function validarCodigoArticuloUnico(string $codigo, ?int $exceptId): void
    {
        $stmt = Db::conexion()->prepare('SELECT id FROM articulos WHERE codigo = :codigo LIMIT 1');
        $stmt->execute(['codigo' => $codigo]);
        $row = $stmt->fetch();
        if (!$row) {
            return;
        }
        if ($exceptId !== null && (int) $row['id'] === $exceptId) {
            return;
        }
        throw new RuntimeException('El codigo de articulo ya existe.');
    }

    private static function intOrNull(mixed $value): ?int
    {
        $n = (int) $value;
        return $n > 0 ? $n : null;
    }

    private static function decimalOrNull(mixed $value): ?float
    {
        $txt = trim((string) $value);
        if ($txt === '') {
            return null;
        }
        return is_numeric($txt) ? (float) $txt : null;
    }

    private static function idsOrEmpty(mixed $value): array
    {
        $raw = is_array($value) ? $value : [$value];
        $ids = [];
        foreach ($raw as $item) {
            $id = (int) $item;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        $ids = array_values(array_unique($ids));
        sort($ids);
        return $ids;
    }

    private static function listarRelacionIds(string $table, string $fkColumn, int $articuloId): array
    {
        if (!self::tablaExiste($table)) {
            return [];
        }
        $stmt = Db::conexion()->prepare("SELECT $fkColumn AS id FROM $table WHERE articulo_id = :articulo_id ORDER BY $fkColumn ASC");
        $stmt->execute(['articulo_id' => $articuloId]);
        $rows = $stmt->fetchAll() ?: [];
        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    private static function syncRelacionIds(PDO $pdo, string $table, string $fkColumn, int $articuloId, array $ids, int $userId): void
    {
        if (!self::tablaExiste($table)) {
            if ($ids === []) {
                return;
            }
            throw new RuntimeException('Falta estructura de relacion para empaques/presentaciones. Ejecuta scripts/sql/create_articulos_variantes.sql');
        }
        $pdo->prepare("DELETE FROM $table WHERE articulo_id = :articulo_id")
            ->execute(['articulo_id' => $articuloId]);

        if ($ids === []) {
            return;
        }

        $ins = $pdo->prepare("INSERT INTO $table (articulo_id, $fkColumn, created_by, updated_by) VALUES (:articulo_id, :fk_id, :created_by, :updated_by)");
        foreach ($ids as $fkId) {
            $ins->execute([
                'articulo_id' => $articuloId,
                'fk_id' => $fkId,
                'created_by' => $userId > 0 ? $userId : null,
                'updated_by' => $userId > 0 ? $userId : null,
            ]);
        }
    }

    private static function tablaExiste(string $table): bool
    {
        $stmt = Db::conexion()->prepare('SELECT COUNT(*) AS total
                                         FROM information_schema.TABLES
                                         WHERE TABLE_SCHEMA = DATABASE()
                                           AND TABLE_NAME = :table');
        $stmt->execute(['table' => $table]);
        return ((int) ($stmt->fetch()['total'] ?? 0)) > 0;
    }
}
