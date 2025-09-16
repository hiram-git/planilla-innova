<?php
$styles = '
<style>
    body {
        background-image: url("assets/images/portada.jpg");
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        height: 100vh;
        overflow: hidden;
    }
    .login-box {
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
        padding: 20px;
    }
</style>';

$content = '
<div class="login-box">
    <div class="login-logo">
        <p id="date"></p>
        <p id="time" class="bold"></p>
    </div>

    <div class="login-box-body">
        <h4 class="login-box-msg">Ingrese ID de Empleado</h4>

        <form id="attendance">
            <input type="hidden" name="csrf_token" value="' . $csrf_token . '">
            <div class="form-group">
                <select class="form-control" name="status">
                    <option value="in">Entrada</option>
                    <option value="out">Salida</option>
                </select>
            </div>
            <div class="form-group has-feedback">
                <input type="text" class="form-control input-lg" id="employee" name="employee" required>
                <span class="fa fa-calendar form-control-feedback"></span>
            </div>
            <div class="row">
                <div class="col-xs-4">
                    <button type="submit" class="btn btn-primary btn-block btn-flat" name="signin">
                        <i class="fa fa-sign-in-alt"></i> Registrar
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="alert alert-success alert-dismissible mt20 text-center" style="display:none;">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <span class="result"><i class="icon fa fa-check"></i> <span class="message"></span></span>
    </div>
    
    <div class="alert alert-danger alert-dismissible mt20 text-center" style="display:none;">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <span class="result"><i class="icon fa fa-warning"></i> <span class="message"></span></span>
    </div>
</div>';

$scripts = '
<script type="text/javascript">
$(function() {
    var interval = setInterval(function() {
        var momentNow = moment();
        momentNow.locale("es");
        $("#date").html(momentNow.format("dddd").substring(0, 3).toUpperCase() + " - " + momentNow.format("DD [de] MMMM [de] YYYY"));
        $("#time").html(momentNow.format("hh:mm:ss A"));
    }, 100);

    $("#attendance").submit(function(e){
        e.preventDefault();
        var attendance = $(this).serialize();
        $.ajax({
            type: "POST",
            url: "home/attendance",
            data: attendance,
            dataType: "json",
            success: function(response){
                if(response.error){
                    $(".alert").hide();
                    $(".alert-danger").show();
                    $(".message").html(response.message);
                } else {
                    $(".alert").hide();
                    $(".alert-success").show();
                    $(".message").html(response.message);
                    $("#employee").val("");
                }
            },
            error: function() {
                $(".alert").hide();
                $(".alert-danger").show();
                $(".message").html("Error de conexión");
            }
        });
    });
});
</script>';

include './app/Views/layouts/main.php';
?>