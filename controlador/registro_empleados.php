<?php
// Controlador: registra un nuevo empleado con domicilio y correos relacionados.
session_start();

if (!empty($_POST['guardar_empleado']) && $_POST['guardar_empleado'] == 'ok') {
    include '../modelo/conexion.php';
    require_once '../modelo/validacion.php';
    require_once '../modelo/repositorio.php';
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $errores = [];
    $contratante = $_SESSION['id_usuario'] ?? null;

    try {
        $input = collect_employee_input($_POST);
        $errores = validate_employee_input($input);

        if (!empty($errores)) {
            throw new Exception('Datos de formulario no válidos.');
        }

        // Persistencia usando repositorio y transacción
        $repo = new EmployeeRepository($conn);
        $repo->begin();

        $idEmpleado = $repo->insertEmployee($input, (int)$contratante, (int)$input['rol']);
        $repo->insertAddress($idEmpleado, $input);
        if ($input['correo_principal'] !== '') { $repo->insertEmail($idEmpleado, $input['correo_principal'], 'principal'); }
        if ($input['correo_secundario'] !== '') { $repo->insertEmail($idEmpleado, $input['correo_secundario'], 'secundario'); }

        $repo->commit();
        echo "<script>alert('Empleado registrado exitosamente.'); window.location.href='../index.php';</script>";

    } catch (mysqli_sql_exception $e) {
        if (isset($repo)) { $repo->rollback(); }
        $_SESSION['form_data'] = $_POST;
        $error_message = $e->getMessage();
        if ($e->getCode() == 1062) {
            if (strpos($error_message, 'UQ_CURP_EMPLEADO')) { $_SESSION['errores']['curp_empleado'] = 'El CURP ya existe.'; }
            elseif (strpos($error_message, 'UQ_RFC_EMPLEADO')) { $_SESSION['errores']['rfc_empleado'] = 'El RFC ya existe.'; }
            elseif (strpos($error_message, 'UQ_TELEFONO_EMPLEADO')) { $_SESSION['errores']['telefono_empleado'] = 'El teléfono ya existe.'; }
            elseif (strpos($error_message, 'UQ_CORREO_EMPLEADO')) { $_SESSION['errores']['correo_principal_empleado'] = 'El correo ya existe.'; }
        } elseif (strpos($error_message, 'CHK_')) {
            if (strpos($error_message, 'CHK_CURP_EMPLEADO')) { $_SESSION['errores']['curp_empleado'] = 'El formato del CURP no es válido.'; }
            elseif (strpos($error_message, 'CHK_RFC_EMPLEADO')) { $_SESSION['errores']['rfc_empleado'] = 'El formato del RFC no es válido.'; }
        } else {
            $_SESSION['errores']['general'] = 'Error de base de datos: ' . $error_message;
        }
        header('Location: ../index.php');
        exit;

    } catch (Exception $e) {
        if (isset($repo)) { $repo->rollback(); }
        $_SESSION['form_data'] = $_POST;
        $_SESSION['errores'] = $errores;
        header('Location: ../index.php');
        exit;

    } finally {
        if (isset($conn)) { $conn->close(); }
    }
} else {
    echo "<script>alert('Acceso no autorizado.'); window.location.href='../index.php';</script>";
}
?>