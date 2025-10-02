<?php
// Controlador: actualiza datos de un empleado existente, su domicilio y correos.
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

if (isset($_POST['actualizar_empleado']) && $_POST['actualizar_empleado'] == 'ok') {
    include '../modelo/conexion.php';
    require_once '../modelo/validacion.php';
    require_once '../modelo/repositorio.php';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $id_empleado = (int)$_POST['id_empleado'];
    $errores = [];

    try {
        $input = collect_employee_input($_POST);

        // Verificaciones de duplicados
        $repo = new EmployeeRepository($conn);
        $repo->begin();

        if ($repo->hasDuplicate($conn, "SELECT ID_EMPLEADO FROM empleados WHERE CURP_EMPLEADO = ? AND ID_EMPLEADO != ?", [$input['curp'], $id_empleado], 'si')) {
            $errores['curp_empleado'] = 'El CURP ya pertenece a otro empleado.';
        }
        if ($repo->hasDuplicate($conn, "SELECT ID_EMPLEADO FROM empleados WHERE RFC_EMPLEADO = ? AND ID_EMPLEADO != ?", [$input['rfc'], $id_empleado], 'si')) {
            $errores['rfc_empleado'] = 'El RFC ya pertenece a otro empleado.';
        }
        if ($repo->hasDuplicate($conn, "SELECT ID_EMPLEADO FROM empleados WHERE TELEFONO_EMPLEADO = ? AND ID_EMPLEADO != ?", [$input['telefono'], $id_empleado], 'si')) {
            $errores['telefono_empleado'] = 'El teléfono ya pertenece a otro empleado.';
        }
        if ($input['correo_principal'] !== '' && $repo->hasDuplicate($conn, "SELECT ID_EMPLEADO FROM correos WHERE CORREO_EMPLEADO = ? AND ID_EMPLEADO != ?", [$input['correo_principal'], $id_empleado], 'si')) {
            $errores['correo_principal_empleado'] = 'El correo principal ya pertenece a otro empleado.';
        }
        if ($input['correo_secundario'] !== '' && $repo->hasDuplicate($conn, "SELECT ID_EMPLEADO FROM correos WHERE CORREO_EMPLEADO = ? AND ID_EMPLEADO != ?", [$input['correo_secundario'], $id_empleado], 'si')) {
            $errores['correo_secundario_empleado'] = 'El correo secundario ya pertenece a otro empleado.';
        }

        if (!empty($errores)) { throw new Exception('Se encontraron datos duplicados.'); }

        $contratante = !empty($_POST['contratante']) ? (int)$_POST['contratante'] : null;

        // Persistencia: empleado, domicilio y correos
        $repo->upsertEmployee($id_empleado, $input, $contratante);
        $repo->upsertAddress($id_empleado, $input);
        if ($input['correo_principal'] !== '') { $repo->upsertEmail($id_empleado, $input['correo_principal'], 'principal'); }
        if ($input['correo_secundario'] !== '') { $repo->upsertEmail($id_empleado, $input['correo_secundario'], 'secundario'); }

        $repo->commit();
        echo "<script>alert('Empleado actualizado correctamente.'); window.location.href='../index.php?id=" . $id_empleado . "';</script>";

    } catch (Exception $e) {
        if (isset($repo)) { $repo->rollback(); }
        $_SESSION['form_data'] = $_POST;
        if (!empty($errores)) { $_SESSION['errores'] = $errores; }
        else { $_SESSION['errores']['general'] = 'Error: ' . $e->getMessage(); }
        header('Location: ../index.php?id=' . $id_empleado);
        exit;

    } finally {
        if (isset($conn)) { $conn->close(); }
    }
} else {
    header('Location: ../index.php');
    exit;
}
?>