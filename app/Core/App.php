<?php

namespace App\Core;

use App\Middleware\PermissionMiddleware;

class App
{
    protected $controller = 'Admin';
    protected $method = 'index';
    protected $params = [];

    public function __construct()
    {
        Bootstrap::init();
    }

    public function run()
    {
        $url = $this->parseUrl();
        
        // Manejo especial para rutas de setup/wizard (sin autenticación)
        if (!empty($url[0]) && $url[0] === 'setup') {
            $this->handleWizardRoutes($url);
            return;
        }
        
        // Manejo especial para ruta crear-empresa (wizard Vuetify)
        if (!empty($url[0]) && $url[0] === 'crear-empresa') {
            $this->handleCrearEmpresaRoute();
            return;
        }
        
        // Manejo especial para rutas API
        if (!empty($url[0]) && $url[0] === 'api') {
            $this->handleApiRoutes($url);
            return;
        }

        // Manejo especial para webhooks (sin autenticación)
        if (!empty($url[0]) && $url[0] === 'webhooks') {
            $this->handleWebhookRoutes($url);
            return;
        }

        // Manejo especial para rutas que empiezan con 'panel'
        if (!empty($url[0]) && $url[0] === 'panel') {
            if (isset($url[1])) {
                // Mapeo de rutas plurales a controladores singulares
                $routeMapping = [
                    'dashboard' => ['controller' => 'Admin', 'method' => 'dashboard'],
                    'login' => ['controller' => 'Admin', 'method' => 'login'],
                    'logout' => ['controller' => 'Admin', 'method' => 'logout'],
                    'employees' => ['controller' => 'Employee', 'method' => null],
                    'positions' => ['controller' => 'Position', 'method' => null],
                    'cargos' => ['controller' => 'Cargo', 'method' => null],
                    'partidas' => ['controller' => 'Partida', 'method' => null],
                    'funciones' => ['controller' => 'Funcion', 'method' => null],
                    'schedules' => ['controller' => 'Schedule', 'method' => null],
                    'attendance' => ['controller' => 'AttendanceController', 'method' => null],
                    'attendance-api-config' => ['controller' => 'AttendanceApiConfigController', 'method' => 'index'],
                    'marcaciones' => ['controller' => 'Timeclock', 'method' => 'index'],
                    'payrolls' => ['controller' => 'PayrollController', 'method' => null],
                    'concepts' => ['controller' => 'ConceptController', 'method' => null],
                    'tipos-planilla' => ['controller' => 'TipoPlanillaController', 'method' => null],
                    'frecuencias' => ['controller' => 'FrecuenciaController', 'method' => null],
                    'situaciones' => ['controller' => 'SituacionController', 'method' => null],
                    'users' => ['controller' => 'UserController', 'method' => null],
                    'roles' => ['controller' => 'RoleController', 'method' => null],
                    'creditors' => ['controller' => 'CreditorController', 'method' => null],
                    'deductions' => ['controller' => 'DeductionController', 'method' => null],
                    'reports' => ['controller' => 'ReportController', 'method' => null],
                    'company' => ['controller' => 'CompanyController', 'method' => null],
                    'tipos-acumulados' => ['controller' => 'TipoAcumuladoController', 'method' => null],
                    'acumulados' => ['controller' => 'AcumuladoController', 'method' => null],
                    'liquidation' => ['controller' => 'LiquidationController', 'method' => null],
                    'vacation' => ['controller' => 'VacationController', 'method' => null],
                    'business-calendar' => ['controller' => 'BusinessCalendarController', 'method' => null],
                    'organizational' => ['controller' => 'OrganizationalController', 'method' => null],
                    'users' => ['controller' => 'UserController', 'method' => null],
                    'roles' => ['controller' => 'RoleController', 'method' => null],
                    // Mantener compatibilidad con rutas singulares
                    'employee' => ['controller' => 'Employee', 'method' => null],
                    'position' => ['controller' => 'Position', 'method' => null],
                    'cargo' => ['controller' => 'Cargo', 'method' => null],
                    'partida' => ['controller' => 'Partida', 'method' => null],
                    'funcion' => ['controller' => 'Funcion', 'method' => null],
                    'schedule' => ['controller' => 'Schedule', 'method' => null],
                    'payroll' => ['controller' => 'PayrollController', 'method' => null],
                    'concept' => ['controller' => 'ConceptController', 'method' => null]
                ];
                
                if (isset($routeMapping[$url[1]])) {
                    $mapping = $routeMapping[$url[1]];
                    $controllerName = 'App\\Controllers\\' . $mapping['controller'];

                    if (class_exists($controllerName)) {
                        // ✅ MANEJO ESPECIAL: attendance-api-config submethods
                        if ($url[1] === 'attendance-api-config' && isset($url[2])) {
                            $this->controller = new $controllerName();
                            $httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

                            if ($httpMethod === 'POST') {
                                // Mapear submétodos para attendance-api-config
                                $submethodMap = [
                                    'save' => 'save',
                                    'test-connection' => 'testConnection',
                                    'sync-now' => 'syncNow',
                                    'enable-sync' => 'enableSync',
                                    'disable-sync' => 'disableSync',
                                    'log-details' => 'getLogDetails',
                                    'clean-logs' => 'cleanOldLogs'
                                ];

                                if (isset($submethodMap[$url[2]]) && method_exists($this->controller, $submethodMap[$url[2]])) {
                                    $this->method = $submethodMap[$url[2]];
                                    $this->params = [];
                                    call_user_func_array([$this->controller, $this->method], $this->params);
                                    return;
                                }
                            }

                            // Si no es POST o no matchea, usar método por defecto
                            $this->method = $mapping['method'] ?? 'index';
                            $this->params = array_slice($url, 2);
                            call_user_func_array([$this->controller, $this->method], $this->params);
                            return;
                        }

                        // ✅ MANEJO ESPECIAL: attendance submethods
                        if ($url[1] === 'attendance' && isset($url[2])) {
                            $httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

                            // Manejo de dispositivos (/panel/attendance/devices)
                            if ($url[2] === 'devices') {
                                $deviceController = new \App\Controllers\AttendanceDeviceController();

                                if ($httpMethod === 'GET') {
                                    if (!isset($url[3])) {
                                        // GET /panel/attendance/devices
                                        $deviceController->index();
                                    } elseif ($url[3] === 'create') {
                                        // GET /panel/attendance/devices/create
                                        $deviceController->create();
                                    } elseif (isset($url[4]) && $url[4] === 'edit') {
                                        // GET /panel/attendance/devices/{id}/edit
                                        $deviceController->edit($url[3]);
                                    }
                                } elseif ($httpMethod === 'POST') {
                                    if ($url[3] === 'store') {
                                        // POST /panel/attendance/devices/store
                                        $deviceController->store();
                                    } elseif (isset($url[4])) {
                                        if ($url[4] === 'update') {
                                            // POST /panel/attendance/devices/{id}/update
                                            $deviceController->update($url[3]);
                                        } elseif ($url[4] === 'delete') {
                                            // POST /panel/attendance/devices/{id}/delete
                                            $deviceController->delete($url[3]);
                                        } elseif ($url[4] === 'test') {
                                            // POST /panel/attendance/devices/{id}/test
                                            $deviceController->testConnection($url[3]);
                                        } elseif ($url[4] === 'toggle') {
                                            // POST /panel/attendance/devices/{id}/toggle
                                            $deviceController->toggle($url[3]);
                                        }
                                    }
                                }
                                return;
                            }

                            // Manejo de historial de sincronización
                            if ($url[2] === 'sync-history') {
                                $this->controller = new $controllerName();

                                if (!isset($url[3])) {
                                    // GET /panel/attendance/sync-history
                                    $this->method = 'syncHistory';
                                    $this->params = [];
                                } else {
                                    // GET /panel/attendance/sync-history/{id}
                                    $this->method = 'syncHistoryDetail';
                                    $this->params = [$url[3]];
                                }
                                call_user_func_array([$this->controller, $this->method], $this->params);
                                return;
                            }

                            $this->controller = new $controllerName();

                            // Rutas GET para attendance
                            if ($httpMethod === 'GET') {
                                if ($url[2] === 'sync' && method_exists($this->controller, 'sync')) {
                                    // Ruta: /panel/attendance/sync
                                    $this->method = 'sync';
                                    $this->params = [];
                                    call_user_func_array([$this->controller, $this->method], $this->params);
                                    return;
                                } elseif ($url[2] === 'detail' && isset($url[3]) && method_exists($this->controller, 'detail')) {
                                    // Ruta: /panel/attendance/detail/2025-10-16
                                    $this->method = 'detail';
                                    $this->params = [$url[3]]; // date
                                    call_user_func_array([$this->controller, $this->method], $this->params);
                                    return;
                                } elseif ($url[2] === 'export-excel' && isset($url[3]) && method_exists($this->controller, 'exportExcel')) {
                                    // Ruta: /panel/attendance/export-excel/2025-10-16
                                    $this->method = 'exportExcel';
                                    $this->params = [$url[3]]; // date
                                    call_user_func_array([$this->controller, $this->method], $this->params);
                                    return;
                                } elseif ($url[2] === 'export-pdf' && isset($url[3]) && method_exists($this->controller, 'exportPDF')) {
                                    // Ruta: /panel/attendance/export-pdf/2025-10-16
                                    $this->method = 'exportPDF';
                                    $this->params = [$url[3]]; // date
                                    call_user_func_array([$this->controller, $this->method], $this->params);
                                    return;
                                }
                            } elseif ($httpMethod === 'POST') {
                                // Rutas POST
                                if ($url[2] === 'sync-now' && method_exists($this->controller, 'syncNow')) {
                                    // POST: /panel/attendance/sync-now
                                    $this->method = 'syncNow';
                                    $this->params = [];
                                    call_user_func_array([$this->controller, $this->method], $this->params);
                                    return;
                                } elseif ($url[2] === 'import-file' && method_exists($this->controller, 'importFile')) {
                                    // POST: /panel/attendance/import-file
                                    $this->method = 'importFile';
                                    $this->params = [];
                                    call_user_func_array([$this->controller, $this->method], $this->params);
                                    return;
                                } elseif ($url[2] === 'manual-entry' && method_exists($this->controller, 'manualEntry')) {
                                    // POST: /panel/attendance/manual-entry
                                    $this->method = 'manualEntry';
                                    $this->params = [];
                                    call_user_func_array([$this->controller, $this->method], $this->params);
                                    return;
                                } elseif ($url[2] === 'detail' && isset($url[3])) {
                                    // POST: /panel/attendance/detail/{id}/{action}
                                    if (isset($url[4])) {
                                        if ($url[4] === 'update' && method_exists($this->controller, 'updateDetail')) {
                                            $this->method = 'updateDetail';
                                            $this->params = [$url[3]];
                                        } elseif ($url[4] === 'delete' && method_exists($this->controller, 'deleteDetail')) {
                                            $this->method = 'deleteDetail';
                                            $this->params = [$url[3]];
                                        } elseif ($url[4] === 'justify' && method_exists($this->controller, 'justifyAbsence')) {
                                            $this->method = 'justifyAbsence';
                                            $this->params = [$url[3]];
                                        } elseif ($url[4] === 'calculate' && method_exists($this->controller, 'calculateAttendance')) {
                                            // POST: /panel/attendance/detail/{id}/calculate
                                            $this->method = 'calculateAttendance';
                                            $this->params = [$url[3]];
                                        }
                                        call_user_func_array([$this->controller, $this->method], $this->params);
                                        return;
                                    }
                                } elseif ($url[2] === 'detect-absences' && method_exists($this->controller, 'detectAbsences')) {
                                    // POST: /panel/attendance/detect-absences
                                    $this->method = 'detectAbsences';
                                    $this->params = [];
                                    call_user_func_array([$this->controller, $this->method], $this->params);
                                    return;
                                } elseif ($url[2] === 'process-calculations' && method_exists($this->controller, 'processCalculations')) {
                                    // POST: /panel/attendance/process-calculations
                                    $this->method = 'processCalculations';
                                    $this->params = [];
                                    call_user_func_array([$this->controller, $this->method], $this->params);
                                    return;
                                } elseif ($url[2] === 'process-day' && method_exists($this->controller, 'processDay')) {
                                    // POST: /panel/attendance/process-day
                                    $this->method = 'processDay';
                                    $this->params = [];
                                    call_user_func_array([$this->controller, $this->method], $this->params);
                                    return;
                                } elseif ($url[2] === 'absence' && isset($url[3]) && isset($url[4]) && $url[4] === 'justify' && method_exists($this->controller, 'justifyAbsenceFromLog')) {
                                    // POST: /panel/attendance/absence/{id}/justify
                                    $this->method = 'justifyAbsenceFromLog';
                                    $this->params = [$url[3]]; // absence_id
                                    call_user_func_array([$this->controller, $this->method], $this->params);
                                    return;
                                }
                            } elseif ($httpMethod === 'GET') {
                                // Rutas GET adicionales para calculadores
                                if ($url[2] === 'absence-report' && method_exists($this->controller, 'getAbsenceReport')) {
                                    // GET: /panel/attendance/absence-report?employee_id=X&start_date=Y&end_date=Z
                                    $this->method = 'getAbsenceReport';
                                    $this->params = [];
                                    call_user_func_array([$this->controller, $this->method], $this->params);
                                    return;
                                } elseif ($url[2] === 'pending-absences-view' && method_exists($this->controller, 'pendingAbsencesView')) {
                                    // GET: /panel/attendance/pending-absences-view (vista HTML)
                                    $this->method = 'pendingAbsencesView';
                                    $this->params = [];
                                    call_user_func_array([$this->controller, $this->method], $this->params);
                                    return;
                                } elseif ($url[2] === 'pending-absences' && method_exists($this->controller, 'getPendingAbsences')) {
                                    // GET: /panel/attendance/pending-absences (AJAX endpoint JSON)
                                    $this->method = 'getPendingAbsences';
                                    $this->params = [];
                                    call_user_func_array([$this->controller, $this->method], $this->params);
                                    return;
                                }
                            }

                            // Si no matchea ninguna ruta especial, usar routing normal
                            $this->method = 'index';
                            $this->params = array_slice($url, 2);
                            call_user_func_array([$this->controller, $this->method], $this->params);
                            return;
                        }

                        // ✅ MIDDLEWARE DE PERMISOS - Verificar acceso antes de instanciar controlador
                        $currentRoute = implode('/', array_slice($url, 0, 3)); // panel/module/action
                        
                        // ✅ EXCEPCIÓN: No verificar permisos en rutas de autenticación y dashboard
                        $publicRoutes = ['panel/login', 'panel/logout', 'admin/login', 'admin/index', 'panel/dashboard'];
                        $simpleRoute = implode('/', array_slice($url, 0, 2)); // panel/dashboard
                        
                        if (!in_array($currentRoute, $publicRoutes) && !in_array($simpleRoute, $publicRoutes)) {
                            $this->checkRoutePermissions($currentRoute);
                        }
                        
                        $this->controller = new $controllerName();
                        
                        // Si el mapping tiene un método específico (como dashboard)
                        if ($mapping['method']) {
                            $this->method = $mapping['method'];
                            $this->params = array_slice($url, 2);
                        } else {
                            // Para otros controladores, manejar métodos y parámetros
                            $this->method = 'index'; // Default
                            $this->params = [];
                            
                            // Verificar método HTTP para manejar POST directo
                            $httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
                            
                            if (!isset($url[2]) && $httpMethod === 'POST') {
                                // POST directo al controlador (ej: /panel/tipos-planilla)
                                if (method_exists($this->controller, 'store')) {
                                    $this->method = 'store';
                                }
                            } elseif (isset($url[2])) {
                                // Manejar rutas especiales según el método HTTP
                                $httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

                                if ($httpMethod === 'POST') {

                                    // Para POST, mapear a métodos específicos
                                    if ($url[2] === 'store' && method_exists($this->controller, 'store')) {
                                        $this->method = 'store';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'create' && method_exists($this->controller, 'create')) {

                                        // POST a /create debe llamar al método create
                                        $this->method = 'create';
                                        $this->params = array_slice($url, 3);
                                    } elseif (is_numeric($url[2])) {
                                        // Para POST con ID, verificar submétodos
                                        if (isset($url[3])) {
                                            if ($url[3] === 'update' && method_exists($this->controller, 'update')) {
                                                $this->method = 'update';
                                                $this->params = [$url[2]];
                                            } elseif ($url[3] === 'delete' && method_exists($this->controller, 'delete')) {
                                                $this->method = 'delete';
                                                $this->params = [$url[2]];
                                            } elseif ($url[3] === 'toggle' && method_exists($this->controller, 'toggleActive')) {
                                                $this->method = 'toggleActive';
                                                $this->params = [$url[2]];
                                            } elseif ($url[3] === 'duplicate' && method_exists($this->controller, 'duplicate')) {
                                                error_log("App.php: Routing to duplicate method for controller " . get_class($this->controller) . " with ID: " . $url[2]);
                                                $this->method = 'duplicate';
                                                $this->params = [$url[2]];
                                            } elseif ($url[3] === 'process' && method_exists($this->controller, 'process')) {
                                                $this->method = 'process';
                                                // Aceptar parámetro adicional para tipo de planilla: /payrolls/{id}/process/{tipo_planilla_id}
                                                if (isset($url[4])) {
                                                    $this->params = [$url[2], $url[4]]; // payroll_id, tipo_planilla_id
                                                } else {
                                                    $this->params = [$url[2]]; // solo payroll_id (compatibilidad)
                                                }
                                            } elseif ($url[3] === 'reprocess' && method_exists($this->controller, 'reprocess')) {
                                                $this->method = 'reprocess';
                                                // Aceptar parámetro adicional para tipo de planilla: /payrolls/{id}/reprocess/{tipo_planilla_id}
                                                if (isset($url[4])) {
                                                    $this->params = [$url[2], $url[4]]; // payroll_id, tipo_planilla_id
                                                } else {
                                                    $this->params = [$url[2]]; // solo payroll_id (compatibilidad)
                                                }
                                            } elseif ($url[3] === 'close' && method_exists($this->controller, 'close')) {
                                                $this->method = 'close';
                                                $this->params = [$url[2]];
                                            } elseif ($url[3] === 'toPending' && method_exists($this->controller, 'toPending')) {
                                                $this->method = 'toPending';
                                                $this->params = [$url[2]];
                                            } elseif ($url[3] === 'markPending' && method_exists($this->controller, 'markPending')) {
                                                $this->method = 'markPending';
                                                $this->params = [$url[2]];
                                            } elseif ($url[3] === 'reopen' && method_exists($this->controller, 'reopen')) {
                                                $this->method = 'reopen';
                                                $this->params = [$url[2]];
                                            } elseif ($url[3] === 'cancel' && method_exists($this->controller, 'cancel')) {
                                                $this->method = 'cancel';
                                                $this->params = [$url[2]];
                                            } elseif ($url[3] === 'progress' && method_exists($this->controller, 'progress')) {
                                                $this->method = 'progress';
                                                $this->params = [$url[2]];
                                            } elseif ($url[3] === 'regenerate-employee' && method_exists($this->controller, 'regenerateEmployee')) {
                                                $this->method = 'regenerateEmployee';
                                                $this->params = [$url[2]]; // payrollId
                                            } elseif ($url[3] === 'recalculate' && method_exists($this->controller, 'recalculate')) {
                                                $this->method = 'recalculate';
                                                $this->params = [$url[2]]; // termination_id
                                            } elseif ($url[3] === 'revertPayroll' && method_exists($this->controller, 'revertPayroll')) {
                                                $this->method = 'revertPayroll';
                                                $this->params = [$url[2]]; // termination_id
                                            } elseif ($url[3] === 'update-notice-days' && method_exists($this->controller, 'updateNoticeDays')) {
                                                $this->method = 'updateNoticeDays';
                                                $this->params = [$url[2]]; // termination_id
                                            }
                                        } else {
                                            // POST con solo ID (sin submétodo) - llamar a update
                                            if (method_exists($this->controller, 'update')) {
                                                $this->method = 'update';
                                                $this->params = [$url[2]];
                                            } else {
                                            }
                                        }
                                    } elseif ($url[2] === 'test-formula' && method_exists($this->controller, 'testFormula')) {
                                        $this->method = 'testFormula';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'validate-formula' && method_exists($this->controller, 'validateFormulaAjax')) {
                                        $this->method = 'validateFormulaAjax';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'toggle-status' && method_exists($this->controller, 'toggleStatus')) {
                                        $this->method = 'toggleStatus';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'getOptions' && method_exists($this->controller, 'getOptions')) {
                                        $this->method = 'getOptions';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'options' && method_exists($this->controller, 'getOptions')) {
                                        $this->method = 'getOptions';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'datatables-ajax' && method_exists($this->controller, 'datatablesAjax')) {
                                        $this->method = 'datatablesAjax';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'getRow' && method_exists($this->controller, 'getRow')) {
                                        $this->method = 'getRow';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'getNextCode' && method_exists($this->controller, 'getNextCode')) {
                                        $this->method = 'getNextCode';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'update-concept-value' && method_exists($this->controller, 'updateConceptValue')) {
                                        $this->method = 'updateConceptValue';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'add-employee-concept' && method_exists($this->controller, 'addEmployeeConcept')) {
                                        $this->method = 'addEmployeeConcept';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'remove-employee-concept' && method_exists($this->controller, 'removeEmployeeConcept')) {
                                        $this->method = 'removeEmployeeConcept';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'recalculate-employee' && method_exists($this->controller, 'recalculateEmployee')) {
                                        $this->method = 'recalculateEmployee';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'restore-calculated-value' && method_exists($this->controller, 'restoreCalculatedValue')) {
                                        $this->method = 'restoreCalculatedValue';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'count-by-type' && method_exists($this->controller, 'countByType')) {
                                        $this->method = 'countByType';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'by-type' && isset($url[3]) && method_exists($this->controller, 'byTypeId')) {
                                        $this->method = 'byTypeId';
                                        $this->params = [$url[3]];
                                    } elseif ($url[2] === 'calculate-period' && method_exists($this->controller, 'calculatePeriod')) {
                                        $this->method = 'calculatePeriod';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'api-config' && isset($url[3])) {
                                        // Rutas para AttendanceApiConfigController
                                        if ($url[3] === 'save' && method_exists($this->controller, 'save')) {
                                            $this->method = 'save';
                                            $this->params = [];
                                        } elseif ($url[3] === 'test-connection' && method_exists($this->controller, 'testConnection')) {
                                            $this->method = 'testConnection';
                                            $this->params = [];
                                        } elseif ($url[3] === 'sync-now' && method_exists($this->controller, 'syncNow')) {
                                            $this->method = 'syncNow';
                                            $this->params = [];
                                        } elseif ($url[3] === 'enable-sync' && method_exists($this->controller, 'enableSync')) {
                                            $this->method = 'enableSync';
                                            $this->params = [];
                                        } elseif ($url[3] === 'disable-sync' && method_exists($this->controller, 'disableSync')) {
                                            $this->method = 'disableSync';
                                            $this->params = [];
                                        } elseif ($url[3] === 'log-details' && method_exists($this->controller, 'getLogDetails')) {
                                            $this->method = 'getLogDetails';
                                            $this->params = [];
                                        } elseif ($url[3] === 'clean-logs' && method_exists($this->controller, 'cleanOldLogs')) {
                                            $this->method = 'cleanOldLogs';
                                            $this->params = [];
                                        }
                                    } elseif ($url[2] === 'upload-logo' && method_exists($this->controller, 'uploadLogo')) {
                                        $this->method = 'uploadLogo';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'delete-logo' && method_exists($this->controller, 'deleteLogo')) {
                                        $this->method = 'deleteLogo';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'update' && isset($url[3]) && method_exists($this->controller, 'update')) {
                                        $this->method = 'update';
                                        $this->params = [$url[3]];
                                    }elseif ($url[2] === 'delete' && isset($url[3]) && method_exists($this->controller, 'delete')) {
                                        $this->method = 'delete';
                                        $this->params = [$url[3]];
                                    }elseif ($url[2] === 'edit' && isset($url[3]) && method_exists($this->controller, 'edit')) {
                                        $this->method = 'edit';
                                        $this->params = [$url[3]];
                                    }elseif ($url[2] === 'duplicate' && isset($url[3]) && method_exists($this->controller, 'duplicate')) {
                                        $this->method = 'duplicate';
                                        $this->params = [$url[3]];
                                    } elseif ($url[2] === 'import' && method_exists($this->controller, 'import')) {
                                        $this->method = 'import';
                                        $this->params = array_slice($url, 3);
                                    }
                                } else {
                                    // Para GET, usar lógica normal
                                    if ($url[2] === 'employee-info' && method_exists($this->controller, 'employeeInfo')) {
                                        $this->method = 'employeeInfo';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'search-employees' && method_exists($this->controller, 'searchEmployees')) {
                                        $this->method = 'searchEmployees';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'options' && method_exists($this->controller, 'getOptions')) {
                                        $this->method = 'getOptions';
                                        $this->params = array_slice($url, 3);
                                    }elseif ($url[2] === 'check-duplicate' && method_exists($this->controller, 'checkDuplicate')) {
                                        $this->method = 'checkDuplicate';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'datatables-ajax' && method_exists($this->controller, 'datatablesAjax')) {
                                        $this->method = 'datatablesAjax';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'terminated' && method_exists($this->controller, 'terminated')) {
                                        $this->method = 'terminated';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'terminated-datatables-ajax' && method_exists($this->controller, 'terminated_datatables_ajax')) {
                                        $this->method = 'terminated_datatables_ajax';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'import' && method_exists($this->controller, 'import')) {
                                        $this->method = 'import';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'import_template' && method_exists($this->controller, 'import_template')) {
                                        $this->method = 'import_template';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'planilla-pdf' && isset($url[3]) && method_exists($this->controller, 'planillaPdf')) {
                                        $this->method = 'planillaPdf';
                                        $this->params = [$url[3]]; // payrollId
                                    } elseif ($url[2] === 'comprobantes-planilla' && isset($url[3]) && method_exists($this->controller, 'comprobantesPlanilla')) {
                                        $this->method = 'comprobantesPlanilla';
                                        $this->params = [$url[3]]; // payrollId
                                    } elseif ($url[2] === 'planilla-excel-panama' && isset($url[3]) && method_exists($this->controller, 'planillaExcelPanama')) {
                                        $this->method = 'planillaExcelPanama';
                                        $this->params = [$url[3]]; // payrollId
                                    } elseif ($url[2] === 'informe03' && method_exists($this->controller, 'informe03')) {
                                        $this->method = 'informe03';
                                        $this->params = isset($url[3]) ? [$url[3]] : [null]; // payrollId opcional
                                    } elseif ($url[2] === 'test-comprobantes' && method_exists($this->controller, 'testComprobantes')) {
                                        $this->method = 'testComprobantes';
                                        $this->params = isset($url[3]) ? [$url[3]] : [null]; // payrollId opcional
                                    } elseif ($url[2] === 'reporte-acreedores' && method_exists($this->controller, 'reporteAcreedores')) {
                                        $this->method = 'reporteAcreedores';
                                        $this->params = isset($url[3]) ? [$url[3]] : [null]; // payrollId opcional
                                    } elseif ($url[2] === 'exports' && method_exists($this->controller, 'exports')) {
                                        $this->method = 'exports';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'export' && isset($url[3])) {
                                        // Rutas de exportación: /panel/reports/export/employees
                                        if ($url[3] === 'employees' && method_exists($this->controller, 'exportEmployees')) {
                                            $this->method = 'exportEmployees';
                                            $this->params = array_slice($url, 4);
                                        } elseif ($url[3] === 'creditors' && method_exists($this->controller, 'exportCreditors')) {
                                            $this->method = 'exportCreditors';
                                            $this->params = array_slice($url, 4);
                                        } elseif ($url[3] === 'concepts' && method_exists($this->controller, 'exportConcepts')) {
                                            $this->method = 'exportConcepts';
                                            $this->params = array_slice($url, 4);
                                        }
                                    } elseif ($url[2] === 'acumulados-empleado-pdf' && isset($url[3]) && method_exists($this->controller, 'acumuladosEmpleadoPdf')) {
                                        // Ruta: /panel/reports/acumulados-empleado-pdf/123
                                        $this->method = 'acumuladosEmpleadoPdf';
                                        $this->params = [$url[3]]; // empleadoId
                                    } elseif ($url[2] === 'acumulados-tipo-pdf' && isset($url[3]) && method_exists($this->controller, 'acumuladosTipoPdf')) {
                                        // Ruta: /panel/reports/acumulados-tipo-pdf/456
                                        $this->method = 'acumuladosTipoPdf';
                                        $this->params = [$url[3]]; // tipoId
                                    } elseif ($url[2] === 'acumulados-general-pdf' && method_exists($this->controller, 'acumuladosGeneralPdf')) {
                                        // Ruta: /panel/reports/acumulados-general-pdf
                                        $this->method = 'acumuladosGeneralPdf';
                                        $this->params = [];
                                    } elseif ($url[2] === 'comprobantes-planilla-horizontal' && isset($url[3]) && method_exists($this->controller, 'comprobantesPlanillaHorizontal')) {
                                        // Ruta: /panel/reports/comprobantes-planilla-horizontal/{payrollId}
                                        $this->method = 'comprobantesPlanillaHorizontal';
                                        $this->params = [$url[3]]; // payrollId
                                    } elseif ($url[2] === 'comprobante-individual' && isset($url[3]) && isset($url[4]) && method_exists($this->controller, 'comprobanteIndividual')) {
                                        // Ruta: /panel/reports/comprobante-individual/{payrollId}/{employeeId}
                                        $this->method = 'comprobanteIndividual';
                                        $this->params = [$url[3], $url[4]]; // payrollId, employeeId
                                    } elseif ($url[2] === 'enviar-comprobante-email' && method_exists($this->controller, 'enviarComprobanteEmailAjax')) {
                                        // Ruta: /panel/reports/enviar-comprobante-email (POST AJAX)
                                        $this->method = 'enviarComprobanteEmailAjax';
                                        $this->params = [];
                                    } elseif ($url[2] === 'cancel-form' && isset($url[3]) && method_exists($this->controller, 'cancelForm')) {
                                        // Ruta: /panel/liquidation/cancel-form/{id}
                                        $this->method = 'cancelForm';
                                        $this->params = [$url[3]]; // termination_id
                                    } elseif ($url[2] === 'create' && method_exists($this->controller, 'create')) {
                                        $this->method = 'create';
                                        $this->params = array_slice($url, 3);
                                    } elseif ($url[2] === 'payroll-detail' && isset($url[3]) && method_exists($this->controller, 'payrollDetail')) {
                                        $this->method = 'payrollDetail';
                                        $this->params = [$url[3]];
                                    } elseif ($url[2] === 'edit' && isset($url[3]) && method_exists($this->controller, 'edit')) {
                                        $this->method = 'edit';
                                        $this->params = [$url[3]];
                                    } elseif (method_exists($this->controller, $url[2])) {
                                        $this->method = $url[2];
                                        $this->params = array_slice($url, 3);
                                    } elseif (is_numeric($url[2])) {
                                        // Si es un ID, verificar si hay submétodo
                                        if (isset($url[3])) {
                                            if ($url[3] === 'employee' && isset($url[4]) && method_exists($this->controller, 'showDetail')) {
                                                // Ruta: /panel/payrolls/13/employee/5
                                                $this->method = 'showDetail';
                                                $this->params = [$url[2], $url[4]]; // payrollId, employeeId
                                            } elseif ($url[3] === 'employees-data' && method_exists($this->controller, 'getEmployeesData')) {
                                                // Ruta: /panel/payrolls/13/employees-data (AJAX DataTables)
                                                $this->method = 'getEmployeesData';
                                                $this->params = [$url[2]]; // payrollId
                                            } elseif ($url[3] === 'employees-list-simple' && method_exists($this->controller, 'getEmployeesListSimple')) {
                                                // Ruta: /panel/payrolls/13/employees-list-simple (Simple JSON list)
                                                $this->method = 'getEmployeesListSimple';
                                                $this->params = [$url[2]]; // payrollId
                                            } elseif ($url[3] === 'acumulados' && method_exists($this->controller, 'getAcumulados')) {
                                                $this->method = 'getAcumulados';
                                                $this->params = [$url[2]];
                                            } elseif (method_exists($this->controller, $url[3])) {
                                                $this->method = $url[3];
                                                $this->params = [$url[2]];
                                            }
                                        } elseif (method_exists($this->controller, 'show')) {
                                            $this->method = 'show';
                                            $this->params = [$url[2]];
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $this->show404();
                        return;
                    }
                } else {
                    $this->show404();
                    return;
                }
            } else {
                // Si solo viene /panel/, redirigir al login
                $this->controller = new \App\Controllers\Admin();
                $this->method = 'index';
                $this->params = [];
            }
        } else {
            // Manejo estándar para otras rutas
            if (empty($url[0])) {
                $url[0] = 'admin';
            }

            // Manejo especial para ruta /marcaciones
            if ($url[0] === 'marcaciones') {
                $this->controller = new \App\Controllers\Timeclock();
                $this->method = 'index';
                $this->params = [];
                call_user_func_array([$this->controller, $this->method], $this->params);
                return;
            }

            $controllerName = 'App\\Controllers\\' . ucfirst($url[0]);
            
            if (class_exists($controllerName)) {
                $this->controller = new $controllerName();
                unset($url[0]);
            } else {
                $this->show404();
                return;
            }
            
            if (isset($url[1])) {
                if (method_exists($this->controller, $url[1])) {
                    $this->method = $url[1];
                    unset($url[1]);
                } else {
                    $this->show404();
                    return;
                }
            }
            
            $this->params = $url ? array_values($url) : [];
        }
        
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    /**
     * Verificar permisos de ruta usando middleware
     */
    private function checkRoutePermissions($route)
    {
        try {
            // Determinar tipo de permiso según método HTTP y acción
            $httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $permissionType = $this->getPermissionType($route, $httpMethod);
            
            // Verificar permiso con bypass para super admin
            PermissionMiddleware::requirePermissionWithAdminBypass($route, $permissionType);
        } catch (\Exception $e) {
            error_log("Error checking route permissions: " . $e->getMessage());
            // En caso de error, permitir acceso (fallback seguro)
        }
    }

    /**
     * Determinar tipo de permiso necesario según ruta y método HTTP
     */
    private function getPermissionType($route, $httpMethod)
    {
        // Para DELETE explícito
        if ($httpMethod === 'DELETE' || strpos($route, '/delete') !== false) {
            return 'delete';
        }

        // Para POST/PUT (crear/actualizar)
        if ($httpMethod === 'POST' || $httpMethod === 'PUT' || 
            strpos($route, '/create') !== false || 
            strpos($route, '/edit') !== false ||
            strpos($route, '/update') !== false ||
            strpos($route, '/store') !== false) {
            return 'write';
        }

        // Por defecto, read
        return 'read';
    }

    public function parseUrl()
    {
        if (isset($_GET['url'])) {
            $url = explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
            
            // ✅ NORMALIZACIÓN: Filtrar elementos vacíos para compatibilidad local/producción
            // Local: Array([0] => panel) 
            // Producción: Array([0] => '', [1] => panel)
            // Resultado normalizado: Array([0] => panel)
            $url = array_values(array_filter($url, function($segment) {
                return $segment !== '' && $segment !== null;
            }));
            
            return $url;
        }
        return [];
    }

    private function show404()
    {
        http_response_code(404);
        $url = $this->parseUrl();

        // Preparar datos para la vista
        $data = [
            'url' => $url,
            'controller' => $this->controller,
            'method' => $this->method
        ];

        // Mostrar controladores disponibles en modo debug
        if (isset($_GET['debug'])) {
            $controllers = glob(__DIR__ . '/../Controllers/*.php');
            $availableControllers = [];
            foreach ($controllers as $controller) {
                $availableControllers[] = basename($controller, '.php');
            }
            $data['availableControllers'] = $availableControllers;
        }

        // Extraer variables para la vista
        extract($data);

        // Incluir la vista 404
        $viewPath = __DIR__ . '/../Views/errors/404.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            // Fallback simple si no existe la vista
            echo "<h1>404 - Página no encontrada</h1>";
            echo "<p>URL solicitada: " . implode('/', $url) . "</p>";
        }
        exit;
    }
    
    /**
     * Manejar rutas API
     */
    private function handleApiRoutes($url)
    {
        if (isset($url[1])) {
            $apiRoutes = [
                'payroll-types' => ['controller' => 'ApiController', 'method' => 'getPayrollTypes']
            ];
            
            if (isset($apiRoutes[$url[1]])) {
                $mapping = $apiRoutes[$url[1]];
                $controllerName = 'App\\Controllers\\' . $mapping['controller'];
                
                if (class_exists($controllerName)) {
                    $controller = new $controllerName();
                    $method = $mapping['method'];
                    
                    if (method_exists($controller, $method)) {
                        $params = array_slice($url, 2);
                        call_user_func_array([$controller, $method], $params);
                        return;
                    }
                }
            }
        }
        
        // Si no se encuentra la ruta API, retornar error JSON
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'API endpoint not found']);
    }

    /**
     * Manejar rutas del wizard multitenancy (sin autenticación)
     * Rutas: /setup/wizard, /setup/wizard/validate-admin, etc.
     */
    protected function handleWizardRoutes($url)
    {
        try {
            if (!isset($url[1]) || $url[1] !== 'wizard') {
                http_response_code(404);
                echo 'Wizard route not found';
                return;
            }

            // Incluir el controlador wizard
            require_once __DIR__ . '/../Controllers/WizardController.php';
            $wizardController = new \WizardController();

            // Manejar diferentes acciones del wizard
            $action = $url[2] ?? 'index';

            switch ($action) {
                case 'index':
                case '':
                    // GET /setup/wizard - Mostrar wizard
                    $wizardController->index();
                    break;

                case 'validate-admin':
                    // POST /setup/wizard/validate-admin (legacy compatibility)
                    $wizardController->validateAdmin();
                    break;

                case 'validate-distributor':
                    // POST /setup/wizard/validate-distributor
                    $wizardController->validateDistributor();
                    break;

                case 'register-company':
                    // POST /setup/wizard/register-company
                    $wizardController->registerCompany();
                    break;

                case 'create-company':
                    // POST /setup/wizard/create-company
                    $wizardController->createCompany();
                    break;

                case 'progress':
                    // GET /setup/wizard/progress
                    $wizardController->getProgress();
                    break;

                case 'reset':
                    // POST /setup/wizard/reset
                    $wizardController->resetWizard();
                    break;

                default:
                    http_response_code(404);
                    echo 'Wizard action not found';
                    break;
            }

        } catch (Exception $e) {
            error_log("Error in wizard routes: " . $e->getMessage());
            http_response_code(500);
            echo 'Internal server error';
        }
    }

    /**
     * Manejar ruta crear-empresa (Wizard Vuetify)
     * Ruta: /crear-empresa
     */
    protected function handleCrearEmpresaRoute()
    {
        try {
            // Incluir el controlador wizard
            require_once __DIR__ . '/../Controllers/WizardController.php';
            $wizardController = new \WizardController();

            // Solo maneja GET para mostrar el formulario
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $wizardController->crearEmpresa();
            } else {
                // Para POST, redirigir a las rutas de wizard normales
                http_response_code(405);
                echo json_encode([
                    'success' => false,
                    'message' => 'Use las rutas /setup/wizard/* para procesar formularios'
                ]);
            }

        } catch (Exception $e) {
            error_log("Error in crear-empresa route: " . $e->getMessage());
            http_response_code(500);
            echo 'Internal server error';
        }
    }

    /**
     * Manejo de rutas de webhooks
     * Ruta: /webhooks/{provider}/{entity}
     * Ejemplo: /webhooks/base44/attendance
     */
    private function handleWebhookRoutes($url)
    {
        try {
            // Validar que tengamos provider y entity
            if (!isset($url[1]) || !isset($url[2])) {
                http_response_code(404);
                echo json_encode(['error' => 'Ruta de webhook inválida']);
                return;
            }

            $provider = $url[1]; // base44
            $entity = $url[2];   // attendance, employee, etc.

            // Mapeo de providers a controladores
            $providerMapping = [
                'base44' => 'Base44WebhookController'
            ];

            if (!isset($providerMapping[$provider])) {
                http_response_code(404);
                echo json_encode(['error' => 'Proveedor no soportado']);
                return;
            }

            // Cargar controlador de webhook
            $controllerClass = '\\App\\Controllers\\Webhooks\\' . $providerMapping[$provider];

            if (!class_exists($controllerClass)) {
                http_response_code(500);
                echo json_encode(['error' => 'Controlador de webhook no encontrado']);
                return;
            }

            $this->controller = new $controllerClass();
            $this->method = 'receive'; // Método estándar para todos los webhooks
            $this->params = [$entity];

            // Ejecutar método
            call_user_func_array([$this->controller, $this->method], $this->params);

        } catch (Exception $e) {
            error_log("Error in webhook route: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }
}