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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido.';
    echo json_encode($response);
    exit;
}

$itemId = $_POST['itemId'] ?? 0;
$destinationNodeId = $_POST['destinationNodeId'] ?? '';
$reason = $_POST['reason'] ?? '';

if (empty($itemId) || empty($destinationNodeId)) {
    $response['message'] = 'Faltan datos para realizar el traspaso.';
    echo json_encode($response);
    exit;
}

// Obtener info del ítem para guardarla en la solicitud y usarla en el log.
$stmt_item = $conn->prepare("SELECT name FROM inventory_items WHERE id = ?");
$stmt_item->bind_param("i", $itemId);
$stmt_item->execute();
$item_result = $stmt_item->get_result();
$item = $item_result->fetch_assoc();
$stmt_item->close();

if (!$item) {
    $response['message'] = 'El ítem a traspasar no existe.';
    echo json_encode($response);
    exit;
}

$action_data = [
    'item_id' => $itemId,
    'item_name' => $item['name'],
    'destination_node_id' => $destinationNodeId,
    'reason' => $reason
];


if ($is_admin) {
    // El admin traspasa directamente.
    $conn->begin_transaction();
    try {
        $stmt_update = $conn->prepare("UPDATE inventory_items SET node_id = ? WHERE id = ?");
        $stmt_update->bind_param("si", $destinationNodeId, $itemId);
        
        if ($stmt_update->execute()) {
            $stmt_update->close();
            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Ítem traspasado correctamente.';

            // Registramos la acción del admin en el historial.
            $details = "Admin '{$_SESSION['username']}' traspasó directamente el ítem '{$item['name']}'.";
            log_activity($conn, $_SESSION['user_id'], $_SESSION['username'], 'item_transferred_admin', $details);
        } else {
            throw new Exception('No se pudo actualizar el área del ítem.');
        }
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Error en el traspaso: ' . $e->getMessage();
    }
} else {
    // El usuario normal crea una solicitud de traspaso.
    $json_data = json_encode($action_data);
    $stmt_request = $conn->prepare(
        "INSERT INTO pending_actions (user_id, username, action_type, item_id, action_data) VALUES (?, ?, 'transfer', ?, ?)"
    );
    $stmt_request->bind_param("isis", $_SESSION['user_id'], $_SESSION['username'], $itemId, $json_data);

    if ($stmt_request->execute()) {
        $response['success'] = true;
        $response['message'] = 'Solicitud de traspaso enviada para aprobación.';

        // Registramos la solicitud del usuario en el historial.
        $details = "Usuario '{$_SESSION['username']}' solicitó traspasar el ítem '{$item['name']}'.";
        log_activity($conn, $_SESSION['user_id'], $_SESSION['username'], 'request_transfer', $details);
    } else {
        $response['message'] = 'Error al enviar la solicitud de traspaso.';
    }
    $stmt_request->close();
}

$conn->close();
echo json_encode($response);
?>