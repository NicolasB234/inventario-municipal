<?php
require_once 'db_connect.php';
require_once 'log_activity.php';
require_once 'org-structure-data.php';
require_once 'utils.php';
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
if (!$id) {
    $response['message'] = 'ID de ítem no válido.';
    echo json_encode($response);
    exit;
}

// Lógica de manejo de imagen...
$upload_dir = '../uploads/';
$imagePath = $_POST['existingImagePath'] ?? null;
$stmt = $conn->prepare("SELECT imagePath, node_id FROM inventory_items WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$currentItem = $result->fetch_assoc();
$oldImagePath = $currentItem['imagePath'] ?? null;
$node_id = $currentItem['node_id'] ?? null;
$stmt->close();

if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] == 0) {
    $file = $_FILES['itemImage'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (in_array($file['type'], $allowed_types) && $file['size'] < 5000000) {
        $filename = uniqid() . '_' . basename($file['name']);
        $target_file = $upload_dir . $filename;
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $imagePath = 'uploads/' . $filename;
            if ($oldImagePath && file_exists('../' . $oldImagePath)) {
                unlink('../' . $oldImagePath);
            }
        }
    }
}

// Recopilar datos del formulario
$action_data = [
    'id' => $id,
    'name' => $_POST['name'],
    'quantity' => $_POST['quantity'],
    'category' => $_POST['category'],
    'description' => $_POST['description'],
    'acquisitionDate' => $_POST['acquisitionDate'],
    'status' => $_POST['status'],
    'imagePath' => $imagePath
];

$areaName = getAreaNameById($orgStructure, $node_id) ?? 'un área desconocida';

if ($is_admin) {
    // La lógica para el admin no cambia...
    try {
        $stmt_update = $conn->prepare("UPDATE inventory_items SET name = ?, quantity = ?, category = ?, description = ?, acquisitionDate = ?, status = ?, imagePath = ? WHERE id = ?");
        $stmt_update->bind_param("sisssssi", $action_data['name'], $action_data['quantity'], $action_data['category'], $action_data['description'], $action_data['acquisitionDate'], $action_data['status'], $action_data['imagePath'], $id);
        if ($stmt_update->execute()) {
            $response['success'] = true;
            $response['message'] = 'Ítem actualizado correctamente.';
            $details = "Admin '{$_SESSION['username']}' actualizó el ítem '{$action_data['name']}' en el área '{$areaName}'.";
            log_activity($conn, $_SESSION['user_id'], $_SESSION['username'], 'item_edited_admin', $details);
            $stmt_find_users = $conn->prepare("SELECT id FROM users WHERE area_id = ?");
            $stmt_find_users->bind_param("s", $node_id);
            $stmt_find_users->execute();
            $users_result = $stmt_find_users->get_result();
            while ($user = $users_result->fetch_assoc()) {
                $user_notification_details = "El administrador '{$_SESSION['username']}' actualizó el ítem '{$action_data['name']}' en tu área.";
                log_activity($conn, $_SESSION['user_id'], $_SESSION['username'], 'item_edited_by_admin', $user_notification_details, $user['id']);
            }
            $stmt_find_users->close();
        } else {
            $response['message'] = 'Error al actualizar el ítem.';
        }
        $stmt_update->close();
    } catch (Exception $e) {
        $response['message'] = 'Error de servidor: ' . $e->getMessage();
    }
} else {
    // --- INICIO DE LA CORRECCIÓN ---
    // La consulta y el enlace de parámetros ahora son correctos.
    $json_data = json_encode($action_data);
    $stmt_request = $conn->prepare(
        "INSERT INTO pending_actions (user_id, username, action_type, item_id, action_data, status) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $action_type = 'edit';
    $status = 'pending';
    // El tipo de dato debe ser 'ississ' (integer, string, string, integer, string, string) para 6 variables.
    $stmt_request->bind_param("ississ", $_SESSION['user_id'], $_SESSION['username'], $action_type, $id, $json_data, $status);
    // --- FIN DE LA CORRECCIÓN ---

    if ($stmt_request->execute()) {
        $response['success'] = true;
        $response['message'] = 'Solicitud de edición enviada para aprobación.';
        $details = "Usuario '{$_SESSION['username']}' solicitó editar el ítem '{$action_data['name']}' en el área '{$areaName}'.";
        log_activity($conn, $_SESSION['user_id'], $_SESSION['username'], 'request_edit', $details);
    } else {
        $response['message'] = 'Error al enviar la solicitud: ' . $stmt_request->error;
    }
    $stmt_request->close();
}

$conn->close();
echo json_encode($response);
?>