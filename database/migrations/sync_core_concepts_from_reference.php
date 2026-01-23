<?php
/**
 * Sincronización de Conceptos Core desde BD de Referencia
 *
 * Este script sincroniza conceptos fundamentales desde una BD de referencia (PINN4941184)
 * a todas las bases de datos tenant activas.
 *
 * Conceptos a sincronizar:
 * - 01: Sueldo
 * - 02: Seguro Social
 * - 03: Seguro Educativo
 * - 04: Impuesto sobre la renta
 *
 * Para cada concepto:
 * - Si existe en el tenant: actualiza la fórmula
 * - Si no existe: crea el concepto completo con todos sus atributos
 *
 * Uso:
 *   php sync_core_concepts_from_reference.php [--dry-run] [--tenant=slug]
 *
 * Opciones:
 *   --dry-run         No ejecuta, solo muestra qué haría
 *   --tenant=slug     Ejecuta solo para un tenant específico
 *
 * @version 1.0.0
 * @date 2026-01-20
 */

class ConceptSyncFromReference
{
    private $masterPdo;
    private $referencePdo;
    private $dryRun = false;
    private $targetTenant = null;

    // BD de referencia
    private const REFERENCE_DB_NAME = 'PINN4941184';

    // Conceptos a sincronizar
    private const CONCEPTS_TO_SYNC = [
        '01' => 'Sueldo',
        '02' => 'Seguro Social',
        '03' => 'Seguro Educativo',
        '04' => 'Impuesto sobre la renta'
    ];

    private $stats = [
        'total_tenants' => 0,
        'successful_tenants' => 0,
        'failed_tenants' => 0,
        'total_concepts_created' => 0,
        'total_concepts_updated' => 0,
        'total_concepts_skipped' => 0,
        'tenant_results' => []
    ];

    public function __construct($dryRun = false, $targetTenant = null)
    {
        $this->dryRun = $dryRun;
        $this->targetTenant = $targetTenant;
        $this->loadEnvironment();
        $this->connectMaster();
        $this->connectReference();
    }

