<?php
// Capa de acceso a datos para empleados y catálogos.
// Centraliza SQL y operaciones transaccionales para mantener los controladores limpios.

require_once __DIR__ . '/conexion.php';

class EmployeeRepository {
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    // Manejo de transacciones
    public function begin(): void { $this->conn->begin_transaction(); }
    public function commit(): void { $this->conn->commit(); }
    public function rollback(): void { $this->conn->rollback(); }

    /** Inserta un empleado y devuelve su ID. */
    public function insertEmployee(array $data, int $contratante, int $idRol): int {
        $sql = "INSERT INTO empleados (NOMBRE_EMPLEADO, APELLIDO_PATERNO, APELLIDO_MATERNO, ID_GENERO, CURP_EMPLEADO, RFC_EMPLEADO, TELEFONO_EMPLEADO, CONTRATANTE, FECHA_CONTRATACION, ID_DEPARTAMENTO, ID_ROL)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'sssisssisii',
            $data['nombre'], $data['apellido_paterno'], $data['apellido_materno'], $data['genero'],
            $data['curp'], $data['rfc'], $data['telefono'], $contratante, $data['fecha_contratacion'], $data['departamento'], $idRol
        );
        $stmt->execute();
        return $this->conn->insert_id;
    }

    /** Actualiza datos de empleado (incluye ID_ROL). */
    public function upsertEmployee(int $idEmpleado, array $data, ?int $contratante): void {
        $sql = "UPDATE empleados SET NOMBRE_EMPLEADO = ?, APELLIDO_PATERNO = ?, APELLIDO_MATERNO = ?, ID_GENERO = ?, CURP_EMPLEADO = ?, RFC_EMPLEADO = ?, TELEFONO_EMPLEADO = ?, FECHA_CONTRATACION = ?, ID_DEPARTAMENTO = ?, CONTRATANTE = ?, ID_ROL = ? WHERE ID_EMPLEADO = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'sssissssiiii',
            $data['nombre'], $data['apellido_paterno'], $data['apellido_materno'], $data['genero'],
            $data['curp'], $data['rfc'], $data['telefono'], $data['fecha_contratacion'], $data['departamento'], $contratante, $data['rol'], $idEmpleado
        );
        $stmt->execute();
    }

    /** Inserta/actualiza el domicilio del empleado. */
    public function upsertAddress(int $idEmpleado, array $data): void {
        $sql = "INSERT INTO domicilios (ID_EMPLEADO, CALLE, NUMERO_EXTERIOR, NUMERO_INTERIOR, COLONIA, ID_MUNICIPIO)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE CALLE=VALUES(CALLE), NUMERO_EXTERIOR=VALUES(NUMERO_EXTERIOR), NUMERO_INTERIOR=VALUES(NUMERO_INTERIOR), COLONIA=VALUES(COLONIA), ID_MUNICIPIO=VALUES(ID_MUNICIPIO)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('issssi', $idEmpleado, $data['calle'], $data['numero_exterior'], $data['numero_interior'], $data['colonia'], $data['municipio']);
        $stmt->execute();
    }

    /** Inserta el domicilio del empleado. */
    public function insertAddress(int $idEmpleado, array $data): void {
        $sql = "INSERT INTO domicilios (ID_EMPLEADO, CALLE, NUMERO_EXTERIOR, NUMERO_INTERIOR, COLONIA, ID_MUNICIPIO) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('issssi', $idEmpleado, $data['calle'], $data['numero_exterior'], $data['numero_interior'], $data['colonia'], $data['municipio']);
        $stmt->execute();
    }

    /** Inserta/actualiza un correo del empleado por tipo (principal/secundario). */
    public function upsertEmail(int $idEmpleado, string $correo, string $tipo): void {
        $sql = "INSERT INTO correos (ID_EMPLEADO, CORREO_EMPLEADO, TIPO_CORREO)
                VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE CORREO_EMPLEADO = VALUES(CORREO_EMPLEADO)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('iss', $idEmpleado, $correo, $tipo);
        $stmt->execute();
    }

    /** Inserta un correo del empleado. */
    public function insertEmail(int $idEmpleado, string $correo, string $tipo): void {
        $sql = "INSERT INTO correos (ID_EMPLEADO, CORREO_EMPLEADO, TIPO_CORREO) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('iss', $idEmpleado, $correo, $tipo);
        $stmt->execute();
    }

    /** Elimina todos los correos asociados a un empleado. */
    public function deleteEmailsByEmployee(int $idEmpleado): void {
        $stmt = $this->conn->prepare('DELETE FROM correos WHERE ID_EMPLEADO = ?');
        $stmt->bind_param('i', $idEmpleado);
        $stmt->execute();
    }

    /** Elimina el domicilio del empleado. */
    public function deleteAddressByEmployee(int $idEmpleado): void {
        $stmt = $this->conn->prepare('DELETE FROM domicilios WHERE ID_EMPLEADO = ?');
        $stmt->bind_param('i', $idEmpleado);
        $stmt->execute();
    }

    /** Elimina al empleado. */
    public function deleteEmployee(int $idEmpleado): void {
        $stmt = $this->conn->prepare('DELETE FROM empleados WHERE ID_EMPLEADO = ?');
        $stmt->bind_param('i', $idEmpleado);
        $stmt->execute();
    }

    /** Ejecuta una consulta de duplicados y retorna si existen filas. */
    public function hasDuplicate(mysqli $conn, string $sql, array $params, string $types): bool {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
}

/** Obtiene un catálogo simple id/nombre, con cláusula WHERE opcional ordenada por nombre. */
function get_catalog(mysqli $conn, string $table, string $idField, string $nameField, string $where = ''): array {
    $data = [];
    $sql = "SELECT $idField, $nameField FROM $table $where ORDER BY $nameField";
    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) { $data[] = $row; }
        $result->free();
    }
    return $data;
}

?>


