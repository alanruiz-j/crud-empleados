<?php
include 'modelo/conexion.php';
require_once 'modelo/helpers_ui.php';
require_once 'modelo/repositorio.php';

// Iniciar sesión para manejar errores
session_start();
// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    // Lo redirige a la página de login.
    header('Location: login.php');
    // Detiene la ejecución del resto de la página.
    exit;
}

$edit_mode = false;
$empleado_data = [];
$page_title = "Agregar empleado";
$form_action = "controlador/registro_empleados.php";

// Variables para mantener datos en caso de error
$form_data = [];
$errores = [];

// Recuperar datos del formulario si hubo error
if (isset($_SESSION['form_data'])) {
    $form_data = $_SESSION['form_data'];
    unset($_SESSION['form_data']);
}

if (isset($_SESSION['errores'])) {
    $errores = $_SESSION['errores'];
    unset($_SESSION['errores']);
}

// Si se recibe un ID por la URL, activamos el modo de edición
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $edit_mode = true;
    $page_title = "Editar Empleado #" . intval($_GET['id']);
    $form_action = "controlador/actualizar_empleado.php";
    $id_empleado = intval($_GET['id']);

    // Consulta robusta para obtener todos los datos necesarios del empleado
    $sql = $conn->prepare("
        SELECT
            e.*,
            d.CALLE, d.NUMERO_EXTERIOR, d.NUMERO_INTERIOR, d.COLONIA, d.ID_MUNICIPIO,
            p.ID_PAIS,
            est.ID_ESTADO,
            (SELECT c.CORREO_EMPLEADO FROM correos c WHERE c.ID_EMPLEADO = e.ID_EMPLEADO AND c.TIPO_CORREO = 'principal' LIMIT 1) as correo_principal,
            (SELECT c.CORREO_EMPLEADO FROM correos c WHERE c.ID_EMPLEADO = e.ID_EMPLEADO AND c.TIPO_CORREO = 'secundario' LIMIT 1) as correo_secundario
        FROM empleados e
        LEFT JOIN domicilios d ON e.ID_EMPLEADO = d.ID_EMPLEADO
        LEFT JOIN municipios m ON d.ID_MUNICIPIO = m.ID_MUNICIPIO
        LEFT JOIN estados est ON m.ID_ESTADO = est.ID_ESTADO
        LEFT JOIN paises p ON est.ID_PAIS = p.ID_PAIS
        WHERE e.ID_EMPLEADO = ?
    ");
    $sql->bind_param("i", $id_empleado);
    $sql->execute();
    $result = $sql->get_result();
    if ($result->num_rows > 0) {
        $empleado_data = $result->fetch_assoc();
    } else {
        echo "<script>alert('No se encontró ningún empleado con el ID: " . $id_empleado . "'); window.location.href='index.php';</script>";
        exit;
    }
}

