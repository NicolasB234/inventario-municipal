<?php
require_once 'db_connect.php';
require_once 'log_activity.php';
header('Content-Type: application/json');
session_start();

$response = ['success' => false, 'message' => ''];
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

if (!$is_admin) {
    $response['message'] = 'Acceso denegado.';
    echo json_encode($response);
    exit;
}

$action_id = isset($_POST['action_id']) ? (int)$_POST['action_id'] : 0;
$review_status = isset($_POST['status']) ? $_POST['status'] : '';
$review_comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if (empty($action_id) || !in_array($review_status, ['approved', 'rejected'])) {
    $response['message'] = 'Datos de revisión no válidos.';
    echo json_encode($response);
    exit;
}

$conn->begin_transaction();
try {
    $stmt_get = $conn->prepare("SELECT * FROM pending_actions WHERE id = ? AND status = 'pending'");
    $stmt_get->bind_param("i", $action_id);
    $stmt_get->execute();
    $result = $stmt_get->get_result();
    $action = $result->fetch_assoc();
    $stmt_get->close();

    if (!$action) {
        throw new Exception('La acción no existe o ya ha sido revisada.');
    }

    $admin_username = $_SESSION['username'];
    $requester_id = $action['user_id'];
    $data = json_decode($action['action_data'], true);
    $item_name_for_log = $data['item_name'] ?? ('ID ' . ($data['item_id'] ?? ($data['id'] ?? 'desconocido')));
    $log_details = '';
    $log_action_type = '';
    
    // Para que el mensaje sea más legible ("dar de baja" en lugar de "decommission")
    $action_text = $action['action_type'];
    if ($action['action_type'] === 'decommission') {
        $action_text = 'dar de baja';
    }

    if ($review_status === 'approved') {
        switch ($action['action_type']) {
            case 'edit':
                $stmt_edit = $conn->prepare("UPDATE inventory_items SET name = ?, quantity = ?, category = ?, description = ?, acquisitionDate = ?, status = ?, imagePath = ? WHERE id = ?");
                $stmt_edit->bind_param("sisssssi", $data['name'], $data['quantity'], $data['category'], $data['description'], $data['acquisitionDate'], $data['status'], $data['imagePath'], $data['id']);
                if (!$stmt_edit->execute()) throw new Exception('Error al actualizar el ítem.');
                $stmt_edit->close();
                break;
            // --- INICIO DE LA CORRECCIÓN ---
            // Se añade el caso para procesar la acción 'decommission'.
            case 'decommission':
                // Al aprobar, se actualiza el estado a 'B' (De Baja).
                $stmt_decommission = $conn->prepare("UPDATE inventory_items SET status = 'B' WHERE id = ?");
                $stmt_decommission->bind_param("i", $data['item_id']);
                if (!$stmt_decommission->execute()) throw new Exception('Error al dar de baja el ítem.');
                $stmt_decommission->close();
                break;
            // --- FIN DE LA CORRECCIÓN ---
            case 'transfer':
                $stmt_transfer = $conn->prepare("UPDATE inventory_items SET node_id = ? WHERE id = ?");
                $stmt_transfer->bind_param("si", $data['destination_node_id'], $data['item_id']);
                if (!$stmt_transfer->execute()) throw new Exception('Error al traspasar el ítem.');
                $stmt_transfer->close();
                break;
        }
        $log_action_type = 'request_approved';
        $log_details = "Su solicitud para {$action_text} el ítem '{$item_name_for_log}' ha sido APROBADA.";
    } else {
        $log_action_type = 'request_rejected';
        $log_details = "Su solicitud para {$action_text} el ítem '{$item_name_for_log}' ha sido RECHAZADA.";
    }

    if (!empty($review_comment)) {
        $log_details .= " Comentario del admin: '" . $review_comment . "'";
    }
    
    log_activity($conn, $_SESSION['user_id'], $admin_username, $log_action_type, $log_details, $requester_id);

    $stmt_update = $conn->prepare("UPDATE pending_actions SET status = ?, reviewed_by = ?, reviewed_at = NOW(), review_comment = ? WHERE id = ?");
    $stmt_update->bind_param("sisi", $review_status, $_SESSION['user_id'], $review_comment, $action_id);
    if (!$stmt_update->execute()) throw new Exception("Fallo al actualizar el estado de la solicitud.");
    $stmt_update->close();

    $conn->commit();
    $response['success'] = true;
    $response['message'] = "La solicitud ha sido marcada como '{$review_status}'. Se ha notificado al usuario.";

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Error al procesar la acción: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>