<?php
require_once 'db_connect.php';
session_start();
header('Content-Type: application/json');

$response = [
    'new_notifications' => [],
    'pending_admin_requests' => 0,
    'refresh_inventory' => false
];

if (!isset($_SESSION['user_id'])) {
    echo json_encode($response);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

try {
    // --- INICIO DE LA CORRECCIÓN ---
    // 1. Lógica para el Administrador: Contar solicitudes y obtener logs generales.
    if ($is_admin) {
        // Contamos las solicitudes pendientes para el número de la campanita.
        $result_pending = $conn->query("SELECT COUNT(*) as count FROM pending_actions WHERE status = 'pending'");
        if ($result_pending) {
            $response['pending_admin_requests'] = (int)$result_pending->fetch_assoc()['count'];
        }
        
        // Obtenemos los últimos 5 logs de actividad general para el panel del admin.
        $result_logs = $conn->query("SELECT * FROM activity_log WHERE recipient_user_id IS NULL ORDER BY id DESC LIMIT 5");
        while ($row = $result_logs->fetch_assoc()) {
            $response['new_notifications'][] = $row;
        }

    // 2. Lógica para el Usuario Normal: Buscar notificaciones personales.
    } else {
        $stmt = $conn->prepare("SELECT * FROM activity_log WHERE recipient_user_id = ? AND id > ? ORDER BY id ASC");
        $stmt->bind_param("ii", $user_id, $last_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $response['new_notifications'][] = $row;
            if (in_array($row['action_type'], ['request_approved', 'request_rejected'])) {
                $response['refresh_inventory'] = true;
            }
        }
        $stmt->close();
    }
    // --- FIN DE LA CORRECCIÓN ---

} catch (Exception $e) {
    // Silencio en caso de error para no romper la interfaz.
}

$conn->close();
echo json_encode($response);
?>