<?php
// Endpoint AJAX: devuelve opciones HTML de estados o municipios según selección.
include 'modelo/conexion.php';
require_once 'modelo/repositorio.php';

header('Content-Type: text/html; charset=utf-8');

try {
    if (isset($_POST['pais_id']) && !empty($_POST['pais_id'])) {
        $pais_id = intval($_POST['pais_id']);
        $estados = get_catalog($conn, 'estados', 'ID_ESTADO', 'NOMBRE_ESTADO', 'WHERE ID_PAIS = ' . $pais_id . ' AND ESTADO_ESTADO = 1');
        echo '<option value="" class="select-placeholder">Seleccione un Estado...</option>';
        if (!empty($estados)) {
            foreach ($estados as $row) {
                echo '<option value="' . $row['ID_ESTADO'] . '">' . htmlspecialchars($row['NOMBRE_ESTADO']) . '</option>';
            }
        } else {
            echo '<option value="" disabled>No hay estados disponibles</option>';
        }
    } elseif (isset($_POST['estado_id']) && !empty($_POST['estado_id'])) {
        $estado_id = intval($_POST['estado_id']);
        $municipios = get_catalog($conn, 'municipios', 'ID_MUNICIPIO', 'NOMBRE_MUNICIPIO', 'WHERE ID_ESTADO = ' . $estado_id . ' AND ESTADO_MUNICIPIO = 1');
        echo '<option value="" class="select-placeholder">Seleccione un Municipio...</option>';
        if (!empty($municipios)) {
            foreach ($municipios as $row) {
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