<?php
if (!empty($_POST['actualizar_empleado']) && $_POST['actualizar_empleado'] == 'ok') {

    include '../modelo/conexion.php';
    $conn->begin_transaction();

    try {
        // --- 1. Recoger datos del formulario ---
        $id_empleado      = intval($_POST['id_empleado']);
        // Se convierte el valor vacío a NULL para la base de datos
        $contratante      = !empty($_POST['contratante_empleado']) ? intval($_POST['contratante_empleado']) : null;
        $nombre           = $_POST['nombre_empleado'];
        $apellido_paterno = $_POST['apellido_paterno_empleado'];
        $apellido_materno = $_POST['apellido_materno_empleado'];
        $genero           = $_POST['genero_empleado'];
        $departamento     = $_POST['departamento_empleado'];
        $id_rol           = $_POST['rol_empleado']; // Asegúrate de recoger el rol
        $curp             = $_POST['curp_empleado'];
        $rfc              = $_POST['rfc_empleado'];
        $correo_principal = $_POST['correo_principal_empleado'];
        $correo_secundario= $_POST['correo_secundario_empleado'];
        $telefono         = $_POST['telefono_empleado'];
        $calle            = $_POST['calle_empleado'];
        $numero_exterior  = $_POST['numero_exterior_empleado'];
        $numero_interior  = $_POST['numero_interior_empleado'];
        $colonia          = $_POST['colonia_empleado'];
        $municipio        = $_POST['municipio_empleado'];

        // --- CAMBIO 1: Eliminamos la consulta incorrecta que buscaba 'ID_DOMICILIO' ---
        // Ya no necesitamos ese bloque, la actualización se hará directamente con ID_EMPLEADO.

        // --- 3. Actualizar empleado ---
        // Añadimos ID_ROL a la consulta de actualización
        $sql_empleado = "UPDATE empleados 
                           SET NOMBRE_EMPLEADO = ?, APELLIDO_PATERNO = ?, APELLIDO_MATERNO = ?, 
                               ID_GENERO = ?, CURP_EMPLEADO = ?, RFC_EMPLEADO = ?, 
                               TELEFONO_EMPLEADO = ?, CONTRATANTE = ?, ID_DEPARTAMENTO = ?, ID_ROL = ?
                         WHERE ID_EMPLEADO = ?";
        $stmt_empleado = $conn->prepare($sql_empleado);
        if (!$stmt_empleado) {
            throw new Exception("Error en prepare() empleado: " . $conn->error);
        }
        // Ajustamos los tipos de datos en bind_param (añadimos 'i' para ID_ROL)
        $stmt_empleado->bind_param("sssisssiiii", $nombre, $apellido_paterno, $apellido_materno, $genero, $curp, $rfc, $telefono, $contratante, $departamento, $id_rol, $id_empleado);
        $stmt_empleado->execute();

        // --- CAMBIO 2: Actualizamos el domicilio usando ID_EMPLEADO ---
        $sql_domicilio = "UPDATE domicilios 
                            SET CALLE = ?, NUMERO_EXTERIOR = ?, NUMERO_INTERIOR = ?, 
                                COLONIA = ?, ID_MUNICIPIO = ?
                          WHERE ID_EMPLEADO = ?"; // La condición ahora es ID_EMPLEADO
        $stmt_domicilio = $conn->prepare($sql_domicilio);
        if (!$stmt_domicilio) {
            throw new Exception("Error en prepare() domicilio: " . $conn->error);
        }
        // Usamos $id_empleado al final en lugar del inexistente $id_domicilio
        $stmt_domicilio->bind_param("ssssii", $calle, $numero_exterior, $numero_interior, $colonia, $municipio, $id_empleado);
        $stmt_domicilio->execute();

        // --- 5. Actualizar correos (DELETE + INSERT es correcto) ---
        $stmt_del_correos = $conn->prepare("DELETE FROM correos WHERE ID_EMPLEADO = ?");
        $stmt_del_correos->bind_param("i", $id_empleado);
        $stmt_del_correos->execute();

        if (!empty($correo_principal)) {
            $sql_correo_p = "INSERT INTO correos (ID_EMPLEADO, CORREO_EMPLEADO, TIPO_CORREO) VALUES (?, ?, 'principal')";
            $stmt_correo_p = $conn->prepare($sql_correo_p);
            $stmt_correo_p->bind_param("is", $id_empleado, $correo_principal);
            $stmt_correo_p->execute();
        }
        if (!empty($correo_secundario)) {
            $sql_correo_s = "INSERT INTO correos (ID_EMPLEADO, CORREO_EMPLEADO, TIPO_CORREO) VALUES (?, ?, 'secundario')";
            $stmt_correo_s = $conn->prepare($sql_correo_s);
            $stmt_correo_s->bind_param("is", $id_empleado, $correo_secundario);
            $stmt_correo_s->execute();
        }

        // --- 6. Confirmar transacción ---
        $conn->commit();
        echo "<script>alert('Empleado actualizado exitosamente.'); window.location.href='../lista-usuarios.php';</script>";

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = json_encode($e->getMessage());
        echo "<script>alert('Error al actualizar empleado: ' + $error_message); window.history.back();</script>";
    } finally {
        if (isset($conn)) {
            $conn->close();
        }
    }
} else {
    echo "<script>alert('Acceso no autorizado.'); window.location.href='../lista-usuarios.php';</script>";
}
?>