    /**
     * Cargar variables de entorno desde .env
     */
    private function loadEnvironment()
    {
        $envFile = __DIR__ . '/../../.env';

        if (!file_exists($envFile)) {
            echo "⚠️  Archivo .env no encontrado, usando valores por defecto\n";
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                $value = trim($value, '"\'');

                if (!isset($_ENV[$key])) {
                    $_ENV[$key] = $value;
                }
            }
        }
    }

    /**
     * Conectar a la base de datos master
     */
    private function connectMaster()
    {
        try {
            $masterConfig = require __DIR__ . '/../../config/master_database.php';

            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $masterConfig['host'],
                $masterConfig['port'],
                $masterConfig['database'],
                $masterConfig['charset']
            );

            $this->masterPdo = new PDO(
                $dsn,
                $masterConfig['username'],
                $masterConfig['password'],
                $masterConfig['options']
            );

            echo "✅ Conectado a base de datos master: {$masterConfig['database']}\n";
        } catch (PDOException $e) {
            die("❌ Error conectando a BD master: " . $e->getMessage() . "\n");
        }
    }

    /**
     * Conectar a la base de datos de referencia (PINN4941184)
     */
    private function connectReference()
    {
        try {
            $host = $_ENV['TENANT_DB_HOST'] ?? 'localhost';
            $port = $_ENV['TENANT_DB_PORT'] ?? 3306;
            $user = $_ENV['TENANT_DB_USER'] ?? 'root';
            $pass = $_ENV['TENANT_DB_PASS'] ?? '';
            $charset = $_ENV['TENANT_DB_CHARSET'] ?? 'utf8mb4';

            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $host,
                $port,
                self::REFERENCE_DB_NAME,
                $charset
            );

            $this->referencePdo = new PDO(
                $dsn,
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ]
            );

            echo "✅ Conectado a BD de referencia: " . self::REFERENCE_DB_NAME . "\n";
        } catch (PDOException $e) {
            die("❌ Error conectando a BD de referencia " . self::REFERENCE_DB_NAME . ": " . $e->getMessage() . "\n");
        }
    }

    /**
     * Obtener lista de tenants activos
     */
    private function getActiveTenants()
    {
        try {
            $sql = "SELECT id, slug, db_host, db_port, db_name, db_user, db_pass_enc, db_charset, status
                    FROM tenants
                    WHERE status = 'ACTIVE'";

            if ($this->targetTenant) {
                $sql .= " AND slug = :slug";
                $stmt = $this->masterPdo->prepare($sql);
                $stmt->execute(['slug' => $this->targetTenant]);
            } else {
                $stmt = $this->masterPdo->query($sql);
            }

            $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($tenants) && $this->targetTenant) {
                throw new Exception("Tenant '{$this->targetTenant}' no encontrado o no está ACTIVO");
            }

            return $tenants;

        } catch (PDOException $e) {
            die("❌ Error obteniendo tenants: " . $e->getMessage() . "\n");
        }
    }

    /**
     * Conectar a la base de datos de un tenant específico
     */
    private function connectToTenant($tenant)
    {
        try {
            $password = $this->decryptPassword($tenant['db_pass_enc']);

            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $tenant['db_host'] ?: 'localhost',
                $tenant['db_port'] ?: 3306,
                $tenant['db_name'],
                $tenant['db_charset'] ?: 'utf8mb4'
            );

            $pdo = new PDO(
                $dsn,
                $tenant['db_user'],
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ]
            );

            return $pdo;

        } catch (PDOException $e) {
            throw new Exception("Error conectando a tenant {$tenant['slug']}: " . $e->getMessage());
        }
    }

    /**
     * Desencriptar password del tenant
     */
    private function decryptPassword($encryptedPassword)
    {
        if (empty($encryptedPassword)) {
            return '';
        }

        $appKey = $_ENV['APP_KEY'] ?? 'changeme-app-key';
        $key = hash('sha256', $appKey, true);
        $iv = substr(hash('sha256', $appKey . '_iv'), 0, 16);

        $decrypted = openssl_decrypt($encryptedPassword, 'AES-256-CBC', $key, 0, $iv);

        return $decrypted !== false ? $decrypted : '';
    }

    /**
     * Obtener concepto de la BD de referencia
     */
    private function getConceptFromReference($conceptCode)
    {
        try {
            $stmt = $this->referencePdo->prepare("
                SELECT *
                FROM concepto
                WHERE concepto = ?
                LIMIT 1
            ");

            $stmt->execute([$conceptCode]);
            $concept = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$concept) {
                echo "      ⚠️  Concepto '$conceptCode' no encontrado en BD de referencia\n";
                return null;
            }

            return $concept;

        } catch (PDOException $e) {
            echo "      ❌ Error obteniendo concepto '$conceptCode' de referencia: " . $e->getMessage() . "\n";
            return null;
        }
    }

    /**
     * Verificar si el concepto existe en el tenant
     */
    private function conceptExistsInTenant($pdo, $conceptCode)
    {
        try {
            $stmt = $pdo->prepare("
                SELECT id
                FROM concepto
                WHERE concepto = ?
                LIMIT 1
            ");

            $stmt->execute([$conceptCode]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $result['id'] : null;

        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Crear concepto en el tenant
     */
    private function createConcept($pdo, $conceptData)
    {
        try {
            // Preparar datos para inserción (excluir id y auto_increment)
            $fields = [];
            $placeholders = [];
            $values = [];

            foreach ($conceptData as $field => $value) {
                if ($field === 'id') {
                    continue; // Skip auto-increment field
                }

                $fields[] = $field;
                $placeholders[] = '?';
                $values[] = $value;
            }

            $sql = sprintf(
                "INSERT INTO concepto (%s) VALUES (%s)",
                implode(', ', $fields),
                implode(', ', $placeholders)
            );

            if ($this->dryRun) {
                return true;
            }

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($values);

            return $result;

        } catch (PDOException $e) {
            echo "         ❌ Error creando concepto: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Actualizar fórmula del concepto en el tenant
     */
    private function updateConceptFormula($pdo, $conceptId, $formula)
    {
        try {
            $sql = "UPDATE concepto SET formula = ? WHERE id = ?";

            if ($this->dryRun) {
                return true;
            }

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$formula, $conceptId]);

            return $result;

        } catch (PDOException $e) {
            echo "         ❌ Error actualizando fórmula: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Sincronizar concepto en un tenant
     */
    private function syncConceptInTenant($pdo, $conceptCode, $referenceData)
    {
        $existingId = $this->conceptExistsInTenant($pdo, $conceptCode);

        if ($existingId) {
            // Actualizar fórmula
            $currentFormula = $this->getCurrentFormula($pdo, $existingId);

            if ($currentFormula === $referenceData['formula']) {
                echo "         ⏭️  '$conceptCode' - Fórmula ya actualizada\n";
                return 'skipped';
            }

            if ($this->updateConceptFormula($pdo, $existingId, $referenceData['formula'])) {
                echo "         ✅ '$conceptCode' - Fórmula actualizada\n";
                return 'updated';
            } else {
                echo "         ❌ '$conceptCode' - Error actualizando\n";
                return 'error';
            }
        } else {
            // Crear concepto
            if ($this->createConcept($pdo, $referenceData)) {
                echo "         ✅ '$conceptCode' - Concepto creado\n";
                return 'created';
            } else {
                echo "         ❌ '$conceptCode' - Error creando\n";
                return 'error';
            }
        }
    }

    /**
     * Obtener fórmula actual de un concepto
     */
    private function getCurrentFormula($pdo, $conceptId)
    {
        try {
            $stmt = $pdo->prepare("SELECT formula FROM concepto WHERE id = ?");
            $stmt->execute([$conceptId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['formula'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Procesar sincronización para un tenant
     */
    private function processTenant($tenant)
    {
        $tenantSlug = $tenant['slug'];
        $tenantDb = $tenant['db_name'];

        echo "\n" . str_repeat("=", 80) . "\n";
        echo "🏢 TENANT: {$tenantSlug} (DB: {$tenantDb})\n";
        echo str_repeat("=", 80) . "\n";

        try {
            // Conectar al tenant
            $pdo = $this->connectToTenant($tenant);
            echo "   ✅ Conectado exitosamente\n\n";

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = 0;

            // Sincronizar cada concepto
            foreach (self::CONCEPTS_TO_SYNC as $code => $description) {
                echo "   🔄 Sincronizando: $code - $description\n";

                // Obtener concepto de referencia
                $referenceData = $this->getConceptFromReference($code);

                if (!$referenceData) {
                    echo "         ⚠️  No se pudo obtener de referencia\n";
                    $errors++;
                    continue;
                }

                // Sincronizar en tenant
                $result = $this->syncConceptInTenant($pdo, $code, $referenceData);

                switch ($result) {
                    case 'created':
                        $created++;
                        $this->stats['total_concepts_created']++;
                        break;
                    case 'updated':
                        $updated++;
                        $this->stats['total_concepts_updated']++;
                        break;
                    case 'skipped':
                        $skipped++;
                        $this->stats['total_concepts_skipped']++;
                        break;
                    case 'error':
                        $errors++;
                        break;
                }
            }

            // Resumen del tenant
            echo "\n   📊 Resumen:\n";
            echo "      ✅ Creados: $created\n";
            echo "      🔄 Actualizados: $updated\n";
            echo "      ⏭️  Sin cambios: $skipped\n";
            if ($errors > 0) {
                echo "      ❌ Errores: $errors\n";
            }

            if ($errors === 0) {
                $this->stats['successful_tenants']++;
                $this->stats['tenant_results'][$tenantSlug] = [
                    'status' => 'success',
                    'created' => $created,
                    'updated' => $updated,
                    'skipped' => $skipped
                ];
            } else {
                $this->stats['failed_tenants']++;
                $this->stats['tenant_results'][$tenantSlug] = [
                    'status' => 'partial',
                    'created' => $created,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'errors' => $errors
                ];
            }

        } catch (Exception $e) {
            echo "   ❌ ERROR: {$e->getMessage()}\n";
            $this->stats['failed_tenants']++;
            $this->stats['tenant_results'][$tenantSlug] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ejecutar sincronización en todos los tenants
     */
    public function run()
    {
        echo "\n╔═══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║       SINCRONIZACIÓN CONCEPTOS CORE DESDE BD DE REFERENCIA               ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";

        echo "\nModo: " . ($this->dryRun ? "🔍 DRY RUN (no ejecuta)" : "⚡ EJECUCIÓN REAL") . "\n";
        echo "BD Referencia: " . self::REFERENCE_DB_NAME . "\n";
        echo "\nConceptos a sincronizar:\n";

        foreach (self::CONCEPTS_TO_SYNC as $code => $description) {
            echo "   • $code - $description\n";
        }

        // Obtener tenants
        $tenants = $this->getActiveTenants();
        $this->stats['total_tenants'] = count($tenants);

        if ($this->targetTenant) {
            echo "\nFiltro: Solo tenant '{$this->targetTenant}'\n";
        }

        echo "\n📋 Tenants encontrados: " . count($tenants) . "\n";

        if (empty($tenants)) {
            echo "\n⚠️  No hay tenants activos para procesar\n";
            return;
        }

        // Procesar cada tenant
        foreach ($tenants as $tenant) {
            $this->processTenant($tenant);
        }

        // Mostrar resumen final
        $this->showSummary();
    }

    /**
     * Mostrar resumen final
     */
    private function showSummary()
    {
        echo "\n\n";
        echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                           RESUMEN FINAL                                   ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════╝\n\n";

        echo "📊 ESTADÍSTICAS GENERALES:\n";
        echo "   Tenants procesados:     {$this->stats['total_tenants']}\n";
        echo "   ✅ Exitosos:            {$this->stats['successful_tenants']}\n";
        echo "   ❌ Con errores:         {$this->stats['failed_tenants']}\n";
        echo "   ➕ Conceptos creados:   {$this->stats['total_concepts_created']}\n";
        echo "   🔄 Conceptos actualizados: {$this->stats['total_concepts_updated']}\n";
        echo "   ⏭️  Conceptos sin cambios: {$this->stats['total_concepts_skipped']}\n\n";

        // Detalles por tenant
        if (!empty($this->stats['tenant_results'])) {
            echo "📋 DETALLE POR TENANT:\n";
            echo str_repeat("─", 80) . "\n";
            printf("%-25s %-15s %-10s %-10s %-10s\n", "TENANT", "STATUS", "CREADOS", "ACTUALIZADOS", "SIN CAMBIOS");
            echo str_repeat("─", 80) . "\n";

            foreach ($this->stats['tenant_results'] as $slug => $result) {
                $statusIcon = $this->getStatusIcon($result['status']);
                $created = $result['created'] ?? '-';
                $updated = $result['updated'] ?? '-';
                $skipped = $result['skipped'] ?? '-';

                printf("%-25s %-15s %-10s %-10s %-10s\n", $slug, $statusIcon, $created, $updated, $skipped);

                if (isset($result['error'])) {
                    echo "   ⚠️  " . substr($result['error'], 0, 70) . "\n";
                }
            }
            echo str_repeat("─", 80) . "\n";
        }

        echo "\n✅ Proceso completado\n\n";
    }

    /**
     * Obtener ícono de status
     */
    private function getStatusIcon($status)
    {
        $icons = [
            'success' => '✅ Exitoso',
            'partial' => '⚠️  Parcial',
            'error' => '❌ Error'
        ];
        return $icons[$status] ?? '❓ Desconocido';
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// EJECUCIÓN DEL SCRIPT
// ═══════════════════════════════════════════════════════════════════════════

if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    // Parsear argumentos
    $options = getopt('', ['dry-run', 'tenant:', 'help']);

    if (isset($options['help'])) {
        echo "\nSincronización de Conceptos Core desde BD de Referencia\n\n";
        echo "Uso: php sync_core_concepts_from_reference.php [opciones]\n\n";
        echo "Opciones:\n";
        echo "  --dry-run          No ejecuta, solo muestra qué haría\n";
        echo "  --tenant=SLUG      Ejecuta solo para el tenant especificado\n";
        echo "  --help             Muestra esta ayuda\n\n";
        echo "Conceptos sincronizados:\n";
        echo "  • 01 - Sueldo\n";
        echo "  • 02 - Seguro Social\n";
        echo "  • 03 - Seguro Educativo\n";
        echo "  • 04 - Impuesto sobre la renta\n\n";
        echo "Ejemplos:\n";
        echo "  php sync_core_concepts_from_reference.php\n";
        echo "  php sync_core_concepts_from_reference.php --dry-run\n";
        echo "  php sync_core_concepts_from_reference.php --tenant=innova\n\n";
        exit(0);
    }

    $dryRun = isset($options['dry-run']);
    $targetTenant = $options['tenant'] ?? null;

    try {
        $syncer = new ConceptSyncFromReference($dryRun, $targetTenant);
        $syncer->run();

    } catch (Exception $e) {
        echo "\n❌ ERROR FATAL: {$e->getMessage()}\n\n";
        exit(1);
    }
}
