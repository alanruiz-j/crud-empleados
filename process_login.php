<?php
session_start();

// CONEXIÓN DIRECTA
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "empleados";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    // Usar die() aquí es aceptable para un error crítico de conexión.
    die("Error de conexión: " . $conn->connect_error);
}

// Validar que la solicitud sea por método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$password_form = isset($_POST['password']) ? $_POST['password'] : ''; // Renombrada para claridad

// Validar que los campos no estén vacíos
if (empty($nombre) || empty($password_form)) {
    $_SESSION['error'] = "Rellena nombre de usuario y contraseña.";
    header('Location: login.php');
    exit; // Salir inmediatamente
}

$sql = "SELECT e.ID_EMPLEADO, e.NOMBRE_EMPLEADO, e.APELLIDO_PATERNO, e.ID_ROL, r.PASSWORD_ROL 
        FROM EMPLEADOS e 
        INNER JOIN ROLES r ON e.ID_ROL = r.ID_ROL 
        WHERE e.NOMBRE_EMPLEADO = ? AND e.ID_ROL = 1 
        LIMIT 1";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('s', $nombre);
    $stmt->execute();
    $result = $stmt->get_result(); // Usar get_result() es más limpio
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verificar la contraseña
        if ($password_form === $user['PASSWORD_ROL']) {
            // Login OK
            session_regenerate_id(true);
            $_SESSION['id_usuario'] = $user['ID_EMPLEADO'];
            $_SESSION['user_name'] = $user['NOMBRE_EMPLEADO'] . ' ' . $user['APELLIDO_PATERNO'];
            $_SESSION['id_rol'] = $user['ID_ROL'];

            header('Location: index.php');
            exit; // Salir inmediatamente después del éxito
        } else {
            // Contraseña incorrecta
            $_SESSION['error'] = "Contraseña incorrecta.";
            header('Location: login.php');
            exit; // Salir inmediatamente
        }
    } else {
        // Usuario no encontrado
        $_SESSION['error'] = "Usuario no encontrado o no tiene permisos de administrador.";
        header('Location: login.php');
        exit; // Salir inmediatamente
    }
    $stmt->close();
} else {
    // Error en la preparación de la consulta
    $_SESSION['error'] = "Error en el sistema. Intente de nuevo.";
    header('Location: login.php');
    exit; // Salir inmediatamente
}

$conn->close();

?>