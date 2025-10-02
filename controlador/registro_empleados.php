<?php
session_start();

if (!empty($_POST['guardar_empleado']) && $_POST['guardar_empleado'] == 'ok') {

    include '../modelo/conexion.php';
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn->begin_transaction();
    $errores = [];

    try {
        // --- 1. Sanitizar y Recoger Datos ---
        // Se usa trim() para eliminar espacios en blanco al inicio y al final.
        $nombre             = trim($_POST['nombre_empleado'] ?? '');
        $apellido_paterno   = trim($_POST['apellido_paterno_empleado'] ?? '');
        $apellido_materno   = trim($_POST['apellido_materno_empleado'] ?? '');
        $genero             = trim($_POST['genero_empleado'] ?? '');
        $departamento       = trim($_POST['departamento_empleado'] ?? '');
        $curp               = strtoupper(trim($_POST['curp_empleado'] ?? ''));
        $rfc                = strtoupper(trim($_POST['rfc_empleado'] ?? ''));
        $correo_principal   = trim($_POST['correo_principal_empleado'] ?? '');
        $correo_secundario  = trim($_POST['correo_secundario_empleado'] ?? '');
        $telefono           = trim($_POST['telefono_empleado'] ?? '');
        $calle              = trim($_POST['calle_empleado'] ?? '');
        $numero_exterior    = trim($_POST['numero_exterior_empleado'] ?? '');
        $numero_interior    = trim($_POST['numero_interior_empleado'] ?? '');
        $colonia            = trim($_POST['colonia_empleado'] ?? '');
        $municipio          = trim($_POST['municipio_empleado'] ?? '');
        $fecha_contratacion = !empty($_POST['fecha_contratacion_empleado']) ? $_POST['fecha_contratacion_empleado'] : date('Y-m-d');
        $id_rol             = 2;
        $contratante        = $_SESSION['id_usuario'] ?? null;

        // --- 2. Bloque de Validaciones del Lado del Servidor ---

        // Campos obligatorios
        if (empty($nombre)) { $errores['nombre_empleado'] = 'El nombre es obligatorio.'; }
        if (empty($genero)) { $errores['genero_empleado'] = 'Debe seleccionar un género.'; }
        if (empty($departamento)) { $errores['departamento_empleado'] = 'Debe seleccionar un departamento.'; }
        if (empty($curp)) { $errores['curp_empleado'] = 'El CURP es obligatorio.'; }
        if (empty($rfc)) { $errores['rfc_empleado'] = 'El RFC es obligatorio.'; }
        if (empty($telefono)) { $errores['telefono_empleado'] = 'El teléfono es obligatorio.'; }
        if (empty($correo_principal)) { $errores['correo_principal_empleado'] = 'El correo principal es obligatorio.'; }
        if (empty($calle)) { $errores['calle_empleado'] = 'La calle es obligatoria.'; }
        if (empty($numero_exterior)) { $errores['numero_exterior_empleado'] = 'El número exterior es obligatorio.'; }
        if (empty($colonia)) { $errores['colonia_empleado'] = 'La colonia es obligatoria.'; }
        if (empty($municipio)) { $errores['municipio_empleado'] = 'Debe seleccionar un municipio.'; }
        if (empty($apellido_paterno) && empty($apellido_materno)) { $errores['apellido_paterno_empleado'] = 'Debe proporcionar al menos un apellido.'; }

        // Formatos
        if (!preg_match('/^([A-Z][AEIOUX][A-Z]{2}[0-9]{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12][0-9]|3[01])[HM](?:AS|B[CS]|C[CLMSH]|D[FG]|G[TR]|HG|JC|M[CNS]|N[ETL]|OC|PL|Q[TR]|S[PLR]|T[CSL]|VZ|YN|ZS)[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9])([0-9])$/', $curp)) { $errores['curp_empleado'] = 'El formato del CURP no es válido.'; }
        if (!preg_match('/^[A-ZÑ&]{3,4}[0-9]{6}(?:[A-Z0-9]{3})?$/', $rfc)) { $errores['rfc_empleado'] = 'El formato del RFC no es válido.'; }
        if (!preg_match('/^[0-9]{10}$/', $telefono)) { $errores['telefono_empleado'] = 'El teléfono debe contener 10 dígitos.'; }
        if (!filter_var($correo_principal, FILTER_VALIDATE_EMAIL)) { $errores['correo_principal_empleado'] = 'El formato del correo principal no es válido.'; }
        if (!empty($correo_secundario) && !filter_var($correo_secundario, FILTER_VALIDATE_EMAIL)) { $errores['correo_secundario_empleado'] = 'El formato del correo secundario no es válido.'; }
        
        // Longitudes
        if (strlen($nombre) > 255) { $errores['nombre_empleado'] = 'El nombre excede la longitud permitida.'; }
        // ... puedes añadir más validaciones de longitud para otros campos si es necesario

        // Si se encontraron errores de validación, detener la ejecución.
        if (!empty($errores)) {
            throw new Exception("Datos de formulario no válidos.");
        }

        // --- 3. Inserción en Base de Datos (si todo es correcto) ---
        $sql_empleado = "INSERT INTO empleados (NOMBRE_EMPLEADO, APELLIDO_PATERNO, APELLIDO_MATERNO, ID_GENERO, CURP_EMPLEADO, RFC_EMPLEADO, TELEFONO_EMPLEADO, CONTRATANTE, FECHA_CONTRATACION, ID_DEPARTAMENTO, ID_ROL) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_empleado = $conn->prepare($sql_empleado);
        $stmt_empleado->bind_param("sssisssisii", $nombre, $apellido_paterno, $apellido_materno, $genero, $curp, $rfc, $telefono, $contratante, $fecha_contratacion, $departamento, $id_rol);
        $stmt_empleado->execute();
        $id_empleado_insertado = $conn->insert_id;

        // Insertar domicilio
        $sql_domicilio = "INSERT INTO domicilios (ID_EMPLEADO, CALLE, NUMERO_EXTERIOR, NUMERO_INTERIOR, COLONIA, ID_MUNICIPIO) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_domicilio = $conn->prepare($sql_domicilio);
        $stmt_domicilio->bind_param("issssi", $id_empleado_insertado, $calle, $numero_exterior, $numero_interior, $colonia, $municipio);
        $stmt_domicilio->execute();
        
        // Insertar correos
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

        $conn->commit();
        echo "<script>alert('Empleado registrado exitosamente.'); window.location.href='../index.php';</script>";

    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $_SESSION['form_data'] = $_POST;
        $error_message = $e->getMessage();
        // Errores de la base de datos (duplicados, formatos, etc.)
        if ($e->getCode() == 1062) {
            if (strpos($error_message, 'UQ_CURP_EMPLEADO')) { $_SESSION['errores']['curp_empleado'] = 'El CURP ya existe.'; }
            elseif (strpos($error_message, 'UQ_RFC_EMPLEADO')) { $_SESSION['errores']['rfc_empleado'] = 'El RFC ya existe.'; }
            elseif (strpos($error_message, 'UQ_TELEFONO_EMPLEADO')) { $_SESSION['errores']['telefono_empleado'] = 'El teléfono ya existe.'; }
            elseif (strpos($error_message, 'UQ_CORREO_EMPLEADO')) { $_SESSION['errores']['correo_principal_empleado'] = 'El correo ya existe.'; }
        } elseif (strpos($error_message, 'CHK_')) {
            if (strpos($error_message, 'CHK_CURP_EMPLEADO')) { $_SESSION['errores']['curp_empleado'] = 'El formato del CURP no es válido.'; }
            elseif (strpos($error_message, 'CHK_RFC_EMPLEADO')) { $_SESSION['errores']['rfc_empleado'] = 'El formato del RFC no es válido.'; }
            // ... agregar más CHK si es necesario
        } else {
            $_SESSION['errores']['general'] = "Error de base de datos: " . $error_message;
        }
        header('Location: ../index.php');
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['form_data'] = $_POST;
        // Si hay errores de validación PHP, los asignamos a la sesión.
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