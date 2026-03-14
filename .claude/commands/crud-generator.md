---
name: crud-generator
description: Generate complete CRUD module following MVC pattern from PATRON_DESARROLLO_MVC.md
argument-hint: module_name table_name field1:type field2:type... (e.g., "Department departments name:string code:string")
allowed-tools: [Write, Read, Edit, Glob]
---

Generate a complete CRUD module for: **$ARGUMENTS**

## 📋 Instructions

Parse the arguments as follows:
- First argument: Module name in PascalCase (e.g., "Department", "Bonus", "Deduction")
- Second argument: Database table name (e.g., "departments", "bonuses")
- Remaining arguments: Field definitions in format `field_name:type` (e.g., "name:string", "amount:decimal", "is_active:boolean")

## 🎯 Steps to Execute

### 1. Read Pattern Documentation
First, read `documentation/PATRON_DESARROLLO_MVC.md` to understand the exact patterns used in this project.

### 2. Generate Model (app/Models/)
Create `app/Models/{ModuleName}.php` following this structure:

```php
<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class {ModuleName}
{
    private Database $db;
    private string $table = '{table_name}';

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
        // [Generate filter conditions based on fields]

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
        $fields = [/* list field names */];
        $placeholders = [/* list :field placeholders */];

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ", created_at)
                VALUES (" . implode(', ', $placeholders) . ", NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([/* map data to placeholders */]);

        return (int) $this->db->getConnection()->lastInsertId();
    }

    /**
     * Actualizar registro
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table}
                SET /* field1 = :field1, field2 = :field2 */, updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([/* map params */]);
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

### 3. Generate Controller (app/Controllers/)
Create `app/Controllers/{ModuleName}Controller.php` with these methods:
- `index()` - Main view
- `datatablesAjax()` - AJAX endpoint for DataTables
- `create()` - AJAX create endpoint
- `update($id)` - AJAX update endpoint
- `delete($id)` - AJAX delete endpoint

**IMPORTANT**:
- Use `header('Content-Type: application/json');` in all AJAX methods
- Include CSRF validation
- Use try-catch with error_log
- Extend `App\Core\Controller`
- Use `$this->requireAuth();` in constructor
- Return JSON with `success`, `message`, and `error` keys

### 4. Generate View (app/Views/admin/{module_snake_case}/index.php)
Create the view with:
- AdminLTE card layout
- DataTable for listing
- Create modal with form
- Edit modal (optional)
- **CRITICAL**: Use `ob_start()` and `ob_get_clean()` pattern for JavaScript:

```php
<?php
ob_start();
?>
<script>
$(document).ready(function() {
    const baseUrl = '<?= \App\Core\UrlHelper::base() ?>';

    // DataTable initialization
    const table = $("#dataTable").DataTable({
        ajax: baseUrl + '/panel/{module-route}/datatables-ajax',
        columns: [/* define columns */],
        language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" }
    });

    // AJAX form submission
    $("#createForm").on("submit", function(e) {
        e.preventDefault();
        // Use toastr for notifications, NOT alert()
    });
});
</script>
<?php
$scripts = ob_get_clean();
?>
```

**CRITICAL RULES**:
- ❌ DO NOT concatenate HTML strings with PHP
- ✅ Write HTML normally, capture scripts with ob_start/ob_get_clean
- ✅ Use `\App\Core\UrlHelper::base()` for base URL in JavaScript
- ✅ Use `toastr` for notifications (NOT alert())
- ✅ Include CSRF token in forms: `<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">`

### 5. Generate Migration (database/migrations/tenant/)
Create `database/migrations/tenant/{YYYY_MM_DD}_create_{table_name}_table.sql`:

```sql
-- UP Migration
CREATE TABLE IF NOT EXISTS {table_name} (
    id INT AUTO_INCREMENT PRIMARY KEY,
    /* Generate fields based on arguments */
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Create rollback: `{YYYY_MM_DD}_create_{table_name}_table.down.sql`:
```sql
DROP TABLE IF EXISTS {table_name};
```

### 6. Route Registration Instructions
Provide clear instructions to register the module in this project routing system.

```php
// app/Core/App.php -> inside $routeMapping:
'{module-route}' => ['controller' => '{ModuleName}Controller', 'method' => null],

// The App router resolves methods by URL segments:
// GET  /panel/{module-route}              -> index()
// GET  /panel/{module-route}/datatables-ajax -> datatablesAjax()
// POST /panel/{module-route}/create       -> create()
// POST /panel/{module-route}/update/{id}  -> update($id)
// POST /panel/{module-route}/delete/{id}  -> delete($id)
//
// Also update app/Core/RouteHelper.php if needed:
// - breadcrumb map entries
// - role access map (canAccessRoute / permissions)
```

### 7. Sidebar Menu Instructions
Provide instructions to add menu entry in sidebar with permissions check:

```php
<?php if ($this->canAccessRoute('/panel/{module-route}')): ?>
<li class="nav-item">
    <a href="<?= \App\Core\UrlHelper::base() ?>/panel/{module-route}" class="nav-link">
        <i class="nav-icon fas fa-icon"></i>
        <p>{Module Display Name}</p>
    </a>
</li>
<?php endif; ?>
```

## 📋 Checklist to Display

After generating files, show this checklist:

- [x] Model created: `app/Models/{ModuleName}.php`
- [x] Controller created: `app/Controllers/{ModuleName}Controller.php`
- [x] View created: `app/Views/admin/{module_snake}/index.php`
- [x] Migration created: `database/migrations/tenant/{date}_create_{table}_table.sql`
- [x] Rollback migration created: `database/migrations/tenant/{date}_create_{table}_table.down.sql`
- [ ] **TODO: Add route mapping in** `app/Core/App.php`
- [ ] **TODO: Update permissions/breadcrumbs in** `app/Core/RouteHelper.php` (if module requires menu + access control)
- [ ] **TODO: Add sidebar menu entry** (see instructions above)
- [ ] **TODO: Run migration**: `php database/migrations/tenant/run_single_migration.php`
- [ ] **TODO: Test CRUD operations** (Create, Read, Update, Delete)

## ⚠️ Critical Reminders

1. **CSRF Protection**: All forms MUST include CSRF token
2. **SQL Injection**: Use PDO prepared statements, NEVER concatenate SQL
3. **ob_start Pattern**: JavaScript MUST use ob_start/ob_get_clean
4. **UrlHelper**: Use `\App\Core\UrlHelper::base()` in JavaScript for URLs
5. **Toastr**: Use toastr for notifications, NOT alert()
6. **Error Handling**: Use try-catch with error_log()
7. **JSON Response**: AJAX methods return JSON with success/error keys
8. **No eval()**: NEVER use eval() under any circumstance

## 📊 Field Type Mapping

Map argument types to SQL types:
- `string` → `VARCHAR(255)`
- `text` → `TEXT`
- `int` / `integer` → `INT`
- `decimal` / `float` → `DECIMAL(10,2)`
- `boolean` / `bool` → `TINYINT(1)`
- `date` → `DATE`
- `datetime` → `DATETIME`
- `timestamp` → `TIMESTAMP`

Map types to HTML input types:
- `string` → `<input type="text">`
- `text` → `<textarea>`
- `int` / `integer` → `<input type="number">`
- `decimal` / `float` → `<input type="number" step="0.01">`
- `boolean` / `bool` → `<input type="checkbox">`
- `date` → `<input type="date">`
- `datetime` → `<input type="datetime-local">`

---

**Remember**: Generate COMPLETE, production-ready code that follows ALL patterns from PATRON_DESARROLLO_MVC.md. Do not leave placeholders or TODOs in generated code files.
