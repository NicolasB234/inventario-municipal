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

// Obtener el JSON enviado desde el frontend
$json_data = file_get_contents('php://input');
$items = json_decode($json_data, true);

if (empty($items) || !is_array($items)) {
    $response['message'] = 'No se recibieron ítems o el formato es incorrecto.';
    echo json_encode($response);
    exit;
}

// Iniciar transacción
$conn->begin_transaction();

try {
    // Preparar la consulta para inserción múltiple
    $query = "INSERT INTO inventory_items (node_id, name, quantity, category, description, acquisitionDate, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);

    $items_added_count = 0;

    foreach ($items as $item) {
        // Asignar valores por defecto si no vienen en el CSV
        $node_id = $item['node_id'] ?? null;
        $name = $item['nombre'] ?? 'Sin Nombre';
        $quantity = (int)($item['cantidad'] ?? 1);
        $category = $item['categoria'] ?? 'Varios';
        $description = $item['descripcion'] ?? '';
        $acquisitionDate = !empty($item['fechaadquisicion']) ? $item['fechaadquisicion'] : null;
        $status = in_array(strtoupper($item['estado'] ?? 'A'), ['A', 'N', 'R', 'B']) ? strtoupper($item['estado']) : 'A';
        
        if (empty($node_id) || empty($name)) {
            // Omitir ítems sin nombre o sin área asignada
            continue;
        }

        $stmt->bind_param("ssissss", $node_id, $name, $quantity, $category, $description, $acquisitionDate, $status);
        $stmt->execute();
        $items_added_count++;
    }

    $stmt->close();

    // Si todo fue bien, confirmar la transacción
    $conn->commit();
    $response['success'] = true;
    $response['message'] = $items_added_count . ' ítem(s) importado(s) correctamente.';

} catch (Exception $e) {
    // Si algo falló, revertir todo
    $conn->rollback();
    $response['message'] = 'Error durante la importación masiva: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>