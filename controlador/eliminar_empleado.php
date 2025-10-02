<?php

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    include '../modelo/conexion.php';
    require_once '../modelo/repositorio.php';
    $id_empleado = intval($_GET['id']);

    try {
        $repo = new EmployeeRepository($conn);
        $repo->begin();

        $repo->deleteEmailsByEmployee($id_empleado);
        $repo->deleteAddressByEmployee($id_empleado);
        $repo->deleteEmployee($id_empleado);

        $repo->commit();

        echo "<script>
                alert('Empleado eliminado exitosamente.');
                window.location.href = '../index.php';
              </script>";

    } catch (Exception $e) {
        if (isset($repo)) { $repo->rollback(); }
        $error_message = json_encode('Error al eliminar el empleado: ' . $e->getMessage());
        echo "<script>
                alert('Error al eliminar el empleado: ' + $error_message);
                window.location.href = '../index.php';
              </script>";
    } finally {
        if (isset($conn)) { $conn->close(); }
    }

} else {
    echo "<script>
            alert('Acceso no autorizado o ID de empleado no válido.');
            window.location.href = '../index.php';
          </script>";
}
?>