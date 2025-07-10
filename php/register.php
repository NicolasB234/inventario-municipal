<?php
// register.php
header('Content-Type: application/json'); // Indicamos que la respuesta será JSON
require_once 'db_connect.php'; // Incluimos el archivo de conexión a la base de datos

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $areaId = trim($_POST['area_id'] ?? ''); // Get area_id

    if (empty($username) || empty($password) || empty($areaId)) { // Validate areaId
        $response['message'] = 'Por favor, rellene todos los campos.';
        echo json_encode($response);
        exit();
    }

    // Hashear la contraseña antes de guardarla
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Verificar si el usuario ya existe
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $response['message'] = 'El nombre de usuario ya existe.';
    } else {
        // Insertar nuevo usuario incluyendo area_id
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash, area_id) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password_hash, $areaId); // 'sss' for three strings

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = '¡Registro exitoso! Ya puedes iniciar sesión.';
        } else {
            $response['message'] = 'Error al registrar el usuario: ' . $conn->error;
        }
    }
    $stmt->close();
} else {
    $response['message'] = 'Método de solicitud no válido.';
}

$conn->close();
echo json_encode($response);
?>