// Wrappers para helpers UI y catálogo, para no cambiar el resto del template
function get_value($form_field_name, $db_field_name = null, $default = '') { return ui_get_value($form_field_name, $db_field_name, $default); }
function hasError($field) { return ui_has_error($field); }
function hasSuccess($field) { return ui_has_success($field); }
function showError($field) { return ui_show_error($field); }
function getCatalogData($conn, $table, $id_field, $name_field, $where = '') { return get_catalog($conn, $table, $id_field, $name_field, $where); }
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="user-solid-full.ico">
    <style>
        .invalid-feedback {
            display: block;
        }
        .was-validated .form-control:invalid, 
        .form-control.is-invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6.4.4.4-.4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        .was-validated .form-control:valid,
        .form-control.is-valid {
            border-color: #198754;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        .was-validated .form-select:invalid,
        .form-select.is-invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6.4.4.4-.4'/%3e%3c/svg%3e"), url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-position: right calc(0.375em + 0.1875rem) center, right 0.75rem center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem), 16px 12px;
        }
        .was-validated .form-select:valid,
        .form-select.is-valid {
            border-color: #198754;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e"), url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-position: right calc(0.375em + 0.1875rem) center, right 0.75rem center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem), 16px 12px;
        }
        .select-placeholder {
            color: #6c757d;
        }
        .form-label .text-danger {
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h1 class="h4 mb-0"><?= htmlspecialchars($page_title) ?></h1>

                        <form action="index.php" method="GET" class="d-flex">
                            <input class="form-control me-2" type="search" placeholder="Buscar empleado por ID" aria-label="Buscar" name="id" required>
                            <button class="btn btn-light" type="submit">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                        </form>

                    </div>
                    <div class="card-body">
                        <?php if (!empty($errores)): ?>
                            <div class="alert alert-danger">
                                <strong>Errores encontrados:</strong> Por favor corrige los siguientes problemas:
                                <ul class="mb-0 mt-1">
                                    <?php foreach ($errores as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (!$edit_mode): ?>
                            <div class="alert alert-info">Registrar un nuevo empleado. Los campos marcados con <span class="text-danger">*</span> son obligatorios.</div>
                        <?php else: ?>
                            <div class="alert alert-warning">Editando empleado #<?= htmlspecialchars($empleado_data['ID_EMPLEADO'] ?? '') ?>. Los campos marcados con <span class="text-danger">*</span> son obligatorios.</div>
                        <?php endif; ?>

                        <form id="employeeForm" class="needs-validation" novalidate method="POST" action="<?= $form_action ?>">

                            <?php if ($edit_mode): ?>
                                <input type="hidden" name="id_empleado" value="<?= htmlspecialchars($empleado_data['ID_EMPLEADO'] ?? '') ?>">

                                <input type="hidden" name="contratante" value="<?= htmlspecialchars($empleado_data['CONTRATANTE'] ?? '') ?>">
                            <?php endif; ?>

                            <?php if ($edit_mode): ?>
                                <h2 class="h5 border-bottom pb-2 mb-3">Detalles del Empleado</h2>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">ID de Empleado</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($empleado_data['ID_EMPLEADO'] ?? '') ?>" readonly style="background-color: #e9ecef;">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="fecha_contratacion" class="form-label">Fecha de Contratación</label>
                                        <input type="date" class="form-control" id="fecha_contratacion" name="fecha_contratacion_empleado" value="<?= get_value('fecha_contratacion_empleado', 'FECHA_CONTRATACION') ?>">
                                    </div>
                                </div>
                                <hr class="my-4">
                            <?php endif; ?>

                            <h2 class="h5 border-bottom pb-2 mb-3">Información Personal</h2>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="nombre" class="form-label">Nombre(s) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control <?= hasError('nombre_empleado') ?> <?= hasSuccess('nombre_empleado') ?>" id="nombre" required name="nombre_empleado" value="<?= get_value('nombre_empleado', 'NOMBRE_EMPLEADO') ?>" maxlength="255">
                                    <?= showError('nombre_empleado') ?>
                                </div>
                                <div class="col-md-4">
                                    <label for="apellidoPaterno" class="form-label">Apellido Paterno</label>
                                    <input type="text" class="form-control <?= hasError('apellido_paterno_empleado') ?> <?= hasSuccess('apellido_paterno_empleado') ?>" id="apellidoPaterno" name="apellido_paterno_empleado" value="<?= get_value('apellido_paterno_empleado', 'APELLIDO_PATERNO') ?>" maxlength="255">
                                    <?= showError('apellido_paterno_empleado') ?>
                                </div>
                                <div class="col-md-4">
                                    <label for="apellidoMaterno" class="form-label">Apellido Materno</label>
                                    <input type="text" class="form-control <?= hasError('apellido_materno_empleado') ?> <?= hasSuccess('apellido_materno_empleado') ?>" id="apellidoMaterno" name="apellido_materno_empleado" value="<?= get_value('apellido_materno_empleado', 'APELLIDO_MATERNO') ?>" maxlength="255">
                                    <?= showError('apellido_materno_empleado') ?>
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-4">
                                    <label for="genero" class="form-label">Género <span class="text-danger">*</span></label>
                                    <select class="form-select <?= hasError('genero_empleado') ?> <?= hasSuccess('genero_empleado') ?>" id="genero" required name="genero_empleado">
                                        <option selected disabled value="" class="select-placeholder">Seleccione...</option>
                                        <?php
                                        $generos = getCatalogData($conn, 'generos', 'ID_GENERO', 'NOMBRE_GENERO', 'WHERE ESTADO_GENERO = 1');
                                        $selected_genero = get_value('genero_empleado', 'ID_GENERO');
                                        foreach ($generos as $genero) {
                                            $selected = ($selected_genero == $genero['ID_GENERO']) ? 'selected' : '';
                                            echo "<option value='" . $genero['ID_GENERO'] . "' $selected>" . htmlspecialchars($genero['NOMBRE_GENERO']) . "</option>";
                                        }
                                        if (empty($generos)) {
                                            echo '<option value="" disabled>No hay géneros disponibles</option>';
                                        }
                                        ?>
                                    </select>
                                    <?= showError('genero_empleado') ?>
                                </div>
                                <div class="col-md-4">
                                    <label for="curp" class="form-label">CURP <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control <?= hasError('curp_empleado') ?>" id="curp" required name="curp_empleado" value="<?= get_value('curp_empleado', 'CURP_EMPLEADO') ?>" minlength="18" maxlength="18" pattern="[A-Z][AEIOUX][A-Z]{2}[0-9]{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12][0-9]|3[01])[HM](?:AS|B[CS]|C[CLMSH]|D[FG]|G[TR]|HG|JC|M[CNS]|N[ETL]|OC|PL|Q[TR]|S[PLR]|T[CSL]|VZ|YN|ZS)[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9]{2}" title="Introduce un CURP válido (18 caracteres)">
                                    <?= showError('curp_empleado') ?>
                                </div>
                                <div class="col-md-4">
                                    <label for="rfc" class="form-label">RFC <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control <?= hasError('rfc_empleado') ?>" id="rfc" required name="rfc_empleado" value="<?= get_value('rfc_empleado', 'RFC_EMPLEADO') ?>" minlength="10" maxlength="13" pattern="[A-ZÑ&]{3,4}[0-9]{6}([A-Z0-9]{3})?" title="Introduce un RFC válido (10 a 13 caracteres)">
                                    <?= showError('rfc_empleado') ?>
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-4">
                                    <label for="departamento" class="form-label">Departamento <span class="text-danger">*</span></label>
                                    <select class="form-select <?= hasError('departamento_empleado') ?> <?= hasSuccess('departamento_empleado') ?>" id="departamento" name="departamento_empleado" required>
                                        <option selected disabled value="" class="select-placeholder">Seleccione...</option>
                                        <?php
                                        $departamentos = getCatalogData($conn, 'departamentos', 'ID_DEPARTAMENTO', 'NOMBRE_DEPARTAMENTO', 'WHERE ESTADO_DEPARTAMENTO = 1');
                                        $selected_dept = get_value('departamento_empleado', 'ID_DEPARTAMENTO');
                                        foreach ($departamentos as $dept) {
                                            $selected = ($selected_dept == $dept['ID_DEPARTAMENTO']) ? 'selected' : '';
                                            echo "<option value='" . $dept['ID_DEPARTAMENTO'] . "' $selected>" . htmlspecialchars($dept['NOMBRE_DEPARTAMENTO']) . "</option>";
                                        }
                                        if (empty($departamentos)) {
                                            echo '<option value="" disabled>No hay departamentos disponibles</option>';
                                        }
                                        ?>
                                    </select>
                                    <?= showError('departamento_empleado') ?>
                                </div>
                                <div class="col-md-4">
                                    <label for="rol" class="form-label">Rol <span class="text-danger">*</span></label>
                                    <select class="form-select <?= hasError('rol_empleado') ?> <?= hasSuccess('rol_empleado') ?>" id="rol" name="rol_empleado" required>
                                        <option selected disabled value="" class="select-placeholder">Seleccione...</option>
                                        <?php
                                        $roles = getCatalogData($conn, 'roles', 'ID_ROL', 'NOMBRE_ROL', '');
                                        $selected_rol = get_value('rol_empleado', 'ID_ROL');
                                        foreach ($roles as $rol) {
                                            $selected = ($selected_rol == $rol['ID_ROL']) ? 'selected' : '';
                                            echo "<option value='" . $rol['ID_ROL'] . "' $selected>" . htmlspecialchars($rol['NOMBRE_ROL']) . "</option>";
                                        }
                                        if (empty($roles)) {
                                            echo '<option value="" disabled>No hay roles disponibles</option>';
                                        }
                                        ?>
                                    </select>
                                    <?= showError('rol_empleado') ?>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h2 class="h5 border-bottom pb-2 mb-3">Datos de Contacto</h2>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="telefono" class="form-label">Teléfono <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control <?= hasError('telefono_empleado') ?>" id="telefono" required name="telefono_empleado" value="<?= get_value('telefono_empleado', 'TELEFONO_EMPLEADO') ?>" pattern="[0-9]{10}" maxlength="10" title="Introduce 10 dígitos sin espacios ni guiones">
                                    <?= showError('telefono_empleado') ?>
                                </div>
                                <div class="col-md-4">
                                    <label for="correoPrincipal" class="form-label">Correo Principal <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control <?= hasError('correo_principal_empleado') ?>" id="correoPrincipal" required name="correo_principal_empleado" value="<?= get_value('correo_principal_empleado', 'correo_principal') ?>" maxlength="255">
                                    <?= showError('correo_principal_empleado') ?>
                                </div>
                                <div class="col-md-4">
                                    <label for="correoSecundario" class="form-label">Correo Secundario</label>
                                    <input type="email" class="form-control <?= hasError('correo_secundario_empleado') ?>" id="correoSecundario" name="correo_secundario_empleado" value="<?= get_value('correo_secundario_empleado', 'correo_secundario') ?>" maxlength="255">
                                    <?= showError('correo_secundario_empleado') ?>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h2 class="h5 border-bottom pb-2 mb-3">Dirección</h2>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="calle" class="form-label">Calle <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control <?= hasError('calle_empleado') ?> <?= hasSuccess('calle_empleado') ?>" id="calle" required name="calle_empleado" value="<?= get_value('calle_empleado', 'CALLE') ?>" maxlength="255">
                                    <?= showError('calle_empleado') ?>
                                </div>
                                <div class="col-md-2">
                                    <label for="numeroExterior" class="form-label">No. Exterior <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control <?= hasError('numero_exterior_empleado') ?>" id="numeroExterior" required name="numero_exterior_empleado" value="<?= get_value('numero_exterior_empleado', 'NUMERO_EXTERIOR') ?>" maxlength="255" pattern="[0-9]+" title="Este campo solo acepta números">
                                    <?= showError('numero_exterior_empleado') ?>
                                </div>
                                <div class="col-md-2">
                                    <label for="numeroInterior" class="form-label">No. Interior</label>
                                    <input type="text" class="form-control" id="numeroInterior" name="numero_interior_empleado" value="<?= get_value('numero_interior_empleado', 'NUMERO_INTERIOR') ?>" maxlength="255" pattern="[0-9]*" title="Este campo solo acepta números">
                                    <?= showError('numero_interior_empleado') ?>
                                </div>
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-md-12">
                                    <label for="colonia" class="form-label">Colonia <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control <?= hasError('colonia_empleado') ?> <?= hasSuccess('colonia_empleado') ?>" id="colonia" required name="colonia_empleado" value="<?= get_value('colonia_empleado', 'COLONIA') ?>" maxlength="255">
                                    <?= showError('colonia_empleado') ?>
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-4">
                                    <label for="pais" class="form-label">País <span class="text-danger">*</span></label>
                                    <select class="form-select <?= hasError('pais_empleado') ?> <?= hasSuccess('pais_empleado') ?>" id="pais" name="pais_empleado" required>
                                        <option value="" class="select-placeholder">Seleccione un País...</option>
                                        <?php
                                        $paises = getCatalogData($conn, 'paises', 'ID_PAIS', 'NOMBRE_PAIS', 'WHERE ESTADO_PAIS = 1');
                                        $selected_pais = get_value('pais_empleado', 'ID_PAIS');
                                        foreach ($paises as $pais) {
                                            $selected = ($selected_pais == $pais['ID_PAIS']) ? 'selected' : '';
                                            echo "<option value='" . $pais['ID_PAIS'] . "' $selected>" . htmlspecialchars($pais['NOMBRE_PAIS']) . "</option>";
                                        }
                                        if (empty($paises)) {
                                            echo '<option value="" disabled>No hay países disponibles</option>';
                                        }
                                        ?>
                                    </select>
                                    <?= showError('pais_empleado') ?>
                                </div>
                                <div class="col-md-4">
                                    <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                                    <select class="form-select <?= hasError('estado_empleado') ?> <?= hasSuccess('estado_empleado') ?>" id="estado" name="estado_empleado" required>
                                        <option value="" class="select-placeholder">Seleccione un Estado...</option>
                                        <?php
                                        $selected_estado = get_value('estado_empleado', 'ID_ESTADO');
                                        if (!empty(get_value('pais_empleado', 'ID_PAIS'))) {
                                            $id_pais_seleccionado = get_value('pais_empleado', 'ID_PAIS');
                                            $estados = getCatalogData($conn, 'estados', 'ID_ESTADO', 'NOMBRE_ESTADO', 'WHERE ID_PAIS = ' . $id_pais_seleccionado . ' AND ESTADO_ESTADO = 1');
                                            foreach ($estados as $estado) {
                                                $selected = ($selected_estado == $estado['ID_ESTADO']) ? 'selected' : '';
                                                echo "<option value='" . $estado['ID_ESTADO'] . "' $selected>" . htmlspecialchars($estado['NOMBRE_ESTADO']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <?= showError('estado_empleado') ?>
                                </div>
                                <div class="col-md-4">
                                    <label for="municipio" class="form-label">Municipio <span class="text-danger">*</span></label>
                                    <select class="form-select <?= hasError('municipio_empleado') ?> <?= hasSuccess('municipio_empleado') ?>" id="municipio" name="municipio_empleado" required>
                                        <option value="" class="select-placeholder">Seleccione un Municipio...</option>
                                        <?php
                                        $selected_municipio = get_value('municipio_empleado', 'ID_MUNICIPIO');
                                        if (!empty(get_value('estado_empleado', 'ID_ESTADO'))) {
                                            $id_estado_seleccionado = get_value('estado_empleado', 'ID_ESTADO');
                                            $municipios = getCatalogData($conn, 'municipios', 'ID_MUNICIPIO', 'NOMBRE_MUNICIPIO', 'WHERE ID_ESTADO = ' . $id_estado_seleccionado . ' AND ESTADO_MUNICIPIO = 1');
                                            foreach ($municipios as $municipio) {
                                                $selected = ($selected_municipio == $municipio['ID_MUNICIPIO']) ? 'selected' : '';
                                                echo "<option value='" . $municipio['ID_MUNICIPIO'] . "' $selected>" . htmlspecialchars($municipio['NOMBRE_MUNICIPIO']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <?= showError('municipio_empleado') ?>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-between align-items-center">
                                <?php if ($edit_mode): ?>
                                    <button type="button" class="btn btn-danger" onclick="confirmarEliminacion(<?= htmlspecialchars($id_empleado) ?>)">
                                        <i class="fa-solid fa-trash"></i> Eliminar Empleado
                                    </button>
                                <?php else: ?>
                                    <div></div>
                                <?php endif; ?>

                                <div>
                                    <a href="index.php" class="btn btn-secondary">Limpiar / Nuevo</a>
                                    <button class="btn btn-primary" type="submit" name="<?= $edit_mode ? 'actualizar_empleado' : 'guardar_empleado' ?>" value="ok">
                                        <?= $edit_mode ? 'Actualizar Cambios' : 'Guardar Empleado' ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Función para confirmar la eliminación
        function confirmarEliminacion(id) {
            if (confirm("¿Estás realmente seguro de que deseas eliminar este empleado? Esta acción es irreversible.")) {
                window.location.href = "controlador/eliminar_empleado.php?id=" + id;
            }
        }

        $(document).ready(function() {
            // Cargar estados y municipios si ya hay valores seleccionados (por error o edición)
            var paisSeleccionado = $('#pais').val();
            if (paisSeleccionado) {
                cargarEstados(paisSeleccionado, '<?= get_value("estado_empleado", "ID_ESTADO") ?>');
            }
            
            var estadoSeleccionado = '<?= get_value("estado_empleado", "ID_ESTADO") ?>';
            if(estadoSeleccionado){
                 cargarMunicipios(estadoSeleccionado, '<?= get_value("municipio_empleado", "ID_MUNICIPIO") ?>');
            }


            // Validación en tiempo real para campos obligatorios
            $('input[required], select[required]').on('blur change', function() {
                validateField($(this));
            });

            function validateField($field) {
                if ($field.val() === null || $field.val().trim() === '') {
                    $field.removeClass('is-valid').addClass('is-invalid');
                } else {
                    $field.removeClass('is-invalid').addClass('is-valid');
                }
            }

            // ----- AJAX para cargar estados cuando cambia el país -----
            $('#pais').on('change', function() {
                var paisId = $(this).val();
                validateField($(this));
                if (paisId) {
                    cargarEstados(paisId);
                } else {
                    $('#estado').html('<option value="" class="select-placeholder">Seleccione un Estado...</option>');
                    $('#municipio').html('<option value="" class="select-placeholder">Seleccione un Municipio...</option>');
                }
            });

            $('#estado').on('change', function() {
                var estadoId = $(this).val();
                 validateField($(this));
                if (estadoId) {
                    cargarMunicipios(estadoId);
                } else {
                    $('#municipio').html('<option value="" class="select-placeholder">Seleccione un Municipio...</option>');
                }
            });

            function cargarEstados(paisId, estadoSeleccionado = null) {
                $.ajax({
                    type: 'POST',
                    url: 'get_ubicaciones.php',
                    data: { pais_id: paisId },
                    success: function(html) {
                        $('#estado').html(html);
                        if (estadoSeleccionado) {
                            $('#estado').val(estadoSeleccionado);
                        }
                        $('#municipio').html('<option value="" class="select-placeholder">Seleccione un Municipio...</option>');
                    },
                    error: function() {
                        $('#estado').html('<option value="" class="select-placeholder">Error al cargar estados</option>');
                    }
                });
            }

            function cargarMunicipios(estadoId, municipioSeleccionado = null) {
                $.ajax({
                    type: 'POST',
                    url: 'get_ubicaciones.php',
                    data: { estado_id: estadoId },
                    success: function(html) {
                        $('#municipio').html(html);
                        if (municipioSeleccionado) {
                            $('#municipio').val(municipioSeleccionado);
                        }
                    },
                    error: function() {
                        $('#municipio').html('<option value="" class="select-placeholder">Error al cargar municipios</option>');
                    }
                });
            }

            // Validación del formulario del lado del cliente
            $('#employeeForm').on('submit', function(e) {
                if (!this.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                $(this).addClass('was-validated');
            });
        });
    </script>

</body>

</html> 