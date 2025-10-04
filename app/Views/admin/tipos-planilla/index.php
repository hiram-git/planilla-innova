<?php
// Vista para listado de tipos de planilla
// Reutiliza el template base de referencia
include __DIR__ . '/../templates/reference_index.php';
?>

<!-- Script adicional para actualizar sessionStorage cuando se edita un tipo de planilla -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php
    // Verificar si hay un tipo de planilla que actualizar en sessionStorage
    if (isset($_SESSION['update_session_storage']) &&
        isset($_SESSION['update_session_storage']['tipo_planilla_id'])):
        $updateData = $_SESSION['update_session_storage'];
        $tipoPlanillaId = $updateData['tipo_planilla_id'];
        $tipoPlanillaNombre = $updateData['tipo_planilla_nombre'];
        // Limpiar la sesión después de leer
        unset($_SESSION['update_session_storage']);
    ?>

    // Actualizar sessionStorage si el tipo de planilla editado es el seleccionado actualmente
    const selectedPayrollType = sessionStorage.getItem("selectedPayrollType");

    if (selectedPayrollType) {
        try {
            const payrollTypeData = JSON.parse(selectedPayrollType);

            // Si el tipo editado es el que está seleccionado, actualizar el nombre
            if (payrollTypeData.id == <?= $tipoPlanillaId ?>) {
                const updatedData = {
                    id: payrollTypeData.id,
                    name: "<?= addslashes($tipoPlanillaNombre) ?>"
                };

                sessionStorage.setItem("selectedPayrollType", JSON.stringify(updatedData));

                // Actualizar también el texto en el navbar si existe
                const navbarElement = document.getElementById("selectedPayrollType");
                if (navbarElement) {
                    navbarElement.textContent = updatedData.name;
                }

                console.log("SessionStorage actualizado para tipo de planilla:", updatedData);

                // Mostrar notificación con toastr
                if (typeof toastr !== 'undefined') {
                    toastr.info("El filtro de tipo de planilla se actualizó automáticamente", "Información");
                }
            }
        } catch (e) {
            console.error("Error actualizando sessionStorage:", e);
        }
    }

    <?php endif; ?>
});
</script>