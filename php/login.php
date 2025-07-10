<?php
// login.php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

$response = ['success' => false, 'message' => '', 'areaId' => null, 'isAdmin' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $response['message'] = 'Por favor, introduzca el usuario y la contraseña.';
        echo json_encode($response);
        exit();
    }

    // Buscar el usuario en la base de datos y obtener el area_id
    $stmt = $conn->prepare("SELECT id, password_hash, area_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Verificar la contraseña hasheada
        if (password_verify($password, $user['password_hash'])) {
            // Contraseña correcta, iniciar sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            $_SESSION['area_id'] = $user['area_id'];
            
            if ($username === 'admin') {
                $_SESSION['is_admin'] = true;
            } else {
                unset($_SESSION['is_admin']);
            }

            $response['success'] = true;
            $response['message'] = 'Inicio de sesión exitosa.';
            $response['areaId'] = $user['area_id']; // CORREGIDO
            $response['isAdmin'] = ($username === 'admin');
        } else {
            $response['message'] = 'Usuario o contraseña incorrecta.'; // CORREGIDO
        }
    } else {
        $response['message'] = 'Usuario o contraseña incorrectos.'; // CORREGIDO
    }
    $stmt->close(); // CORREGIDO
} else {
    $response['message'] = 'Método de solicitud no válido.'; // CORREGIDO
}

$conn->close(); // CORREGIDO
echo json_encode($response); // CORREGIDO
?>