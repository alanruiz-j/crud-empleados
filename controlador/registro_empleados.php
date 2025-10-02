    <?php

    if (!empty($_POST['guardar_empleado']) && $_POST['guardar_empleado'] == 'ok') {

        include '../modelo/conexion.php';

        // Iniciar transacción
        $conn->begin_transaction();

        try {
            // --- 1. Recoger los datos ---
            $contratante = !empty($_POST['contratante_empleado']) ? $_POST['contratante_empleado'] : null;
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
            // Rol (parece faltar en tu formulario)
            $id_rol             = 1; // Asigna un rol por defecto o recógelo del POST

            // --- 2. Validación (esto está bien) ---
            if (empty($nombre) || (empty($apellido_paterno) && empty($apellido_materno)) || 
                empty($telefono) || empty($calle) || empty($numero_exterior) || 
                empty($colonia) || empty($municipio)) {
                throw new Exception("Por favor, complete todos los campos obligatorios.");
            }

            // --- 3. PRIMERO: Insertar empleado ---
            // (Quitamos ID_DOMICILIO de aquí)
            $sql_empleado = "INSERT INTO empleados (NOMBRE_EMPLEADO, APELLIDO_PATERNO, APELLIDO_MATERNO, ID_GENERO, CURP_EMPLEADO, RFC_EMPLEADO, TELEFONO_EMPLEADO, CONTRATANTE, FECHA_CONTRATACION, ID_DEPARTAMENTO, ID_ROL) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_empleado = $conn->prepare($sql_empleado);
            if (!$stmt_empleado) {
                throw new Exception("Error en prepare() empleado: " . $conn->error);
            }
            $stmt_empleado->bind_param("sssisssisii", $nombre, $apellido_paterno, $apellido_materno, $genero, $curp, $rfc, $telefono, $contratante, $fecha_contratacion, $departamento, $id_rol);
            $stmt_empleado->execute();
            $id_empleado_insertado = $conn->insert_id; // ¡Este es el ID que necesitamos!

            // --- 4. SEGUNDO: Insertar domicilio usando el ID del nuevo empleado ---
            // (Añadimos ID_EMPLEADO aquí)
            $sql_domicilio = "INSERT INTO domicilios (ID_EMPLEADO, CALLE, NUMERO_EXTERIOR, NUMERO_INTERIOR, COLONIA, ID_MUNICIPIO) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_domicilio = $conn->prepare($sql_domicilio);
            if (!$stmt_domicilio) {
                throw new Exception("Error en prepare() domicilio: " . $conn->error);
            }
            $stmt_domicilio->bind_param("issssi", $id_empleado_insertado, $calle, $numero_exterior, $numero_interior, $colonia, $municipio);
            $stmt_domicilio->execute();
            
            // --- 5. Insertar correos (esto está bien) ---
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
            // Usamos json_encode para escapar caracteres especiales en el mensaje de error para JavaScript
            $error_message = json_encode($e->getMessage());
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