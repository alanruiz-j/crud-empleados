<?php
session_start();

// CONEXIÓN DIRECTA - mejor usar esto para evitar problemas de rutas
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "empleados";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($nombre === '' || $password === '') {
    $_SESSION['error'] = "Rellena nombre de usuario y contraseña.";
    header('Location: login.php');
    exit;
}

// CONSULTA CORREGIDA: JOIN con ROLES para obtener la contraseña
$sql = "SELECT e.ID_EMPLEADO, e.NOMBRE_EMPLEADO, e.APELLIDO_PATERNO, e.ID_ROL, 
               r.PASSWORD_ROL 
        FROM EMPLEADOS e 
        INNER JOIN ROLES r ON e.ID_ROL = r.ID_ROL 
        WHERE e.NOMBRE_EMPLEADO = ? AND e.ID_ROL = 1 
        LIMIT 1";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('s', $nombre);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id_empleado, $nombre_empleado, $apellido, $id_rol, $password_rol);
        $stmt->fetch();

        // Verificar la contraseña (comparación directa con la de ROLES)
        if ($password === $password_rol) {
            // Login OK
            session_regenerate_id(true);
            $_SESSION['id_usuario'] = $id_empleado;
            $_SESSION['user_name'] = $nombre_empleado . ' ' . $apellido;
            $_SESSION['id_rol'] = $id_rol;

            header('Location: index.php');
            exit;
        } else {
            $_SESSION['error'] = "Contraseña incorrecta.";
        }
    } else {
        $_SESSION['error'] = "Usuario no encontrado o no tiene permisos de administrador.";
    }
    $stmt->close();
} else {
    $_SESSION['error'] = "Error en la consulta: " . $conn->error;
}

$conn->close();
header('Location: login.php');
exit;
?>