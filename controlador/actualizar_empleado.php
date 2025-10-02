<?php

if (!empty($_POST['actualizar_empleado']) && $_POST['actualizar_empleado'] == 'ok') {

    include '../modelo/conexion.php';
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // --- 1. Recoger los datos del formulario ---
        $id_empleado        = $_POST['id_empleado'];
        $contratante        = !empty($_POST['contratante_empleado']) ? $_POST['contratante_empleado'] : null;
        $nombre             = $_POST['nombre_empleado'];
        $apellido_paterno   = $_POST['apellido_paterno_empleado'];
        $apellido_materno   = $_POST['apellido_materno_empleado'];
        $genero             = $_POST['genero_empleado'];
        $departamento       = $_POST['departamento_empleado'];
        // ELIMINAMOS la variable $rol ya que no la usaremos
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
        $fecha_contratacion = $_POST['fecha_contratacion_empleado'];

        // --- 2. Actualizar la tabla de empleados (SIN ACTUALIZAR EL ROL) ---
        $sql_empleado = "UPDATE empleados SET NOMBRE_EMPLEADO=?, APELLIDO_PATERNO=?, APELLIDO_MATERNO=?, ID_GENERO=?, CURP_EMPLEADO=?, RFC_EMPLEADO=?, TELEFONO_EMPLEADO=?, CONTRATANTE=?, FECHA_CONTRATACION=?, ID_DEPARTAMENTO=? WHERE ID_EMPLEADO=?";
        $stmt_empleado = $conn->prepare($sql_empleado);
        // Quitamos el parámetro del rol y ajustamos los tipos de datos
        $stmt_empleado->bind_param("sssisssisii", $nombre, $apellido_paterno, $apellido_materno, $genero, $curp, $rfc, $telefono, $contratante, $fecha_contratacion, $departamento, $id_empleado);
        $stmt_empleado->execute();

        // --- 3. Actualizar la tabla de domicilios ---
        $sql_domicilio = "UPDATE domicilios SET CALLE=?, NUMERO_EXTERIOR=?, NUMERO_INTERIOR=?, COLONIA=?, ID_MUNICIPIO=? WHERE ID_EMPLEADO=?";
        $stmt_domicilio = $conn->prepare($sql_domicilio);
        $stmt_domicilio->bind_param("ssssii", $calle, $numero_exterior, $numero_interior, $colonia, $municipio, $id_empleado);
        $stmt_domicilio->execute();

        // --- 4. Actualizar correos (UPSERT: Update or Insert) ---

        // Correo Principal
        if (!empty($correo_principal)) {
            $sql_upsert_p = "INSERT INTO correos (ID_EMPLEADO, CORREO_EMPLEADO, TIPO_CORREO) VALUES (?, ?, 'principal') ON DUPLICATE KEY UPDATE CORREO_EMPLEADO = VALUES(CORREO_EMPLEADO)";
            $stmt_upsert_p = $conn->prepare($sql_upsert_p);
            $stmt_upsert_p->bind_param("is", $id_empleado, $correo_principal);
            $stmt_upsert_p->execute();
        } else {
            // Si el campo está vacío, eliminamos el correo principal si existe
            $conn->query("DELETE FROM correos WHERE ID_EMPLEADO = $id_empleado AND TIPO_CORREO = 'principal'");
        }
        
        // Correo Secundario
        if (!empty($correo_secundario)) {
            $sql_upsert_s = "INSERT INTO correos (ID_EMPLEADO, CORREO_EMPLEADO, TIPO_CORREO) VALUES (?, ?, 'secundario') ON DUPLICATE KEY UPDATE CORREO_EMPLEADO = VALUES(CORREO_EMPLEADO)";
            $stmt_upsert_s = $conn->prepare($sql_upsert_s);
            $stmt_upsert_s->bind_param("is", $id_empleado, $correo_secundario);
            $stmt_upsert_s->execute();
        } else {
            // Si el campo está vacío, eliminamos el correo secundario si existe
            $conn->query("DELETE FROM correos WHERE ID_EMPLEADO = $id_empleado AND TIPO_CORREO = 'secundario'");
        }

        // Confirmar cambios
        $conn->commit();
        echo "<script>alert('Empleado actualizado exitosamente.'); window.location.href='../index.php';</script>";

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = json_encode($e->getMessage());
        echo "<script>alert('Error al actualizar empleado: ' + $error_message); window.location.href='../index.php?id=$id_empleado';</script>";
    } finally {
        if (isset($conn)) {
            $conn->close();
        }
    }
} else {
    echo "<script>alert('Acceso no autorizado.'); window.location.href='../index.php';</script>";
}
?>