<?php
// Incluye tu archivo de conexión a la base de datos
include 'modelo/conexion.php';

// --- Lógica para obtener Estados ---
if (!empty($_POST["pais_id"])) {
    $paisId = $conn->real_escape_string($_POST['pais_id']);
    
    // Prepara la consulta SQL para obtener los estados de ese país
    $query = $conn->query("SELECT ID_ESTADO, NOMBRE_ESTADO FROM estados WHERE ID_PAIS = $paisId ORDER BY NOMBRE_ESTADO ASC");

    // Verifica si se encontraron estados
    if ($query->num_rows > 0) {
        echo '<option value="">Seleccione un Estado...</option>';
        while ($row = $query->fetch_assoc()) {
            echo '<option value="' . $row['ID_ESTADO'] . '">' . $row['NOMBRE_ESTADO'] . '</option>';
        }
    } else {
        echo '<option value="">No hay estados disponibles</option>';
    }
} 
// --- Lógica para obtener Municipios ---
elseif (!empty($_POST["estado_id"])) {
    // Escapa el ID del estado para seguridad
    $estadoId = $conn->real_escape_string($_POST['estado_id']);

    // Prepara la consulta SQL para obtener los municipios de ese estado
    $query = $conn->query("SELECT ID_MUNICIPIO, NOMBRE_MUNICIPIO FROM municipios WHERE ID_ESTADO = $estadoId ORDER BY NOMBRE_MUNICIPIO ASC");

    // Verifica si se encontraron municipios
    if ($query->num_rows > 0) {
        echo '<option value="">Seleccione un Municipio...</option>';
        while ($row = $query->fetch_assoc()) {
            echo '<option value="' . $row['ID_MUNICIPIO'] . '">' . $row['NOMBRE_MUNICIPIO'] . '</option>';
        }
    } else {
        echo '<option value="">No hay municipios disponibles</option>';
    }
}
?>