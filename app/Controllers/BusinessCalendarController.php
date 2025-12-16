<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\UrlHelper;
use App\Core\Security;
use App\Middleware\AuthMiddleware;

class BusinessCalendarController extends Controller
{
    public function __construct()
    {
        AuthMiddleware::requireAuth();
    }

    /**
     * Vista principal - Calendario visual con FullCalendar.js
     */
    public function index()
    {
        $businessCalendar = $this->model('BusinessCalendar');

        $year = isset($_GET['year']) && is_numeric($_GET['year'])
            ? (int)$_GET['year']
            : date('Y');

        // Obtener todos los días especiales del año para el calendario
        $sql = "SELECT * FROM business_calendar
                WHERE year_value = ?
                AND day_type IN ('FERIADO', 'DUELO_NACIONAL', 'ESPECIAL')
                ORDER BY date_value";

        $specialDays = $businessCalendar->db->findAll($sql, [$year]);

        // Convertir a formato FullCalendar
        $calendarEvents = [];
        $colors = \App\Models\BusinessCalendar::getDayTypeColors();

        foreach ($specialDays as $day) {
            $calendarEvents[] = [
                'id' => $day['id'],
                'title' => $day['description'],
                'start' => $day['date_value'],
                'backgroundColor' => $colors[$day['day_type']] ?? '#6c757d',
                'borderColor' => $colors[$day['day_type']] ?? '#6c757d',
                'extendedProps' => [
                    'dayType' => $day['day_type'],
                    'status' => $day['status']
                ]
            ];
        }

        // Obtener TODOS los días del año para badges
        $sqlAllDays = "SELECT date_value, day_type, description, status
                       FROM business_calendar
                       WHERE year_value = ?
                       ORDER BY date_value";

        $allDays = $businessCalendar->db->findAll($sqlAllDays, [$year]);

        // Indexar por fecha para acceso rápido
        $daysByDate = [];
        foreach ($allDays as $day) {
            $daysByDate[$day['date_value']] = [
                'day_type' => $day['day_type'],
                'description' => $day['description'] ?? '',
                'status' => $day['status']
            ];
        }

        $data = [
            'title' => 'Calendario Empresarial',
            'page_title' => 'Calendario Empresarial',
            'year' => $year,
            'calendar_events' => $calendarEvents,
            'days_by_date' => $daysByDate,
            'day_type_colors' => $colors,
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->view('admin/business_calendar/calendar', $data);
    }

    /**
     * Vista secundaria - Listado de días especiales
     */
    public function listado()
    {
        $businessCalendar = $this->model('BusinessCalendar');

        // Obtener año seleccionado o año actual
        $year = isset($_GET['year']) && is_numeric($_GET['year'])
            ? (int)$_GET['year']
            : date('Y');

        // Obtener feriados y días especiales del año
        $holidays = $businessCalendar->getHolidaysByYear($year);

        // Obtener estadísticas del año
        $stats = $businessCalendar->getCalendarStats($year);

        // Procesar estadísticas para vista
        $statsProcessed = [
            'total_dias' => 0,
            'dias_laborables' => 0,
            'feriados' => 0,
            'no_laborables' => 0,
            'especiales' => 0
        ];

        foreach ($stats as $stat) {
            $statsProcessed['total_dias'] += $stat['count'];

            switch ($stat['day_type']) {
                case 'LABORAL':
                    $statsProcessed['dias_laborables'] += $stat['count'];
                    break;
                case 'FERIADO':
                    $statsProcessed['feriados'] += $stat['count'];
                    break;
                case 'NO_LABORAL':
                    $statsProcessed['no_laborables'] += $stat['count'];
                    break;
                case 'ESPECIAL':
                case 'DUELO_NACIONAL':
                    $statsProcessed['especiales'] += $stat['count'];
                    break;
            }
        }

        $data = [
            'title' => 'Calendario Empresarial - Listado',
            'page_title' => 'Calendario Empresarial - Listado',
            'year' => $year,
            'holidays' => $holidays,
            'stats' => $statsProcessed,
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->view('admin/business_calendar/index', $data);
    }

    /**
     * Crear nuevo día especial
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(UrlHelper::route('panel/business-calendar'));
        }

        AuthMiddleware::validateCSRF();

        $data = Security::sanitizeInput($_POST);
        $businessCalendar = $this->model('BusinessCalendar');

        // Validación básica
        $errors = [];

        if (empty($data['date_value'])) {
            $errors['date_value'] = 'La fecha es obligatoria';
        }

        if (empty($data['day_type'])) {
            $errors['day_type'] = 'El tipo de día es obligatorio';
        }

        if (empty($data['description'])) {
            $errors['description'] = 'La descripción es obligatoria';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect(UrlHelper::route('panel/business-calendar'));
        }

        try {
            // Obtener campo is_paid_holiday
            $isPaidHoliday = isset($data['is_paid_holiday']) && $data['is_paid_holiday'] == '1' ? 1 : 0;

            // Logging para debugging
            error_log("BusinessCalendarController::store - Data received: " . json_encode([
                'date_value' => $data['date_value'] ?? 'NOT SET',
                'day_type' => $data['day_type'] ?? 'NOT SET',
                'status' => $data['status'] ?? 'NORMAL',
                'description' => $data['description'] ?? 'NOT SET',
                'is_paid_holiday' => $isPaidHoliday
            ]));

            $success = $businessCalendar->addSpecialDay(
                $data['date_value'],
                $data['day_type'],
                $data['status'] ?? 'NORMAL',
                $data['description'],
                $isPaidHoliday
            );

            if ($success) {
                $_SESSION['success'] = 'Día especial agregado exitosamente';
            } else {
                $_SESSION['error'] = 'Error al agregar día especial';
            }

            $this->redirect(UrlHelper::route('panel/business-calendar/listado'));
        } catch (\Exception $e) {
            error_log("BusinessCalendarController::store - Exception: " . $e->getMessage());
            $_SESSION['error'] = 'Error al agregar día especial: ' . $e->getMessage();
            $this->redirect(UrlHelper::route('panel/business-calendar/listado'));
        }
    }

    /**
     * Actualizar día especial existente
     * ✅ NUEVO: Permite editar días del calendario
     */
    public function update($id)
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            exit;
        }

        AuthMiddleware::validateCSRF();

        $data = Security::sanitizeInput($_POST);
        $businessCalendar = $this->model('BusinessCalendar');

        try {
            // Verificar que el día existe
            $day = $businessCalendar->find($id);

            if (!$day) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Día no encontrado'
                ]);
                exit;
            }

            // Preparar datos para actualizar
            $updateData = [];

            if (isset($data['day_type'])) {
                $updateData['day_type'] = $data['day_type'];
            }

            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
            }

            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }

            // Campo is_paid_holiday
            if (isset($data['is_paid_holiday'])) {
                $updateData['is_paid_holiday'] = $data['is_paid_holiday'] == '1' ? 1 : 0;
            }

            $success = $businessCalendar->updateDay($id, $updateData);

            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Día actualizado exitosamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al actualizar el día'
                ]);
            }

        } catch (\Exception $e) {
            error_log("Error en update: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * Eliminar día especial
     */
    public function delete($id)
    {
        AuthMiddleware::validateCSRF();

        $businessCalendar = $this->model('BusinessCalendar');

        try {
            // No permitir eliminar feriados nacionales permanentes
            $dayInfo = $businessCalendar->find($id);

            if (!$dayInfo) {
                $_SESSION['error'] = 'Día no encontrado';
                $this->redirect(UrlHelper::route('panel/business-calendar'));
            }

            // Permitir solo eliminar días tipo ESPECIAL o personalizados
            if ($dayInfo['day_type'] === 'FERIADO') {
                $_SESSION['warning'] = 'No se pueden eliminar feriados nacionales. Solo días especiales personalizados.';
                $this->redirect(UrlHelper::route('panel/business-calendar'));
            }

            $businessCalendar->delete($id);
            $_SESSION['success'] = 'Día especial eliminado exitosamente';

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al eliminar: ' . $e->getMessage();
        }

        $this->redirect(UrlHelper::route('panel/business-calendar'));
    }

    /**
     * API: Obtener días laborables entre fechas (AJAX)
     */
    public function getWorkingDays()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Método no permitido']);
            exit;
        }

        $data = Security::sanitizeInput($_POST);
        $businessCalendar = $this->model('BusinessCalendar');

        if (empty($data['start_date']) || empty($data['end_date'])) {
            echo json_encode(['error' => 'Fechas requeridas']);
            exit;
        }

        try {
            $workingDays = $businessCalendar->getWorkingDaysBetween(
                $data['start_date'],
                $data['end_date']
            );

            echo json_encode([
                'success' => true,
                'working_days' => $workingDays,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date']
            ]);
        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }

        exit;
    }

    /**
     * Inicializar calendario completo de un año
     */
    public function initializeYear()
    {
        error_log("BusinessCalendarController::initializeYear - METHOD CALLED");
        error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("BusinessCalendarController::initializeYear - NOT POST, redirecting");
            $this->redirect(UrlHelper::route('panel/business-calendar'));
        }

        error_log("BusinessCalendarController::initializeYear - Validating CSRF");
        AuthMiddleware::validateCSRF();
        error_log("BusinessCalendarController::initializeYear - CSRF validated");

        $data = Security::sanitizeInput($_POST);
        error_log("BusinessCalendarController::initializeYear - POST data sanitized: " . json_encode($data));

        $businessCalendar = $this->model('BusinessCalendar');

        // Validación
        if (empty($data['year']) || !is_numeric($data['year'])) {
            $_SESSION['error'] = 'Año inválido';
            $this->redirect(UrlHelper::route('panel/business-calendar/listado'));
        }

        $year = (int)$data['year'];

        // Validar rango de años razonable
        $currentYear = (int)date('Y');
        if ($year < 2020 || $year > ($currentYear + 5)) {
            $_SESSION['error'] = 'El año debe estar entre 2020 y ' . ($currentYear + 5);
            $this->redirect(UrlHelper::route('panel/business-calendar/listado'));
        }

        try {
            // Obtener opción de sábados como medio día
            $saturdayHalfDay = isset($data['saturday_half_day']) && $data['saturday_half_day'] == '1';

            // Debug logging
            error_log("BusinessCalendarController::initializeYear - Year: {$year}, Saturday Half Day: " . ($saturdayHalfDay ? 'YES' : 'NO'));
            error_log("POST data: " . json_encode($data));

            $result = $businessCalendar->initializeYear($year, $saturdayHalfDay);

            if ($result['success']) {
                $message = "Calendario $year inicializado exitosamente. ";
                $message .= "Días insertados: {$result['inserted']}, Total: {$result['total']}";

                if ($saturdayHalfDay && isset($result['updated']) && $result['updated'] > 0) {
                    $message .= ". Sábados actualizados a medio día: {$result['updated']}";
                } elseif ($saturdayHalfDay) {
                    $message .= " (Sábados marcados como medio día)";
                }

                $_SESSION['success'] = $message;
            } else {
                $_SESSION['error'] = 'Error al inicializar calendario: ' . ($result['error'] ?? 'Error desconocido');
            }

            $this->redirect(UrlHelper::route('panel/business-calendar/listado?year=' . $year));
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al inicializar calendario: ' . $e->getMessage();
            $this->redirect(UrlHelper::route('panel/business-calendar/listado'));
        }
    }

    /**
     * Sincronizar calendario desde API externa (Manual)
     * Incluye confirmación antes de proceder
     */
    public function syncFromApi()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            exit;
        }

        AuthMiddleware::validateCSRF();

        try {
            // Obtener parámetros
            $year = isset($_POST['year']) && is_numeric($_POST['year'])
                ? (int)$_POST['year']
                : date('Y');

            $replace = isset($_POST['replace']) && $_POST['replace'] === 'true';
            $dryRun = isset($_POST['dry_run']) && $_POST['dry_run'] === 'true';

            // Inicializar servicio de sincronización
            $syncService = new \App\Services\CalendarSyncService();

            // Ejecutar sincronización
            $stats = $syncService->syncFromApi([
                'year' => $year,
                'replace' => $replace,
                'dry_run' => $dryRun
            ]);

            $errors = $syncService->getErrors();

            // Preparar respuesta
            $response = [
                'success' => true,
                'stats' => $stats,
                'errors' => $errors,
                'message' => $dryRun
                    ? "Simulación completada (dry-run): {$stats['inserted']} a insertar, {$stats['updated']} a actualizar"
                    : "Sincronización completada: {$stats['inserted']} insertados, {$stats['updated']} actualizados, {$stats['skipped']} omitidos"
            ];

            if ($stats['errors'] > 0) {
                $response['message'] .= ". {$stats['errors']} errores encontrados.";
            }

            if ($stats['deleted'] > 0) {
                $response['message'] .= " {$stats['deleted']} registros reemplazados.";
            }

            echo json_encode($response);

        } catch (\Exception $e) {
            error_log("Error en syncFromApi: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error durante la sincronización: ' . $e->getMessage(),
                'stats' => ['fetched' => 0, 'inserted' => 0, 'updated' => 0, 'skipped' => 0, 'deleted' => 0, 'errors' => 1]
            ]);
        }

        exit;
    }
}
