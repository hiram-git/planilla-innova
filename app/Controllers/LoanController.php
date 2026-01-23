<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Employee;
use App\Models\Creditor;
use DateInterval;
use DateTime;
use Exception;
use PDO;

class LoanController extends Controller
{
    private Loan $loanModel;
    private LoanInstallment $installmentModel;
    private Employee $employeeModel;
    private Creditor $creditorModel;
    private $conceptModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->loanModel = new Loan();
        $this->installmentModel = new LoanInstallment();
        $this->employeeModel = new Employee();
        $this->creditorModel = new Creditor();
        $this->conceptModel = new \App\Models\Concept();
    }

    public function index()
    {
        $loans = $this->loanModel->all();
        $employees = $this->employeeModel->getAllEmployees();
        $loanTypes = $this->loanModel->getTiposPrestamo();
        $employeesById = [];
        foreach ($employees as $emp) {
            $employeesById[$emp['id']] = trim(($emp['firstname'] ?? '') . ' ' . ($emp['lastname'] ?? ''));
        }

        $this->render('admin/loans/index', [
            'title' => 'Préstamos',
            'loans' => $loans,
            'employeesById' => $employeesById,
            'loanTypes' => $loanTypes,
            'csrf_token' => AuthMiddleware::generateCSRF()
        ]);
    }

    public function create()
    {
        $employees = $this->employeeModel->getAllEmployees();
        $creditors = $this->creditorModel->getOptions();
        $loanTypes = $this->loanModel->getTiposPrestamo();
        $this->render('admin/loans/create', [
            'title' => 'Nuevo Préstamo',
            'employees' => $employees,
            'creditors' => $creditors,
            'loan_types' => $loanTypes,
            'csrf_token' => AuthMiddleware::generateCSRF()
        ]);
    }

    public function store()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect(\App\Core\UrlHelper::route('panel/loans/create'));
        }

        AuthMiddleware::validateCSRF();

        $creditorId = isset($_POST['creditor_id']) ? (int)$_POST['creditor_id'] : 0;
        $loanType = trim($_POST['loan_type'] ?? '');
        $loanTypes = $this->loanModel->getTiposPrestamo();
        if (!array_key_exists($loanType, $loanTypes)) {
            $matchedKey = array_search($loanType, $loanTypes, true);
            if ($matchedKey !== false) {
                $loanType = $matchedKey;
            }
        }
        $data = [
            'employee_id' => $_POST['employee_id'] ?? null,
            'creditor_id' => $creditorId ?: null,
            'loan_type' => $loanType,
            'frequency' => $_POST['frequency'] ?? 'mensual',
            'allow_december' => isset($_POST['allow_december']) ? 1 : 0,
            'total_amount' => (float)($_POST['total_amount'] ?? 0),
            'installments_count' => (int)($_POST['installments_count'] ?? 0),
            'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Validaciones básicas
        $errors = [];
        if (empty($data['employee_id'])) $errors[] = 'Seleccione un empleado.';
        if (empty($data['creditor_id'])) {
            $errors[] = 'Seleccione un acreedor.';
        } elseif (!$this->creditorModel->findById($data['creditor_id'])) {
            $errors[] = 'El acreedor seleccionado no existe.';
        }
        if (empty($data['loan_type'])) $errors[] = 'Tipo de préstamo requerido.';
        if (!in_array($data['frequency'], ['semanal', 'quincenal', 'mensual'])) $errors[] = 'Frecuencia inválida.';
        if ($data['total_amount'] <= 0) $errors[] = 'Monto total debe ser mayor a 0.';
        if ($data['installments_count'] <= 0) $errors[] = 'Cantidad de cuotas debe ser mayor a 0.';
        if (!$this->isValidDate($data['start_date'])) $errors[] = 'Fecha de inicio inválida.';

        if (!empty($errors)) {
            $_SESSION['error'] = implode(' ', $errors);
            $this->redirect(\App\Core\UrlHelper::route('panel/loans/create'));
        }

        try {
            // Guardar préstamo
            $loanId = $this->loanModel->create($data);
            if (!$loanId) {
                throw new Exception('No se pudo guardar el préstamo.');
            }

            // Generar cuotas
            $installments = $this->generateInstallments(
                $loanId,
                $data['total_amount'],
                $data['installments_count'],
                $data['frequency'],
                $data['allow_december'],
                $data['start_date']
            );

            foreach ($installments as $installment) {
                $this->installmentModel->create($installment);
            }

            $_SESSION['success'] = 'Préstamo creado y cuotas generadas correctamente.';
            $this->redirect(\App\Core\UrlHelper::route('panel/loans'));

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al crear préstamo: ' . $e->getMessage();
            $this->redirect(\App\Core\UrlHelper::route('panel/loans/create'));
        }
    }

    /**
     * Ver préstamo en modo readonly
     */
    public function show($id)
    {
        $loan = $this->loanModel->find($id);
        if (!$loan) {
            $_SESSION['error'] = 'Préstamo no encontrado.';
            $this->redirect(\App\Core\UrlHelper::route('panel/loans'));
        }

        $db = $this->installmentModel->getDatabase();
        $installments = $db->findAll(
            "SELECT * FROM loan_installments WHERE loan_id = ? ORDER BY installment_number",
            [(int)$id]
        );

        $employees = $this->employeeModel->getAllEmployees();
        $employeesById = [];
        foreach ($employees as $emp) {
            $employeesById[$emp['id']] = trim(($emp['firstname'] ?? '') . ' ' . ($emp['lastname'] ?? ''));
        }

        $creditors = $this->creditorModel->getOptions();
        $creditorsById = [];
        foreach ($creditors as $creditor) {
            $creditorsById[$creditor['id']] = $creditor['description'] ?? 'Acreedor';
        }

        $this->render('admin/loans/show', [
            'title' => 'Ver Préstamo',
            'loan' => $loan,
            'employeeName' => $employeesById[$loan['employee_id']] ?? 'Empleado',
            'creditorName' => $creditorsById[$loan['creditor_id']] ?? 'Acreedor',
            'loan_types' => $this->loanModel->getTiposPrestamo(),
            'installments' => $installments,
            'csrf_token' => AuthMiddleware::generateCSRF()
        ]);
    }

    public function edit($id)
    {
        $loan = $this->loanModel->find($id);
        if (!$loan) {
            $_SESSION['error'] = 'Préstamo no encontrado.';
            $this->redirect(\App\Core\UrlHelper::route('panel/loans'));
        }

        $db = $this->installmentModel->getDatabase();
        $installments = $db->findAll(
            "SELECT * FROM loan_installments WHERE loan_id = ? ORDER BY installment_number",
            [(int)$id]
        );

        $employees = $this->employeeModel->getAllEmployees();
        $employeesById = [];
        foreach ($employees as $emp) {
            $employeesById[$emp['id']] = trim(($emp['firstname'] ?? '') . ' ' . ($emp['lastname'] ?? ''));
        }

        $creditors = $this->creditorModel->getOptions();
        $creditorsById = [];
        foreach ($creditors as $creditor) {
            $creditorsById[$creditor['id']] = $creditor['description'] ?? 'Acreedor';
        }

        $this->render('admin/loans/edit', [
            'title' => 'Editar Préstamo',
            'loan' => $loan,
            'employeeName' => $employeesById[$loan['employee_id']] ?? 'Empleado',
            'creditorName' => $creditorsById[$loan['creditor_id']] ?? 'Acreedor',
            'loan_types' => $this->loanModel->getTiposPrestamo(),
            'installments' => $installments,
            'csrf_token' => AuthMiddleware::generateCSRF()
        ]);
    }

    public function update($id)
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect(\App\Core\UrlHelper::route('panel/loans'));
        }

        AuthMiddleware::validateCSRF();

        $loan = $this->loanModel->find($id);
        if (!$loan) {
            $_SESSION['error'] = 'Préstamo no encontrado.';
            $this->redirect(\App\Core\UrlHelper::route('panel/loans'));
        }

        $status = strtolower($loan['status'] ?? ($loan['estado'] ?? ''));
        if ($status === 'anulado') {
            $_SESSION['error'] = 'El préstamo está anulado y no puede editarse.';
            $this->redirect(\App\Core\UrlHelper::route('panel/loans/edit/' . $id));
        }

        $loanType = trim($_POST['loan_type'] ?? '');
        if ($loanType === '') {
            $_SESSION['error'] = 'Tipo de préstamo requerido.';
            $this->redirect(\App\Core\UrlHelper::route('panel/loans/edit/' . $id));
        }

        $loanTypes = $this->loanModel->getTiposPrestamo();
        if (!array_key_exists($loanType, $loanTypes)) {
            $matchedKey = array_search($loanType, $loanTypes, true);
            if ($matchedKey !== false) {
                $loanType = $matchedKey;
            }
        }

        try {
            $result = $this->loanModel->update($id, ['loan_type' => $loanType]);
            if (!$result) {
                throw new Exception('No se pudo actualizar el préstamo.');
            }

            $_SESSION['success'] = 'Préstamo actualizado correctamente.';
            $this->redirect(\App\Core\UrlHelper::route('panel/loans'));
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al actualizar préstamo: ' . $e->getMessage();
            $this->redirect(\App\Core\UrlHelper::route('panel/loans/edit/' . $id));
        }
    }

    public function cancel($id)
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect(\App\Core\UrlHelper::route('panel/loans'));
        }

        AuthMiddleware::validateCSRF();

        $loan = $this->loanModel->find($id);
        if (!$loan) {
            $_SESSION['error'] = 'Préstamo no encontrado.';
            $this->redirect(\App\Core\UrlHelper::route('panel/loans'));
        }

        $status = strtolower($loan['status'] ?? ($loan['estado'] ?? ''));
        if ($status === 'anulado') {
            $_SESSION['success'] = 'El préstamo ya estaba anulado.';
            $this->redirect(\App\Core\UrlHelper::route('panel/loans'));
        }

        try {
            $this->loanModel->cancelLoan((int)$id);
            $_SESSION['success'] = 'Préstamo anulado correctamente.';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al anular préstamo: ' . $e->getMessage();
        }

        $this->redirect(\App\Core\UrlHelper::route('panel/loans'));
    }

    /**
    * Generar detalle de cuotas según frecuencia y regla de diciembre
    */
    private function generateInstallments(int $loanId, float $totalAmount, int $count, string $frequency, bool $allowDecember, string $startDate): array
    {
        $installments = [];
        $amountPer = round($totalAmount / $count, 2);

        // Ajustar última cuota si hay diferencia por redondeo
        $remaining = $totalAmount;
        $currentDate = new DateTime($startDate);

        for ($i = 1; $i <= $count; $i++) {
            if ($i < $count) {
                $amount = $amountPer;
                $remaining -= $amount;
            } else {
                $amount = round($remaining, 2);
            }

            $dueDate = clone $currentDate;
            if (!$allowDecember && (int)$dueDate->format('m') === 12) {
                // Mover a enero manteniendo día si existe
                $dueDate->modify('first day of January ' . ($dueDate->format('Y') + 1));
            }

            $installments[] = [
                'loan_id' => $loanId,
                'installment_number' => $i,
                'amount' => $amount,
                'due_date' => $dueDate->format('Y-m-d'),
                'status' => 'pendiente', // ✅ Estado correcto según migración
                'created_at' => date('Y-m-d H:i:s')
            ];

            // Calcular siguiente fecha
            $currentDate = $this->addFrequency($currentDate, $frequency);
        }

        return $installments;
    }

    private function addFrequency(DateTime $date, string $frequency): DateTime
    {
        $newDate = clone $date;
        switch ($frequency) {
            case 'semanal':
                $newDate->add(new DateInterval('P7D'));
                break;
            case 'quincenal':
                $newDate->add(new DateInterval('P15D'));
                break;
            case 'mensual':
            default:
                $day = (int)$date->format('d');
                $newDate->add(new DateInterval('P1M'));
                // Ajustar fin de mes si el día original era alto
                if ((int)$newDate->format('d') !== $day) {
                    $newDate->modify('last day of last month');
                }
                break;
        }
        return $newDate;
    }

    private function isValidDate($date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    protected function requireAuth()
    {
        \App\Middleware\AuthMiddleware::requireAuth();
    }

    /**
     * Verificar que existe un concepto para el acreedor, si no existe crearlo
     * Retorna: 'created', 'exists', o 'error'
     */
    private function ensureConceptForCreditor($creditorId)
    {
        try {
            // Obtener información del acreedor
            $creditor = $this->creditorModel->findById($creditorId);
            if (!$creditor) {
                error_log("Acreedor no encontrado con ID: $creditorId");
                return 'error';
            }

            // Buscar si ya existe un concepto con la fórmula que referencie este acreedor
            $existingConcept = $this->findConceptByCreditorId($creditorId);

            if ($existingConcept) {
                error_log("Concepto ya existe para acreedor $creditorId: ID {$existingConcept['id']}");
                return 'exists';
            }

            // Crear el concepto automáticamente
            $created = $this->createConceptForCreditor($creditorId, $creditor['description']);

            return $created ? 'created' : 'error';

        } catch (Exception $e) {
            error_log("Error en ensureConceptForCreditor para acreedor $creditorId: " . $e->getMessage());
            return 'error';
        }
    }

    /**
     * 💳 Buscar concepto existente por creditor_id directo
     * Busca primero por creditor_id, luego por fórmula (fallback legacy)
     */
    private function findConceptByCreditorId($creditorId)
    {
        try {
            $db = $this->conceptModel->db;

            // ✅ MÉTODO 1: Búsqueda directa por creditor_id (más rápido y preciso)
            $sql = "SELECT * FROM concepto
                    WHERE creditor_id = ?
                    AND activo = 1
                    LIMIT 1";

            $stmt = $db->prepare($sql);
            $stmt->execute([$creditorId]);
            $concept = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($concept) {
                error_log("💳 Concepto encontrado por creditor_id: {$concept['id']}");
                return $concept;
            }

            // ❌ MÉTODO 2: Fallback para conceptos legacy sin creditor_id
            $creditor = $this->creditorModel->findById($creditorId);
            if (!$creditor || empty($creditor['creditor_id'])) {
                return null;
            }

            $creditorCode = $creditor['creditor_id'];

            $sql = "SELECT * FROM concepto
                    WHERE (formula LIKE ? OR formula LIKE ?)
                    AND activo = 1
                    LIMIT 1";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                "%CUOTAPRESTAMO(FICHA, '$creditorCode')%",
                "%CUOTAPRESTAMO(FICHA, $creditorId)%"
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Error buscando concepto por creditor_id: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear concepto automáticamente para el acreedor
     * Usa la fórmula CUOTAPRESTAMO(FICHA, creditor_id)
     */
    private function createConceptForCreditor($creditorId, $creditorDescription)
    {
        try {
            // Obtener el creditor_id (código) del acreedor
            $creditor = $this->creditorModel->findById($creditorId);
            if (!$creditor || empty($creditor['creditor_id'])) {
                error_log("No se pudo obtener el código del acreedor ID: $creditorId");
                return false;
            }

            $creditorCode = $creditor['creditor_id'];

            // Generar código único para el concepto
            $conceptCode = $this->generateConceptCode($creditorDescription);

            // Crear descripción del concepto
            $conceptDescription = 'DEDUCCIÓN ' . strtoupper($creditorDescription);

            // Crear la fórmula con el código del acreedor (creditor_id)
            // Usar comillas simples para el parámetro string
            $formula = "CUOTAPRESTAMO(FICHA, '$creditorCode')";

            // Datos del concepto
            $conceptData = [
                'concepto' => $conceptCode,
                'descripcion' => $conceptDescription,
                'tipo_concepto' => 'D', // Deducción
                'formula' => $formula,
                'creditor_id' => $creditorId, // 💳 Relación directa con acreedor
                'valor_fijo' => null,
                'monto_cero' => 1, // Permitir montos cero para deducciones
                'monto_calculo' => 1, // Usar cálculo con fórmula
                'imprime_detalles' => 1, // Imprimir en detalles de planilla
                'activo' => 1 // Concepto activo
            ];

            // Crear el concepto
            $conceptId = $this->conceptModel->create($conceptData);

            if ($conceptId) {
                // Crear relaciones por defecto para el concepto
                $this->createDefaultConceptRelations($conceptId);

                error_log("Concepto CUOTAPRESTAMO creado automáticamente para acreedor $creditorId (código: $creditorCode): Concepto ID $conceptId");
                return true;
            }

            return false;

        } catch (Exception $e) {
            error_log("Error creando concepto para acreedor $creditorId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear relaciones por defecto para conceptos de acreedores
     * Basado en CreditorController::createDefaultConceptRelations()
     */
    private function createDefaultConceptRelations($conceptId)
    {
        try {
            $db = $this->conceptModel->db;

            // 1. Relaciones con TIPOS DE PLANILLA
            // Por defecto, aplicar a tipos más comunes: Quincenal (1) y Mensual (2)
            $defaultPayrollTypes = [1, 2]; // Quincenal y Planilla cada mes

            foreach ($defaultPayrollTypes as $typeId) {
                $stmt = $db->prepare("INSERT IGNORE INTO concepto_tipos_planilla (concepto_id, tipo_planilla_id) VALUES (?, ?)");
                $stmt->execute([$conceptId, $typeId]);
            }

            // 2. Relaciones con FRECUENCIAS
            // Por defecto, aplicar frecuencia "Se aplica en todas las planillas" (1)
            $defaultFrequencies = [1]; // Se aplica en todas las planillas

            foreach ($defaultFrequencies as $frequencyId) {
                $stmt = $db->prepare("INSERT IGNORE INTO concepto_frecuencias (concepto_id, frecuencia_id) VALUES (?, ?)");
                $stmt->execute([$conceptId, $frequencyId]);
            }

            // 3. Relaciones con SITUACIONES
            // Por defecto, aplicar solo a empleados activos (1)
            $defaultSituations = [1]; // Empleado activo

            foreach ($defaultSituations as $situationId) {
                $stmt = $db->prepare("INSERT IGNORE INTO concepto_situaciones (concepto_id, situacion_id) VALUES (?, ?)");
                $stmt->execute([$conceptId, $situationId]);
            }

            // 4. Relaciones con TIPOS DE ACUMULADO
            // Por defecto, aplicar "Por concepto" (22)
            $defaultAccumulatedTypes = [22]; // Por concepto

            foreach ($defaultAccumulatedTypes as $accumulatedTypeId) {
                $stmt = $db->prepare("INSERT IGNORE INTO concepto_acumulados (concepto_id, tipo_acumulado_id) VALUES (?, ?)");
                $stmt->execute([$conceptId, $accumulatedTypeId]);
            }

            error_log("Relaciones por defecto creadas para concepto $conceptId");
            return true;

        } catch (Exception $e) {
            error_log("Error creando relaciones por defecto para concepto $conceptId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generar código único para el concepto
     * Basado en CreditorController::generateConceptCode()
     */
    private function generateConceptCode($creditorDescription)
    {
        // Generar código basado en las primeras letras del nombre del acreedor
        $words = explode(' ', strtoupper($creditorDescription));
        $code = '';

        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $code .= substr($word, 0, 2);
            }
        }

        // Limitar a 6 caracteres máximo
        $code = substr($code, 0, 6);

        // Verificar si el código ya existe y agregar número si es necesario
        $originalCode = $code;
        $counter = 1;

        while ($this->conceptModel->isCodeDuplicate($code)) {
            $code = $originalCode . sprintf('%02d', $counter);
            $counter++;

            // Evitar bucle infinito
            if ($counter > 99) {
                $code = 'ACR' . sprintf('%03d', rand(1, 999));
                break;
            }
        }

        return $code;
    }
}
