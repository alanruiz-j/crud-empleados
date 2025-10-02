<?php
session_start(); // ← AGREGAR ESTO AL INICIO

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
        $fecha_contratacion = date('Y-m-d');
        $id_rol = 2; // Rol de operador
        
        // ← NUEVO: Obtener el ID del administrador que está registrando
        $contratante = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : null;

        // --- 2. Validación ---
        if (empty($nombre) || empty($genero) || empty($departamento) || empty($municipio)) {
            throw new Exception("Por favor, complete todos los campos obligatorios (*).");
        }

        // --- 3. PRIMERO: Insertar empleado ---
        // AGREGAR CONTRATANTE al INSERT
        $sql_empleado = "INSERT INTO empleados (NOMBRE_EMPLEADO, APELLIDO_PATERNO, APELLIDO_MATERNO, ID_GENERO, CURP_EMPLEADO, RFC_EMPLEADO, TELEFONO_EMPLEADO, CONTRATANTE, FECHA_CONTRATACION, ID_DEPARTAMENTO, ID_ROL) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_empleado = $conn->prepare($sql_empleado);
        
        // AGREGAR $contratante al bind_param
        $stmt_empleado->bind_param("sssisssisii", $nombre, $apellido_paterno, $apellido_materno, $genero, $curp, $rfc, $telefono, $contratante, $fecha_contratacion, $departamento, $id_rol);
        $stmt_empleado->execute();
        $id_empleado_insertado = $conn->insert_id;

        // --- 4. SEGUNDO: Insertar domicilio usando el ID del nuevo empleado ---
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

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = json_encode("Error: " . $e->getMessage());
        echo "<script>alert('Error al registrar empleado: ' + $error_message); window.location.href='../index.php';</script>";
    } finally {
        if (isset($conn)) {
            $conn->close();
        }
    }
} else {
    echo "<script>alert('Acceso no autorizado.'); window.location.href='../index.php';</script>";
}
?>