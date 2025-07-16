<?php
require_once 'db_connect.php';
// Incluimos el archivo que contiene la nueva función de log.
require_once 'log_activity.php'; 
header('Content-Type: application/json');
session_start();

$response = ['success' => false, 'message' => ''];
$is_admin = $_SESSION['is_admin'] ?? false;

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Acceso no autorizado.';
    echo json_encode($response);
    exit;
}

$id = $_POST['id'] ?? 0;

if ($id > 0) {
    // Primero, obtenemos la información del ítem para usar su nombre en el log.
    $stmt_get = $conn->prepare("SELECT * FROM inventory_items WHERE id = ?");
    $stmt_get->bind_param("i", $id);
    $stmt_get->execute();
    $result = $stmt_get->get_result();
    $item = $result->fetch_assoc();
    $stmt_get->close();

    if (!$item) {
        $response['message'] = 'El ítem no existe.';
        echo json_encode($response);
        exit;
    }

    if ($is_admin) {
        // El admin elimina directamente el registro y la imagen si existe.
        if (!empty($item['imagePath']) && file_exists('../' . $item['imagePath'])) {
            unlink('../' . $item['imagePath']);
        }
        
        $stmt_delete = $conn->prepare("DELETE FROM inventory_items WHERE id = ?");
        $stmt_delete->bind_param("i", $id);
        if ($stmt_delete->execute()) {
            $response['success'] = true;
            $response['message'] = 'Ítem eliminado correctamente.';
            
            // Registramos la acción del admin en el historial.
            $details = "Admin '{$_SESSION['username']}' eliminó directamente el ítem '{$item['name']}'.";
            log_activity($conn, $_SESSION['user_id'], $_SESSION['username'], 'item_deleted_admin', $details);
        } else {
            $response['message'] = 'Error al eliminar el ítem.';
        }
        $stmt_delete->close();
    } else {
        // El usuario normal crea una solicitud de eliminación.
        $action_data = json_encode(['item_id' => $id, 'item_name' => $item['name']]);
        $stmt_request = $conn->prepare(
            "INSERT INTO pending_actions (user_id, username, action_type, item_id, action_data) VALUES (?, ?, 'delete', ?, ?)"
        );
        $stmt_request->bind_param("isis", $_SESSION['user_id'], $_SESSION['username'], $id, $action_data);
        
        if ($stmt_request->execute()) {
            $response['success'] = true;
            $response['message'] = 'Solicitud de eliminación enviada para aprobación.';

            // Registramos la solicitud del usuario en el historial.
            $details = "Usuario '{$_SESSION['username']}' solicitó eliminar el ítem '{$item['name']}'.";
            log_activity($conn, $_SESSION['user_id'], $_SESSION['username'], 'request_delete', $details);
        } else {
            $response['message'] = 'Error al enviar la solicitud.';
        }
        $stmt_request->close();
    }
} else {
    $response['message'] = 'ID de ítem no válido.';
}

$conn->close();
echo json_encode($response);
?>