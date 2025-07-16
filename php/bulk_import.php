<?php
require_once 'db_connect.php';
require_once 'log_activity.php';
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
    // El admin importa directamente
    require_once 'org-structure-data.php';
    function createAreaMap($structure) {
        $map = [];
        $traverse = function ($nodes, $path, &$map) use (&$traverse) {
            foreach ($nodes as $node) {
                $currentPath = array_merge($path, [$node['name']]);
                $map[implode(' > ', $currentPath)] = $node['id'];
                if (!empty($node['children'])) {
                    $traverse($node['children'], $currentPath, $map);
                }
            }
        };
        $traverse($structure, [], $map);
        return $map;
    }
    $areaMap = createAreaMap($orgStructure);
    
    $conn->begin_transaction();
    try {
        $query = "INSERT INTO inventory_items (node_id, name, quantity, category, description, acquisitionDate, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $items_added_count = 0;

        foreach ($items as $item) {
            $areaName = trim($item['area'] ?? '');
            $node_id = $areaMap[$areaName] ?? null;
            if (!$node_id) continue;

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

    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Error durante la importación: ' . $e->getMessage();
    }

} else {
    // El usuario normal crea una solicitud de importación
    $action_data = json_encode($items); // Guardamos todos los ítems para que el admin los revise
    $stmt_request = $conn->prepare("INSERT INTO pending_actions (user_id, username, action_type, action_data) VALUES (?, ?, 'import', ?)");
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