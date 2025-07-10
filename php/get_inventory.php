<?php
require_once 'db_connect.php';
header('Content-Type: application/json');
session_start(); // Necesario para acceder a $_SESSION

$response = ['success' => false, 'data' => []];
$node_id = $_GET['node_id'] ?? '';

if (empty($node_id) && !($_SESSION['is_admin'] ?? false)) {
    $response['message'] = 'Node ID no proporcionado.';
    echo json_encode($response);
    exit;
}

try {
    // --- CAMBIO: Lógica para el super-usuario admin ---
    $is_admin = $_SESSION['is_admin'] ?? false;

    // Si es admin y no ha seleccionado un área específica, muestra todos los ítems.
    // Si el admin selecciona un área, $node_id tendrá valor y se usará la segunda condición.
    if ($is_admin && empty($node_id)) {
        $stmt = $conn->prepare("SELECT * FROM inventory_items ORDER BY node_id, name ASC");
    } else {
        // Comportamiento original para usuarios normales o cuando el admin selecciona un área
        $stmt = $conn->prepare("SELECT * FROM inventory_items WHERE node_id = ? ORDER BY name ASC");
        $stmt->bind_param("s", $node_id);
    }
    // --- FIN DEL CAMBIO ---

    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        // Convertir tipos de datos para consistencia con JS
        $row['id'] = (int)$row['id'];
        $row['quantity'] = (int)$row['quantity'];
        $items[] = $row;
    }

    $response['success'] = true;
    $response['data'] = $items;

    $stmt->close();
} catch (Exception $e) {
    $response['message'] = 'Error de servidor: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>