<?php
require_once 'db_connect.php';
header('Content-Type: application/json');
session_start();

$response = ['success' => false, 'message' => ''];

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
// El motivo y otros datos se usarán en la notificación desde el frontend.

if (empty($itemId) || empty($destinationNodeId)) {
    $response['message'] = 'Faltan datos para realizar el traspaso.';
    echo json_encode($response);
    exit;
}

// Iniciar transacción para asegurar la integridad de los datos
$conn->begin_transaction();

try {
    // Verificamos que el ítem existe antes de actualizarlo
    $stmt_check = $conn->prepare("SELECT id FROM inventory_items WHERE id = ?");
    $stmt_check->bind_param("i", $itemId);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        throw new Exception('El ítem que intenta traspasar no existe.');
    }
    $stmt_check->close();

    // Actualizamos el 'node_id' del ítem para moverlo a la nueva área
    $stmt_update = $conn->prepare("UPDATE inventory_items SET node_id = ? WHERE id = ?");
    $stmt_update->bind_param("si", $destinationNodeId, $itemId);
    
    if (!$stmt_update->execute()) {
        throw new Exception('No se pudo actualizar el área del ítem.');
    }
    $stmt_update->close();

    // Si todo salió bien, confirmamos los cambios
    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Ítem traspasado correctamente.';

} catch (Exception $e) {
    // Si algo falla, revertimos todos los cambios
    $conn->rollback();
    $response['message'] = 'Error en el traspaso: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>