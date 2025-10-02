<?php
if (!empty($_GET['id'])) {
    include '../modelo/conexion.php';
    $id_empleado = intval($_GET['id']);

    $conn->begin_transaction();

    try {
        // Primero obtenemos el ID_DOMICILIO para eliminar después
        $res = $conn->query("SELECT ID_DOMICILIO FROM empleados WHERE ID_EMPLEADO = $id_empleado");
        if ($res->num_rows === 0) {
            throw new Exception("Empleado no encontrado.");
        }
        $row = $res->fetch_assoc();
        $id_domicilio = $row['ID_DOMICILIO'];

        // Eliminar correos
        $conn->query("DELETE FROM correos WHERE ID_EMPLEADO = $id_empleado");

        // Eliminar empleado
        $conn->query("DELETE FROM empleados WHERE ID_EMPLEADO = $id_empleado");

        // Eliminar domicilio
        $conn->query("DELETE FROM domicilios WHERE ID_DOMICILIO = $id_domicilio");

        $conn->commit();
        echo "<script>alert('Empleado eliminado exitosamente.'); window.location.href='../lista-usuarios.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error al eliminar empleado: " . $e->getMessage() . "'); window.location.href='../lista-usuarios.php';</script>";
    } finally {
        $conn->close();
    }
} else {
    echo "<script>alert('ID de empleado no proporcionado.'); window.location.href='../lista-usuarios.php';</script>";
}
?>
