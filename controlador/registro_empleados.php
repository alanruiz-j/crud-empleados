<?php
session_start(); // Asegúrate de que la sesión esté iniciada

if (!empty($_POST['guardar_empleado']) && $_POST['guardar_empleado'] == 'ok') {

    include '../modelo/conexion.php';

    // Habilitar reporte de errores de MySQLi para que lance excepciones
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // --- 1. Recoger los datos ---
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
        $id_rol = 2; // Rol de operador
        $contratante = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : null;

        // --- 2. Validación (básica, puedes agregar más) ---
        if (empty($nombre) || empty($genero) || empty($departamento) || empty($municipio)) {
            throw new Exception("Por favor, complete todos los campos obligatorios (*).");
        }

        // --- 3. Insertar empleado ---
        $sql_empleado = "INSERT INTO empleados (NOMBRE_EMPLEADO, APELLIDO_PATERNO, APELLIDO_MATERNO, ID_GENERO, CURP_EMPLEADO, RFC_EMPLEADO, TELEFONO_EMPLEADO, CONTRATANTE, FECHA_CONTRATACION, ID_DEPARTAMENTO, ID_ROL) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_empleado = $conn->prepare($sql_empleado);
        $stmt_empleado->bind_param("sssisssisii", $nombre, $apellido_paterno, $apellido_materno, $genero, $curp, $rfc, $telefono, $contratante, $fecha_contratacion, $departamento, $id_rol);
        $stmt_empleado->execute();
        $id_empleado_insertado = $conn->insert_id;

        // --- 4. Insertar domicilio ---
        $sql_domicilio = "INSERT INTO domicilios (ID_EMPLEADO, CALLE, NUMERO_EXTERIOR, NUMERO_INTERIOR, COLONIA, ID_MUNICIPIO) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_domicilio = $conn->prepare($sql_domicilio);
        $stmt_domicilio->bind_param("issssi", $id_empleado_insertado, $calle, $numero_exterior, $numero_interior, $colonia, $municipio);
        $stmt_domicilio->execute();
        
        // --- 5. Insertar correos ---
        if (!empty($correo_principal)) {
            $sql_correo_p = "INSERT INTO correos (ID_EMPLEADO, CORREO_EMPLEADO, TIPO_CORREO) VALUES (?, ?, 'principal')";
            $stmt_correo_p = $conn->prepare($sql_correo_p);
            $stmt_correo_p->bind_param("is", $id_empleado_insertado, $correo_principal);
            $stmt_correo_p->execute();
        }

        if (!empty($correo_secundario)) {
            $sql_correo_s = "INSERT INTO correos (ID_EMPLEADO, CORREO_EMPLEADO, TIPO_CORREO) VALUES (?, ?, 'secundario')";
            $stmt_correo_s = $conn->prepare($sql_correo_s);
            $stmt_correo_s->bind_param("is", $id_empleado_insertado, $correo_secundario);
            $stmt_correo_s->execute();
        }

        // Confirmar cambios
        $conn->commit();
        echo "<script>alert('Empleado registrado exitosamente.'); window.location.href='../index.php';</script>";

    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        // Guardar los datos del formulario para mostrarlos de nuevo
        $_SESSION['form_data'] = $_POST;
        $error_message = $e->getMessage();

        // Verificar si el error es por una entrada duplicada (código 1062)
        if ($e->getCode() == 1062) {
            if (strpos($error_message, 'UQ_CURP_EMPLEADO') !== false) {
                $_SESSION['errores']['curp_empleado'] = 'El CURP ingresado ya existe.';
            } elseif (strpos($error_message, 'UQ_RFC_EMPLEADO') !== false) {
                $_SESSION['errores']['rfc_empleado'] = 'El RFC ingresado ya existe.';
            } elseif (strpos($error_message, 'UQ_TELEFONO_EMPLEADO') !== false) {
                $_SESSION['errores']['telefono_empleado'] = 'El teléfono ingresado ya existe.';
            } elseif (strpos($error_message, 'UQ_CORREO_EMPLEADO') !== false) {
                // Verificar cuál de los correos es el duplicado
                if (strpos($error_message, "'$correo_principal'") !== false) {
                    $_SESSION['errores']['correo_principal_empleado'] = 'El correo principal ya existe.';
                }
                if (!empty($correo_secundario) && strpos($error_message, "'$correo_secundario'") !== false) {
                    $_SESSION['errores']['correo_secundario_empleado'] = 'El correo secundario ya existe.';
                }
            } else {
                $_SESSION['errores']['general'] = 'Error: Se ha producido un error de duplicado no identificado.';
            }
        } elseif (strpos($error_message, 'CHK_CURP_EMPLEADO') !== false) {
        $_SESSION['errores']['curp_empleado'] = 'El formato del CURP no es válido.';
    } 
    // ✅ AÑADIR ESTAS LÍNEAS
    elseif (strpos($error_message, 'CHK_RFC_EMPLEADO') !== false) {
        $_SESSION['errores']['rfc_empleado'] = 'El formato del RFC no es válido.';
    } 
    else {
         $_SESSION['errores']['general'] = "Error de base de datos: " . $error_message;
    }
        
        // Redirigir de vuelta al formulario
        header('Location: ../index.php');
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['form_data'] = $_POST;
        $_SESSION['errores']['general'] = "Error: " . $e->getMessage();
        header('Location: ../index.php');
        exit;

    } finally {
        if (isset($conn)) {
            $conn->close();
        }
    }
} else {
    echo "<script>alert('Acceso no autorizado.'); window.location.href='../index.php';</script>";
}
?>