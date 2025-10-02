<?php

function sanitize_string($value) {
    return trim((string)($value ?? ''));
}

function sanitize_upper($value) {
    return strtoupper(sanitize_string($value));
}

function validate_required(array &$errors, string $key, $value, string $message) {
    if ($value === null || $value === '' ) {
        $errors[$key] = $message;
    }
}

function validate_regex(array &$errors, string $key, $value, string $pattern, string $message) {
    if ($value !== '' && !preg_match($pattern, $value)) {
        $errors[$key] = $message;
    }
}

function validate_email(array &$errors, string $key, $value, string $message) {
    if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        $errors[$key] = $message;
    }
}

function validate_length_max(array &$errors, string $key, $value, int $max, string $message) {
    if ($value !== '' && strlen($value) > $max) {
        $errors[$key] = $message;
    }
}

function collect_employee_input(array $source): array {
    return [
        'nombre' => sanitize_string($source['nombre_empleado'] ?? ''),
        'apellido_paterno' => sanitize_string($source['apellido_paterno_empleado'] ?? ''),
        'apellido_materno' => sanitize_string($source['apellido_materno_empleado'] ?? ''),
        'genero' => sanitize_string($source['genero_empleado'] ?? ''),
        'departamento' => sanitize_string($source['departamento_empleado'] ?? ''),
        'rol' => sanitize_string($source['rol_empleado'] ?? ''),
        'curp' => sanitize_upper($source['curp_empleado'] ?? ''),
        'rfc' => sanitize_upper($source['rfc_empleado'] ?? ''),
        'correo_principal' => sanitize_string($source['correo_principal_empleado'] ?? ''),
        'correo_secundario' => sanitize_string($source['correo_secundario_empleado'] ?? ''),
        'telefono' => sanitize_string($source['telefono_empleado'] ?? ''),
        'calle' => sanitize_string($source['calle_empleado'] ?? ''),
        'numero_exterior' => sanitize_string($source['numero_exterior_empleado'] ?? ''),
        'numero_interior' => sanitize_string($source['numero_interior_empleado'] ?? ''),
        'colonia' => sanitize_string($source['colonia_empleado'] ?? ''),
        'municipio' => sanitize_string($source['municipio_empleado'] ?? ''),
        'fecha_contratacion' => !empty($source['fecha_contratacion_empleado']) ? $source['fecha_contratacion_empleado'] : date('Y-m-d'),
    ];
}

function validate_employee_input(array $data): array {
    $errors = [];
    validate_required($errors, 'nombre_empleado', $data['nombre'], 'El nombre es obligatorio.');
    validate_required($errors, 'genero_empleado', $data['genero'], 'Debe seleccionar un género.');
    validate_required($errors, 'departamento_empleado', $data['departamento'], 'Debe seleccionar un departamento.');
    validate_required($errors, 'rol_empleado', $data['rol'], 'Debe seleccionar un rol.');
    validate_required($errors, 'curp_empleado', $data['curp'], 'El CURP es obligatorio.');
    validate_required($errors, 'rfc_empleado', $data['rfc'], 'El RFC es obligatorio.');
    validate_required($errors, 'telefono_empleado', $data['telefono'], 'El teléfono es obligatorio.');
    validate_required($errors, 'correo_principal_empleado', $data['correo_principal'], 'El correo principal es obligatorio.');
    validate_required($errors, 'calle_empleado', $data['calle'], 'La calle es obligatoria.');
    validate_required($errors, 'numero_exterior_empleado', $data['numero_exterior'], 'El número exterior es obligatorio.');
    validate_required($errors, 'colonia_empleado', $data['colonia'], 'La colonia es obligatoria.');
    validate_required($errors, 'municipio_empleado', $data['municipio'], 'Debe seleccionar un municipio.');

    if ($data['apellido_paterno'] === '' && $data['apellido_materno'] === '') {
        $errors['apellido_paterno_empleado'] = 'Debe proporcionar al menos un apellido.';
    }

    validate_regex($errors, 'curp_empleado', $data['curp'], '/^([A-Z][AEIOUX][A-Z]{2}[0-9]{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12][0-9]|3[01])[HM](?:AS|B[CS]|C[CLMSH]|D[FG]|G[TR]|HG|JC|M[CNS]|N[ETL]|OC|PL|Q[TR]|S[PLR]|T[CSL]|VZ|YN|ZS)[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9])([0-9])$/', 'El formato del CURP no es válido.');
    validate_regex($errors, 'rfc_empleado', $data['rfc'], '/^[A-ZÑ&]{3,4}[0-9]{6}(?:[A-Z0-9]{3})?$/', 'El formato del RFC no es válido.');
    validate_regex($errors, 'telefono_empleado', $data['telefono'], '/^[0-9]{10}$/', 'El teléfono debe contener 10 dígitos.');
    validate_email($errors, 'correo_principal_empleado', $data['correo_principal'], 'El formato del correo principal no es válido.');
    if ($data['correo_secundario'] !== '') {
        validate_email($errors, 'correo_secundario_empleado', $data['correo_secundario'], 'El formato del correo secundario no es válido.');
    }

    validate_length_max($errors, 'nombre_empleado', $data['nombre'], 255, 'El nombre excede la longitud permitida.');

    return $errors;
}

?>


