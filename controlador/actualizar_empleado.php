<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

if (isset($_POST['actualizar_empleado']) && $_POST['actualizar_empleado'] == 'ok') {

    include '../modelo/conexion.php';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn->begin_transaction();

    $id_empleado = $_POST['id_empleado'];
    $errores = [];

    try {
        // --- 1. Recoger datos del formulario ---
        $nombre             = $_POST['nombre_empleado'];
        $apellido_paterno   = $_POST['apellido_paterno_empleado'];
        $apellido_materno   = $_POST['apellido_materno_empleado'];
        $genero             = $_POST['genero_empleado'];
        $departamento       = $_POST['departamento_empleado'];
        $curp               = $_POST['curp_empleado'];
        $rfc                = $_POST['rfc_empleado'];
        $correo_principal   = $_POST['correo_principal_empleado'];
        $correo_secundario  = $_POST['correo_secundario_empleado'];
        $telefono           = $_POST['telefono_empleado'];
        $calle              = $_POST['calle_empleado'];
        $numero_exterior    = $_POST['numero_exterior_empleado'];
        $numero_interior    = $_POST['numero_interior_empleado'];
        $colonia            = $_POST['colonia_empleado'];
        $municipio          = $_POST['municipio_empleado'];
        $fecha_contratacion = $fecha_contratacion = !empty($_POST['fecha_contratacion_empleado']) ? $_POST['fecha_contratacion_empleado'] : date('Y-m-d');;
        $contratante        = !empty($_POST['contratante']) ? $_POST['contratante'] : null;

        // --- 2. Verificaciones manuales de duplicados ---
        
        // Función para verificar duplicados
        function check_duplicate($conn, $sql, $params, $types, &$errores, $error_key, $error_message) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errores[$error_key] = $error_message;
            }
            $stmt->close();
        }

        // Verificar CURP, RFC y Teléfono
        check_duplicate($conn, "SELECT ID_EMPLEADO FROM empleados WHERE CURP_EMPLEADO = ? AND ID_EMPLEADO != ?", [$curp, $id_empleado], "si", $errores, 'curp_empleado', 'El CURP ya pertenece a otro empleado.');
        check_duplicate($conn, "SELECT ID_EMPLEADO FROM empleados WHERE RFC_EMPLEADO = ? AND ID_EMPLEADO != ?", [$rfc, $id_empleado], "si", $errores, 'rfc_empleado', 'El RFC ya pertenece a otro empleado.');
        check_duplicate($conn, "SELECT ID_EMPLEADO FROM empleados WHERE TELEFONO_EMPLEADO = ? AND ID_EMPLEADO != ?", [$telefono, $id_empleado], "si", $errores, 'telefono_empleado', 'El teléfono ya pertenece a otro empleado.');

        // Verificar Correos
        if (!empty($correo_principal)) {
            check_duplicate($conn, "SELECT ID_EMPLEADO FROM correos WHERE CORREO_EMPLEADO = ? AND ID_EMPLEADO != ?", [$correo_principal, $id_empleado], "si", $errores, 'correo_principal_empleado', 'El correo principal ya pertenece a otro empleado.');
        }
        if (!empty($correo_secundario)) {
            check_duplicate($conn, "SELECT ID_EMPLEADO FROM correos WHERE CORREO_EMPLEADO = ? AND ID_EMPLEADO != ?", [$correo_secundario, $id_empleado], "si", $errores, 'correo_secundario_empleado', 'El correo secundario ya pertenece a otro empleado.');
        }

        // Si se encontraron errores, detener la ejecución
        if (!empty($errores)) {
            throw new Exception("Se encontraron datos duplicados.");
        }

        // --- 3. Actualizar tabla 'empleados' (si no hay errores) ---
        $sql_empleado = "UPDATE empleados SET 
                            NOMBRE_EMPLEADO = ?, APELLIDO_PATERNO = ?, APELLIDO_MATERNO = ?, 
                            ID_GENERO = ?, CURP_EMPLEADO = ?, RFC_EMPLEADO = ?, TELEFONO_EMPLEADO = ?, 
                            FECHA_CONTRATACION = ?, ID_DEPARTAMENTO = ?, CONTRATANTE = ? 
                        WHERE ID_EMPLEADO = ?";
        $stmt_empleado = $conn->prepare($sql_empleado);
        $stmt_empleado->bind_param("sssissssiii", $nombre, $apellido_paterno, $apellido_materno, $genero, $curp, $rfc, $telefono, $fecha_contratacion, $departamento, $contratante, $id_empleado);
        $stmt_empleado->execute();

        // --- 4. Actualizar tabla 'domicilios' ---
        $sql_domicilio = "INSERT INTO domicilios (ID_EMPLEADO, CALLE, NUMERO_EXTERIOR, NUMERO_INTERIOR, COLONIA, ID_MUNICIPIO) 
                          VALUES (?, ?, ?, ?, ?, ?) 
                          ON DUPLICATE KEY UPDATE 
                            CALLE = VALUES(CALLE), NUMERO_EXTERIOR = VALUES(NUMERO_EXTERIOR), 
                            NUMERO_INTERIOR = VALUES(NUMERO_INTERIOR), COLONIA = VALUES(COLONIA), 
                            ID_MUNICIPIO = VALUES(ID_MUNICIPIO)";
        $stmt_domicilio = $conn->prepare($sql_domicilio);
        $stmt_domicilio->bind_param("issssi", $id_empleado, $calle, $numero_exterior, $numero_interior, $colonia, $municipio);
        $stmt_domicilio->execute();

        // --- 5. Actualizar correos ---
        if (!empty($correo_principal)) {
            $sql_correo_p = "INSERT INTO correos (ID_EMPLEADO, CORREO_EMPLEADO, TIPO_CORREO) 
                             VALUES (?, ?, 'principal') ON DUPLICATE KEY UPDATE CORREO_EMPLEADO = VALUES(CORREO_EMPLEADO)";
            $stmt_correo_p = $conn->prepare($sql_correo_p);
            $stmt_correo_p->bind_param("is", $id_empleado, $correo_principal);
            $stmt_correo_p->execute();
        }
        
        // Puedes agregar lógica para eliminar el correo si el campo se deja vacío
        // ...

        if (!empty($correo_secundario)) {
            $sql_correo_s = "INSERT INTO correos (ID_EMPLEADO, CORREO_EMPLEADO, TIPO_CORREO) 
                             VALUES (?, ?, 'secundario') ON DUPLICATE KEY UPDATE CORREO_EMPLEADO = VALUES(CORREO_EMPLEADO)";
            $stmt_correo_s = $conn->prepare($sql_correo_s);
            $stmt_correo_s->bind_param("is", $id_empleado, $correo_secundario);
            $stmt_correo_s->execute();
        }
        
        $conn->commit();
        echo "<script>alert('Empleado actualizado correctamente.'); window.location.href='../index.php?id=" . $id_empleado . "';</script>";

    } catch (Exception $e) {
        $conn->rollback();
        
        // Guardar datos del formulario para mostrarlos de nuevo
        $_SESSION['form_data'] = $_POST;
        
        // Si ya teníamos errores de validación, los usamos. Si no, mostramos el error general.
        if (!empty($errores)) {
            $_SESSION['errores'] = $errores;
        } else {
            $_SESSION['errores']['general'] = "Error: " . $e->getMessage();
        }
        
        // Redirigir de vuelta al formulario de edición
        header('Location: ../index.php?id=' . $id_empleado);
        exit;

    } finally {
        if (isset($conn)) {
            $conn->close();
        }
    }
} else {
    header('Location: ../index.php');
    exit;
}
?>