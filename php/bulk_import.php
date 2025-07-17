<?php
require_once 'db_connect.php';
require_once 'log_activity.php';
require_once 'org-structure-data.php';
require_once 'utils.php';
header('Content-Type: application/json');
session_start();

$response = ['success' => false, 'message' => ''];
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

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

$json_data = file_get_contents('php://input');
$items = json_decode($json_data, true);

if (json_last_error() !== JSON_ERROR_NONE || empty($items) || !is_array($items)) {
    $response['message'] = 'Error en los datos recibidos o formato incorrecto.';
    echo json_encode($response);
    exit;
}

if ($is_admin) {
    $areaMap = createAreaMap($orgStructure);
    $conn->begin_transaction();
    $affected_areas = []; // Para rastrear qué áreas fueron modificadas

    try {
        $query = "INSERT INTO inventory_items (node_id, name, quantity, category, description, acquisitionDate, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $items_added_count = 0;

        foreach ($items as $item) {
            $areaName = trim($item['area'] ?? '');
            $node_id = $areaMap[$areaName] ?? null;
            if (!$node_id) continue;

            // Rastrear áreas afectadas para notificar a sus usuarios
            if (!in_array($node_id, $affected_areas)) {
                $affected_areas[] = $node_id;
            }

            $name = $item['nombre'] ?? 'Sin Nombre';
            $quantity = (int)($item['cantidad'] ?? 1);
            $category = $item['categoria'] ?? 'Varios';
            $description = $item['descripcion'] ?? '';
            $acquisitionDate = !empty($item['fechaadquisicion']) ? $item['fechaadquisicion'] : null;
            $statusLabel = strtolower(trim($item['estado'] ?? 'Apto'));
            $statusMap = ['apto' => 'A', 'no apto' => 'N', 'recuperable' => 'R', 'de baja' => 'B'];
            $status = $statusMap[$statusLabel] ?? 'A';
            
            $stmt->bind_param("ssissss", $node_id, $name, $quantity, $category, $description, $acquisitionDate, $status);
            $stmt->execute();
            $items_added_count++;
        }
        $stmt->close();
        
        $conn->commit();
        $response['success'] = true;
        $response['message'] = "Admin '{$_SESSION['username']}' importó {$items_added_count} ítem(s) correctamente.";
        log_activity($conn, $_SESSION['user_id'], $_SESSION['username'], 'import_admin', $response['message']);

        // Notificar a los usuarios de todas las áreas afectadas
        foreach ($affected_areas as $area_id) {
            $stmt_find_users = $conn->prepare("SELECT id FROM users WHERE area_id = ?");
            $stmt_find_users->bind_param("s", $area_id);
            $stmt_find_users->execute();
            $users_result = $stmt_find_users->get_result();
            
            while ($user = $users_result->fetch_assoc()) {
                $areaName = getAreaNameById($orgStructure, $area_id) ?? 'desconocida';
                $user_notification_details = "El administrador '{$_SESSION['username']}' importó nuevos ítems a tu área ({$areaName}).";
                log_activity($conn, $_SESSION['user_id'], $_SESSION['username'], 'bulk_import_by_admin', $user_notification_details, $user['id']);
            }
            $stmt_find_users->close();
        }

    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Error durante la importación: ' . $e->getMessage();
    }

} else {
    // La lógica de solicitud del usuario normal no cambia.
    $action_data = json_encode($items);
    $stmt_request = $conn->prepare("INSERT INTO pending_actions (user_id, username, action_type, action_data, status) VALUES (?, ?, 'import', ?, 'pending')");
    $stmt_request->bind_param("iss", $_SESSION['user_id'], $_SESSION['username'], $action_data);

    if ($stmt_request->execute()) {
        $response['success'] = true;
        $response['message'] = 'Solicitud de importación enviada para aprobación.';
        $log_details = "Usuario '{$_SESSION['username']}' solicitó importar " . count($items) . " ítems.";
        log_activity($conn, $_SESSION['user_id'], $_SESSION['username'], 'request_import', $log_details);
    } else {
        $response['message'] = 'Error al enviar la solicitud de importación.';
    }
    $stmt_request->close();
}

$conn->close();
echo json_encode($response);
?>