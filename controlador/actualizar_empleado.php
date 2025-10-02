    <?php
    if (!empty($_POST['actualizar_empleado']) && $_POST['actualizar_empleado'] == 'ok') {

        include '../modelo/conexion.php';
        $conn->begin_transaction();

        try {
            // --- 1. Recoger datos del formulario ---
            $id_empleado       = intval($_POST['id_empleado']);
            $contratante       = intval($_POST['contratante_empleado']);
            $nombre            = $_POST['nombre_empleado'];
            $apellido_paterno  = $_POST['apellido_paterno_empleado'];
            $apellido_materno  = $_POST['apellido_materno_empleado'];
            $genero            = $_POST['genero_empleado'];
            $departamento      = $_POST['departamento_empleado'];
            $curp              = $_POST['curp_empleado'];
            $rfc               = $_POST['rfc_empleado'];
            $correo_principal  = $_POST['correo_principal_empleado'];
            $correo_secundario = $_POST['correo_secundario_empleado'];
            $telefono          = $_POST['telefono_empleado'];
            $calle             = $_POST['calle_empleado'];
            $numero_exterior   = $_POST['numero_exterior_empleado'];
            $numero_interior   = $_POST['numero_interior_empleado'];
            $colonia           = $_POST['colonia_empleado'];
            $municipio         = $_POST['municipio_empleado'];

            // --- 2. Verificar que el empleado existe ---
            $check = $conn->prepare("SELECT ID_DOMICILIO FROM empleados WHERE ID_EMPLEADO = ?");
            $check->bind_param("i", $id_empleado);
            $check->execute();
            $result = $check->get_result();
            if ($result->num_rows === 0) {
                throw new Exception("El empleado con ID $id_empleado no existe.");
            }
            $row = $result->fetch_assoc();
            $id_domicilio = $row['ID_DOMICILIO']; // para actualizar domicilio

            // --- 3. Actualizar empleado ---
            $sql_empleado = "UPDATE empleados 
                            SET NOMBRE_EMPLEADO = ?, 
                                APELLIDO_PATERNO = ?, 
                                APELLIDO_MATERNO = ?, 
                                ID_GENERO = ?, 
                                CURP_EMPLEADO = ?, 
                                RFC_EMPLEADO = ?, 
                                TELEFONO_EMPLEADO = ?, 
                                CONTRATANTE = ?, 
                                ID_DEPARTAMENTO = ?
                            WHERE ID_EMPLEADO = ?";
            $stmt_empleado = $conn->prepare($sql_empleado);
            if (!$stmt_empleado) {
                throw new Exception("Error en prepare() empleado: " . $conn->error . " | SQL: " . $sql_empleado);
            }
            $stmt_empleado->bind_param(
                "sssisssiii",
                $nombre,
                $apellido_paterno,
                $apellido_materno,
                $genero,
                $curp,
                $rfc,
                $telefono,
                $contratante,
                $departamento,
                $id_empleado
            );
            $stmt_empleado->execute();

            // --- 4. Actualizar domicilio ---
            $sql_domicilio = "UPDATE domicilios 
                            SET CALLE = ?, 
                                NUMERO_EXTERIOR = ?, 
                                NUMERO_INTERIOR = ?, 
                                COLONIA = ?, 
                                ID_MUNICIPIO = ?
                            WHERE ID_DOMICILIO = ?";
            $stmt_domicilio = $conn->prepare($sql_domicilio);
            if (!$stmt_domicilio) {
                throw new Exception("Error en prepare() domicilio: " . $conn->error . " | SQL: " . $sql_domicilio);
            }
            $stmt_domicilio->bind_param("ssssii", $calle, $numero_exterior, $numero_interior, $colonia, $municipio, $id_domicilio);
            $stmt_domicilio->execute();

            // --- 5. Actualizar correos ---
            // Primero eliminar los existentes para evitar duplicados
            $conn->query("DELETE FROM correos WHERE ID_EMPLEADO = $id_empleado");

            if (!empty($correo_principal)) {
                $sql_correo_p = "INSERT INTO correos (ID_EMPLEADO, CORREO_EMPLEADO, TIPO_CORREO) VALUES (?, ?, 'PRINCIPAL')";
                $stmt_correo_p = $conn->prepare($sql_correo_p);
                $stmt_correo_p->bind_param("is", $id_empleado, $correo_principal);
                $stmt_correo_p->execute();
            }
            if (!empty($correo_secundario)) {
                $sql_correo_s = "INSERT INTO correos (ID_EMPLEADO, CORREO_EMPLEADO, TIPO_CORREO) VALUES (?, ?, 'SECUNDARIO')";
                $stmt_correo_s = $conn->prepare($sql_correo_s);
                $stmt_correo_s->bind_param("is", $id_empleado, $correo_secundario);
                $stmt_correo_s->execute();
            }

            // --- 6. Confirmar transacción ---
            $conn->commit();
            echo "<script>alert('Empleado actualizado exitosamente.'); window.location.href='../lista-usuarios.php';</script>";

        } catch (Exception $e) {
            $conn->rollback();
            echo "<pre>Error al actualizar empleado: " . $e->getMessage() . "</pre>";
        } finally {
            if (isset($conn)) {
                $conn->close();
            }
        }
    } else {
        echo "<script>alert('Acceso no autorizado.'); window.location.href='../lista-usuarios.php';</script>";
    }
    ?>
