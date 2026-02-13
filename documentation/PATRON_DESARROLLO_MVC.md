# 📐 PATRÓN DE DESARROLLO MVC - Sistema Planillas

## 🎯 Objetivo
Este documento define el patrón estándar para crear nuevos módulos en el sistema, siguiendo el principio **DRY (Don't Repeat Yourself)** para optimizar tiempos y tokens.

---

## 📁 ESTRUCTURA DE ARCHIVOS

```
app/
├── Controllers/
│   └── NombreModuloController.php
├── Models/
│   └── NombreModulo.php
├── Services/
│   └── NombreModuloService.php (opcional)
└── Views/
    └── admin/
        └── nombre_modulo/
            ├── index.php
            ├── create.php (opcional)
            └── edit.php (opcional)
```

---

## 🎮 CONTROLLER - Patrón Estándar

```php
<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Models\NombreModulo;

class NombreModuloController extends Controller
{
    private $model;

    public function __construct()
    {
        $this->requireAuth();
        $this->model = new NombreModulo();
    }

    /**
     * Índice principal - Listado
     * GET /ruta-modulo
     */
    public function index()
    {
        // Obtener datos necesarios
        $items = $this->model->getAll();

        $data = [
            'title' => 'Título del Módulo',
            'page_title' => 'Título de la Página',
            'items' => $items,
            'csrf_token' => Security::generateToken()
        ];

        $this->render('admin/nombre_modulo/index', $data);
    }

    /**
     * Obtener datos para DataTables (AJAX)
     * GET /ruta-modulo/data
     */
    public function getData()
    {
        header('Content-Type: application/json');

        try {
            $items = $this->model->getAll();

            // Formatear datos para DataTables
            $data = [];
            foreach ($items as $item) {
                $data[] = [
                    'id' => $item['id'],
                    'nombre' => $item['nombre'],
                    // ... más campos
                ];
            }

            echo json_encode([
                'data' => $data,
                'recordsTotal' => count($data),
                'recordsFiltered' => count($data)
            ]);

        } catch (\Exception $e) {
            error_log("Error en getData: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Crear nuevo registro (AJAX)
     * POST /ruta-modulo/create
     */
    public function create()
    {
        header('Content-Type: application/json');

        try {
            $data = [
                'campo1' => $_POST['campo1'] ?? null,
                'campo2' => $_POST['campo2'] ?? null,
            ];

            $id = $this->model->create($data);

            echo json_encode([
                'success' => true,
                'message' => 'Registro creado exitosamente',
                'id' => $id
            ]);

        } catch (\Exception $e) {
            error_log("Error en create: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Actualizar registro (AJAX)
     * POST /ruta-modulo/update/{id}
     */
    public function update($id)
    {
        header('Content-Type: application/json');

        try {
            $data = [
                'campo1' => $_POST['campo1'] ?? null,
                'campo2' => $_POST['campo2'] ?? null,
            ];

            $result = $this->model->update($id, $data);

            echo json_encode([
                'success' => true,
                'message' => 'Registro actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            error_log("Error en update: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Eliminar registro (AJAX)
     * POST /ruta-modulo/delete/{id}
     */
    public function delete($id)
    {
        header('Content-Type: application/json');

        try {
            $result = $this->model->delete($id);

            echo json_encode([
                'success' => true,
                'message' => 'Registro eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            error_log("Error en delete: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
```

---

## 📊 MODEL - Patrón Estándar

```php
<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class NombreModulo
{
    private Database $db;
    private string $table = 'nombre_tabla';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los registros
     */
    public function getAll(array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        // Aplicar filtros si existen
        if (!empty($filters['campo'])) {
            $sql .= " AND campo = :campo";
            $params[':campo'] = $filters['campo'];
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener un registro por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Crear nuevo registro
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table} (campo1, campo2, created_at)
                VALUES (:campo1, :campo2, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':campo1' => $data['campo1'],
            ':campo2' => $data['campo2']
        ]);

        return (int) $this->db->getConnection()->lastInsertId();
    }

    /**
     * Actualizar registro
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table}
                SET campo1 = :campo1, campo2 = :campo2, updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':campo1' => $data['campo1'],
            ':campo2' => $data['campo2']
        ]);
    }

    /**
     * Eliminar registro
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
```

---

## 🎨 VISTA - Patrón Estándar

```php
<?php
/**
 * Vista: Nombre del Módulo
 * Ruta: /panel/ruta-modulo
 */
?>

<!-- Main content -->
<div class="content">
    <div class="container-fluid">

        <!-- Título y botones de acción -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Listado de Registros</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createModal">
                                <i class="fas fa-plus"></i> Nuevo Registro
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="dataTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Campo 1</th>
                                    <th>Campo 2</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Crear -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear Registro</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="createForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Campo 1 *</label>
                        <input type="text" class="form-control" name="campo1" required>
                    </div>
                    <div class="form-group">
                        <label>Campo 2</label>
                        <input type="text" class="form-control" name="campo2">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Iniciar captura de scripts
ob_start();
?>
<script>
$(document).ready(function() {
    // Base URL del proyecto
    const baseUrl = '<?= \App\Core\UrlHelper::base() ?>';

    // Inicializar DataTable
    const table = $("#dataTable").DataTable({
        ajax: baseUrl + '/panel/ruta-modulo/data',
        columns: [
            { data: "id" },
            { data: "campo1" },
            { data: "campo2" },
            {
                data: function(row) {
                    return `<button class="btn btn-sm btn-info edit-btn" data-id="${row.id}">
                        <i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">
                        <i class="fas fa-trash"></i></button>`;
                },
                orderable: false
            }
        ],
        language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" }
    });

    // Crear registro
    $("#createForm").on("submit", function(e) {
        e.preventDefault();
        const btn = $(this).find("[type='submit']");
        btn.prop("disabled", true).html("<i class='fas fa-spinner fa-spin'></i> Guardando...");

        $.post(baseUrl + '/panel/ruta-modulo/create', $(this).serialize(), function(resp) {
            btn.prop("disabled", false).html("<i class='fas fa-save'></i> Guardar");

            if(resp.success) {
                $("#createModal").modal("hide");
                toastr.success(resp.message);
                table.ajax.reload();
                $("#createForm")[0].reset();
            } else {
                toastr.error(resp.error || "Error desconocido");
            }
        }).fail(function() {
            btn.prop("disabled", false).html("<i class='fas fa-save'></i> Guardar");
            toastr.error("Error de conexión");
        });
    });

    // Eliminar registro
    $(document).on("click", ".delete-btn", function() {
        const id = $(this).data("id");

        if(confirm("¿Está seguro de eliminar este registro?")) {
            $.post(baseUrl + '/panel/ruta-modulo/delete/' + id, {}, function(resp) {
                if(resp.success) {
                    toastr.success(resp.message);
                    table.ajax.reload();
                } else {
                    toastr.error(resp.error || "Error desconocido");
                }
            }).fail(function() {
                toastr.error("Error de conexión");
            });
        }
    });
});
</script>
<?php
$scripts = ob_get_clean();
?>
```

---

## 🔧 REGLAS IMPORTANTES

### ✅ **HACER (DO)**
1. **Usar `ob_start()` y `ob_get_clean()`** para capturar scripts
2. **Escribir HTML/PHP normalmente**, NO concatenar strings
3. **Usar `\App\Core\UrlHelper::base()`** para URLs base en JavaScript
4. **Usar `toastr`** para notificaciones (NO `alert()`)
5. **Retornar JSON** en métodos AJAX con `header('Content-Type: application/json')`
6. **Usar prepared statements** con PDO para prevenir SQL injection
7. **Validar permisos** en cada método del controller
8. **Manejar excepciones** con try-catch y error_log
9. **Usar CSRF tokens** en formularios
10. **Seguir convención de nombres**: camelCase para métodos, snake_case para BD

### ❌ **NO HACER (DON'T)**
1. **NO concatenar strings PHP** para construir vistas (`$content .= '...'`)
2. **NO usar `echo` directo** para HTML en controllers
3. **NO usar `eval()`** bajo ninguna circunstancia
4. **NO hardcodear URLs** en JavaScript (usar baseUrl)
5. **NO usar queries SQL directos** sin prepared statements
6. **NO exponer datos sensibles** en responses JSON
7. **NO olvidar validar entrada** de usuario
8. **NO usar variables globales** ($_GET, $_POST) sin validar
9. **NO mezclar lógica de negocio** en controllers (usar Services)
10. **NO duplicar código** (aplicar principio DRY)

---

## 📝 CHECKLIST PARA NUEVO MÓDULO

- [ ] Crear migración SQL en `database/migrations/tenant/`
- [ ] Crear Model en `app/Models/`
- [ ] Crear Controller en `app/Controllers/`
- [ ] Registrar rutas en router correspondiente
- [ ] Crear vista index en `app/Views/admin/nombre_modulo/`
- [ ] Usar patrón `ob_start()` / `ob_get_clean()` para scripts
- [ ] Implementar métodos AJAX con `header('Content-Type: application/json')`
- [ ] Agregar validaciones y manejo de errores
- [ ] Implementar CSRF protection
- [ ] Probar funcionalidad completa (CRUD)
- [ ] Documentar cambios en CHANGELOG.md

---

## 🚀 EJEMPLO DE IMPLEMENTACIÓN RÁPIDA

Para crear un módulo nuevo llamado "Departamentos":

1. **Migración**: `database/migrations/tenant/YYYY_MM_DD_create_departments_table.sql`
2. **Model**: `app/Models/Department.php`
3. **Controller**: `app/Controllers/DepartmentController.php`
4. **Vista**: `app/Views/admin/departments/index.php`
5. **Ruta**: Agregar en router principal

Tiempo estimado: **30-45 minutos** siguiendo este patrón.

---

## 📚 REFERENCIAS

- **Módulo de Referencia**: `app/Views/admin/attendance/list.php`
- **Controller de Referencia**: `app/Controllers/AttendanceController.php`
- **Model de Referencia**: `app/Models/AttendanceRecord.php`

---

**Última Actualización**: 03 de Febrero, 2026
**Versión**: 1.0
