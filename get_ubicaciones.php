<?php
include 'modelo/conexion.php';

header('Content-Type: text/html; charset=utf-8');

try {
    if (isset($_POST['pais_id']) && !empty($_POST['pais_id'])) {
        $pais_id = intval($_POST['pais_id']);
        $sql = $conn->prepare("SELECT ID_ESTADO, NOMBRE_ESTADO FROM estados WHERE ID_PAIS = ? AND ESTADO_ESTADO = 1 ORDER BY NOMBRE_ESTADO");
        $sql->bind_param("i", $pais_id);
        $sql->execute();
        $result = $sql->get_result();
        
        echo '<option value="" class="select-placeholder">Seleccione un Estado...</option>';
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<option value="' . $row['ID_ESTADO'] . '">' . htmlspecialchars($row['NOMBRE_ESTADO']) . '</option>';
            }
        } else {
            echo '<option value="" disabled>No hay estados disponibles</option>';
        }
    } elseif (isset($_POST['estado_id']) && !empty($_POST['estado_id'])) {
        $estado_id = intval($_POST['estado_id']);
        $sql = $conn->prepare("SELECT ID_MUNICIPIO, NOMBRE_MUNICIPIO FROM municipios WHERE ID_ESTADO = ? AND ESTADO_MUNICIPIO = 1 ORDER BY NOMBRE_MUNICIPIO");
        $sql->bind_param("i", $estado_id);
        $sql->execute();
        $result = $sql->get_result();
        
        echo '<option value="" class="select-placeholder">Seleccione un Municipio...</option>';
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<option value="' . $row['ID_MUNICIPIO'] . '">' . htmlspecialchars($row['NOMBRE_MUNICIPIO']) . '</option>';
            }
        } else {
            echo '<option value="" disabled>No hay municipios disponibles</option>';
        }
    }
} catch (Exception $e) {
    echo '<option value="" disabled>Error al cargar datos</option>';
}
?>