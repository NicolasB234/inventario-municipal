<?php
require_once 'db_connect.php';
header('Content-Type: application/json');
session_start();

$response = ['success' => false, 'data' => []];
$node_id = $_GET['node_id'] ?? '';
$is_admin = $_SESSION['is_admin'] ?? false;

if (empty($node_id) && !$is_admin) {
    $response['message'] = 'Node ID no proporcionado.';
    echo json_encode($response);
    exit;
}

try {
    // --- INICIO DE LA CORRECCIÓN ---
    // Ahora, todas las consultas filtran los ítems para excluir aquellos con estado 'B' (De Baja).
    
    // Si es admin y está en la vista "Todas las Áreas", muestra todos los ítems activos.
    if ($is_admin && empty($node_id)) {
        // Se añade "WHERE status != 'B'" para ocultar los ítems dados de baja.
        $stmt = $conn->prepare("SELECT * FROM inventory_items WHERE status != 'B' ORDER BY node_id, name ASC");
    } else {
        // Para usuarios normales o cuando el admin selecciona un área, también se filtra por estado.
        $stmt = $conn->prepare("SELECT * FROM inventory_items WHERE node_id = ? AND status != 'B' ORDER BY name ASC");
        $stmt->bind_param("s", $node_id);
    }
    // --- FIN DE LA CORRECCIÓN ---

    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
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