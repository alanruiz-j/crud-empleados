<?php

// Helpers de UI para manejo de valores, estados y errores en formularios

function ui_get_value($form_field_name, $db_field_name = null, $default = '') {
    global $form_data, $empleado_data, $edit_mode;
    if (isset($form_data[$form_field_name])) {
        return htmlspecialchars($form_data[$form_field_name]);
    }
    if ($edit_mode) {
        if ($db_field_name && isset($empleado_data[$db_field_name])) {
            return htmlspecialchars($empleado_data[$db_field_name]);
        }
        if (isset($empleado_data[$form_field_name])) {
            return htmlspecialchars($empleado_data[$form_field_name]);
        }
    }
    return $default;
}

function ui_has_error($field) {
    global $errores;
    return isset($errores[$field]) ? 'is-invalid' : '';
}

function ui_has_success($field) {
    global $errores, $form_data, $_POST;
    $hasValue = !empty($form_data[$field]) || (!empty($_POST[$field]) && !isset($errores[$field]));
    return !isset($errores[$field]) && $hasValue ? 'is-valid' : '';
}

function ui_show_error($field) {
    global $errores;
    if (isset($errores[$field])) {
        return '<div class="invalid-feedback">' . htmlspecialchars($errores[$field]) . '</div>';
    }
    return '';
}

?>


