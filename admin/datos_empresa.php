<?php include 'includes/session.php';
// Fetch company data if exists
$company = null;
$result = $conn->query("SELECT * FROM companies WHERE id=1");
if ($result->num_rows > 0) {
    $company = $result->fetch_assoc();
}
$conn->close();
?>
<?php include 'includes/header.php'; ?>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <?php include 'includes/navbar.php'; ?>
  <?php include 'includes/menubar.php'; ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
     
    <section class="content-header">      
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Empresa</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#"><i class="fa fa-dashboard"></i> Inicio</a></li>
              <li class="breadcrumb-item active">Datos de la Organización</li>
            </ol>
          </div>
        </div>
      </div>
    </section>
    <!-- Main content -->
    <section class="content">      
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo $company ? 'Editar Empresa' : 'Registrar Empresa'; ?></h3>
                        </div>
                        <form id="companyForm" class="form-horizontal">
                            <div class="card-body">
                                <input type="hidden" id="action" name="action" value="save">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label for="company_name" class="col-sm-4 col-form-label">Nombre de la Empresa</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control" id="company_name" name="company_name" placeholder="Ingrese el nombre de la empresa" value="<?php echo $company ? htmlspecialchars($company['company_name']) : ''; ?>" required>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="ruc" class="col-sm-4 col-form-label">RUC</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control" id="ruc" name="ruc" placeholder="Ingrese el RUC" value="<?php echo $company ? htmlspecialchars($company['ruc']) : ''; ?>" required>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="legal_representative" class="col-sm-4 col-form-label">Representante Legal</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control" id="legal_representative" name="legal_representative" placeholder="Ingrese el nombre del representante" value="<?php echo $company ? htmlspecialchars($company['legal_representative']) : ''; ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label for="address" class="col-sm-4 col-form-label">Dirección</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control" id="address" name="address" placeholder="Ingrese la dirección" value="<?php echo $company ? htmlspecialchars($company['address']) : ''; ?>" required>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="phone" class="col-sm-4 col-form-label">Teléfono</label>
                                            <div class="col-sm-8">
                                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="Ingrese el número de teléfono" value="<?php echo $company ? htmlspecialchars($company['phone']) : ''; ?>" required>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="email" class="col-sm-4 col-form-label">Correo Electrónico</label>
                                            <div class="col-sm-8">
                                                <input type="email" class="form-control" id="email" name="email" placeholder="Ingrese el correo electrónico" value="<?php echo $company ? htmlspecialchars($company['email']) : ''; ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary btn-flat float-right"><i class="fas fa-save"></i> Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>   
  </div>
    
  <?php include 'includes/footer.php'; ?>
</div>
<?php include 'includes/scripts.php'; ?>
<script>
$(document).ready(function() {
    // Verify SweetAlert2 is loaded
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 no está cargado. Verifica la URL del CDN o la conexión de red.');
        alert('Error: No se pudo cargar SweetAlert2. Por favor, revisa tu conexión o el CDN.');
        return;
    }

    // Client-side form validation
    $('#companyForm').submit(function(e) {
        e.preventDefault();

        // Get form values
        let company_name = $('#company_name').val().trim();
        let ruc = $('#ruc').val().trim();
        let legal_representative = $('#legal_representative').val().trim();
        let address = $('#address').val().trim();
        let phone = $('#phone').val().trim();
        let email = $('#email').val().trim();

        // Validation patterns
        let namePattern = /^[a-zA-Z0-9\s.,&-]{3,255}$/;
        let rucPattern = /^[A-Z0-9-]{6,20}$/;
        let representativePattern = /^[a-zA-Z\sáéíóúÁÉÍÓÚñÑ]{3,255}$/;
        let addressPattern = /^[a-zA-Z0-9\s,.-]{5,255}$/;
        let phonePattern = /^\+?507[0-9]{7,9}$|^[0-9]{4}-?[0-9]{4}$/;
        let emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

        // Validate fields
        let errors = [];
        if (!namePattern.test(company_name)) {
            errors.push('El nombre de la empresa debe tener entre 3 y 255 caracteres y solo puede incluir letras, números, espacios, puntos, comas, & y -.');
        }
        if (!rucPattern.test(ruc)) {
            errors.push('El RUC debe tener entre 6 y 20 caracteres y seguir un formato válido (Ej. 123456-1-123456 o AV-123456-123456).');
        }
        if (!representativePattern.test(legal_representative)) {
            errors.push('El representante legal debe tener entre 3 y 255 caracteres y solo puede incluir letras, espacios y tildes.');
        }
        if (!addressPattern.test(address)) {
            errors.push('La dirección debe tener entre 5 y 255 caracteres y solo puede incluir letras, números, espacios, comas, puntos y guiones.');
        }
        if (!phonePattern.test(phone)) {
            errors.push('El teléfono debe seguir un formato válido de Panamá (Ej. +50712345678 o 1234-5678).');
        }
        if (!emailPattern.test(email) || email.length > 100) {
            errors.push('El correo electrónico debe ser válido (Ej. nombre@dominio.com) y no exceder los 100 caracteres.');
        }

        // Show errors if any
        if (errors.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Errores de Validación',
                html: errors.join('<br>'),
                confirmButtonText: 'Aceptar'
            });
            return;
        }

        // Submit form via AJAX
        $.ajax({
            url: 'company_process.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                Swal.fire({
                    icon: response.status,
                    title: response.status === 'success' ? 'Éxito' : 'Error',
                    text: response.message,
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    if (response.status === 'success') {
                        location.reload();
                    }
                });
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error en la solicitud AJAX: ' + error,
                    confirmButtonText: 'Aceptar'
                });
            }
        });
    });
});
</script>
</body>
</html>
