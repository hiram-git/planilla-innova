<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Employee;
use DateInterval;
use DateTime;
use Exception;

class LoanController extends Controller
{
    private Loan $loanModel;
    private LoanInstallment $installmentModel;
    private Employee $employeeModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->loanModel = new Loan();
        $this->installmentModel = new LoanInstallment();
        $this->employeeModel = new Employee();
    }

    public function index()
    {
        $loans = $this->loanModel->all();
        $employees = $this->employeeModel->getAllEmployees();
        $employeesById = [];
        foreach ($employees as $emp) {
            $employeesById[$emp['id']] = trim(($emp['firstname'] ?? '') . ' ' . ($emp['lastname'] ?? ''));
        }

        $this->render('admin/loans/index', [
            'title' => 'Préstamos',
            'loans' => $loans,
            'employeesById' => $employeesById
        ]);
    }

    public function create()
    {
        $employees = $this->employeeModel->getAllEmployees();
        $this->render('admin/loans/create', [
            'title' => 'Nuevo Préstamo',
            'employees' => $employees,
            'csrf_token' => \App\Middleware\AuthMiddleware::generateCSRF()
        ]);
    }

    public function store()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect(\App\Core\UrlHelper::route('panel/loans/create'));
        }

        \App\Middleware\AuthMiddleware::validateCSRF();

        $data = [
            'employee_id' => $_POST['employee_id'] ?? null,
            'loan_type' => trim($_POST['loan_type'] ?? ''),
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
                'status' => 'generada',
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
}
