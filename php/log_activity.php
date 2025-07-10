<?php
require_once 'db_connect.php';
session_start();

// Este script no devuelve nada al frontend, solo registra la acción.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? null;
    $username = $_SESSION['username'] ?? 'Sistema';
    $area_id = $_SESSION['area_id'] ?? null;
    
    $action_type = $_POST['type'] ?? 'unknown';
    $details = $_POST['message'] ?? '';

    if (!empty($details)) {
        try {
            $stmt = $conn->prepare(
                "INSERT INTO activity_log (user_id, username, area_id, action_type, details) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("issss", $user_id, $username, $area_id, $action_type, $details);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            // No hacemos nada para no interrumpir al usuario, pero se podría registrar en un log de errores del servidor.
            error_log('Error al registrar actividad: ' . $e->getMessage());
        }
    }
}
$conn->close();
?>