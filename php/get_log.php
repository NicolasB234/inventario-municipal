<?php
require_once 'db_connect.php';
header('Content-Type: application/json');

$response = ['success' => false, 'data' => []];

try {
    // Obtenemos los últimos 200 registros, ordenados por fecha descendente
    $result = $conn->query("SELECT id, username, area_id, action_type, details, timestamp FROM activity_log ORDER BY timestamp DESC LIMIT 200");
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }

    $response['success'] = true;
    $response['data'] = $logs;

} catch (Exception $e) {
    $response['message'] = 'Error de servidor: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>