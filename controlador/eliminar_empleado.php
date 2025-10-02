<?php

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    
    include '../modelo/conexion.php';
    $id_empleado = intval($_GET['id']);

    // Iniciar una transacción
    $conn->begin_transaction();

    try {
        // --- 1. Eliminar los correos asociados al empleado ---
        // Se usa una sentencia preparada para mayor seguridad
        $stmt_correos = $conn->prepare("DELETE FROM correos WHERE ID_EMPLEADO = ?");
        $stmt_correos->bind_param("i", $id_empleado);
        $stmt_correos->execute();

        // --- 2. Eliminar el domicilio asociado al empleado ---
        $stmt_domicilio = $conn->prepare("DELETE FROM domicilios WHERE ID_EMPLEADO = ?");
        $stmt_domicilio->bind_param("i", $id_empleado);
        $stmt_domicilio->execute();

        // --- 3. Finalmente, eliminar al empleado ---
        // Esto debe hacerse al final porque las otras tablas dependen de este ID.
        $stmt_empleado = $conn->prepare("DELETE FROM empleados WHERE ID_EMPLEADO = ?");
        $stmt_empleado->bind_param("i", $id_empleado);
        $stmt_empleado->execute();
        
        // Si todas las eliminaciones fueron exitosas, se confirman los cambios
        $conn->commit();

        echo "<script>
                alert('Empleado eliminado exitosamente.');
                window.location.href = '../index.php';
              </script>";

    } catch (Exception $e) {
        // Si algo falla, se revierten todos los cambios
        $conn->rollback();
        $error_message = json_encode("Error al eliminar el empleado: " . $e->getMessage());
        
        echo "<script>
                alert('Error al eliminar el empleado: ' + $error_message);
                window.location.href = '../index.php';
              </script>";
    } finally {
        // Se cierra la conexión en cualquier caso
        if (isset($conn)) {
            $conn->close();
        }
    }

} else {
    echo "<script>
            alert('Acceso no autorizado o ID de empleado no válido.');
            window.location.href = '../index.php';
          </script>";
}
?>